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
     * @throws \PhalconKit\Exception\ServiceException When default PHP-session
     *     storage cannot renew the session before authenticating.
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
            
            $loginFailedMessage = new Message(
                'Login Failed',
                'email',
                'LoginFailed',
                401,
                ['fields' => ['email', 'password']]
            );
            $loginForbiddenMessage = new Message(
                'Login Forbidden',
                'email',
                'LoginForbidden',
                403,
                ['fields' => ['email', 'password']]
            );
            
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
                // The default storage renews the PHP session before authenticating.
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
     * Request or redeem a time-limited, single-use password reset token.
     *
     * New reset records use `v1:<expiry>:<hash>` in the existing resetToken
     * column; legacy records are rejected and require a new reset request.
     * `identity.resetPassword.lifetime` is seconds (default 1800). Token hashes
     * use the same configured salt as verification. Raw tokens are delivered
     * only through sendPasswordResetNotification(), never returned to clients.
     *
     * Redemption atomically claims the stored token and saves the hashed password
     * in one write-connection transaction. Custom persistence/password hooks must
     * preserve the documented helper contracts. Existing sessions are not revoked
     * automatically; applications own that policy and notification delivery.
     *
     * @param array<string, mixed>|null $params Email, optional resetToken and password.
     * @return array<string, mixed> Empty on success/unknown request email, or validation messages.
     * @throws SecurityException When random generation or hashing fails.
     * @throws \PhalconKit\Exception\ConfigurationException For invalid token lifetime.
     * @throws \PhalconKit\Exception\ServiceException When a transaction cannot be owned or committed.
     */
    public function reset(?array $params = null): array
    {
        $params ??= [];
        $validation = new Validation();
        foreach (['email', 'resetToken', 'password'] as $field) {
            if (isset($params[$field]) && !is_string($params[$field])) {
                $validation->appendMessage(new Message('Invalid reset request', $field, 'NotValid', 400));
                return ['messages' => $validation->getMessages()];
            }
        }
        $redeeming = isset($params['resetToken']) && $params['resetToken'] !== '';
        $validation->add('email', new PresenceOf(['message' => 'required']));
        $validation->add('email', new Email(['message' => 'email-not-valid']));
        if ($redeeming) {
            $validation->add('password', new PresenceOf(['message' => 'required']));
        }
        $validation->validate($params);
        $options = $this->config->pathToArray('identity.resetPassword') ?? [];
        if ($options['disable'] ?? false) {
            $validation->appendMessage(new Message('Reset password is disabled', 'resetPassword', 'ResetPasswordDisabled', 403));
        }
        if ($validation->getMessages()->count()) {
            return ['messages' => $validation->getMessages()];
        }

        $user = $this->findUserByEmail($params['email']);
        if (!$redeeming) {
            if (!$user || $user->isDeleted()) {
                return [];
            }
            $lifetime = filter_var($options['lifetime'] ?? 1800, FILTER_VALIDATE_INT);
            if ($lifetime === false || $lifetime < 1 || $lifetime > 86400) {
                throw new \PhalconKit\Exception\ConfigurationException('Password reset lifetime must be between 1 and 86400 seconds.');
            }
            $token = $this->security->getRandom()->base64Safe(32);
            $expiresAt = time() + $lifetime;
            $previous = $user->getResetToken();
            $user->setResetToken('v1:' . $expiresAt . ':' . $user->hash($token));
            try {
                $saved = $user->save();
            }
            catch (\Throwable $exception) {
                $user->setResetToken($previous);
                throw $exception;
            }
            if (!$saved) {
                $user->setResetToken($previous);
                return ['messages' => $user->getMessages()];
            }
            $this->sendPasswordResetNotification($user, $token, $expiresAt);
            return [];
        }

        $record = $user?->getResetToken();
        if (!$user || $user->isDeleted() || !is_string($record)
            || !$this->validatePasswordResetToken($user, $record, $params['resetToken'])
            || !$this->persistPasswordReset($user, $record, $params['password'])
        ) {
            $validation->appendMessage(new Message('Invalid or expired reset token', 'token', 'NotValid', 400));
            return ['messages' => $validation->getMessages()];
        }
        $this->clearIdentityCache();
        return [];
    }

    /**
     * Verify the stored reset record's format, expiry, and configured-salt hash.
     *
     * Custom record formats must retain expiry checks and reject legacy undated
     * hashes. Validation alone does not consume the record; persistence must
     * compare and consume the exact record atomically.
     *
     * @param \PhalconKit\Models\Interfaces\UserInterface $user User whose hash policy verifies the token.
     * @param string $record Stored, versioned expiry/hash record.
     * @param string $token Raw credential supplied by the client.
     * @return bool Whether the token matches an unexpired record.
     */
    protected function validatePasswordResetToken(\PhalconKit\Models\Interfaces\UserInterface $user, string $record, string $token): bool
    {
        $parts = explode(':', $record, 3);
        return count($parts) === 3 && $parts[0] === 'v1'
            && ctype_digit($parts[1]) && strlen($parts[1]) <= 12
            && (int)$parts[1] > time() && $token !== '' && strlen($token) <= 512
            && $user->checkHash($parts[2], $token);
    }

    /**
     * Atomically claim a reset record and persist the new password through model hooks.
     *
     * The default uses the mapped user id/resetToken columns and the user's write
     * connection, which must support transactions. It refuses an existing outer
     * transaction rather than committing or rolling back caller-owned work.
     * Failed saves/claims roll back and restore the model's credential fields.
     * Overrides for other stores must implement atomic compare-and-consume plus
     * password persistence, and retain false-on-lost-race semantics.
     *
     * @param \PhalconKit\Models\Interfaces\UserInterface $user Existing active user.
     * @param string $record Exact validated record to consume.
     * @param string $password New plaintext password; never logged or returned.
     * @return bool True only after a successful commit; false for lost claims or save rejection.
     * @throws \PhalconKit\Exception\ServiceException When transaction setup/commit fails.
     */
    protected function persistPasswordReset(\PhalconKit\Models\Interfaces\UserInterface $user, string $record, string $password): bool
    {
        $connection = $user->getWriteConnection();
        if ($connection->isUnderTransaction() || !$connection->begin()) {
            throw new \PhalconKit\Exception\ServiceException('Password reset requires its own write transaction.');
        }
        $previousPassword = $user->getPassword();
        $committed = false;
        try {
            $columns = array_flip($user->getModelsMetaData()->getColumnMap($user) ?? []);
            $idColumn = $connection->escapeIdentifier($columns['id'] ?? 'id');
            $tokenColumn = $connection->escapeIdentifier($columns['resetToken'] ?? 'resetToken');
            $table = $connection->escapeIdentifier($user->getSource());
            if ($user->getSchema()) {
                $table = $connection->escapeIdentifier($user->getSchema()) . '.' . $table;
            }
            $claimed = $connection->execute(
                'UPDATE ' . $table . ' SET ' . $tokenColumn . ' = NULL WHERE ' . $idColumn . ' = ? AND ' . $tokenColumn . ' = ?',
                [$user->getId(), $record],
                [\Phalcon\Db\Column::BIND_PARAM_INT, \Phalcon\Db\Column::BIND_PARAM_STR]
            );
            // The claim may have waited for another transaction's row lock.
            if (!$claimed || $connection->affectedRows() !== 1
                || (int)(explode(':', $record, 3)[1] ?? 0) <= time()
            ) {
                return false;
            }
            $user->setResetToken(null);
            $this->setPasswordAfterReset($user, $password);
            if (!$user->save()) {
                return false;
            }
            if (!$connection->commit()) {
                throw new \PhalconKit\Exception\ServiceException('Could not commit password reset.');
            }
            $committed = true;
            return true;
        }
        finally {
            if (!$committed) {
                try {
                    $connection->rollback();
                }
                finally {
                    $user->setResetToken($record);
                    $user->setPassword($previousPassword);
                }
            }
        }
    }

    /**
     * Set a securely hashed password before reset persistence.
     *
     * Applications with a model setter/save hook that already hashes plaintext
     * must override this helper to avoid double hashing. Other password policy
     * checks belong in model validation and can reject save() transactionally.
     *
     * @param \PhalconKit\Models\Interfaces\UserInterface $user Model participating in the reset transaction.
     * @param string $password New plaintext password to hash and assign.
     */
    protected function setPasswordAfterReset(\PhalconKit\Models\Interfaces\UserInterface $user, string $password): void
    {
        $user->setPassword($user->hash($password));
    }

    /**
     * Deliver a successfully persisted reset token through an application-owned channel.
     *
     * Override for mail/queue delivery. The default intentionally sends nothing;
     * applications must provide delivery before exposing reset requests. Do not
     * log tokens or expose them in HTTP responses. Delivery failures propagate.
     *
     * @param \PhalconKit\Models\Interfaces\UserInterface $user Recipient of the reset notification.
     * @param string $token Raw credential for a trusted reset URL or message.
     * @param int $expiresAt Unix timestamp after which the token must be rejected.
     */
    protected function sendPasswordResetNotification(\PhalconKit\Models\Interfaces\UserInterface $user, string $token, int $expiresAt): void
    {
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
