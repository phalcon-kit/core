<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Migrations;

use Phalcon\Config\Config as MigrationConfig;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Db\Enum;
use Phalcon\Di\Di;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Mvc\Model\Migration;
use Phalcon\Mvc\Model\MetaData\Memory;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Mvc\Model\Behavior\Security as ModelSecurity;
use PhalconKit\Models\Oauth2;
use PhalconKit\Models\Profile;
use PhalconKit\Models\User;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Support\Helper;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\Support\Fixtures\DatabaseTestCase;

/** Exercise the shipped baseline with the real migration runner and native ORM. */
final class CoreBaselineDatabaseTest extends DatabaseTestCase
{
    private Mysql $db;
    private string $database;
    private array $options;

    protected function setUp(): void
    {
        $this->db = $this->connectTestDatabase();
        $root = dirname(__DIR__, 3);
        require_once $root . '/resources/migrations/4.0.0/core.php';
        $this->database = 'phalconkit_baseline_' . bin2hex(random_bytes(8));
        // Deliberately inherit neither the desired charset nor collation.
        $this->db->execute('CREATE DATABASE `' . $this->database . '` CHARACTER SET latin1 COLLATE latin1_swedish_ci');
        $this->db->execute('USE `' . $this->database . '`');
        $this->db->execute('SET NAMES utf8mb4');
        $this->options = [
            'config' => new MigrationConfig(['database' => $this->db->getDescriptor() + [
                'adapter' => 'Mysql', 'dbname' => $this->database, 'charset' => 'utf8mb4',
            ]]),
            'migrationsDir' => [$root . '/resources/migrations'],
            'migrationsInDb' => true,
            'directory' => $root,
            'tsBased' => false,
            'version' => '4.0.0',
        ];
        Migrations::resetStorage();
    }

    protected function tearDown(): void
    {
        if (isset($this->database)) {
            if ($this->db->isUnderTransaction()) {
                $this->db->rollback();
            }
            $this->db->execute('DROP DATABASE `' . $this->database . '`');
            Migrations::resetStorage();
        }
        if (isset($this->db)) {
            $this->db->close();
        }
    }

    public function testFreshInstallPreservesIntegrityAndNativeModelWrites(): void
    {
        // The runner normally creates this; an empty non-InnoDB log is normalized too.
        $this->db->execute('CREATE TABLE phalcon_migrations (version VARCHAR(255) PRIMARY KEY, start_time TIMESTAMP NOT NULL, end_time TIMESTAMP NOT NULL) ENGINE=MyISAM');
        self::assertStringContainsString('successfully migrated', $this->runMigration());
        $tables = $this->db->fetchAll('SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME', Enum::FETCH_ASSOC);
        $expected = explode(' ', 'audit audit_detail backup email email_file feature file file_relation group group_feature group_role group_type job job_scheduler log oauth2 phalcon_migrations profile role role_feature role_role session setting template type user user_feature user_group user_role user_type');
        self::assertSame($expected, array_column($tables, 'TABLE_NAME'));
        foreach ($tables as $table) {
            self::assertSame('InnoDB', $table['ENGINE'], $table['TABLE_NAME']);
            self::assertSame('utf8mb4_unicode_ci', $table['TABLE_COLLATION'], $table['TABLE_NAME']);
        }
        $references = $this->db->fetchAll('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL', Enum::FETCH_ASSOC);
        self::assertCount(94, $references);
        foreach ($references as $reference) {
            self::assertSame($this->database, $reference['REFERENCED_TABLE_SCHEMA']);
            self::assertContains($reference['REFERENCED_TABLE_NAME'], $expected);
        }
        $columns = $this->db->fetchAll('SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()', Enum::FETCH_ASSOC);
        foreach ($columns as $column) {
            $name = $column['TABLE_NAME'] . '.' . $column['COLUMN_NAME'];
            if ($column['COLUMN_NAME'] === 'id' || str_ends_with($column['COLUMN_NAME'], '_id') || in_array($column['COLUMN_NAME'], ['created_by', 'created_as', 'updated_by', 'deleted_by'], true)) {
                self::assertSame('bigint', $column['DATA_TYPE'], $name);
                self::assertStringContainsString('unsigned', $column['COLUMN_TYPE'], $name);
            }
            if ($column['COLUMN_NAME'] === 'uuid') {
                self::assertSame(36, (int) $column['CHARACTER_MAXIMUM_LENGTH']);
                self::assertSame('ascii', $column['CHARACTER_SET_NAME']);
            }
            if (in_array($name, ['audit.table', 'audit_detail.column', 'file_relation.relation_table'], true)) {
                self::assertSame(64, (int) $column['CHARACTER_MAXIMUM_LENGTH']);
            }
            if (in_array($name, ['oauth2.access_token', 'oauth2.refresh_token', 'session.token', 'session.jwt', 'user.password', 'user.reset_token', 'oauth2.provider_uuid'], true)) {
                self::assertSame('utf8mb4_bin', $column['COLLATION_NAME'], $name);
            }
        }
        self::assertSame(1, (int) (new \CoreMigration_400())->getConnection()->fetchOne('SELECT @@SESSION.foreign_key_checks AS enabled', Enum::FETCH_ASSOC)['enabled']);
        self::assertSame('4.0.0', $this->db->fetchOne('SELECT version FROM phalcon_migrations', Enum::FETCH_ASSOC)['version']);
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) AS total FROM user', Enum::FETCH_ASSOC)['total']);

