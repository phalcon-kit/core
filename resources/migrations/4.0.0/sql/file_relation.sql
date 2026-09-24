-- Core 4.0 fresh-install baseline. References use the selected database.
CREATE TABLE `file_relation` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `file_id` BIGINT UNSIGNED NOT NULL,
    `relation_table` VARCHAR(64) NOT NULL COMMENT 'e.g., post, user_profile',
    `relation_id` BIGINT UNSIGNED NOT NULL COMMENT 'The ID of the record the file is related to',
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
    `created_by` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uuid_UNIQUE` (`uuid`),
    UNIQUE KEY `uq_file_relation` (`file_id`, `relation_table`, `relation_id`),
    KEY `idx_relation_lookup` (`relation_table`, `relation_id`),
    KEY `fk_file_relation_created_by` (`created_by`),
    CONSTRAINT `fk_file_relation_created_by` FOREIGN KEY (`created_by`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_file_relation_file_id` FOREIGN KEY (`file_id`) REFERENCES `file`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
