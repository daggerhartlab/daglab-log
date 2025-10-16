<?php

namespace DagLabLog;

class ErrorSeverityManager {

	/**
	 * Get master severity configuration
	 * Each severity maps to: [log_level, human_name, description, priority]
	 */
	private static function getSeverities(): array {
		return [
			// FATAL LEVEL (Priority 60+)
			E_ERROR => [
				'log_level' => 'fatal',
				'name' => __('Fatal Error', 'daglab-log'),
				'description' => __('Fatal run-time error that stops script execution', 'daglab-log'),
				'priority' => 70
			],
			E_CORE_ERROR => [
				'log_level' => 'fatal',
				'name' => __('Core Fatal Error', 'daglab-log'),
				'description' => __('Fatal error in PHP core', 'daglab-log'),
				'priority' => 70
			],
			E_COMPILE_ERROR => [
				'log_level' => 'fatal',
				'name' => __('Compile Fatal Error', 'daglab-log'),
				'description' => __('Fatal compile-time error', 'daglab-log'),
				'priority' => 70
			],
			E_PARSE => [
				'log_level' => 'fatal',
				'name' => __('Parse Error', 'daglab-log'),
				'description' => __('Compile-time parse error', 'daglab-log'),
				'priority' => 70
			],

			// ERROR LEVEL (Priority 40-59)
			E_RECOVERABLE_ERROR => [
				'log_level' => 'error',
				'name' => __('Recoverable Error', 'daglab-log'),
				'description' => __('Catchable fatal error', 'daglab-log'),
				'priority' => 50
			],
			E_USER_ERROR => [
				'log_level' => 'error',
				'name' => __('User Error', 'daglab-log'),
				'description' => __('User-generated error message', 'daglab-log'),
				'priority' => 50
			],

			// WARNING LEVEL (Priority 20-39)
			E_WARNING => [
				'log_level' => 'warning',
				'name' => __('Warning', 'daglab-log'),
				'description' => __('Run-time warning (non-fatal)', 'daglab-log'),
				'priority' => 30
			],
			E_CORE_WARNING => [
				'log_level' => 'warning',
				'name' => __('Core Warning', 'daglab-log'),
				'description' => __('Warning from PHP core', 'daglab-log'),
				'priority' => 30
			],
			E_COMPILE_WARNING => [
				'log_level' => 'warning',
				'name' => __('Compile Warning', 'daglab-log'),
				'description' => __('Compile-time warning', 'daglab-log'),
				'priority' => 30
			],
			E_USER_WARNING => [
				'log_level' => 'warning',
				'name' => __('User Warning', 'daglab-log'),
				'description' => __('User-generated warning', 'daglab-log'),
				'priority' => 30
			],

			// NOTICE LEVEL (Priority 10-19)
			E_NOTICE => [
				'log_level' => 'notice',
				'name' => __('Notice', 'daglab-log'),
				'description' => __('Run-time notice (minor issue)', 'daglab-log'),
				'priority' => 15
			],
			E_USER_NOTICE => [
				'log_level' => 'notice',
				'name' => __('User Notice', 'daglab-log'),
				'description' => __('User-generated notice', 'daglab-log'),
				'priority' => 15
			],
			E_DEPRECATED => [
				'log_level' => 'notice',
				'name' => __('Deprecated', 'daglab-log'),
				'description' => __('Feature deprecated in current PHP version', 'daglab-log'),
				'priority' => 12
			],
			E_USER_DEPRECATED => [
				'log_level' => 'notice',
				'name' => __('User Deprecated', 'daglab-log'),
				'description' => __('User-generated deprecation warning', 'daglab-log'),
				'priority' => 12
			],
			E_STRICT => [
				'log_level' => 'notice',
				'name' => __('Strict Standards', 'daglab-log'),
				'description' => __('Coding standard suggestion', 'daglab-log'),
				'priority' => 10
			]
		];
	}

	/**
	 * Get log level hierarchy (higher number = more severe)
	 */
	private static function getLogLevels(): array {
		return [
			'debug' => 0,
			'info' => 10,
			'notice' => 20,
			'warning' => 30,
			'error' => 40,
			'fatal' => 50
		];
	}

