-- Core 4.0 fresh-install baseline. References use the selected database.
CREATE TABLE `log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `level` INT NOT NULL DEFAULT '0',
    `type` ENUM('critical','alert','error','warning','notice','info','debug','emergency','other') NOT NULL DEFAULT 'other',
    `message` TEXT NOT NULL,
    `context` LONGTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
    `created_by` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uuid_UNIQUE` (`uuid`),
    KEY `idx_type_level` (`type`, `level`),
    KEY `fk_log_created_by` (`created_by`),
    CONSTRAINT `fk_log_created_by` FOREIGN KEY (`created_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
