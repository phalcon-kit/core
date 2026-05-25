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
use PhalconKit\Identity\Traits\Abstracts\AbstractSession;

/**
 * Implements session-based user impersonation.
 *
 * The effective `userId` is replaced with the target user while the original
 * user id is stored in `asUserId`. Calling {@see logoutAs()} restores the
 * original id. Authorization is currently the legacy admin/dev role check; a
 * configurable permission contract is tracked as a future design topic.
 */
trait Impersonation
{
    use AbstractRole;
    use AbstractSession;
    
    /**
     * Switch the current session to another user.
     *
     * The target `userId` must be present, numeric, and resolvable through the
     * configured user model. If the target id equals the current `asUserId`, the
     * method treats the request as a return-to-self action and restores the
     * original session.
     *
     * @param array<string, mixed> $params Parameters containing `userId`.
     *
     * @return array{messages?: \Phalcon\Messages\Messages, loggedIn: bool, loggedInAs: bool, jwt?: string, refreshToken?: string, refreshed?: bool}
     *
     * @throws \Phalcon\Encryption\Security\Exception When stateless token key
     *     generation fails.
     * @throws \Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException
     *     When stateless JWT creation fails.
     */
    public function loginAs(array $params = []): array
    {
        // Validation
        $validation = new Validation();
        $validation->add('userId', new PresenceOf(['message' => 'required']));
        $validation->add('userId', new Numericality(['message' => 'not-numeric']));
        $messages = $validation->validate($params);
        $statelessJwt = [];
        
        // Legacy default: only admin/dev roles can impersonate users. A
        // config-driven permission contract remains a public API design topic.
        if (!count($messages) && $this->hasRole(['admin', 'dev'])) {
            $sessionIdentity = $this->getSessionIdentity();
            
            // himself, return back to normal login
            if (
                isset($sessionIdentity['asUserId'])
                && (int)$sessionIdentity['asUserId'] === (int)$params['userId']
            ) {
                return $this->logoutAs();
            }
            
            // login as using id
            $asUser = $this->findUserById((int)$params['userId']);
            if ($asUser) {
                $this->setSessionIdentity([
                    'userId' => (int)$params['userId'],
                    'asUserId' => $sessionIdentity['userId'],
                ]);
                $statelessJwt = $this->getJwtForStatelessIdentity();
            }
            else {
                $validation->appendMessage(new Message('User Not Found', 'userId', 'PresenceOf', 404));
            }
        }
        
        return array_merge($statelessJwt, [
            'messages' => $validation->getMessages(),
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
        ]);
    }
    
    /**
     * Restore the original user from an impersonated session.
     *
     * If both `userId` and `asUserId` are present, the original id becomes the
     * effective `userId` and the impersonation marker is removed.
     *
     * @return array{loggedIn: bool, loggedInAs: bool, jwt?: string, refreshToken?: string, refreshed?: bool} Login state after
     *     the restore attempt.
     *
     * @throws \Phalcon\Encryption\Security\Exception When stateless token key
     *     generation fails.
     * @throws \Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException
     *     When stateless JWT creation fails.
     */
    public function logoutAs(): array
    {
        $statelessJwt = [];
        $sessionIdentity = $this->getSessionIdentity();
        if (!empty($sessionIdentity['userId']) && !empty($sessionIdentity['asUserId'])) {
            $this->setSessionIdentity(['userId' => $sessionIdentity['asUserId']]);
            $statelessJwt = $this->getJwtForStatelessIdentity();
        }
        
        return array_merge($statelessJwt, [
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
        ]);
    }
}
