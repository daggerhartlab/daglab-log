# Daggerhart Lab - Error Logging

A comprehensive logging plugin for WordPress that captures and manages PHP errors, warnings, notices, and custom log messages.

## Description

This plugin provides error logging capabilities for WordPress, allowing developers to track, monitor, and manage application errors through a custom database table and admin interface.

## Features

- **Error Handling**: Automatic capture of PHP errors, warnings, notices, and exceptions
- **Custom Channel Logging**: Create custom log channels for different parts of your application
- **Admin Interface**: View and manage logs through the WordPress admin dashboard
- **Configurable Log Levels**: Set minimum severity levels for logging (error, warning, notice, etc.)
- **Email Digests**: Optional email notifications with configurable frequency (daily, weekly, etc.)
- **Automatic Cleanup**: Cron-based log cleanup to maintain database performance
- **Entry Limits**: Configurable maximum number of log entries to retain

## Installation

1. Upload the plugin files to `/wp-content/plugins/daglab-log/` directory
2. Run `composer install` in the plugin directory to install dependencies
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure settings under the admin menu

## Usage

### Automatic Error Logging

Once activated, the plugin automatically captures PHP errors, warnings, and exceptions.

### Custom Channel Logging

```php
$logger = new \DagLabLog\Logging\ChannelLogger('my-channel');
$logger->error('An error occurred');
$logger->warning('A warning message');
$logger->notice('An informational notice');
```

### Settings

Configure the plugin through the WordPress admin:

- **Minimum Log Level**: Set the minimum severity to log
- **Max Entries**: Maximum number of log entries to retain
- **Email Digest**: Enable/disable email notifications
- **Digest Frequency**: How often to send digest emails
- **Digest Email**: Email address for notifications

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Composer for dependency management

## License

GPL-3.0+

## Author

Daggerhart Lab
[https://daggerhartlab.com](https://daggerhartlab.com)

## Repository

[https://github.com/daggerhartlab/daglab-log](https://github.com/daggerhartlab/daglab-log)
