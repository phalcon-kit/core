-- Core 4.0 fresh-install baseline. References use the selected database.
CREATE TABLE `audit` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` BIGINT UNSIGNED NULL,
    `uuid` CHAR(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `model` VARCHAR(255) NOT NULL COMMENT 'The class name of the model being changed',
    `table` VARCHAR(64) NOT NULL COMMENT 'The database table name',
    `primary` BIGINT UNSIGNED NOT NULL COMMENT 'The primary key of the record being changed',
    `event` ENUM('create','update','delete','restore','other') NOT NULL DEFAULT 'other',
    `before` LONGTEXT NULL COMMENT 'JSON snapshot of data before the change',
    `after` LONGTEXT NULL COMMENT 'JSON snapshot of data after the change',
    `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
    `created_by` BIGINT UNSIGNED NULL COMMENT 'The user who performed the action',
    `created_as` BIGINT UNSIGNED NULL COMMENT 'The user being impersonated (if any)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uuid_UNIQUE` (`uuid`),
    KEY `idx_audited_record` (`table`, `primary`),
    KEY `idx_created_by` (`created_by`),
    KEY `fk_audit_created_as` (`created_as`),
    CONSTRAINT `fk_audit_created_as` FOREIGN KEY (`created_as`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_audit_created_by` FOREIGN KEY (`created_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
