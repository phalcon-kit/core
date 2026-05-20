# Phalcon Kit Core
[![CI](https://github.com/phalcon-kit/core/actions/workflows/main.yml/badge.svg)](https://github.com/phalcon-kit/core/actions/workflows/main.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=phalcon-kit_core&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=phalcon-kit_core)

![Version](https://img.shields.io/packagist/v/phalcon-kit/core)
![PHP](https://img.shields.io/packagist/dependency-v/phalcon-kit/core/php)
![Downloads](https://img.shields.io/packagist/dt/phalcon-kit/core)
![License](https://img.shields.io/packagist/l/phalcon-kit/core)

> Previously known as Zemit CMS - now Phalcon Kit, rebuilt and rebranded for the future.

Welcome to [Phalcon Kit Core](https://github.com/phalcon-kit/core), a
convention-driven toolkit for building real applications on top of the
[Phalcon PHP Framework](https://phalcon.io). It gives you the boring parts
up front - bootstrap, configuration, modules, providers, REST controllers,
models, permissions, CLI tasks, WebSocket support, and reusable AI guidance -
so your application code can stay focused on business logic.

Phalcon Kit is designed to feel predictable from day one. Start with the
defaults, override only what your app owns, and keep the same structure as the
project grows. The package includes a tested core baseline, documented extension
points, generated API docs, and AI-ready skills that help agents follow the same
framework patterns your team uses.

#### Key Highlights:

- **Pre-configured service providers**: Register ready-to-use DI services for
  config, response, identity, cache, database, logging, mail, storage, OAuth,
  OpenAI, and more.
- **Integrated identity and security**: Use JWT/session-backed identity,
  password login, impersonation, role inheritance, ACL permissions, and
  config-driven security behaviors across controllers, actions, models, CLI,
  and WebSocket tasks.
- **Modular application structure**: Use predictable Frontend, API, CLI,
  WebSocket, Admin, and OAuth2 module boundaries, then override them in your app
  without editing vendor code.
- **RESTful resource conventions**: Build model-backed APIs with save fields,
  filter fields, search fields, exposers, eager loading, joins, and row-level
  permission conditions.
- **Batch eager loading**: Load nested relation graphs with `findWith()`,
  `findFirstWith()`, controller `initializeWith()`, and relation-level
  `QueryBuilder` constraints instead of lazy-loading model loops.
- **Database-first model scaffolding**: Generate abstract models, interfaces,
  PHP enum classes, comments, column maps, relationships, validations, and model
  tests from the real schema, then keep business logic in concrete models.
- **Relationship-aware model persistence**: Save one-to-many and many-to-many
  relation payloads through model assignment, with generated aliases, nested
  validation messages, eager loading, and soft-delete-aware relation updates.
- **Config-first customization**: Compose modules, providers, model aliases,
  permissions, roles, locale, router defaults, and integration settings through
  app config.
- **Tested core baseline**: The repository ships unit tests and Composer scripts
  for PHPUnit, Psalm, coding standards, package skeleton validation, and
  generated docs.
- **AI-ready documentation**: Reusable Codex/agent skills are included so AI
  assistants can follow PhalconKit conventions in application and core package
  work.

Whether you're a seasoned Phalcon developer or new to the framework, Phalcon
Kit Core gives you a clear path from a small module to a full application.

Let's dive in and explore what Phalcon Kit Core has in store for your development journey!

## Getting Started

### Creating a new project using Phalcon Kit
For a new application, start from the [Phalcon Kit App](https://github.com/phalcon-kit/app)
skeleton. It gives you the recommended app structure, default config, modules,
and entrypoints.

```shell
# Replace <new-project-name> by your project name
composer create-project phalcon-kit/app <new-project-name>
```

### Adding Phalcon Kit to your existing project
For an existing project, install the core package with Composer.

```shell
composer require phalcon-kit/core
```

You can then use Phalcon Kit classes through Composer autoloading. To use the
full runtime flow, bootstrap the application with `PhalconKit\Bootstrap` or an
app-specific subclass.

Minimal entrypoint:

```php
// index.php
<?php

use Phalcon\Autoload\Loader;

defined('APP_PATH') || define('APP_PATH', dirname(__DIR__) . '/app');

$loader = new Loader();
$loader->setFiles(['vendor/autoload.php']);
$loader->setNamespaces(['App' => APP_PATH]);
$loader->register();

echo (new \App\Bootstrap())->run();
```

Application bootstrap:

```php
namespace App;

use App\Config\Config;

class Bootstrap extends \PhalconKit\Bootstrap
{
    public function initialize(): void
    {
        $this->setConfig(new Config());
    }
}
```

### Configuration
Phalcon Kit loads environment-backed defaults through config. Keep secrets in
`.env`, and keep application structure in `App\Config\Config`: modules,
providers, model aliases, router defaults, locale, permissions, and integration
settings.

Example `.env` values:

```ini
# My App Config
MY_APP_VARIABLE="my-app-value"

# Example: Database Config
DATABASE_HOST=<my-db-host>
DATABASE_DBNAME=<my-db-name>
DATABASE_USERNAME=<my-db-user>
DATABASE_PASSWORD=<my-db-pass>
```

Example app config override:

```php
namespace App\Config;

class Config extends \PhalconKit\Bootstrap\Config
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        parent::__construct([
            'app' => [
                'name' => \PhalconKit\Support\Env::get('APP_NAME', 'My App'),
            ],
            'modules' => [
                \PhalconKit\Mvc\Module::NAME_API => [
                    'className' => \App\Modules\Api\Module::class,
                    'path' => APP_PATH . '/Modules/Api/Module.php',
                ],
            ],
        ], $insensitive);
    }
}
```

Provider overrides are config-first. Replace a core provider by keeping the
core provider class as the key, and register new app services with the app
provider as both key and value:

```php
'providers' => [
    \PhalconKit\Provider\Identity\ServiceProvider::class =>
        \App\Provider\Identity\ServiceProvider::class,
    \App\Provider\Firebase\ServiceProvider::class =>
        \App\Provider\Firebase\ServiceProvider::class,
],
```

### Initialize Database
We are using phalcon cli to run & generate database migration.
```shell
./vendor/bin/phalcon migration run --config=./src/Config/Migration.php --directory=./ --migrations=./src/Migrations/ --no-auto-increment --force --verbose --log-in-db
```

### Serve Application
To use Web MVC modules of Phalcon Kit Core locally, you can use PHP's built-in web server,
note that this web server is designed to aid application development.
It may also be useful for testing purposes or for application demonstrations
that are run in controlled environments. It is not intended to be a full-featured web server.
```shell
php -S 0.0.0.0:8000 /public/index.php
```
You should now be able to access Phalcon Kit Core Frontend module from http://localhost:8000

This web server runs only one single-threaded process, so PHP applications will stall if a request is blocked.
For more information about the CLI SAPI built-in web server, refer to the official documentation:
https://www.php.net/manual/en/features.commandline.webserver.php

### Full-featured Web Server
If you want to expose the application to the public world wide web,
you can use apache, nginx or any similar production ready web servers.

You will need a Web server service to point to the `/public/` folder of your new project.
Here is virtual host example using apache 2.4 + php-fpm 8.4 from remi repository on Redhat.

```apacheconf
<VirtualHost *:80>
    ServerName domain.tld
    ServerAlias www.domain.tld
    DocumentRoot /mnt/hgfs/dev/phalcon-kit/core/public/

    <Directory /mnt/hgfs/dev/phalcon-kit/core/public/>
        Options -Indexes +FollowSymLinks +MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.(php|phar)$>
        SetHandler "proxy:unix:/var/opt/remi/php84/run/php-fpm/www.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>

<VirtualHost *:443>
    ServerName domain.tld
    ServerAlias www.domain.tld
    DocumentRoot /mnt/hgfs/dev/phalcon-kit/core/public/

    <Directory /mnt/hgfs/dev/phalcon-kit/core/public/>
        Options -Indexes +FollowSymLinks +MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.(php|phar)$>
        SetHandler "proxy:unix:/var/opt/remi/php84/run/php-fpm/www.sock|fcgi://localhost"
    </FilesMatch>

    SetEnv HTTPS on
    SetEnv HTTP_X_FORWARDED_PROTO https
</VirtualHost>
```

## Requirements
Phalcon Kit Core is designed to work seamlessly with a specific set of technologies and PHP extensions to ensure optimal performance and functionality.

To check and install the necessary PHP extensions and manage Phalcon Kit's dependencies, use Composer:

```bash
composer require phalcon-kit/core
```

This command will automatically verify if your environment meets the requirements for running Phalcon Kit Core and install any missing dependencies.

By meeting these requirements, you can ensure a smooth and efficient experience with Phalcon Kit Core.

### Languages & Compatibilities

Phalcon Kit is built to be flexible and powerful, supporting a wide range of technologies and components. While we have certain core requirements, you have the freedom to integrate additional tools as per your project's needs.

- **[Composer](https://getcomposer.org/)**: Required for managing dependencies in Phalcon Kit. Composer simplifies the installation and update process of PHP packages, making it a vital tool for managing Phalcon Kit's components.
- **[PHP](https://secure.php.net/) >= 8.4**: Essential for Phalcon Kit, PHP 8.4+ brings modern features and improved performance.
- **[PhalconPHP](https://phalconphp.com/) 5.13.x**: Our core framework. Phalcon's efficiency and rich feature set are crucial for Phalcon Kit's performance.
- **Database Flexibility**: While we recommend [MySQL](https://www.mysql.com/) >= 8.0 for its robustness, Phalcon Kit is compatible with other databases supported by Phalcon. This flexibility allows you to choose the database that best fits your project's requirements.
- **PSR Standards**: Compliance with PSR standards is mandatory, ensuring interoperability and standard coding practices.

Additionally, while not mandatory, the following are highly recommended for enhancing performance and functionality:

- **[Redis](https://redis.io/)**: Excellent for advanced caching mechanisms, session storage, and more.
- **[APCu](https://www.php.net/manual/en/book.apcu.php)**: Useful for opcode caching, reducing runtime for PHP scripts.
- **[Opcache](https://www.php.net/manual/en/book.opcache.php)**: Improves PHP performance by storing precompiled script bytecode.

By utilizing these technologies, Phalcon Kit offers a scalable, robust platform for developing web applications, giving you the flexibility to tailor the environment to your needs.

## AI-Assisted Development

Phalcon Kit Core includes reusable AI guidance for Codex and agent-based
development. These skills teach agents how PhalconKit apps are actually
structured: app bootstrap, config composition, providers, modules, REST
controllers, exposers, permissions, controller behaviors, model aliases, CLI
tasks, WebSocket tasks, Fractal transformers, Docker/local environment setup,
deployment config, official Phalcon baseline references, and maintainer rules.
See [AI.md](AI.md) for the full guide.

- Framework users can mount `resources/skills/phalconkit-app-developer` from this package, or from `vendor/phalcon-kit/core/resources/skills/phalconkit-app-developer` inside an installed application.
- Core contributors can use `resources/skills/phalconkit-core-maintainer` together with the repo-local [AGENTS.md](AGENTS.md).
- The app-developer skill includes focused references for framework usage,
  native Phalcon docs, configuration, providers, identity/security, controller
  behaviors, REST API controllers, CLI tasks, WebSocket tasks, eager loading,
  transformers, models, relationships, migrations, scaffolding, Docker/local
  environment setup, and deployment configuration.
- The core-maintainer skill includes package conventions and a coverage/gap
  checklist for improving AI-facing documentation.
- These assets are documentation and agent instructions only; they do not add a
  runtime AI layer or change PHP APIs.

## Contact Information
Got questions, feedback, or need assistance with Phalcon Kit? We're here to help!

- **Community Support**: Join our community on [GitHub Discussions](https://github.com/orgs/phalcon-kit/discussions). It's a great place to seek help, share your Phalcon Kit experiences, and connect with fellow users and the development team.
- **Issue Reporting**: Encounter a bug or have a feature request? Please file a detailed report on our [GitHub Issues](https://github.com/phalcon-kit/core/issues) page.

Your input and interactions are invaluable to Phalcon Kit's ongoing development and success. Don't hesitate to reach out - we're always eager to hear from you!

## Contributing
We warmly welcome contributions to the Phalcon Kit project! Whether you're skilled in coding, documentation, design, or testing, your input can make a significant difference.

Here are some ways you can contribute:

- **Code Contributions**: Submit bug fixes, add new features, or enhance existing ones.
- **Documentation**: Improve or update the documentation to make Phalcon Kit more accessible to users.
- **Issue Reporting**: Report bugs or propose new ideas and enhancements.
- **Community Support**: Help new users by answering questions on our forums or social media channels.

To get started, please review our [CONTRIBUTING.md](https://github.com/phalcon-kit/core/blob/master/CONTRIBUTING.md) guide. It covers everything you need to know about contributing to Phalcon Kit, including how to submit your changes and our coding standards.

Join us in shaping Phalcon Kit into an even more powerful and user-friendly CMS!

## License
Phalcon Kit is dedicated to open-source and community-driven development, proudly licensed under the BSD 3-Clause License. This license grants you broad freedom to use, modify, and distribute the software, ensuring that Phalcon Kit remains a community asset accessible to all.

We respect intellectual property and the efforts of contributors. As such, all use of Phalcon Kit should adhere to the conditions outlined in the license.

For the complete terms and conditions of the BSD 3-Clause License, please refer to our [LICENSE.txt](https://github.com/phalcon-kit/core/blob/master/LICENSE.txt) file.

(c) 2017-present, Phalcon Kit Team. All rights reserved.
