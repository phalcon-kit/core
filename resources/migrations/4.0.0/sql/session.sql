-- Core 4.0 fresh-install baseline. References use the selected database.
CREATE TABLE `session` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `as_user_id` BIGINT UNSIGNED NULL,
    `token` VARCHAR(128) COLLATE utf8mb4_bin NOT NULL,
    `jwt` TEXT COLLATE utf8mb4_bin NULL,
    `meta` LONGTEXT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uuid_UNIQUE` (`uuid`),
    UNIQUE KEY `token_UNIQUE` (`token`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_expires_at` (`expires_at`),
    KEY `fk_session_as_user_id` (`as_user_id`),
    CONSTRAINT `fk_session_as_user_id` FOREIGN KEY (`as_user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_session_user_id` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
