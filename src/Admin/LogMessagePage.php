<?php

namespace DagLabLog\Admin;

use DagLabLog\ErrorSeverityManager;
use DagLabLog\LogTableManager;

class LogMessagePage {
	const PAGE_SLUG = 'daglab-log-detail';
	private string $capability = 'manage_options';

	public static function bootstrap(): void {
		$page = new static();
		add_action('admin_menu', [ $page, 'hookAdminMenu' ] );
		add_action('admin_enqueue_scripts', [ $page, 'hookAdminEnqueueScripts' ] );
	}

	/**
	 * Register the admin page.
	 */
	public function hookAdminMenu(): void {
		add_submenu_page(
			'none', // Don't add the page to any menu.
			'Error Log Detail',
			'Error Log Detail',
			$this->capability,
			static::PAGE_SLUG,
			[ $this, 'showPage' ]
		);
	}

	/**
	 * Enqueue scripts and styles for this page only.
	 *
	 * @param string $hook
	 */
	public function hookAdminEnqueueScripts(string $hook): void {
		if ($hook !== 'admin_page_' . static::PAGE_SLUG) {
			return;
		}
		wp_enqueue_style( 'daglab_log_admin', plugin_dir_url( DAGLAB_LOG_PLUGIN_FILE ) . 'css/daglab-log-admin.css', [], '1.0' );
	}

