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
	 * Safely get and validate $_SERVER variables.
	 *
	 * @param string $key The $_SERVER key to retrieve
	 * @param string $default Default value if key doesn't exist
	 * @return string Sanitized value
	 */
	public function getServerVar(string $key, string $default = ''): string {
		if (!isset($_SERVER[$key])) {
			return $default;
		}

		$value = $_SERVER[$key];

		// Sanitize based on the type of variable
		switch ($key) {
			case 'REQUEST_METHOD':
				return in_array($value, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'], true)
					? $value
					: $default;

			case 'REQUEST_URI':
			case 'HTTP_REFERER':
				// Remove any null bytes and control characters
				$value = str_replace("\0", '', $value);
				$filtered = filter_var($value, FILTER_SANITIZE_URL);
				return $filtered !== false ? $filtered : $default;

			case 'HTTP_HOST':
				// Validate hostname format
				$value = str_replace("\0", '', $value);
				$filtered = filter_var($value, FILTER_SANITIZE_URL);
				return $filtered !== false ? $filtered : $default;

			case 'HTTPS':
				return in_array(strtolower($value), ['on', '1', 'true', 'yes'], true) ? 'on' : 'off';

			case 'HTTP_X_FORWARDED_FOR':
			case 'REMOTE_ADDR':
				// Sanitize IP addresses
				$value = sanitize_text_field($value);
				// For X-Forwarded-For, take only the first IP
				if ($key === 'HTTP_X_FORWARDED_FOR' && str_contains($value, ',')) {
					$value = trim(explode(',', $value)[0]);
				}
				return $value;

			default:
				return sanitize_text_field($value);
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
		$host = $this->getServerVar('HTTP_HOST');
		$uri = $this->getServerVar('REQUEST_URI');

		if (empty($host) || empty($uri)) {
			return null;
		}

		$protocol = ($this->getServerVar('HTTPS') === 'on') ? 'https' : 'http';
		return $this->truncateText($protocol . '://' . $host . $uri, 500);
	}

	/**
	 * Get referer.
	 */
	private function getReferer() {
		$referer = $this->getServerVar('HTTP_REFERER');
		return !empty($referer) ? $this->truncateText($referer, 500) : null;
	}

	/**
	 * Get visitor's hostname.
	 */
	private function getVisitorHostname(): ?string {
		$hostnames = [];

		$forwarded = $this->getServerVar('HTTP_X_FORWARDED_FOR');
		if (!empty($forwarded)) {
			$hostnames[] = $forwarded;
		}

		$remote = $this->getServerVar('REMOTE_ADDR');
		if (!empty($remote)) {
			$hostnames[] = $remote;
		}

		$hostname = implode(', ', $hostnames);
		return !empty($hostname) ? $this->truncateText($hostname, 255) : null;
	}

}
