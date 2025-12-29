<?php

namespace DagLabLog\Admin;

use DagLabLog\ErrorSeverityManager;
use DagLabLog\LogTableManager;

class LogsPage {

	const PAGE_SLUG = 'daglab-log-viewer';
	private string $capability = 'manage_options';

	public static function bootstrap(): void {
		$page = new static();
		add_action('admin_menu', [ $page, 'hookAdminMenu' ] );
		add_action('admin_enqueue_scripts', [ $page, 'hookAdminEnqueueScripts' ] );
	}

	/**
	 * Add the admin menu page.
	 */
	public function hookAdminMenu(): void {
		add_management_page(
			__('DagLab Log Viewer', 'daglab-log'),
			__('DagLab Logs', 'daglab-log'),
			$this->capability,
			static::PAGE_SLUG,
			array($this, 'showPage')
		);
	}

	/**
	 * Enqueue scripts and styles for this page only.
	 */
	public function hookAdminEnqueueScripts($hook): void {
		if ($hook !== 'tools_page_' . static::PAGE_SLUG) {
			return;
		}

		wp_enqueue_style( 'daglab_log_admin', plugin_dir_url( DAGLAB_LOG_PLUGIN_FILE ) . 'css/daglab-log-admin.css', [], '1.0' );
	}

