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

use Phalcon\Db\Column;
use Phalcon\Filter\Exception as FilterException;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Messages\Message;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Filter\Validation;
use PhalconKit\Identity\Traits\Abstracts\AbstractSession;
use PhalconKit\Identity\Traits\Abstracts\AbstractUser;
use PhalconKit\Models\Interfaces\Oauth2Interface;

/**
 * Links provider OAuth2 identities to local users.
 *
 * The trait owns the core OAuth2 persistence flow: find or create the provider
 * identity, store current tokens and profile metadata, attach the provider
 * identity to the logged-in local user when possible, then establish the
 * PhalconKit identity payload for the linked user. Stateless identity mode
 * returns refreshed JWT values after a successful login so API clients can
 * replace the token that now carries the linked user id.
 */
trait Oauth2
{
    use AbstractSession;
    use AbstractUser;
    
    /**
     * Create/update an OAuth2 identity and log in its linked local user.
     *
     * If the provider identity is not linked yet and a local user is already
     * logged in, the provider identity is attached to that user. Otherwise the
     * saved provider identity must already contain a user id before login can
     * succeed.
     *
     * The models service resolves the configured OAuth2 class. Replacements must
     * implement Oauth2Interface and return model instances (or null when absent)
     * from findFirst(). New records use a fresh instance, never the cached model
     * held by the resolver. Application validation and save hooks remain active.
     *
     * @param string $provider Provider key.
     * @param string $providerUuid Stable provider-side user identifier.
     * @param string $accessToken Provider access token.
     * @param string|null $refreshToken Optional provider refresh token.
     * @param array<string, mixed>|null $meta Optional provider profile data.
     *
     * @return array{saved: bool, loggedIn: bool, loggedInAs: bool, messages: \Phalcon\Messages\Messages, jwt?: string, refreshToken?: string, refreshed?: bool}
     *
     * @throws FilterException When OAuth provider fields cannot be sanitized.
     * @throws \Phalcon\Filter\Validation\Exception When validation cannot be configured.
     * @throws \Phalcon\Encryption\Security\Exception When stateless token key
     *     generation fails after a successful OAuth2 login.
     * @throws \Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException
     *     When stateless JWT creation fails after a successful OAuth2 login.
     * @throws ServiceException When the model mapping/lookup violates the OAuth2
     *     contract, or PHP-session storage cannot renew before authenticating.
     */
    public function oauth2(string $provider, string $providerUuid, string $accessToken, ?string $refreshToken = null, ?array $meta = []): array
    {
        $statelessJwt = [];
        
        /** @var class-string<Oauth2Interface> $oauth2Class */
        $oauth2Class = $this->models->getOauth2()::class;
        $oauth2 = $oauth2Class::findFirst([
            'provider = :provider: and provider_uuid = :providerUuid:',
            'bind' => [
                'provider' => $this->filter->sanitize($provider, 'string'),
                'providerUuid' => $providerUuid,
            ],
            'bindTypes' => [
                'provider' => Column::BIND_PARAM_STR,
                'providerUuid' => Column::BIND_PARAM_STR,
            ],
        ]);
        if ($oauth2 === null) {
            $oauth2 = new $oauth2Class();
            $oauth2->setProvider($provider);
            $oauth2->setProviderUuid($providerUuid);
        }
        if (!$oauth2 instanceof Oauth2Interface) {
            throw new ServiceException(sprintf(
                'Expected "%s::findFirst()" to return "%s" or null; got "%s".',
                $oauth2Class,
                Oauth2Interface::class,
                get_debug_type($oauth2)
            ));
        }
        $oauth2->setAccessToken($accessToken);
        $oauth2->setRefreshToken($refreshToken);
        $oauth2->setMeta(!empty($meta) ? json_encode($meta) : null);
        $oauth2->setEmail($meta['email'] ?? null);
        
        // legacy fields support, these fields were removed from the oauth2 table
        $oauth2->assign([
            'name' => $meta['name'] ?? null,
            'firstName' => $meta['first_name'] ?? null,
            'lastName' => $meta['last_name'] ?? null,
        ]);
        
        // link the current user to the oauth2 entity
        $oauth2UserId = $oauth2->getUserId();
        $sessionUserId = $this->getUserId();
        if (empty($oauth2UserId) && !empty($sessionUserId)) {
            $oauth2->setUserId($sessionUserId);
        }
        
        // prepare validation
        $validation = new Validation();
        
        // save the oauth2 entity
        $saved = $oauth2->save();
        
        // user id is required
        $validation->add('userId', new PresenceOf(['message' => 'userId is required']));
        $validation->validate(['userId' => $oauth2->getUserId()]);

        // validate() resets its message collection; retain model errors afterwards.
        foreach ($oauth2->getMessages() as $message) {
            $validation->appendMessage($message);
        }
        
        // All validation passed
        if ($saved && !$validation->getMessages()->count()) {
            $user = $this->findUserById($oauth2->getUserId());
            
            // user not found, login failed
            if (!isset($user)) {
                $validation->appendMessage(new Message('Login Failed', 'id', 'LoginFailed', 401));
            }
            
            // access forbidden, login failed
            elseif ($user->isDeleted()) {
                $validation->appendMessage(new Message('Login Forbidden', 'password', 'LoginForbidden', 403));
            }
            
            // login success
            else {
                $this->setSessionIdentity(['userId' => $user->getId()]);
                $statelessJwt = $this->getJwtForStatelessIdentity();
            }
        }
        
        return array_merge($statelessJwt, [
            'saved' => $saved,
            'loggedIn' => $this->isLoggedIn(false, true),
            'loggedInAs' => $this->isLoggedIn(true, true),
            'messages' => $validation->getMessages(),
        ]);
    }
}
