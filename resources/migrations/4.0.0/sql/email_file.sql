-- Core 4.0 fresh-install baseline. References use the selected database.
CREATE TABLE `email_file` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `email_id` BIGINT UNSIGNED NOT NULL,
    `file_id` BIGINT UNSIGNED NOT NULL,
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
    `created_by` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uuid_UNIQUE` (`uuid`),
    UNIQUE KEY `uq_email_file` (`email_id`, `file_id`),
    KEY `idx_file_id` (`file_id`),
    KEY `fk_email_file_created_by` (`created_by`),
    CONSTRAINT `fk_email_file_created_by` FOREIGN KEY (`created_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_email_file_email_id` FOREIGN KEY (`email_id`) REFERENCES `email`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_email_file_file_id` FOREIGN KEY (`file_id`) REFERENCES `file`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
