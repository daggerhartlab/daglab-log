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

}
