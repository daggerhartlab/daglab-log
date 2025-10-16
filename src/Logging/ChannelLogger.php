<?php

namespace DagLabLog\Logging;

class ChannelLogger extends Logger {

	public function __construct(
		private string $channel,
	) {}

	public function log(string $message, array $context = []): void {
		$context['channel'] = $this->channel;
		$context['log_level'] = $context['log_level'] ?? 'debug';
		parent::log($message, $context);
	}

	public function debug(string $message, array $context = []): void {
		$context['log_level'] = 'debug';
		$this->log($message, $context);
	}

	public function info(string $message, array $context = []): void {
		$context['log_level'] = 'info';
		$this->log($message, $context);
	}

	public function notice(string $message, array $context = []): void {
		$context['log_level'] = 'notice';
		$this->log($message, $context);
	}

	public function warning(string $message, array $context = []): void {
		$context['log_level'] = 'warning';
		$this->log($message, $context);
	}

	public function error(string $message, array $context = []): void {
		$context['log_level'] = 'error';
		$this->log($message, $context);
	}

	public function fatal(string $message, array $context = []): void {
		$context['log_level'] = 'fatal';
		$this->log($message, $context);
	}

}
