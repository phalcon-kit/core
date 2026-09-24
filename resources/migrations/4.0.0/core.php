<?php

declare(strict_types=1);

use Phalcon\Db\Enum;
use PhalconKit\Migrations\SqlMigration;

/**
 * Install the retained Core 4 schema into a fresh MySQL/MariaDB database.
 *
 * Run the whole migration through phalcon/migrations; the file name represents
 * one baseline, not a table named "core". Each SQL file creates one table with its
 * constraints. Dependency ordering keeps foreign-key checks enabled throughout.
 * All names are connection-local, including when the database is not phalcon_kit.
 *
 * Existing applications keep their own migration history and schema. This
 * baseline deliberately refuses nonempty databases and destructive rollbacks.
 * MySQL DDL commits implicitly: a failed install must be investigated and the
 * disposable database recreated before retrying. See ../README.md.
 */
final class CoreMigration_400 extends SqlMigration
{
    /**
     * Create the 29 retained tables and their foreign keys in dependency order.
     *
     * Requires the MySQL adapter, a selected empty database, DDL privileges, and
     * enabled foreign-key checks. The runner's empty phalcon_migrations table is
     * allowed and normalized to the same engine/encoding as the application tables.
     * No accounts, roles, or other application records are seeded.
     *
     * @throws RuntimeException If the connection/database is unsuitable for a fresh install.
     * @throws PDOException If a DDL statement fails; preceding DDL is not rolled back.
     */
    public function morph(): void
    {
        $connection = $this->getConnection();
        if ($connection->getDialectType() !== 'mysql') {
            throw new RuntimeException('The Core 4 baseline requires MySQL or MariaDB.');
        }
        if ($connection->isUnderTransaction()) {
            throw new RuntimeException('Run the Core 4 baseline outside a transaction; MySQL DDL commits implicitly.');
        }
        $state = $connection->fetchOne('SELECT DATABASE() AS db, @@SESSION.foreign_key_checks AS fk', Enum::FETCH_ASSOC);
        if (empty($state['db']) || (int) $state['fk'] !== 1) {
            throw new RuntimeException('Select a fresh database and enable foreign_key_checks before installing Core 4.');
        }
        $tables = $connection->listTables();
        if (array_diff($tables, ['phalcon_migrations']) !== []) {
            throw new RuntimeException('The Core 4 baseline requires an empty database; preserve existing schemas and use application-owned upgrade migrations.');
        }
        if (in_array('phalcon_migrations', $tables, true)) {
            $history = $connection->fetchOne('SELECT COUNT(*) AS total FROM `phalcon_migrations`', Enum::FETCH_ASSOC);
            if ((int) $history['total'] !== 0) {
                throw new RuntimeException('The Core 4 baseline cannot replace existing migration history.');
            }
        }

        $this->executeSqlFiles($this->sqlFiles());
        if (in_array('phalcon_migrations', $tables, true)) {
            $connection->execute('ALTER TABLE `phalcon_migrations` ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    /**
     * Refuse automatic removal of a schema that may contain application data.
     *
     * Restore a reviewed backup or use an application-owned migration instead.
     * Disposable test databases may be dropped explicitly by their test owner.
     *
     * @throws RuntimeException Always; no tables or migration history are changed here.
     */
    public function down(): void
    {
        throw new RuntimeException('The Core 4 baseline has no destructive rollback; restore a backup or use an application-owned migration.');
    }

    /** @return list<string> One CREATE TABLE statement per file, in dependency order. */
    private function sqlFiles(): array
    {
        $tables = [
            'user', 'audit', 'backup', 'feature', 'file',
            'group', 'job', 'job_scheduler', 'log', 'oauth2',
            'profile', 'role', 'session', 'setting', 'template',
            'type', 'audit_detail', 'email', 'file_relation', 'group_feature',
            'group_role', 'group_type', 'role_feature', 'role_role', 'user_feature',
            'user_group', 'user_role', 'user_type', 'email_file',
        ];
        return array_map(static fn (string $table): string => __DIR__ . '/sql/' . $table . '.sql', $tables);
    }
}
