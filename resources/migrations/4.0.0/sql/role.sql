-- Core 4.0 fresh-install baseline. References use the selected database.
CREATE TABLE `role` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `key` VARCHAR(50) NOT NULL COMMENT 'Programmatic identifier, e.g., admin, editor',
    `label` VARCHAR(100) NOT NULL COMMENT 'Human-readable name, e.g., Administrator',
    `position` INT UNSIGNED NOT NULL DEFAULT '0',
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
    `created_by` BIGINT UNSIGNED NULL,
    `updated_at` DATETIME NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    `deleted_at` DATETIME NULL,
    `deleted_by` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uuid_UNIQUE` (`uuid`),
    UNIQUE KEY `key_UNIQUE` (`key`),
    KEY `fk_role_created_by` (`created_by`),
    KEY `fk_role_updated_by` (`updated_by`),
    KEY `fk_role_deleted_by` (`deleted_by`),
    CONSTRAINT `fk_role_created_by` FOREIGN KEY (`created_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_role_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_role_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
