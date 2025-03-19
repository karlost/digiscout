# DigiScout - Website Monitoring Application

DigiScout is a comprehensive website monitoring application built with Laravel and Backpack Admin. It allows you to monitor multiple websites using various tools to ensure they are running optimally.

## Features

- **Dashboard**: Get a quick overview of all your monitored websites
- **Multiple Monitoring Tools**:
  - Ping: Check server availability and response time
  - HTTP Status: Verify HTTP status codes and detect redirects
  - DNS Check: Validate DNS records and configurations
  - Load Time: Measure website loading performance
  - SSL Certificate: Monitor SSL certificate validity and expiration
- **Notifications**: Receive alerts when monitoring checks fail via email and Discord
- **Scheduling**: Automatically run checks at configurable intervals
- **Admin Interface**: Easy-to-use Backpack admin panel with Pro features
- **Tool Configuration**: Configure which monitoring tools are used for each website
- **Notification Management**: View and manage notifications with bulk actions
- **Flexible Monitoring Settings**: Each website can have multiple monitoring tools with customizable intervals and thresholds

## Development Progress

- **Completed**: Basic website management with CRUD operations
- **Completed**: Monitoring tools system with configurable default settings
- **Completed**: Many-to-many relationship between websites and monitoring tools
- **Completed**: Custom pivot model (WebsiteMonitoringSetting) with additional fields
- **Completed**: WebsiteObserver to manage relationship between websites and monitoring tools
- **Completed**: Fixed SQL error with 'interval' field by implementing custom sync method
- **Completed**: PHPUnit tests for website monitoring functionality
- **Completed**: Notification management with bulk actions

## Upcoming Features

- **Implemented**: Notification counter in the admin navbar
- **Implemented**: Enhanced bulk actions for notification management (multiple select, filter, etc.)
- **Need to implement**: REST API for integration with external systems
- **Need to implement**: User roles and permissions system
- **Need to implement**: More detailed analytics tools
- **Need to implement**: WebSocket integration for real-time notifications
- **Need to implement**: Custom notification rules with advanced filtering
- **Need to implement**: Mobile application for on-the-go monitoring 
- **Need to implement**: Enhanced reporting with exportable PDF reports

## Technical Solutions

### Monitoring Tool Relationship Fix

The application previously encountered an SQL error: "SQLSTATE[HY000]: General error: 1364 Field 'interval' doesn't have a default value". 
This occurred because the website_monitoring_settings pivot table requires an 'interval' field with no default value.

The problem was resolved through:

1. Creating a custom syncMonitoringTools method in the Website model that handles proper interval assignment
2. Fetching default_interval values from each MonitoringTool when creating new settings
3. Preserving existing interval values during updates
4. Implementing a fallback interval of 5 minutes when no value is provided

A temporary fix was also applied by adding a default value of 10 to the interval column in the database.

## Development Stats

This project was developed 100% using Claude Code, an AI pair programming assistant.

```
Total cost:            $29.39
Total duration (API):  1h 35m 10.1s
Total duration (wall): 4h 25m 51.0s
Total code changes:    9198 lines added, 1526 lines removed
```

## Installation

1. Clone the repository
2. Create an auth.json file with Backpack Pro credentials:
   ```json
   {
       "http-basic": {
           "repo.backpackforlaravel.com": {
               "username": "your-backpack-username",
               "password": "your-backpack-password"
           }
       }
   }
   ```
3. Run `composer install`
4. Copy `.env.example` to `.env` and configure:
   - Database settings
   - Email settings for notifications
   - Discord webhook URL for Discord notifications
5. Run database migrations: `php artisan migrate`
6. Seed the database: `php artisan db:seed`
7. Generate application key: `php artisan key:generate`
8. Link storage: `php artisan storage:link`

## Production Setup

To set up the application for production use:

1. Configure a cron job to run the Laravel scheduler:
   ```
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

2. Set up a queue worker (preferably with Supervisor):
   ```
   [program:digiscout-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/path-to-your-project/storage/logs/worker.log
   stopwaitsecs=3600
   ```

3. Set up HTTPS with a valid SSL certificate

## Discord Notifications

To enable Discord notifications:

1. Create a webhook in your Discord server (Server Settings → Integrations → Webhooks)
2. Copy the webhook URL and add it to your `.env` file as `DISCORD_WEBHOOK_URL`
3. For each website monitoring setting, enable the "Send Discord Notifications" option

## Usage

1. Log in to the admin panel at `/admin` with email `admin@example.com` and password `password123`
2. Add websites you want to monitor
3. Configure monitoring tools for each website with appropriate intervals and notification settings
4. View results on the dashboard
5. Check the monitoring logs in `storage/logs/monitoring.log`

## System Requirements

- PHP 8.2+
- MySQL database
- Composer
- Laravel 11
- Backpack for Laravel 6+ with Pro

## License

The application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
