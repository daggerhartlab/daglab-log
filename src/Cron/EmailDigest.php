<?php

namespace DagLabLog\Cron;

use DagLabLog\Admin\LogsPage;
use DagLabLog\Admin\SettingsPage;
use DagLabLog\ErrorSeverityManager;
use DagLabLog\LogTableManager;
use DagLabLog\Settings;

class EmailDigest {

	const CRON_HOOK = 'daglab_log_email_digest';

	public static function bootstrap() {
		$plugin = new static();
		// Schedule the cron job
		add_action('init', [ $plugin, 'scheduleJob' ] );

		// Register the cleanup function
		add_action(static::CRON_HOOK, [ $plugin, 'process' ] );

		// Clean up on plugin deactivation
		register_deactivation_hook(__FILE__, [ $plugin, 'unscheduleJob' ] );
	}

	/**
	 * Schedule the cleanup cron if not already scheduled.
	 */
	public function scheduleJob(bool $enabled = null, string $frequency = null): void {
		$enabled = $enabled ?? Settings::getDigestEnabled();
		$frequency = $frequency ?? Settings::getDigestFrequency();
		if ($enabled && !wp_next_scheduled(static::CRON_HOOK)) {
			wp_schedule_event(time(), $frequency, static::CRON_HOOK);
		}
	}

	/**
	 * Remove scheduled cron on deactivation.
	 */
	public function unscheduleJob(): void {
		wp_clear_scheduled_hook(static::CRON_HOOK);
	}

	/**
	 * Send an email digest of log messages.
	 */
	public function process(): void {
		$stats = $this->getDigestStats();

		// If no new logs, do not send email.
		if (empty($stats)) {
			return;
		}

		$schedules = wp_get_schedules();
		$schedule = $schedules[Settings::getDigestFrequency()];
		$start_date = wp_date('Y-m-d g:ia', time() - $schedule['interval'] - 180);
		$end_date = wp_date('Y-m-d g:ia');

		$subject = get_bloginfo('name') . ' : ' . __('DagLab Log Digest', 'daglab-log');
		$message = "This is a summary of log events that have occurred on your WordPress site.\n\n";
		$message .= "Date range: $start_date -> $end_date\n\n";
		foreach ($stats as $level => $count) {
			$name = ErrorSeverityManager::getLogLevelName($level);
			$message .= "$name ($level): $count\n";
		}
		$message .= "\nRecent log messages can be reviewed: " . admin_url('tools.php?page=' . LogsPage::PAGE_SLUG);
		$message .= "\nSettings for this digest can be changed: " . admin_url('options_general.php?page=' . SettingsPage::PAGE_SLUG);

		\wp_mail( Settings::getDigestEmail(), $subject, $message );
	}

	/**
	 * Get digest statistics for the digest frequency.
	 */
	private function getDigestStats(): array {
		global $wpdb;

		$table_name = LogTableManager::getTableName();
		$schedules = wp_get_schedules();
		$schedule = $schedules[Settings::getDigestFrequency()];
		// Since "now - frequency - 3 minutes for padding".
		$since = date('Y-m-d H:i:s', time() - $schedule['interval'] - 180);

		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT level, COUNT(*) as count FROM $table_name WHERE timestamp >= %s GROUP BY level ORDER BY count DESC",
			$since
		), ARRAY_A);

		$stats = [];
		foreach ($results as $row) {
			$stats[$row['level']] = $row['count'];
		}

		return $stats;
	}

}