        $previousDi = Di::getDefault();
        $previousHelper = Helper::$helperFactory;
        $previousSecurity = ModelSecurity::getStaticEnabled();
        try {
            $di = new \PhalconKit\Di\Di();
            Di::setDefault($di);
            Helper::$helperFactory = null;
            // This fixture exercises persistence; authorization has dedicated suites.
            ModelSecurity::setStaticEnabled(false);
            $di->setShared('db', $this->db);
            $di->setShared('config', new Config(['cache' => ['cli' => 'memory']]));
            $di->setShared('models', new \PhalconKit\Support\Models());
            (new \PhalconKit\Provider\ModelsCache\ServiceProvider($di))->register($di);
            (new \PhalconKit\Provider\Security\ServiceProvider($di))->register($di);
            $identity = $this->createStub(\PhalconKit\Identity\ManagerInterface::class);
            $identity->method('getUser')->willReturn(null);
            $di->setShared('identity', $identity);
            $di->setShared('modelsManager', new Manager());
            $di->setShared('modelsMetadata', new Memory());
            $di->setShared('helper', new HelperFactory());
            $di->setShared('translate', new \Phalcon\Translate\Adapter\NativeArray(new \Phalcon\Translate\InterpolatorFactory(), ['content' => []]));
            $di->setShared('filter', (new \Phalcon\Filter\FilterFactory())->newInstance());
            $user = new User();
            $user->email = 'baseline@example.test';
            $profile = new Profile();
            $profile->firstName = 'François 🐘';
            $profile->lastName = 'Baseline';
            $oauth = new Oauth2();
            $oauth->provider = 'microsoft';
            $oauth->providerUuid = 'synthetic-subject';
            $oauth->accessToken = str_repeat('AccessToken.', 200);
            $oauth->refreshToken = str_repeat('RefreshToken.', 200);
            $user->ProfileList = [$profile];
            $user->Oauth2List = [$oauth];
            self::assertTrue($user->save(), json_encode($user->getMessages(), JSON_THROW_ON_ERROR));
            self::assertSame($profile->firstName, Profile::findFirst($profile->id)->firstName);
            self::assertSame((int) $user->id, (int) Oauth2::findFirst($oauth->id)->userId);
            self::assertSame($oauth->accessToken, Oauth2::findFirst($oauth->id)->accessToken);
            self::assertSame($oauth->refreshToken, Oauth2::findFirst($oauth->id)->refreshToken);

            $audit = new \PhalconKit\Models\Audit([
                'model' => User::class, 'table' => str_repeat('t', 64),
                'primary' => $user->id, 'event' => 'create',
            ]);
            self::assertTrue($audit->save(), json_encode($audit->getMessages(), JSON_THROW_ON_ERROR));
            $detail = new \PhalconKit\Models\AuditDetail(['auditId' => $audit->id, 'column' => str_repeat('c', 64)]);
            self::assertTrue($detail->save(), json_encode($detail->getMessages(), JSON_THROW_ON_ERROR));
            self::assertSame($audit->table, \PhalconKit\Models\Audit::findFirst($audit->id)->table);
            self::assertSame($detail->column, \PhalconKit\Models\AuditDetail::findFirst($detail->id)->column);

            // A real InnoDB transaction covers nested parent/child persistence.
            $this->db->begin();
            $rolledBack = new User();
            $rolledBack->email = 'rolled-back@example.test';
            $rolledBack->ProfileList = [new Profile(['firstName' => 'Rolled back'])];
            self::assertTrue($rolledBack->save(), json_encode($rolledBack->getMessages(), JSON_THROW_ON_ERROR));
            $this->db->rollback();
            self::assertNull(User::findFirst($rolledBack->id));
            self::assertSame(1, (int) Profile::count());
        } finally {
            Helper::$helperFactory = $previousHelper;
            ModelSecurity::setStaticEnabled($previousSecurity);
            Di::reset();
            if ($previousDi !== null) {
                Di::setDefault($previousDi);
            }
        }

