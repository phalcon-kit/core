-- Core 4.0 fresh-install baseline. References use the selected database.
CREATE TABLE `audit_detail` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `audit_id` BIGINT UNSIGNED NOT NULL,
    `column` VARCHAR(64) NOT NULL,
    `before` MEDIUMTEXT NULL,
    `after` MEDIUMTEXT NULL,
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
    `created_by` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uuid_UNIQUE` (`uuid`),
    KEY `idx_audit_id` (`audit_id`),
    KEY `fk_audit_detail_created_by` (`created_by`),
    CONSTRAINT `fk_audit_detail_audit_id` FOREIGN KEY (`audit_id`) REFERENCES `audit`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_audit_detail_created_by` FOREIGN KEY (`created_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
