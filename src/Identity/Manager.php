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

use Phalcon\Encryption\Security\Exception;
use PhalconKit\Di\Injectable;
use PhalconKit\Filter\Validation;
use PhalconKit\Identity\Traits\Acl;
use PhalconKit\Identity\Traits\Impersonation;
use PhalconKit\Identity\Traits\Jwt;
use PhalconKit\Identity\Traits\Oauth2;
use PhalconKit\Identity\Traits\Role;
use PhalconKit\Identity\Traits\Session;
use PhalconKit\Identity\Traits\User;
use PhalconKit\Mvc\ModelInterface;
use PhalconKit\Support\Options\Options;
use PhalconKit\Support\Options\OptionsInterface;
use Phalcon\Filter\Validation\Validator\Confirmation;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Messages\Message;

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
     * Retrieves the identity based on the provided user expose parameter.
     *
     * @param array|null $userExpose Optional parameter to specify user-related data exposure.
     * @return array The resulting identity data array.
     */
    public function get(?array $userExpose = null): array
    {
        return $this->getIdentity($userExpose);
    }
    
    /**
     * Retrieves the identity information based on the provided user expose parameter.
     *
     * @param array|null $userExpose Optional parameter specifying details for user data exposure.
     * @return array An associative array containing identity details such as logged-in status, user data, impersonation, roles, and groups.
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
     * Handles the login process by validating the provided parameters, checking user credentials,
     * and managing session state. Returns the login status along with any validation messages.
     *
     * @param array $params Parameters for login, typically including 'email' and 'password'.
     * @return array Contains login status, logged-in user information, and validation messages.
     */
    public function login(array $params = []): array
    {
        $validation = new Validation();
        $validation->add('email', new PresenceOf(['message' => 'required']));
        $validation->add('email', new Email(['message' => 'email-not-valid']));
        $validation->add('password', new PresenceOf(['message' => 'required']));
        $validation->validate($params);
        
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
                // save userId into session
                $this->setSessionIdentity(['userId' => $user->getId()]);
            }
        }
        
        return [
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
            'messages' => $validation->getMessages(),
        ];
    }
    
    /**
     * Logs out the current user by removing the session identity and returns the login status.
     *
     * @return array An associative array containing the user's login status and identity status after logout.
     */
    public function logout(): array
    {
        $this->removeSessionIdentity();
        
        return [
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
        ];
    }

    /**
     * Resets a user's password or generates a reset token for the user, depending on the input parameters.
     *
     * @param array|null $params Parameters including email, token,
     *                           password, and password confirmation for the reset operation.
     *                           - 'email': The user's email address.
     *                           - 'token': An optional reset token for password update.
     *                           - 'password': The new password to set (relevant with token).
     *                           - 'passwordConfirm': The confirmation of the new password.
     * @return array An array containing the following keys:
     *               - 'saved': A boolean indicating whether the save operation was successful.
     *               - 'sent': A boolean indicating whether the reset token email was sent successfully.
     *               - 'messages': A collection of validation or processing messages.
     * @throws Exception
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
            
            // @todo send confirmation email
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
            
            // @todo send reset email
        }
        
        // everything went fine
        // OWASP: to prevent user enumeration, we return an empty array here
        return [];
    }
    
    /**
     * Collects and returns a list of entities from the specified property of the model, keyed by a method from each entity.
     *
     * @param ModelInterface|null $model The model containing the property with the list of entities.
     * @param string $property The name of the property in the model that holds the list of entities.
     * @param string $keyMethod The name of the method in each entity used to generate the key. Defaults to 'getKey'.
     * @return array An associative array of entities, keyed by the specified method from each entity.
     */
    private function collectList(?ModelInterface $model, string $property, string $keyMethod = 'getKey'): array
    {
        if (!isset($model) || !property_exists($model, $property)) {
            return [];
        }
        
        $ret = [];
        foreach ($model->$property as $entity) {
            if (!is_object($entity) || !method_exists($entity, $keyMethod)) {
                throw new \LogicException(sprintf(
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
