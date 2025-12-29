<?php

namespace DagLabLog\Logging;

use DagLabLog\ErrorSeverityManager;

class ErrorHandler
{
	private Logger $logger;

	private bool $active = false;

	/**
	 * Prevent infinite loop if logging itself causes an error.
	 *
	 * @var bool
	 */
	private static bool $isLogging = false;

	/**
	 * Store the previously set error handler for chaining.
	 *
	 * @var null|callable|callable-string
	 */
	private $previousErrorHandler = null;

	/**
	 * Store the previously set exception handler for chaining.
	 *
	 * @var null|callable|callable-string
	 */
	private $previousExceptionHandler = null;

	public function __construct(Logger $logger) {
		$this->logger = $logger;
		$this->activate();
	}

	/**
	 * Constructor
	 */
	public static function bootstrap(): void {
		new static(new Logger());
	}

	/**
	 * Activate the error handler.
	 */
	public function activate(): void {
		if ($this->active) {
			return; // Already active
		}

		// Store previous handlers before replacing them
		$this->previousErrorHandler = set_error_handler([$this, 'handleError']);
		$this->previousExceptionHandler = set_exception_handler([$this, 'handleException']);

		// Register our shutdown function for fatal errors
		register_shutdown_function([$this, 'handleFatalError']);

		$this->active = true;
	}

	/**
	 * Deactivate and restore previous handlers.
	 */
	public function deactivate(): void {
		if (!$this->active) {
			return; // Not active
		}

		// Restore previous handlers
		if ($this->previousErrorHandler !== null) {
			set_error_handler($this->previousErrorHandler);
		} else {
			restore_error_handler();
		}

		if ($this->previousExceptionHandler !== null) {
			set_exception_handler($this->previousExceptionHandler);
		} else {
			restore_exception_handler();
		}

		$this->active = false;
	}

	/**
	 * Handle regular PHP errors
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @param array $context
	 *
	 * @return false|mixed
	 */
	public function handleError(int $severity, string $message, string $file, int $line, array $context = []): mixed {
		// Log the error with our custom handler
		$this->logError($severity, $message, $file, $line, null, 'ERROR');

		// Chain to the previous error handler if it exists
		if ($this->previousErrorHandler && is_callable($this->previousErrorHandler)) {
			try {
				return call_user_func($this->previousErrorHandler, $severity, $message, $file, $line, $context);
			} catch (\Exception $e) {
				// If the previous handler throws an exception, log it and continue
				$this->logError(E_ERROR, 'Previous error handler threw exception: ' . $e->getMessage(), __FILE__, __LINE__, null, 'HANDLER_ERROR');
			}
		}

		// Return false to continue with PHP's internal error handler
		// Return true to stop further error handling
		return false;
	}

	/**
	 * Handle uncaught exceptions
	 *
	 * @param \Exception $exception
	 */
	public function handleException($exception): void {
		// Log the exception with our custom handler.
		$this->logError(
			E_ERROR,
			'Uncaught Exception: ' . $exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			$exception->getTraceAsString(),
			'EXCEPTION'
		);

		// Chain to the previous exception handler if it exists.
		if ($this->previousExceptionHandler && is_callable($this->previousExceptionHandler)) {
			try {
				call_user_func($this->previousExceptionHandler, $exception);
			} catch (\Exception $e) {
				// If the previous handler throws an exception, log it.
				$this->logError(E_ERROR, 'Previous exception handler threw exception: ' . $e->getMessage(), __FILE__, __LINE__, null, 'HANDLER_ERROR');
			}
		}
	}

	/**
	 * Handle fatal errors via a shutdown function.
	 */
	public function handleFatalError(): void {
		$error = error_get_last();

		if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_USER_ERROR])) {
			$this->logError(
				$error['type'],
				$error['message'],
				$error['file'],
				$error['line'],
				null,
				'FATAL'
			);
		}
	}

	/**
	 * Log error with custom formatting.
	 */
	private function logError(int $severity, string $message, string $file, int $line, $trace = null, $type = 'ERROR'): void {
		// Prevent infinite loop if logging itself causes an error
		if (self::$isLogging) {
			error_log('DagLab Log: Prevented infinite loop in error handler');
			return;
		}

		self::$isLogging = true;

		try {
			// Prevent noise from logs on favicon and 404 pages.
			if (
				$this->isFaviconRequest() ||
				$this->isFaviconRelatedError($message, $file) ||
				is_404()
			) {
				return;
			}

			// Create log entry
			$logEntry = sprintf(
				"%s in %s on line %d",
				$message,
				$file,
				$line
			);

			// Add stack trace if available
			if ($trace) {
				$logEntry .= "\nStack trace:\n" . $trace;
			}

			// Add request info if available
			$requestUri = $this->logger->getServerVar('REQUEST_URI');
			if (!empty($requestUri)) {
				$requestMethod = $this->logger->getServerVar('REQUEST_METHOD', 'UNKNOWN');
				$logEntry .= "\nRequest: " . $requestMethod . ' ' . $requestUri;
			}

			$this->logger->writeLog(
				'php',
				ErrorSeverityManager::getLogLevel($severity),
				$logEntry,
				$severity
			);
		} finally {
			self::$isLogging = false;
		}
	}

	/**
	 * Determine if the request is for a favicon.
	 *
	 * @return bool
	 */
	private function isFaviconRequest(): bool {
		$uri = $this->logger->getServerVar('REQUEST_URI');
		if (empty($uri)) {
			return false;
		}

		$uri = strtolower($uri);

		$favicon_indicators = [
			'favicon.ico',
			'favicon.png',
			'favicon.gif',
			'apple-touch-icon',
			'android-chrome',
			'mstile-',
			'browserconfig.xml',
			'site.webmanifest'
		];

		foreach ($favicon_indicators as $indicator) {
			if ( str_contains( $uri, $indicator ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if the message is related to favicons.
	 *
	 * @param string $message
	 * @param string $file
	 *
	 * @return bool
	 */
	private function isFaviconRelatedError(string $message, string $file): bool {
		// Common favicon-related error patterns.
		$favicon_error_patterns = [
			'favicon',
			'apple-touch-icon',
			'android-chrome',
			'No such file or directory.*favicon',
			'failed to open stream.*favicon'
		];

		$search_text = strtolower($message . ' ' . ($file ?? ''));

		foreach ($favicon_error_patterns as $pattern) {
			if (preg_match('/' . $pattern . '/i', $search_text)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cleanup on destruction.
	 */
	public function __destruct() {
		if ($this->active) {
			$this->deactivate();
		}
	}

}
