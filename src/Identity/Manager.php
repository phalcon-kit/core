<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Identity;

use Phalcon\Encryption\Security\Exception as SecurityException;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;
use PhalconKit\Di\Injectable;
use PhalconKit\Exception\LogicException;
use PhalconKit\Filter\Validation;
use PhalconKit\Identity\Traits\Acl;
use PhalconKit\Identity\Traits\Impersonation;
use PhalconKit\Identity\Traits\Jwt;
use PhalconKit\Identity\Traits\Oauth2;
use PhalconKit\Identity\Traits\Role;
use PhalconKit\Identity\Traits\Session;
use PhalconKit\Identity\Traits\User;
use PhalconKit\Mvc\Model\Behavior\Security;
use PhalconKit\Mvc\ModelInterface;
use PhalconKit\Support\Options\Options;
use PhalconKit\Support\Options\OptionsInterface;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Messages\Message;

/**
 * Coordinates authentication state for PhalconKit applications.
 *
 * The manager exposes a compact identity API on top of several lower-level
 * traits: user lookup, session-backed identity storage, JWT claim handling,
 * OAuth2 account linking, role inheritance, ACL role construction, and
 * impersonation. It expects the application DI to provide the standard
 * PhalconKit services used by those traits, including config, models, request,
 * security, session, JWT, and bootstrap services.
 *
 * Identity state is stored as a small payload keyed by the active JWT claim
 * key. The payload normally lives in the session service; when
 * `identity.stateless` is enabled it lives directly in the JWT claim so API
 * clients can avoid server-side identity persistence. The primary payload keys
 * are `userId` for the effective user and `asUserId` for the original user
 * during impersonation. Login and password reset responses deliberately avoid
 * exposing whether an email address exists unless validation has already
 * failed, so downstream code should preserve that behavior when overriding the
 * manager.
 */
class Manager extends Injectable implements ManagerInterface, OptionsInterface
{
    use Options;
    
    use Acl;
    use Impersonation;
    use Jwt;
    use Oauth2;
    use Role;
    use Session;
    use User;
    
    /**
     * Return the current identity payload.
     *
     * This method is the short public entry point used by controllers and API
     * responses. It delegates to {@see getIdentity()} so subclasses only need
     * to customize the detailed identity payload in one place.
     *
     * @param array|null $userExpose Optional expose definition passed to user
     *     models before they are returned in the payload.
     *
     * @return array<string, mixed> Identity payload for the current request.
     */
    public function get(?array $userExpose = null): array
    {
        return $this->getIdentity($userExpose);
    }
    
    /**
     * Build the current identity payload.
     *
     * The payload includes both the effective user and the original user when
     * impersonating. Related role, type, and group lists are normalized into
     * maps keyed by each related entity's `getKey()` value so ACL checks and
     * API consumers can use stable identifiers without inspecting model
     * relation internals.
     *
     * @param array|null $userExpose Optional expose definition passed to
     *     `expose()` on user models before returning them.
     *
     * @return array{
     *     loggedInAs: bool,
     *     userAs: mixed,
     *     loggedIn: bool,
     *     user: mixed,
     *     roleList: array<string, object>,
     *     typeList: array<string, object>,
     *     groupList: array<string, object>
     * }
     *
     * @throws LogicException When a related role/type/group entity cannot
     *     provide a stable key.
     */
    public function getIdentity(?array $userExpose = null): array
    {
        $userAs = $this->getUserAs();
        $user = $this->getUser();
        
        return [
            'loggedInAs' => $this->isLoggedInAs(),
            'userAs' => isset($userExpose, $userAs) ? $userAs->expose($userExpose) : $userAs,
            
            'loggedIn' => $this->isLoggedIn(),
            'user' => isset($userExpose, $user) ? $user->expose($userExpose) : $user,
            
            'roleList' => $this->collectList($user, 'rolelist'),
            'typeList' => $this->collectList($user, 'typelist'),
            'groupList' => $this->collectList($user, 'grouplist'),
        ];
    }
    
