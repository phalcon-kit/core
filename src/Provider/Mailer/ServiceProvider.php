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

namespace PhalconKit\Provider\Mailer;

use Phalcon\Events\ManagerInterface;
use Phalcon\Incubator\Mailer\Manager;
use PhalconKit\Di\DiInterface;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Provider\AbstractServiceProvider;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Registers the mailer manager service.
 *
 * Mailer configuration is resolved from `mailer.driver`, `mailer.default`,
 * and `mailer.drivers.<driver>`. Driver options are merged over defaults before
 * constructing Phalcon Incubator's mailer manager, then the DI container and
 * shared events manager are attached when available.
 */
class ServiceProvider extends AbstractServiceProvider
{
    /**
     * @var string[]
     */
    private const array SUPPORTED_DRIVERS = [
        'sendmail',
        'smtp',
    ];

    /**
     * @var string[]
     */
    private const array SMTP_ENCRYPTIONS = [
        '',
        PHPMailer::ENCRYPTION_SMTPS,
        PHPMailer::ENCRYPTION_STARTTLS,
    ];

    protected string $serviceName = 'mailer';

    /**
     * Register the shared `mailer` service.
     *
     * SMTP encryption is normalized case-insensitively and validated before the
     * mailer is created so bad config fails before network I/O. The SMTP driver
     * also enables PHPMailer authentication explicitly because SMTP credentials
     * in the merged options imply authenticated transport.
     *
     * @throws ConfigurationException When the selected driver, option shape, or
     *     SMTP encryption value is invalid.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->getConfig();

            $mailerConfig = $config->pathToArray('mailer', []);

            $driver = self::normalizeMailerToken($mailerConfig['driver'] ?? '', 'mailer.driver');
            self::assertSupportedDriver($driver);

            $defaultOptions = self::resolveDefaultOptions($mailerConfig);
            $drivers = self::normalizeDrivers($mailerConfig['drivers'] ?? null);
            $driverOptions = $drivers[$driver] ?? [];
            $options = self::normalizeOptions(
                array_merge($defaultOptions, $driverOptions),
                $driver
            );

            $manager = new Manager($options);
            $manager->setDI($di);

            $eventsManager = $di->get('eventsManager');
            if ($eventsManager instanceof ManagerInterface) {
                $manager->setEventsManager($eventsManager);
            }

            if ($driver === 'smtp') {
                $manager->getMailer()->SMTPAuth = true;
            }

            return $manager;
        });
    }

    /**
     * Normalize and validate the options passed to the incubator mailer.
     *
     * @param array<mixed> $options
     *
     * @return array<mixed>
     *
     * @throws ConfigurationException When SMTP encryption is unsupported.
     */
    private static function normalizeOptions(array $options, string $driver): array
    {
        $configuredDriver = self::normalizeMailerToken(
            $options['driver'] ?? $driver,
            'mailer driver option'
        );
        if ($configuredDriver !== $driver) {
            throw new ConfigurationException(sprintf(
                'Mailer driver option "%s" does not match selected driver "%s".',
                $configuredDriver,
                $driver
            ));
        }

        $options['driver'] = $driver;

        if ($driver !== 'smtp' || !array_key_exists('encryption', $options)) {
            return $options;
        }

        $encryption = $options['encryption'];
        if ($encryption === null || $encryption === false) {
            $encryption = '';
        } elseif (!is_string($encryption)) {
            throw new ConfigurationException(
                'Mailer SMTP encryption must be a string, false, or null.'
            );
        }

        $encryption = self::normalizeOptionalMailerToken($encryption);
        if (!in_array($encryption, self::SMTP_ENCRYPTIONS, true)) {
            throw new ConfigurationException(sprintf(
                'Unsupported mailer SMTP encryption "%s"; use "ssl", "tls", or an empty string.',
                $options['encryption']
            ));
        }

        $options['encryption'] = $encryption;

        return $options;
    }

    /**
     * Return validated defaults from the canonical key or legacy alias.
     *
     * @param array<mixed> $mailerConfig
     *
     * @return array<mixed>
     */
    private static function resolveDefaultOptions(array $mailerConfig): array
    {
        $defaults = [];

        if (array_key_exists('defaults', $mailerConfig)) {
            $defaults = self::normalizeOptionArray($mailerConfig['defaults'], 'mailer.defaults');
        }

        if (array_key_exists('default', $mailerConfig)) {
            $defaults = array_merge(
                $defaults,
                self::normalizeOptionArray($mailerConfig['default'], 'mailer.default')
            );
        }

        return $defaults;
    }

    /**
     * Normalize and validate driver option groups keyed by driver name.
     *
     * @return array<string, array<mixed>>
     */
    private static function normalizeDrivers(mixed $drivers): array
    {
        if ($drivers === null) {
            return [];
        }

        if (!is_array($drivers)) {
            throw new ConfigurationException('Mailer drivers config must be an array.');
        }

        $normalized = [];
        foreach ($drivers as $driver => $options) {
            $driver = self::normalizeMailerToken($driver, 'mailer.drivers key');
            $normalized[$driver] = self::normalizeOptionArray(
                $options,
                sprintf('mailer.drivers.%s', $driver)
            );
        }

        return $normalized;
    }

    /**
     * Validate a mailer option group.
     *
     * @return array<mixed>
     */
    private static function normalizeOptionArray(mixed $options, string $path): array
    {
        if ($options === null) {
            return [];
        }

        if (!is_array($options)) {
            throw new ConfigurationException(sprintf('%s must be an array.', $path));
        }

        return $options;
    }

    /**
     * Validate the selected driver against the providers this class can create.
     */
    private static function assertSupportedDriver(string $driver): void
    {
        if (!in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new ConfigurationException(sprintf(
                'Unsupported mailer driver "%s"; use "sendmail" or "smtp".',
                $driver
            ));
        }
    }

    /**
     * Normalize ASCII mailer config tokens such as driver and encryption names.
     */
    private static function normalizeMailerToken(mixed $value, string $path): string
    {
        if (!is_string($value)) {
            throw new ConfigurationException(sprintf('%s must be a string.', $path));
        }

        $value = strtolower(trim($value));
        if ($value === '') {
            throw new ConfigurationException(sprintf('%s must not be empty.', $path));
        }

        return $value;
    }

    /**
     * Normalize optional mailer config tokens that may intentionally be empty.
     */
    private static function normalizeOptionalMailerToken(string $value): string
    {
        return strtolower(trim($value));
    }
}