	/**
	 * Display the logs page.
	 */
	public function showPage(): void {
		if (!current_user_can($this->capability)) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'daglab-log'));
		}

		$this->handleActions();

		$channel_filter = sanitize_text_field($_GET['channel'] ?? '');
		$level_filter = sanitize_text_field($_GET['log_level'] ?? '');
		$per_page = absint($_GET['per_page'] ?? 50);
		$page_num = absint($_GET['paged'] ?? 1);

		$logs = $this->getLogs($channel_filter, $level_filter, $per_page, $page_num);
		$total_logs = $this->getTotalLogCount($level_filter);
		?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>
			<?php $this->showFilters($channel_filter, $level_filter, $per_page); ?>
			<?php $this->showStats(); ?>
			<?php if (!empty($logs)): ?>
				<?php $this->showLogsTable($logs); ?>
				<?php $this->showPagination($total_logs, $per_page, $page_num); ?>
			<?php else: ?>
				<div class="notice notice-info">
					<p><?= __('No error logs found.', 'daglab-log'); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle page actions.
	 */
	private function handleActions(): void {
		if (!isset($_GET['action']) || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'error_log_action')) {
			return;
		}

		$action = sanitize_text_field($_GET['action']);

		if ($action === 'clear_logs') {
			$this->clearAllLogs();
			add_action('admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>Error logs cleared successfully.</p></div>';
			});
		}
	}

	/**
	 * Display filter controls.
	 *
	 * @param string $current_level
	 * @param int $current_per_page
	 */
	private function showFilters(string $current_channel, string $current_level, int $current_per_page): void {
		$levels = $this->getAvailableLevels();
		$channels = $this->getAvailableChannels();
		$clear_url = wp_nonce_url(
			admin_url('tools.php?page=' . static::PAGE_SLUG . '&action=clear_logs'),
			'error_log_action'
		);
		?>
		<div class="error-log-filters">
			<form method="get" class="filter-form">
				<input type="hidden" name="page" value="<?= esc_attr(static::PAGE_SLUG); ?>">

				<select name="channel">
					<option value=""><?= __('All Channels', 'daglab-log') ?></option>
					<?php foreach ($channels as $channel): ?>
						<option value="<?= esc_attr($channel); ?>" <?php selected($current_channel, $channel); ?>>
							<?= esc_html($channel); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<select name="log_level">
					<option value=""><?= __('All Levels', 'daglab-log') ?></option>
					<?php foreach ($levels as $level): ?>
						<option value="<?= esc_attr($level); ?>" <?php selected($current_level, $level); ?>>
							<?= esc_html(ucfirst($level)); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<select name="per_page">
					<?php foreach ([25, 50, 100, 200] as $num): ?>
						<option value="<?= $num; ?>" <?php selected($current_per_page, $num); ?>>
							<?= $num; ?> <?= __('per page', 'daglab-log') ?>
						</option>
					<?php endforeach; ?>
				</select>

				<input type="submit" class="button" value="Filter">
				<a href="<?= esc_url($clear_url); ?>" class="button button-secondary"
				   onclick="return confirm('Are you sure you want to clear all error logs?');">
					<?= __('Clear All Logs', 'daglab-log') ?>
				</a>
			</form>
		</div>
		<?php
	}

	/**
	 * Display error statistics.
	 */
	private function showStats(): void {
		$stats = $this->getErrorStats();
		?>
		<div class="error-log-stats">
			<h3><?= __('Recent Activity (Last 24 Hours)', 'daglab-log') ?></h3>
			<div class="stats-grid">
				<?php foreach ($stats as $level => $count): ?>
					<div class="stat-item stat-<?= esc_attr(strtolower($level)); ?>">
						<div class="stat-number"><?= number_format($count); ?></div>
						<div class="stat-label"><?= esc_html(ucfirst($level)); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the logs table.
	 */
	private function showLogsTable($logs): void {
		?>
		<table class="wp-list-table widefat fixed striped error-logs-table">
			<thead>
			<tr>
				<th scope="col" style="width: 120px;"><?= __('Timestamp', 'daglab-log') ?></th>
				<th scope="col" style="width: 100px;"><?= __('Channel', 'daglab-log') ?></th>
				<th scope="col" style="width: 100px;"><?= __('Level', 'daglab-log') ?></th>
				<th scope="col"><?= __('Message', 'daglab-log') ?></th>
				<th scope="col" style="width: 200px;"><?= __('Location', 'daglab-log') ?></th>
				<th scope="col" style="width: 80px;"><?= __('User', 'daglab-log') ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($logs as $log): ?>
				<tr class="log-row log-level-<?= esc_attr($log->level ?? 'unknown'); ?>">
					<td>
						<?= esc_html(mysql2date('M j, Y H:i', $log->timestamp)); ?>
						<?php if ($log->saved) : ?>
							<div>💾 <?= __('Saved', 'daglab-log') ?></div>
						<?php endif ?>
						<div><a href="<?= LogMessagePage::getLogUrl($log->id) ?>">view</a></div>
					</td>
					<td class="log-channel">
						<?= esc_html($log->channel) ?>
					</td>
					<td class="log-level">
						<?php $level_name = ErrorSeverityManager::getLogLevelName($log->level); ?>
						<?php $severity_name = ErrorSeverityManager::getSeverityName($log->severity); ?>
						<span title="<?= $severity_name ?>"><?= esc_html($level_name) ?></span>
					</td>
					<td class="log-message">
						<div class="message-preview">
							<?= esc_html(wp_trim_words($log->message ?? '', 15)); ?>
						</div>
						<?php if (strlen($log->message ?? '') > 100): ?>
							<details class="show-full-message">
								<summary><?= __('Show Full Message', 'daglab-log') ?></summary>
								<div class="fill-message"><pre><?= esc_html($log->message); ?></pre></div>
							</details>
						<?php endif; ?>
					</td>
					<td class="log-location">
						<?php if (!empty($log->location)): ?>
							<div><?= esc_html(wp_trim_words($log->location, 3, '...')); ?></div>
						<?php endif; ?>
						<?php if (!empty($log->hostname)): ?>
							<small class="hostname"><?= esc_html($log->hostname); ?></small>
						<?php endif; ?>
					</td>
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
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Display pagination.
	 */
	private function showPagination($total_items, $per_page, $current_page): void {
		$total_pages = ceil($total_items / $per_page);

		if ($total_pages <= 1) {
			return;
		}

		$base_url = admin_url('tools.php?page=' . static::PAGE_SLUG);
		$current_params = $_GET;
		unset($current_params['paged']);

		?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<?php
				echo paginate_links([
					'base' => add_query_arg('paged', '%#%', $base_url . '&' . http_build_query($current_params)),
					'format' => '',
					'prev_text' => '&laquo; Previous',
					'next_text' => 'Next &raquo;',
					'total' => $total_pages,
					'current' => $current_page
				]);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get error logs from database.
	 */
	private function getLogs(string $channel_filter = '', string $level_filter = '', int $per_page = 50, int $page = 1): array {
		global $wpdb;

		$table_name = LogTableManager::getTableName();
		$offset = ($page - 1) * $per_page;

		$where_clauses = [];
		$where_values = [];

		if ($channel_filter) {
			$where_clauses[] = 'channel = %s';
			$where_values[] = $channel_filter;
		}

		if ($level_filter) {
			$where_clauses[] = 'level = %s';
			$where_values[] = $level_filter;
		}

		$where = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

		$sql = "SELECT * FROM $table_name $where ORDER BY timestamp DESC LIMIT %d OFFSET %d";
		$where_values[] = $per_page;
		$where_values[] = $offset;

		return $wpdb->get_results($wpdb->prepare($sql, $where_values));
	}

	/**
	 * Get total log count for pagination.
	 */
	private function getTotalLogCount(string $level_filter = ''): int {
		global $wpdb;

		$table_name = LogTableManager::getTableName();
		if (!empty($level_filter)) {
			return $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE level = %s",
				$level_filter
			));
		}

		return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
	}

	/**
	 * Get available log levels for filter dropdown.
	 */
	private function getAvailableLevels(): array {
		global $wpdb;

		$table_name = LogTableManager::getTableName();
		$levels = $wpdb->get_col("SELECT DISTINCT level FROM $table_name ORDER BY severity");

		return $levels ?: [];
	}

	/**
	 * Get available log channels for filter dropdown.
	 */
	private function getAvailableChannels(): array {
		global $wpdb;

		$table_name = LogTableManager::getTableName();
		$channels = $wpdb->get_col("SELECT DISTINCT channel FROM $table_name ORDER BY channel");

		return $channels ?: [];
	}

	/**
	 * Get error statistics for the last 24 hours.
	 */
	private function getErrorStats(): array {
		global $wpdb;

		$table_name = LogTableManager::getTableName();
		$since = date('Y-m-d H:i:s', strtotime('-24 hours'));

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

	/**
	 * Clear all error logs.
	 */
	private function clearAllLogs(): void {
		global $wpdb;

		$table_name = LogTableManager::getTableName();
		$wpdb->query("TRUNCATE TABLE $table_name");
	}

}