    /**
     * Validate credentials and establish the session identity.
     *
     * The login flow accepts an email address and password, validates both
     * fields, checks the configured user model, and stores the authenticated
     * `userId` in the identity payload. Missing users, disabled passwords, and
     * invalid passwords all return the same generic login-failed message so the
     * response does not reveal whether an account exists. Deleted users are
     * rejected with a forbidden message after password verification succeeds.
     * When stateless identity is enabled, successful responses also include a
     * freshly signed JWT/refresh-token pair containing the new identity payload.
     *
     * Successful login also refreshes the global model security roles from the
     * effective ACL roles, allowing model behaviors to evaluate the newly
     * authenticated identity immediately.
     *
     * @param array<string, mixed> $params Login fields. Supported keys are
     *     `email` and `password`.
     *
     * @return array{loggedIn: bool, loggedInAs: bool, messages: \Phalcon\Messages\Messages, jwt?: string, refreshToken?: string, refreshed?: bool}
     *
     * @throws SecurityException When stateless token key generation fails.
     * @throws ValidatorException When stateless JWT creation fails.
     */
    public function login(array $params = []): array
    {
        $validation = new Validation();
        $validation->add('email', new PresenceOf(['message' => 'required']));
        $validation->add('email', new Email(['message' => 'email-not-valid']));
        $validation->add('password', new PresenceOf(['message' => 'required']));
        $validation->validate($params);
        $statelessJwt = [];
        
        $messages = $validation->getMessages();
        if (!$messages->count()) {
            $user = !empty($params['email']) ? $this->findUserByEmail($params['email']) : null;
            
            $loginFailedMessage = new Message('Login Failed', ['email', 'password'], 'LoginFailed', 401);
            $loginForbiddenMessage = new Message('Login Forbidden', ['email', 'password'], 'LoginForbidden', 403);
            
            if (!isset($user)) {
                // user isn't found, login failed
                $validation->appendMessage($loginFailedMessage);
            }
            else if (empty($user->getPassword())) {
                // password disabled, login failed
                $validation->appendMessage($loginFailedMessage);
            }
            else if (!$user->checkHash($user->getPassword(), $params['password'])) {
                // password failed, login failed
                $validation->appendMessage($loginFailedMessage);
            }
            else if ($user->isDeleted()) {
                // password match, user is deleted login forbidden
                $validation->appendMessage($loginForbiddenMessage);
            }
            
            // login success
            else {
                // save userId into the configured identity storage
                $this->setSessionIdentity(['userId' => $user->getId()]);

                // Update roles globally in the model security behavior
                Security::setRoles($this->identity->getAclRoles());

                $statelessJwt = $this->getJwtForStatelessIdentity();
            }
        }
        
        return array_merge($statelessJwt, [
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
            'messages' => $validation->getMessages(),
        ]);
    }
    
    /**
     * Remove the current identity payload.
     *
     * Logout clears the identity stored under the current claim key. It does not
     * clear unrelated session data. Stateless clients receive a refreshed
     * anonymous token response and must replace/discard any older authenticated
     * token client-side; JWTs are not server-revoked without an application
     * revocation strategy.
     *
     * @return array{loggedIn: bool, loggedInAs: bool, jwt?: string, refreshToken?: string, refreshed?: bool} Login state after
     *     the identity has been removed.
     *
     * @throws SecurityException When stateless token key generation fails.
     * @throws ValidatorException When stateless JWT creation fails.
     */
    public function logout(): array
    {
        $this->removeSessionIdentity();
        
        return array_merge($this->getJwtForStatelessIdentity(), [
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
        ]);
    }

