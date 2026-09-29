<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Models;

use PhalconKit\Models\Abstracts\UserAbstract;
use PhalconKit\Models\Interfaces\UserInterface;

/**
 * Class User
 *
 * This class represents a User object.
 * It extends the UserAbstract class and implements the UserInterface.
 */
class User extends UserAbstract implements UserInterface
{
    #[\Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->addDefaultRelationships();
    }

    public function validation(): bool
    {
        $validator = $this->genericValidation();
        $this->addDefaultValidations($validator);
        return $this->validate($validator);
    }

    /**
     * Hash a newly assigned plaintext password before persistence.
     *
     * Uses the model hash helper so identity login applies the same configured
     * salt and algorithm. Recognized Phalcon password hashes are preserved, including
     * on unrelated saves; null/empty values continue to disable password login.
     * Application models overriding this hook own their password preparation and
     * should call the parent when they use this concrete Core model's contract.
     * Models mapped from another base retain their own assignment/save behavior.
     *
     * @return bool Always true; validation remains the validation hook's concern.
     * @throws \PhalconKit\Exception\ServiceException When hashing services are unavailable.
     * @psalm-suppress MissingReturnType Keep the event hook compatible with untyped application overrides.
     */
    public function beforeSave()
    {
        $password = $this->getPassword();
        if (is_string($password) && $password !== '' && !$this->isPasswordHash($password)) {
            $this->setPassword($this->hash($password));
        }

        return true;
    }

    /**
     * Recognize complete hashes produced by Phalcon's configurable algorithms.
     *
     * PHP recognizes password_hash() formats but not Phalcon's legacy crypt()
     * formats. Preserve both when importing hashes or saving an existing user;
     * checking only their prefix could mistake ordinary plaintext for a hash.
     * Applications using another hash format can extend this detection.
     *
     * @param string $password Assigned password or previously stored hash.
     * @return bool Whether the value already has a supported password-hash format.
     */
    protected function isPasswordHash(string $password): bool
    {
        if (password_get_info($password)['algo'] !== null) {
            return true;
        }

        $formats = [
            '~\A\$2[axy]\$(?:0[4-9]|[12][0-9]|3[01])\$[./0-9A-Za-z]{53}\z~',
            '~\A\$1\$[^$\x00]{0,8}\$[./0-9A-Za-z]{22}\z~',
            '~\A\$5\$(?:rounds=[0-9]+\$)?[^$\x00]{0,16}\$[./0-9A-Za-z]{43}\z~',
            '~\A\$6\$(?:rounds=[0-9]+\$)?[^$\x00]{0,16}\$[./0-9A-Za-z]{86}\z~',
        ];
        foreach ($formats as $format) {
            if (preg_match($format, $password) === 1) {
                return true;
            }
        }

        return false;
    }
}
