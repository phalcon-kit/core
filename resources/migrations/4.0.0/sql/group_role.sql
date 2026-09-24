-- Core 4.0 fresh-install baseline. References use the selected database.
CREATE TABLE `group_role` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `position` INT UNSIGNED NOT NULL DEFAULT '0',
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
    `created_by` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uuid_UNIQUE` (`uuid`),
    UNIQUE KEY `uq_group_role` (`group_id`, `role_id`),
    KEY `idx_role_id` (`role_id`),
    KEY `fk_group_role_created_by` (`created_by`),
    CONSTRAINT `fk_group_role_created_by` FOREIGN KEY (`created_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_group_role_group_id` FOREIGN KEY (`group_id`) REFERENCES `group`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_group_role_role_id` FOREIGN KEY (`role_id`) REFERENCES `role`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
