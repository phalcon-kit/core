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

namespace PhalconKit\Identity\Traits;

use Phalcon\Filter\Validation\Validator\Numericality;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Messages\Message;
use PhalconKit\Filter\Validation;
use PhalconKit\Identity\Traits\Abstracts\AbstractRole;

trait Impersonation
{
    use AbstractRole;
    
    /**
     * Allows an admin or developer to log in as another user based on their user ID.
     * Validates the provided parameters to ensure the presence and numericality of the user ID.
     * Also handles the scenario where the user attempts to return to their original session.
     *
     * @param array $params Associative array containing the key 'userId', which represents the ID of the user to log in as.
     * @return array An array containing the validation messages, login status, and login-as status:
     *               - 'messages': Validation messages, if any.
     *               - 'loggedIn': Boolean indicating whether the user is logged in under their original session.
     *               - 'loggedInAs': Boolean indicating whether the user is logged in as another user.
     */
    public function loginAs(array $params = []): array
    {
        // Validation
        $validation = new Validation();
        $validation->add('userId', new PresenceOf(['message' => 'required']));
        $validation->add('userId', new Numericality(['message' => 'not-numeric']));
        $messages = $validation->validate($params);
        
        // Legacy default: only admin/dev roles can impersonate users. A
        // config-driven permission contract remains a public API design topic.
        if (!count($messages) && $this->hasRole(['admin', 'dev'])) {
            $sessionIdentity = $this->getSessionIdentity();
            
            // himself, return back to normal login
            if ((int)$sessionIdentity['asUserId'] === (int)$params['userId']) {
                return $this->logoutAs();
            }
            
            // login as using id
            $asUser = $this->findUserById((int)$params['userId']);
            if ($asUser) {
                $this->setSessionIdentity([
                    'userId' => (int)$params['userId'],
                    'asUserId' => $sessionIdentity['userId'],
                ]);
            }
            else {
                $validation->appendMessage(new Message('User Not Found', 'userId', 'PresenceOf', 404));
            }
        }
        
        return [
            'messages' => $validation->getMessages(),
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
        ];
    }
    
    /**
     * Logs out from a session where the user was logged in (impersonating)
     * as another user, reverting back to the original session identity.
     * If the current session identity includes an 'asUserId', the identity
     * is updated to the corresponding 'userId'.
     *
     * @return array An array containing the user's login status after reverting:
     *               - 'loggedIn': Boolean indicating whether the original user is logged in.
     *               - 'loggedInAs': Boolean indicating whether the session is currently logged in as another user.
     */
    public function logoutAs(): array
    {
        $sessionIdentity = $this->getSessionIdentity();
        if (!empty($sessionIdentity['userId']) && !empty($sessionIdentity['asUserId'])) {
            $this->setSessionIdentity(['userId' => $sessionIdentity['asUserId']]);
        }
        
        return [
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
        ];
    }
}