	/**
	 * Get log level display information
	 */
	public static function getLogLevelInfo(): array {
		return [
			'debug' => [
				'name' => __('Debug', 'daglab-log'),
				'description' => __('Detailed diagnostic information.', 'daglab-log'),
				'color' => '#ffffff'
			],
			'info' => [
				'name' => __('Info', 'daglab-log'),
				'description' => __('General informational messages.', 'daglab-log'),
				'color' => '#dddddd'
			],
			'notice' => [
				'name' => __('Notice', 'daglab-log'),
				'description' => __('Normal but significant conditions.', 'daglab-log'),
				'color' => '#dcfdff'
			],
			'warning' => [
				'name' => __('Warning', 'daglab-log'),
				'description' => __('Warning conditions that should be addressed.', 'daglab-log'),
				'color' => '#ffffdc'
			],
			'error' => [
				'name' => __('Error', 'daglab-log'),
				'description' => __('Error conditions that affect functionality.', 'daglab-log'),
				'color' => '#fff3dc'
			],
			'fatal' => [
				'name' => __('Fatal', 'daglab-log'),
				'description' => __('System is unusable, immediate attention required.', 'daglab-log'),
				'color' => '#ffdcdc'
			]
		];
	}

	/**
	 * Get log level for the severity.
	 */
	public static function getLogLevel($severity): string {
		$severities = self::getSeverities();
		return $severities[$severity]['log_level'] ?? 'error';
	}

	/**
	 * Get the human-readable name for a severity.
	 */
	public static function getSeverityName($severity): string {
		$severities = self::getSeverities();
		return $severities[$severity]['name'] ?? __('Unknown Error', 'daglab-log');
	}

	/**
	 * Get the description for a severity.
	 */
	public static function getSeverityDescription($severity): string {
		$severities = self::getSeverities();
		return $severities[$severity]['description'] ?? 'Unknown error type';
	}

	/**
	 * Get priority for a severity (higher = more severe).
	 */
	public static function getSeverityPriority($severity): int {
		$severities = self::getSeverities();
		return $severities[$severity]['priority'] ?? 50;
	}

	/**
	 * Get all severity information.
	 */
	public static function getSeverityInfo($severity): array {
		$severities = self::getSeverities();
		return $severities[$severity] ?? [
			'log_level' => 'error',
			'name' => 'Unknown Error',
			'description' => 'Unknown error type',
			'priority' => 50
		];
	}

	/**
	 * Check if a severity exists
	 */
	public static function isValidSeverity($severity): bool {
		$severities = self::getSeverities();
		return isset($severities[$severity]);
	}

	/**
	 * Get all severities for a specific log level
	 */
	public static function getSeveritiesByLogLevel($log_level): array {
		$severities = self::getSeverities();
		$result = [];
		foreach ($severities as $severity => $info) {
			if ($info['log_level'] === $log_level) {
				$result[] = $severity;
			}
		}
		return $result;
	}

	/**
	 * Get human-readable name for a log level.
	 *
	 * @param string $log_level
	 *
	 * @return string
	 */
	public static function getLogLevelName(string $log_level): string {
		$log_level_info = self::getLogLevelInfo();
		return $log_level_info[$log_level]['name'] ?? ucfirst($log_level);
	}

	/**
	 * Get description for a log level
	 *
	 * @param string $log_level
	 *
	 * @return string
	 */
	public static function getLogLevelDescription(string $log_level): string {
		$log_level_info = self::getLogLevelInfo();
		return $log_level_info[$log_level]['description'] ?? '';
	}

	/**
	 * Get color for a log level (for UI)
	 *
	 * @param string $log_level
	 *
	 * @return string
	 */
	public static function getLogLevelColor(string $log_level): string {
		$log_level_info = self::getLogLevelInfo();
		return $log_level_info[$log_level]['color'] ?? '#6c757d';
	}

	/**
	 * Get numeric priority for a log level
	 *
	 * @param string $log_level
	 *
	 * @return int
	 */
	public static function getLogLevelPriority(string $log_level): int {
		$log_levels = self::getLogLevels();
		return $log_levels[$log_level] ?? 40;
	}

	/**
	 * Check if a log level exists
	 *
	 * @param string $log_level
	 *
	 * @return bool
	 */
	public static function isValidLogLevel(string $log_level): bool {
		$log_levels = self::getLogLevels();
		return isset($log_levels[$log_level]);
	}

	/**
	 * Get all log levels ordered by priority
	 *
	 * @param bool $ascending
	 *
	 * @return array
	 */
	public static function getAllLogLevels(bool $ascending = true): array {
		$levels = self::getLogLevels();
		$ascending ? asort($levels) : arsort($levels);
		return array_keys($levels);
	}

	/**
	 * Get log level options for dropdowns
	 *
	 * @return array
	 */
	public static function getLogLevelOptions(): array {
		$options = [];
		foreach (self::getAllLogLevels() as $level) {
			$options[$level] = self::getLogLevelName($level) . ' (' . self::getLogLevelDescription($level) . ')';
		}
		return $options;
	}

