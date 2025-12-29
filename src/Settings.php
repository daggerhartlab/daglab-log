<?php

namespace DagLabLog;

class Settings {

	public const OPTION_NAME = 'daglab_log_settings';
	public const DEFAULT_VALUES = [
		'min_log_level' => 'error',
		'max_entries' => 1000,
		'digest_enabled' => false,
		'digest_frequency' => 'daily',
		'digest_min_log_level' => 'error',
		'digest_email' => '',
		// Privacy settings
		'anonymize_ip' => false,           // OFF by default (preserve existing behavior)
		'strip_query_params' => false,     // OFF by default (preserve debugging context)
		'mask_sensitive_params' => true,   // ON by default (safe default - protects tokens/keys)
	];

	public static function all(): array {
		$stored = get_option( self::OPTION_NAME ) ?: [];
		return array_replace( static::DEFAULT_VALUES, $stored );
	}

	public static function getMinLogLevel(): string {
		return self::all()['min_log_level'];
	}

	public static function getMaxEntries(): int {
		return self::all()['max_entries'];
	}
	public static function getDigestEnabled(): bool {
		return self::all()['digest_enabled'];
	}

	public static function getDigestFrequency(): string {
		return self::all()['digest_frequency'];
	}

	public static function getDigestMinLogLevel(): string {
		return self::all()['digest_min_log_level'];
	}

	public static function getDigestEmail(): string {
		return self::all()['digest_email'];
	}

	public static function getAnonymizeIp(): bool {
		return self::all()['anonymize_ip'];
	}

	public static function getStripQueryParams(): bool {
		return self::all()['strip_query_params'];
	}

	public static function getMaskSensitiveParams(): bool {
		return self::all()['mask_sensitive_params'];
	}

}
