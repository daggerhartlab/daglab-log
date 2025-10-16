<?php

namespace DagLabLog\Admin;

use DagLabLog\Cron\EmailDigest;
use DagLabLog\ErrorSeverityManager;
use DagLabLog\Settings;

class SettingsPage {

	const PAGE_SLUG = 'daglab_log_settings';
	private string $optionGroup = Settings::OPTION_NAME . '_group';
	private string $optionName = Settings::OPTION_NAME;

	public static function bootstrap(): void {
		$page = new static();
		add_action('admin_menu', [ $page, 'hookAdminMenu' ] );
		add_action('admin_init', [ $page, 'hookAdminInit' ] );
		add_action('admin_enqueue_scripts', [ $page, 'hookAdminEnqueueScripts' ] );
	}

	/**
	 * Add our settings page to the admin menu.
	 */
	public function hookAdminMenu(): void {
		add_options_page(
			__('DagLab Log Settings', 'daglab-log'),
			__('DagLab Log', 'daglab-log'),
			'manage_options',
			static::PAGE_SLUG,
			[ $this, 'showPage' ]
		);
	}

	/**
	 * Initialize settings.
	 */
	public function hookAdminInit(): void {
		register_setting(
			$this->optionGroup,
			$this->optionName,
			[ $this, 'settingsValidate' ]
		);

		add_settings_section(
			'daglab_log_main_section',
			__('Logger Settings', 'daglab-log'),
			[ $this, 'sectionCallback' ],
			static::PAGE_SLUG
		);

		add_settings_field(
			 'min_log_level',
			__('Log Level', 'daglab-log'),
			[ $this, 'fieldLogLevel' ],
			static::PAGE_SLUG,
			'daglab_log_main_section'
		);

		add_settings_field(
			'max_entries',
			__('Maximum Log Entries', 'daglab-log'),
			[ $this, 'fieldMaxEntries' ],
			static::PAGE_SLUG,
			'daglab_log_main_section'
		);

		add_settings_field(
			'digest_enabled',
			__('Digest Enabled', 'daglab-log'),
			[ $this, 'fieldDigestEnabled' ],
			static::PAGE_SLUG,
			'daglab_log_main_section'
		);

		add_settings_field(
			'digest_min_log_level',
			__('Digest Log Level', 'daglab-log'),
			[ $this, 'fieldDigestLogLevel' ],
			static::PAGE_SLUG,
			'daglab_log_main_section'
		);

		add_settings_field(
			'digest_frequency',
			__('Digest Frequency', 'daglab-log'),
			[ $this, 'fieldDigestFrequency' ],
			static::PAGE_SLUG,
			'daglab_log_main_section'
		);

		add_settings_field(
			'digest_email',
			__('Digest Email', 'daglab-log'),
			[ $this, 'fieldDigestEmail' ],
			static::PAGE_SLUG,
			'daglab_log_main_section'
		);
	}

	/**
	 * Enqueue scripts and styles for this page only.
	 */
	public function hookAdminEnqueueScripts($hook): void {
		if ($hook !== 'settings_page_' . static::PAGE_SLUG) {
			return;
		}

		wp_enqueue_style( 'daglab_log_admin', plugin_dir_url( DAGLAB_LOG_PLUGIN_FILE ) . 'css/daglab-log-admin.css', [], '1.0' );
	}

	/**
	 * Settings section callback
	 */
	public function sectionCallback(): void {
		echo '<p>' . __('Configure the settings for error logging below:', 'daglab-log') . '</p>';
	}