	/**
	 * Display the log detail page.
	 */
	public function showPage(): void {
		// Check permissions
		if (!current_user_can($this->capability)) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'daglab-log'));
		}

		// Get and validate the ID parameter
		$log_id = absint($_GET['id'] ?? 0);

		if (!$log_id) {
			wp_die(__('Invalid log ID provided.', 'daglab-log'));
		}

		// Get the log entry
		$log_entry = $this->getLogEntry($log_id);

		if (!$log_entry) {
			wp_die(__('Log entry not found.', 'daglab-log'));
		}

		$this->handleActions($log_entry);
		$this->showLogDetail($log_entry);
	}

	/**
	 * Handle log message actions.
	 *
	 * @param object $log_entry
	 *
	 * @return void
	 */
	private function handleActions(object $log_entry): void {
		if (isset($_GET['action'])) {
			if ($_GET['action'] === 'save') {
				$this->saveLogEntry($log_entry);
				$log_entry->saved = 1;
				echo '<div class="notice notice-success is-dismissible"><p>Log entry saved.</p></div>';
			}
			else if ($_GET['action'] === 'unsave') {
				$this->unsaveLogEntry($log_entry);
				$log_entry->saved = 0;
				echo '<div class="notice notice-success is-dismissible"><p>Log entry no longer saved.</p></div>';
			}
		}
	}

	/**
	 * Get a single log entry by ID
	 *
	 * @param int $id
	 *
	 * @return object|null
	 */
	private function getLogEntry(int $id): ?object {
		global $wpdb;

		$table_name = LogTableManager::getTableName();

		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		));
	}

	/**
	 * Mark the log as saved.
	 *
	 * @param object $log_entry
	 *
	 * @return void
	 */
	private function saveLogEntry(object $log_entry): void {
		global $wpdb;
		$table_name = LogTableManager::getTableName();
		$wpdb->update($table_name, [ 'saved' => 1 ], [ 'id' => $log_entry->id ]);
	}

	/**
	 * Mark the log as not saved.
	 *
	 * @param object $log_entry
	 *
	 * @return void
	 */
	private function unSaveLogEntry(object $log_entry): void {
		global $wpdb;
		$table_name = LogTableManager::getTableName();
		$wpdb->update($table_name, [ 'saved' => 0 ], [ 'id' => $log_entry->id ]);
	}

	/**
	 * Render the log detail page
	 *
	 * @param object $log
	 */
	private function showLogDetail(object $log): void {
		?>
		<div class="wrap">
			<h1><?= __('Error Log Detail #', 'daglab-log') . esc_html($log->id); ?></h1>

			<div class="log-detail-container">
				<table class="form-table" role="presentation">
					<tbody>
					<tr>
						<th scope="row"><?= __('Timestamp', 'daglab-log') ?></th>
						<td><?= esc_html(mysql2date('F j, Y g:i:s A', $log->timestamp)); ?></td>
					</tr>

					<tr>
						<th scope="row"><?= __('Channel', 'daglab-log') ?></th>
						<td>
                            <code><?= esc_html($log->channel) ?></code>
						</td>
					</tr>

					<tr>
						<th scope="row"><?= __('Severity', 'daglab-log') ?></th>
						<td>
                            <span class="severity-badge severity-<?= esc_attr($log->severity); ?>">
                                <?= esc_html(ErrorSeverityManager::getSeverityName($log->severity)); ?>
                            </span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?= __('Level', 'daglab-log') ?></th>
						<td><?= esc_html(ErrorSeverityManager::getLogLevelName($log->level)); ?></td>
					</tr>

					<tr>
						<th scope="row"><?= __('Message', 'daglab-log') ?></th>
						<td>
							<div class="log-message-full">
								<pre><?= esc_html($log->message ?? ''); ?></pre>
							</div>
						</td>
					</tr>

					<?php if (!empty($log->location)): ?>
						<tr>
							<th scope="row"><?= __('Location', 'daglab-log') ?></th>
							<td>
								<a href="<?= esc_url($log->location); ?>" target="_blank" rel="noopener">
									<?= esc_html($log->location); ?>
								</a>
							</td>
						</tr>
					<?php endif; ?>

					<?php if (!empty($log->referer)): ?>
						<tr>
							<th scope="row"><?= __('Referer', 'daglab-log') ?></th>
							<td>
								<a href="<?= esc_url($log->referer); ?>" target="_blank" rel="noopener">
									<?= esc_html($log->referer); ?>
								</a>
							</td>
						</tr>
					<?php endif; ?>

					<?php if (!empty($log->hostname)): ?>
						<tr>
							<th scope="row"><?= __('Hostname', 'daglab-log') ?></th>
							<td><?= esc_html($log->hostname); ?></td>
						</tr>
					<?php endif; ?>

					<tr>
						<th scope="row"><?= __('User', 'daglab-log') ?></th>
						<td>
							<?php $user = get_user_by('id', $log->user_id); ?>
							<?php if ($user) : ?>
								<?php $edit_url = get_edit_user_link($log->user_id); ?>
								<a href="<?= esc_url($edit_url) ?>"><?= esc_html($user->display_name) ?></a>
								(<?= esc_html($user->user_login) ?>)
							<?php else: ?>
								<?= __('User #') . esc_html($log->user_id) ?>
							<?php endif	?>
						</td>
					</tr>

					<tr>
						<th scope="row"><?= __('Saved', 'daglab-log') ?></th>
						<td>
							<?php if ($log->saved) : ?>
								💾 <em><?= __('Saved', 'daglab-log') ?></em>
							<?php else : ?>
								<em>- <?= __('no', 'daglab-log') ?> -</em>
							<?php endif ?>
						</td>
					</tr>
					</tbody>
				</table>

				<div class="log-actions">
					<a href="<?= esc_url(admin_url('tools.php?page=' . LogsPage::PAGE_SLUG)); ?>" class="button">
						← <?= __('Back to Error Logs', 'daglab-log') ?>
					</a>
					<?php if ($log->saved) : ?>
						<a href="<?= esc_url(admin_url('admin.php?page=' . static::PAGE_SLUG . '&id=' . $log->id . '&action=unsave')); ?>" class="button">
							<?= __('Unsave Log Message', 'daglab-log') ?>
						</a>
					<?php else : ?>
						<a href="<?= esc_url(admin_url('admin.php?page=' . static::PAGE_SLUG . '&id=' . $log->id . '&action=save')); ?>" class="button">
							<?= __('Save Log Message', 'daglab-log') ?>
						</a>
					<?php endif ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Generate a shareable URL for a log entry.
	 *
	 * @param int $log_id
	 *
	 * @return string
	 */
	public static function getLogUrl(int $log_id): string {
		return admin_url('admin.php?page=' . static::PAGE_SLUG . '&id=' . absint($log_id));
	}

}
