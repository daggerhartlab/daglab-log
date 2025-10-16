<?php

namespace DagLabLog;

class LogTableManager {

	const VERSION = '1.0.0';
	const VERSION_OPTION = 'daglab_log_db_version';
	const RAW_TABLE_NAME = 'daglab_log';

	/**
	 * Check if the table needs upgrade.
	 */
	public static function maybeUpdate(): void {
		$current_version = get_option(static::VERSION_OPTION, '0.0.0');

		if (version_compare($current_version, static::VERSION, '<')) {
			static::createTable();
			update_option(static::VERSION_OPTION, static::VERSION);
		}
	}

	public static function getTableName(): string {
		global $wpdb;

		// Define table name with WordPress prefix
		return $wpdb->prefix . static::RAW_TABLE_NAME;
	}


	public static function createTable(): void {
		global $wpdb;
		$table_name = static::getTableName();

		// Check if the table already exists.
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
			return;
		}

		// SQL for creating the table.
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
	        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	        user_id int unsigned DEFAULT 0,
	        channel varchar(64) DEFAULT '',
	        level varchar(16) NOT NULL,
	        severity int unsigned DEFAULT NULL,
	        message longtext NOT NULL,
	        location varchar(500) DEFAULT NULL,
	        referer varchar(500) DEFAULT NULL,
	        hostname varchar(255) DEFAULT NULL,
	        saved tinyint(1) unsigned NOT NULL DEFAULT 0,
	        timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	        PRIMARY KEY (id),
	        KEY idx_user_id (user_id),
	        KEY idx_channel (channel),
	        KEY idx_severity (severity),
	        KEY idx_level (level)
	    ) $charset_collate;";

		// Include WordPress upgrade functions.
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// Create the table.
		$result = dbDelta($sql);

		// Log the result
		if (empty($result)) {
			error_log("Error Log Table: Failed to create table $table_name");
		} else {
			// Set the database version for future upgrades.
			add_option('error_log_table_version',  static::VERSION);
		}

		// Create indexes separately if needed (sometimes dbDelta misses them)
		static::createIndexes($table_name);
	}

	/**
	 * Create additional indexes for better performance.
	 */
	public static function createIndexes($table_name): void {
		global $wpdb;

		// Additional composite indexes for common queries.
		$indexes = [
			'idx_recent_errors' => "CREATE INDEX idx_recent_errors ON $table_name (timestamp DESC, severity)",
			'idx_user_errors' => "CREATE INDEX idx_user_errors ON $table_name (user_id, severity, timestamp DESC)",
			'idx_error_frequency' => "CREATE INDEX idx_error_frequency ON $table_name (level, message(100), timestamp)",
			'idx_location_errors' => "CREATE INDEX idx_location_errors ON $table_name (location(100), timestamp DESC)",
		];

		foreach ($indexes as $index_name => $index) {
			// Check if the index already exists.
			$index_exists = $wpdb->get_var($wpdb->prepare("
	            SELECT COUNT(*)
	            FROM information_schema.statistics
	            WHERE table_schema = %s
	            AND table_name = %s
	            AND index_name = %s
	        ", DB_NAME, $table_name, $index_name));

			// Create index only if it doesn't exist.
			if (!$index_exists) {
				$result = $wpdb->query($index);
				if ($result === false) {
					error_log("Failed to create index {$index_name}: " . $wpdb->last_error);
				}
			}
		}
	}

	/**
	 * Plugin deactivation hook - optionally remove table.
	 */
	public static function dropTable(): void {
		global $wpdb;

		// Only uncomment this if you want to remove data on deactivation
		$table_name = static::getTableName();
		$wpdb->query("DROP TABLE IF EXISTS $table_name");
		delete_option(static::VERSION_OPTION);
	}

}
