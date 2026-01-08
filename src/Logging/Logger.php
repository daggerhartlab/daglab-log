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
	 * Sanitize URL based on privacy settings.
	 *
	 * @param string $url The URL to sanitize
	 * @return string Sanitized URL
	 */
	private function sanitizeUrl(string $url): string {
		if (empty($url)) {
			return $url;
		}

		// Option 1: Strip all query parameters
		if (Settings::getStripQueryParams()) {
			return strtok($url, '?') ?: $url;
		}

		// Option 2: Mask sensitive query parameters
		if (Settings::getMaskSensitiveParams()) {
			$sensitive_params = array_merge([
				'token',
				'api_key',
				'apikey',
				'password',
				'pass',
				'secret',
				'key',
				'auth',
				'access_token',
				'refresh_token'
			], Settings::getAdditionalSensitiveParams());

			$parsed = parse_url($url);

			// If no query string, return original URL
			if (!isset($parsed['query'])) {
				return $url;
			}

			parse_str($parsed['query'], $params);

			// Mask sensitive parameters
			foreach ($sensitive_params as $param) {
				if (isset($params[$param])) {
					$params[$param] = '*****';
				}
			}

			$parsed['query'] = http_build_query($params);
			return $this->buildUrl($parsed);
		}

		return $url;
	}

	/**
	 * Anonymize IP address based on privacy settings.
	 *
	 * @param string $ip The IP address to anonymize
	 * @return string Anonymized IP address
	 */
	private function anonymizeIp(string $ip): string {
		if (!Settings::getAnonymizeIp()) {
			return $ip;
		}

		// IPv4: Remove last octet (e.g., 192.168.1.100 → 192.168.1.0)
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return preg_replace('/\.\d+$/', '.0', $ip);
		}

		// IPv6: Keep first 48 bits only (e.g., 2001:0db8:85a3::8a2e:0370:7334 → 2001:0db8:85a3::)
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$parts = explode(':', $ip);
			return implode(':', array_slice($parts, 0, 3)) . '::';
		}

		return $ip;
	}

	/**
	 * Rebuild URL from parse_url() components.
	 *
	 * @param array $parsed Parsed URL components from parse_url()
	 * @return string Reconstructed URL
	 */
	private function buildUrl(array $parsed): string {
		$url = '';

		if (isset($parsed['scheme'])) {
			$url .= $parsed['scheme'] . '://';
		}

		if (isset($parsed['host'])) {
			$url .= $parsed['host'];
		}

		if (isset($parsed['port'])) {
			$url .= ':' . $parsed['port'];
		}

		if (isset($parsed['path'])) {
			$url .= $parsed['path'];
		}

		if (isset($parsed['query'])) {
			$url .= '?' . $parsed['query'];
		}

		if (isset($parsed['fragment'])) {
			$url .= '#' . $parsed['fragment'];
		}

		return $url;
	}

	/**
	 * Truncate text to a specified length.
	 */
	private function truncateText($text, $length): string {
		return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 3) . '...' : $text;
	}

	/**
	 * Get current URL.
	 */
	public function getCurrentUrl(): ?string {
		$host = $this->getServerVar('HTTP_HOST');
		$uri = $this->getServerVar('REQUEST_URI');

		if (empty($host) || empty($uri)) {
			return null;
		}

		$protocol = ($this->getServerVar('HTTPS') === 'on') ? 'https' : 'http';
		$url = $protocol . '://' . $host . $uri;
		return $this->truncateText($this->sanitizeUrl($url), 500);
	}

	/**
	 * Get referer.
	 */
	private function getReferer(): ?string {
		$referer = $this->getServerVar('HTTP_REFERER');
		return !empty($referer) ? $this->truncateText($this->sanitizeUrl($referer), 500) : null;
	}

	/**
	 * Get visitor's hostname.
	 */
	private function getVisitorHostname(): ?string {
		$hostnames = [];

		$forwarded = $this->getServerVar('HTTP_X_FORWARDED_FOR');
		if (!empty($forwarded)) {
			$hostnames[] = $this->anonymizeIp($forwarded);
		}

		$remote = $this->getServerVar('REMOTE_ADDR');
		if (!empty($remote)) {
			$hostnames[] = $this->anonymizeIp($remote);
		}

		$hostname = implode(', ', $hostnames);
		return !empty($hostname) ? $this->truncateText($hostname, 255) : null;
	}

}