        // Distinct opaque tokens must not collide or authenticate through case folding.
        $this->db->execute("INSERT INTO session (uuid, token, expires_at) VALUES ('00000000-0000-0000-0000-000000000001', 'TokenABC', '2030-01-01'), ('00000000-0000-0000-0000-000000000002', 'tokenabc', '2030-01-01')");
        self::assertSame(1, (int) $this->db->fetchOne("SELECT COUNT(*) AS total FROM session WHERE token = 'TokenABC'", Enum::FETCH_ASSOC)['total']);
        self::assertSame(0, (int) $this->db->fetchOne("SELECT COUNT(*) AS total FROM session WHERE token = 'TOKENABC'", Enum::FETCH_ASSOC)['total']);
        try {
            $this->db->execute("INSERT INTO profile (uuid, user_id) VALUES ('00000000-0000-0000-0000-000000000003', 999999)");
            self::fail('A foreign key accepted a nonexistent user.');
        } catch (\PDOException $exception) {
            self::assertSame('23000', (string) $exception->getCode());
        }
        self::assertStringContainsString('up to date', $this->runMigration());
        $rollbackError = null;
        try {
            (new \CoreMigration_400())->down();
        } catch (\RuntimeException $exception) {
            $rollbackError = $exception;
        }
        self::assertInstanceOf(\RuntimeException::class, $rollbackError);
        self::assertStringContainsString('no destructive rollback', $rollbackError->getMessage());
        self::assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) AS total FROM user', Enum::FETCH_ASSOC)['total']);
        self::assertSame('4.0.0', $this->db->fetchOne('SELECT version FROM phalcon_migrations', Enum::FETCH_ASSOC)['version']);
    }

    public function testExistingTablesAndLegacyHistoryRemainUntouched(): void
    {
        $this->db->execute('CREATE TABLE user (id INT PRIMARY KEY, application_field VARCHAR(20)) ENGINE=InnoDB');
        $this->db->insert('user', [7, 'keep me'], ['id', 'application_field']);
        $this->db->execute('CREATE TABLE record (id INT PRIMARY KEY) ENGINE=InnoDB');
        $this->db->insert('record', [8], ['id']);
        $this->db->execute('CREATE TABLE phalcon_migrations (version VARCHAR(255) PRIMARY KEY, start_time TIMESTAMP, end_time TIMESTAMP) ENGINE=InnoDB');
        $this->db->execute("INSERT INTO phalcon_migrations (version) VALUES ('1.0.0')");
        $before = $this->db->fetchAll('SHOW CREATE TABLE user', Enum::FETCH_ASSOC);
        try {
            $this->runMigration();
            self::fail('The baseline accepted an existing application schema.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('empty database', $exception->getMessage());
        }
        self::assertSame($before, $this->db->fetchAll('SHOW CREATE TABLE user', Enum::FETCH_ASSOC));
        self::assertSame([['id' => 7, 'application_field' => 'keep me']], $this->db->fetchAll('SELECT * FROM user', Enum::FETCH_ASSOC));
        self::assertSame([['id' => 8]], $this->db->fetchAll('SELECT * FROM record', Enum::FETCH_ASSOC));
        self::assertSame([['version' => '1.0.0']], $this->db->fetchAll('SELECT version FROM phalcon_migrations', Enum::FETCH_ASSOC));
        self::assertCount(3, $this->db->listTables());

        // Even a database containing only legacy history is not a new installation.
        $this->db->execute('DROP TABLE user, record');
        try {
            $this->runMigration();
            self::fail('The baseline accepted legacy migration history.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('existing migration history', $exception->getMessage());
        }
        self::assertSame(['phalcon_migrations'], $this->db->listTables());
    }

    public function testDisabledChecksAndActiveTransactionsAreRejectedBeforeDdl(): void
    {
        Migration::setup($this->options['config']->database);
        $baseline = new \CoreMigration_400();
        $connection = $baseline->getConnection();
        $connection->execute('SET SESSION foreign_key_checks = 0');
        try {
            $baseline->morph();
            self::fail('Disabled integrity checks were accepted.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('enable foreign_key_checks', $exception->getMessage());
        }
        self::assertSame(0, (int) $connection->fetchOne('SELECT @@SESSION.foreign_key_checks AS enabled', Enum::FETCH_ASSOC)['enabled']);
        self::assertSame([], $this->db->listTables());
        $connection->execute('SET SESSION foreign_key_checks = 1');
        $connection->begin();
        try {
            $baseline->morph();
            self::fail('DDL would have committed an active transaction.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('outside a transaction', $exception->getMessage());
        } finally {
            self::assertTrue($connection->isUnderTransaction());
            $connection->rollback();
        }
        self::assertSame([], $this->db->listTables());
    }

    private function runMigration(): string
    {
        ob_start();
        try {
            Migrations::run($this->options);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