    /**
     * Start or complete a password reset flow.
     *
     * When only `email` is provided, the manager creates a random reset token,
     * stores its hash on the user record, and returns an empty response on
     * success. When `resetToken` and `password` are provided, the token is
     * verified against the stored hash before the password is updated and the
     * reset token is cleared.
     *
     * To prevent user enumeration, a valid request for a missing email returns
     * the same empty response shape as a successful request. Validation failures
     * and persistence failures still return messages because those are
     * actionable by the caller. Notification delivery is intentionally left to
     * application code until the framework has a mailer/event contract for this
     * flow.
     *
     * @param array<string, mixed>|null $params Reset fields. Supported keys are
     *     `email`, optional `resetToken`, and `password` when completing a
     *     reset.
     *
     * @return array<string, mixed> Empty on successful or intentionally opaque
     *     outcomes, or `messages` when validation/persistence fails.
     *
     * @throws SecurityException When token generation fails.
     */
    public function reset(?array $params = null): array
    {
        // email is required and must be valid
        $validation = new Validation();
        $validation->add('email', new PresenceOf(['message' => 'required']));
        $validation->add('email', new Email(['message' => 'email-not-valid']));
        $validation->validate($params);

        // reset password is disabled from config
        $resetPasswordConfig = $this->config->pathToArray('identity.resetPassword') ?? [];
        if ($resetPasswordConfig['disable'] ?? false) {
            $validation->appendMessage(new Message('Reset password is disabled', 'resetPassword', 'ResetPasswordDisabled', 403));
        }
        
        // invalid email
        $messages = $validation->getMessages();
        if (count($messages)) {
            return ['messages' => $messages];
        }
        
        // retrieve the user using the provided email
        $user = isset($params['email']) ? $this->findUserByEmail($params['email']) : false;
        
        // user not found
        if (!$user) {
            // OWASP: to prevent user enumeration, we return an empty array here
            return [];
        }
        
        // password reset request
        if (!empty($params['resetToken'])) {
            // a password is required
            $validation->add('password', new PresenceOf(['message' => 'required']));
            $validation->validate($params);
            
            // check if the token is valid
            if (!$user->checkHash($user->getResetToken(), $params['resetToken'])) {
                $validation->appendMessage(new Message('not-valid', 'token', 'NotValid', 400));
            }
            
            // validation failed, return messages
            $messages = $validation->getMessages();
            if (count($messages)) {
                return ['messages' => $messages];
            }
            
            // remove the reset token and set the new password
            $user->setResetToken(null);
            $user->setPassword($params['password']);
            if (!$user->save()) {
                return ['messages' => $user->getMessages()];
            }
            
            // Notification delivery is app-specific until the identity manager
            // has a mailer/event contract for password-reset confirmations.
        }
        
        // reset token request
        else {
            // prepare reset token
            $resetToken = $this->security->getRandom()->base64Safe(32);
            $user->setResetToken($user->hash($resetToken, $user->getEmail()));
            
            // save hashed reset token
            if (!$user->save()) {
                return ['messages' => $user->getMessages()];
            }
            
            // Notification delivery is app-specific until the identity manager
            // has a mailer/event contract for reset-token messages.
        }
        
        // everything went fine
        // OWASP: to prevent user enumeration, we return an empty array here
        return [];
    }
    
    /**
     * Normalize a related model list into a key-indexed map.
     *
     * Identity payloads need stable role, type, and group keys regardless of
     * whether relations were eager-loaded, staged as dirty related records, or
     * assigned to public fixture properties in tests. This helper checks those
     * sources in order and ignores missing or non-iterable values.
     *
     * @param ModelInterface|null $model Model that may expose the relation.
     * @param string $property Relation alias or property name to read.
     * @param string $keyMethod Method each related entity must expose to
     *     provide the map key.
     *
     * @return array<string, object> Related entities keyed by their stable key.
     *
     * @throws LogicException When a related entity is not an object or does not
     *     implement the required key method.
     */
    private function collectList(?ModelInterface $model, string $property, string $keyMethod = 'getKey'): array
    {
        if (!isset($model)) {
            return [];
        }

        $list = null;
        if ($model->hasLoadedRelatedAlias($property)) {
            $list = $model->getLoadedRelatedAlias($property);
        }
        elseif ($model->hasDirtyRelatedAlias($property)) {
            $list = $model->getDirtyRelatedAlias($property);
        }
        elseif (property_exists($model, $property)) {
            $list = $model->$property;
        }
        else {
            return [];
        }

        if (!is_iterable($list)) {
            return [];
        }
        
        $ret = [];
        foreach ($list as $entity) {
            if (!is_object($entity) || !method_exists($entity, $keyMethod)) {
                throw new LogicException(sprintf(
                    'Entity %s must implement method %s()',
                    is_object($entity) ? get_class($entity) : gettype($entity),
                    $keyMethod
                ));
            }

            $ret [$entity->$keyMethod()] = $entity;
        }
        
        return $ret;
    }
}