	/**
	 * Check if a severity should be logged based on minimum log level.
	 *
	 * @param string $log_level
	 * @param string $min_log_level
	 *
	 * @return bool
	 */
	public static function shouldLog(string $log_level, string $min_log_level): bool {
		if (!static::isValidLogLevel($log_level) || !static::isValidLogLevel($min_log_level)) {
			return false;
		}
		$log_level_priority = self::getLogLevelPriority($log_level);
		$min_priority = self::getLogLevelPriority($min_log_level);

		return $log_level_priority >= $min_priority;
	}

	/**
	 * Compare two severities (returns -1, 0, or 1)
	 */
	public static function compareSeverities($severity1, $severity2): int {
		$priority1 = self::getSeverityPriority($severity1);
		$priority2 = self::getSeverityPriority($severity2);

		return $priority1 <=> $priority2;
	}

	/**
	 * Compare two log levels
	 */
	public static function compareLogLevels($level1, $level2): int {
		$priority1 = self::getLogLevelPriority($level1);
		$priority2 = self::getLogLevelPriority($level2);

		return $priority1 <=> $priority2;
	}

	/**
	 * Get severities that would be logged at a given level
	 */
	public static function getSeveritiesForMinLevel($min_log_level): array {
		$min_priority = self::getLogLevelPriority($min_log_level);
		$severities_data = self::getSeverities();
		$severities = [];

		foreach ($severities_data as $severity => $info) {
			$severity_priority = self::getLogLevelPriority($info['log_level']);
			if ($severity_priority >= $min_priority) {
				$severities[] = $severity;
			}
		}

		// Sort by priority
		usort($severities, [self::class, 'compareSeverities']);

		return $severities;
	}

	/**
	 * Format severity for display
	 */
	public static function formatSeverity($severity, $include_description = false): string {
		$info = self::getSeverityInfo($severity);
		$formatted = $info['name'];

		if ($include_description) {
			$formatted .= ' - ' . $info['description'];
		}

		return $formatted;
	}

	/**
	 * Format log level for display
	 */
	public static function formatLogLevel($log_level, $include_description = false): string {
		$name = self::getLogLevelName($log_level);

		if ($include_description) {
			$description = self::getLogLevelDescription($log_level);
			return $name . ' - ' . $description;
		}

		return $name;
	}

	/**
	 * Get severity statistics
	 */
	public static function getSeverityStats(): array {
		$log_level_info = self::getLogLevelInfo();
		$stats = [];
		foreach (self::getAllLogLevels() as $level) {
			$severities = self::getSeveritiesByLogLevel($level);
			$stats[$level] = [
				'count' => count($severities),
				'severities' => $severities,
				'info' => $log_level_info[$level]
			];
		}
		return $stats;
	}

	/**
	 * Convert string severity to constant (useful for dynamic input)
	 */
	public static function stringToSeverity($severity_string): int {
		$severity_map = [
			'E_ERROR' => E_ERROR,
			'E_WARNING' => E_WARNING,
			'E_PARSE' => E_PARSE,
			'E_NOTICE' => E_NOTICE,
			'E_CORE_ERROR' => E_CORE_ERROR,
			'E_CORE_WARNING' => E_CORE_WARNING,
			'E_COMPILE_ERROR' => E_COMPILE_ERROR,
			'E_COMPILE_WARNING' => E_COMPILE_WARNING,
			'E_USER_ERROR' => E_USER_ERROR,
			'E_USER_WARNING' => E_USER_WARNING,
			'E_USER_NOTICE' => E_USER_NOTICE,
			'E_STRICT' => E_STRICT,
			'E_RECOVERABLE_ERROR' => E_RECOVERABLE_ERROR,
			'E_DEPRECATED' => E_DEPRECATED,
			'E_USER_DEPRECATED' => E_USER_DEPRECATED
		];

		return $severity_map[strtoupper($severity_string)] ?? 0;
	}

	/**
	 * Convert severity constant to string
	 */
	public static function severityToString($severity): string {
		$severity_map = array_flip([
			'E_ERROR' => E_ERROR,
			'E_WARNING' => E_WARNING,
			'E_PARSE' => E_PARSE,
			'E_NOTICE' => E_NOTICE,
			'E_CORE_ERROR' => E_CORE_ERROR,
			'E_CORE_WARNING' => E_CORE_WARNING,
			'E_COMPILE_ERROR' => E_COMPILE_ERROR,
			'E_COMPILE_WARNING' => E_COMPILE_WARNING,
			'E_USER_ERROR' => E_USER_ERROR,
			'E_USER_WARNING' => E_USER_WARNING,
			'E_USER_NOTICE' => E_USER_NOTICE,
			'E_STRICT' => E_STRICT,
			'E_RECOVERABLE_ERROR' => E_RECOVERABLE_ERROR,
			'E_DEPRECATED' => E_DEPRECATED,
			'E_USER_DEPRECATED' => E_USER_DEPRECATED
		]);

		return $severity_map[$severity] ?? 'UNKNOWN';
	}
}