	/**
	 * Log level select box.
	 */
	public function fieldLogLevel(): void {
		$value = Settings::getMinLogLevel();
		ob_start();
		?>
		<select id="min_log_level" name="<?= $this->optionName ?>[min_log_level]">
			<?php foreach (ErrorSeverityManager::getLogLevelOptions() as $key => $label) : ?>
				<option value="<?= esc_attr($key) ?>" <?= selected($value, $key, false) ?>><?= esc_html($label) ?></option>
			<?php endforeach; ?>
		</select>

		<p class="description"><?= __('Select the minimum level of errors to log.', 'daglab-log') ?></p>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Max entries number field.
	 */
	public function fieldMaxEntries(): void {
		$value = Settings::getMaxEntries();
		ob_start();
		?>
		<input type="number" id="max_entries" name="<?= $this->optionName ?>[max_entries]" value="<?= esc_attr($value) ?>" min="1000" max="100000" step="1000" />
		<p class="description"><?= __('Maximum number of log entries to keep in the database (1,000-100,000).', 'daglab-log') ?></p>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Digest enabled checkbox.
	 */
	public function fieldDigestEnabled(): void {
		$value = Settings::getDigestEnabled();
		ob_start();
		?>
		<input type="hidden" name="<?= $this->optionName ?>[digest_enabled]" value="0">
		<input id="digest_enabled" type="checkbox" name="<?= $this->optionName ?>[digest_enabled]" value="1" <?php checked(1, $value) ?> />
		<?= __('Enable email digest of error logs using cron.', 'daglab-log') ?>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Digest log level select box.
	 */
	public function fieldDigestLogLevel(): void {
		$value = Settings::getDigestMinLogLevel();
		ob_start();
		?>
		<select id="digest_min_log_level" name="<?= $this->optionName ?>[digest_min_log_level]">
			<?php foreach (ErrorSeverityManager::getLogLevelOptions() as $key => $label) : ?>
				<option value="<?= esc_attr($key) ?>" <?= selected($value, $key, false) ?>><?= esc_html($label) ?></option>
			<?php endforeach; ?>
		</select>

		<p class="description"><?= __('Select the minimum level of errors to summarize in the email digest.', 'daglab-log') ?></p>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Digest frequency select box.
	 */
	public function fieldDigestFrequency(): void {
		$value = Settings::getDigestFrequency();
		$schedules = wp_get_schedules();
		ob_start();
		?>
		<select id="digest_frequency" name="<?= $this->optionName ?>[digest_frequency]">
			<?php foreach ($schedules as $frequency => $details) : ?>
				<option value="<?= esc_attr($frequency) ?>" <?php selected($value, $frequency) ?>><?= esc_html($details['display']) ?></option>
			<?php endforeach; ?>
		</select>

		<p class="description"><?= __('Select the frequency of the log digest emails.', 'daglab-log') ?></p>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Digest email addresses.
	 */
	public function fieldDigestEmail(): void {
		$value = Settings::getDigestEmail();
		ob_start();
		?>
		<input id="digest_email" type="text" size="42" name="<?= $this->optionName ?>[digest_email]" value="<?= esc_attr($value) ?>" />
		<p class="description"><?= __('Provide the email addresses that should receive the logs digest email. Separate multiple addresses with commas.', 'daglab-log') ?></p>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Validate and sanitize settings
	 */
	public function settingsValidate($input): array {
		$validated = Settings::all();

		// Log level.
		if (ErrorSeverityManager::isValidLogLevel($input['min_log_level'] ?? $validated['min_log_level'])) {
			$validated['min_log_level'] = sanitize_text_field($input['min_log_level']);
		}
		else {
			add_settings_error(
				$this->optionName,
				'invalid_log_level',
				__('Invalid log level selected. Using default (Error).', 'daglab-log')
			);
		}

		// Max entries.
		$max_entries = intval($input['max_entries'] ?? $validated['max_entries']);
		if ($max_entries >= 1000 && $max_entries <= 100000) {
			$validated['max_entries'] = $max_entries;
		}
		else {
			add_settings_error(
				$this->optionName,
				'invalid_max_entries',
				__('Maximum entries must be between 100 and 100,000. Using default (1,000).', 'daglab-log')
			);
		}

		// Digest enabled.
		$validated['digest_enabled'] = (bool) $input['digest_enabled'];

		// Digest log level.
		if (ErrorSeverityManager::isValidLogLevel($input['digest_min_log_level'] ?? $validated['digest_min_log_level'])) {
			$validated['digest_min_log_level'] = sanitize_text_field($input['digest_min_log_level']);
		}
		else {
			add_settings_error(
				$this->optionName,
				'invalid_digest_log_level',
				__('Invalid digest log level selected. Using default (Error).', 'daglab-log')
			);
		}

		// Digest frequency.
		$schedules = wp_get_schedules();
		$digest_frequency_changed = false;
		if (isset($schedules[$input['digest_frequency']])) {
			$digest_frequency_changed = $validated['digest_frequency'] !== $input['digest_frequency'];
			$validated['digest_frequency'] = $input['digest_frequency'];
		}
		else {
			add_settings_error(
				$this->optionName,
				'invalid_digest_frequency',
				__('Invalid digest frequency. Using default (Once Daily).', 'daglab-log')
			);
		}

		// Digest email.
		if (!empty($input['digest_email']) && str_contains($input['digest_email'], '@')) {
			$validated['digest_email'] = sanitize_email($input['digest_email']);
		}
		// If the digest is enabled but we don't have a valid email, error.
		else if ($input['digest_enabled'] && empty($input['digest_email'])) {
			add_settings_error(
				$this->optionName,
				'invalid_digest_email',
				__('Invalid digest email address.', 'daglab-log')
			);
		}

		// Handle cron job changes.
		$email_digest = new EmailDigest();
		if (!$validated['digest_enabled']) {
			// Unschedule the cron job when setting is disabled.
			$email_digest->unscheduleJob();
		}

		// Re-schedule the cron job if the frequency changed.
		if ($digest_frequency_changed) {
			$email_digest->unscheduleJob();
			// Pass new setting values in to let the scheduleJob() logic
			// determine if the job should be scheduled.
			$email_digest->scheduleJob($validated['digest_enabled'], $validated['digest_frequency']);
		}

		return $validated;
	}

	/**
	 * Settings page HTML
	 */
	public function showPage(): void {
		if (!current_user_can('manage_options')) {
			return;
		}

		settings_errors($this->optionName);

		?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields($this->optionGroup);

				do_settings_sections(static::PAGE_SLUG);

				submit_button(__('Save Settings', 'daglab-log'));
				?>
			</form>

			<div class="postbox" style="padding: 10px 20px;">
				<h3><?= __('Log Levels', 'daglab-log') ?></h3>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<td><?= __('Name', 'daglab-log') ?></td>
						<td><?= __('Priority', 'daglab-log') ?></td>
						<td><?= __('Description', 'daglab-log') ?></td>
					</thead>
					<tbody>
					<?php foreach (ErrorSeverityManager::getAllLogLevels() as $level) : ?>
						<tr class="log-level-<?= $level ?>">
							<td class="log-level"><?= ErrorSeverityManager::getLogLevelName( $level ) ?></td>
							<td><?= ErrorSeverityManager::getLogLevelPriority( $level ) ?></td>
							<td><?= ErrorSeverityManager::getLogLevelDescription( $level ) ?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>

				<?php
				$email_jobs = array_filter(_get_cron_array(), function($value) {
					return array_key_exists(EmailDigest::CRON_HOOK, $value);
				});
				?>
				<?php if (!empty($email_jobs)) : ?>
					<?php
					$schedules = wp_get_schedules();
					$next_time = array_key_first($email_jobs);
					$details = array_shift($email_jobs);
					$details = array_shift($details);
					$details = array_shift($details);
					?>
					<h3><?= __('Digest Cron Job') ?></h3>
					<p><strong><?= __('Scheduled', 'daglab-log') ?>:</strong> <?= $schedules[$details['schedule']]['display'] ?></p>
					<p><strong><?= __('Next scheduled for', 'daglab-log') ?>:</strong> <?= wp_date('Y-m-d g:ia', $next_time) ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

}
