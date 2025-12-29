<?php

namespace DagLabLog\Logging;

use DagLabLog\ErrorSeverityManager;
use DagLabLog\LogTableManager;
use DagLabLog\Settings;

class Logger {

	public function log(string $message, array $context = []): void {
		$this->writeLog(
			$context['channel'] ?? '',
			$context['log_level'] ?? 'debug',
			$message,
				$context['severity'] ?? null
		);
	}

	/**
	 * Log a message of any severity.
	 *
	 * @param string $channel
	 * @param string $log_level
	 * @param string $message
	 * @param int|null $severity
	 *
	 * @return void
	 */
	public function writeLog(string $channel, string $log_level, string $message, int $severity = null): void {
		if (!ErrorSeverityManager::shouldLog($log_level, Settings::getMinLogLevel())) {
			return;
		}

		global $wpdb;

		$data = [
			'user_id' => get_current_user_id(),
			'channel' => sanitize_text_field($this->truncateText($channel, 64)),
			'level' => sanitize_text_field($log_level),
			'severity' => $severity,
			'message' => sanitize_textarea_field($message),
			'location' => $this->getCurrentUrl(),
			'referer' => $this->getReferer(),
			'hostname' => $this->getVisitorHostname(),
			'timestamp' => current_time('mysql', true), // GMT time.
		];

		$result = $wpdb->insert(LogTableManager::getTableName(), $data);

		if ($result === false) {
			// Fallback to error_log to avoid losing critical error information
			error_log(sprintf(
				'DagLab Log: Failed to insert log entry. Error: %s. Message: %s',
				$wpdb->last_error,
				substr($message, 0, 200)
			));
		}
	}

	/**
	 * Truncate text to a specified length.
	 */
	private function truncateText($text, $length) {
		return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 3) . '...' : $text;
	}

	/**
	 * Get current URL.
	 */
	private function getCurrentUrl() {
		if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['REQUEST_URI'])) {
			return null;
		}

		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		return $this->truncateText( $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 500);
	}

	/**
	 * Get referer.
	 */
	private function getReferer() {
		return isset($_SERVER['HTTP_REFERER']) ? $this->truncateText($_SERVER['HTTP_REFERER'], 500) : null;
	}

	/**
	 * Get visitor's hostname.
	 */
	private function getVisitorHostname(): ?string {
		$hostnames = [];
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$hostnames[] = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$hostnames[] = $_SERVER['REMOTE_ADDR'];
		}

		$hostname = implode(', ', $hostnames);
		return !empty($hostname) ? $this->truncateText($hostname, 255) : null;
	}

}
