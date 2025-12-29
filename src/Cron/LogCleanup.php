<?php

namespace DagLabLog\Cron;

use DagLabLog\LogTableManager;
use DagLabLog\Settings;

class LogCleanup {

	const CRON_HOOK = 'daglab_log_cleanup';

	public static function bootstrap() {
		$plugin = new static();
		// Schedule the cron job
		add_action('init', [ $plugin, 'scheduleJob' ] );

		// Register the cleanup function
		add_action(static::CRON_HOOK, [ $plugin, 'process' ] );

		// Clean up on plugin deactivation
		register_deactivation_hook(DAGLAB_LOG_PLUGIN_FILE, [ $plugin, 'unscheduleJob' ] );
	}

	/**
	 * Schedule the cleanup cron if not already scheduled.
	 */
	public function scheduleJob(): void {
		if (!wp_next_scheduled(static::CRON_HOOK)) {
			wp_schedule_event(time(), 'twicedaily', static::CRON_HOOK);
		}
	}

	/**
	 * Remove scheduled cron on deactivation.
	 */
	public function unscheduleJob(): void {
		wp_clear_scheduled_hook(static::CRON_HOOK);
	}

	/**
	 * Cleanup old logs - keep only the latest max_entries.
	 */
	public function process(): int {
		global $wpdb;

		$table_name = LogTableManager::getTableName();
		$current_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
		$max_entries = Settings::getMaxEntries();

		if ($current_count <= $max_entries) {
			return 0;
		}

		// Delete oldest entries using two-step approach for MySQL compatibility.
		$delete_count = $current_count - $max_entries;

		// First, get the IDs to delete
		$ids_to_delete = $wpdb->get_col($wpdb->prepare(
			"SELECT id FROM $table_name
			 WHERE saved = 0
			 ORDER BY timestamp ASC
			 LIMIT %d",
			$delete_count
		));

		// Then delete them if any found
		$deleted = 0;
		if (!empty($ids_to_delete)) {
			$ids_placeholder = implode(',', array_fill(0, count($ids_to_delete), '%d'));
			$deleted = $wpdb->query($wpdb->prepare(
				"DELETE FROM $table_name WHERE id IN ($ids_placeholder)",
				...$ids_to_delete
			));
		}

		// Log the cleanup activity
		if ($deleted > 0) {
			error_log("Error Log Cleanup: Deleted $deleted old entries, keeping latest $max_entries");
		}

		return $deleted;
	}

}
