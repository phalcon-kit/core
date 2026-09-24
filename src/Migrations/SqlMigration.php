<?php

declare(strict_types=1);

namespace PhalconKit\Migrations;

use Phalcon\Migrations\Mvc\Model\Migration;
use PhalconKit\Exception\RuntimeException;

/**
 * Phalcon migration base for application-owned SQL files.
 *
 * Requires the optional phalcon/migrations package (^3.0.1). Extend this class
 * and call the SQL helpers from morph(), up(), or down(); the standard runner
 * still configures the connection and records successful migration versions.
 *
 * Use one complete SQL statement per file and resolve paths with __DIR__. Files
 * are executed verbatim, in the supplied order, without splitting SQL or changing
 * transaction/foreign-key settings. Do not interpolate request data into SQL.
 * MySQL DDL commits implicitly; supply an appropriate rollback/data recovery plan
 * in each application migration. The helpers cannot make DDL transactional.
 */
abstract class SqlMigration extends Migration
{
    /**
     * Read and execute one SQL statement using the runner's active connection.
     *
     * @param string $path Local SQL file; an absolute path based on __DIR__ is recommended.
     * @throws RuntimeException If the file is missing, unreadable, empty, or execution returns false.
     * @throws \PDOException If the database rejects the statement.
     */
    protected function executeSqlFile(string $path): void
    {
        $this->executeSqlFiles([$path]);
    }

    /**
     * Read every file first, then execute statements in the given order.
     *
     * A missing or empty file prevents the entire batch from starting. A database
     * error stops subsequent statements; earlier statements may already be committed.
     * Neither migration history nor database integrity settings are changed here.
     *
     * @param list<string> $paths Local SQL files, each containing one complete statement.
     * @throws RuntimeException If any file is missing, unreadable, empty, or execution returns false.
     * @throws \PDOException If the database rejects a statement.
     */
    protected function executeSqlFiles(array $paths): void
    {
        $statements = [];
        foreach ($paths as $path) {
            if (!is_file($path) || !is_readable($path)) {
                throw new RuntimeException('Missing or unreadable migration SQL file: ' . $path);
            }
            $statement = file_get_contents($path);
            if ($statement === false || trim($statement) === '') {
                throw new RuntimeException('Unreadable or empty migration SQL file: ' . $path);
            }
            $statements[] = $statement;
        }
        foreach ($statements as $index => $statement) {
            if (!$this->getConnection()->execute($statement)) {
                throw new RuntimeException('Migration SQL execution failed: ' . $paths[$index]);
            }
        }
    }
}
