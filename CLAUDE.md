# DigiScout Development Guide

## Build/Test/Run Commands
- Run server: `php artisan serve`
- Queue worker: `php artisan queue:work` 
- Monitor websites: `php artisan monitoring:run`
- All tests: `php artisan test`
- Unit tests only: `php artisan test --testsuite=Unit`
- Feature tests only: `php artisan test --testsuite=Feature`
- Single test: `php artisan test tests/Unit/Services/PingServiceTest.php`
- Specific test method: `php artisan test --filter=test_ping_service_returns_success_result`
- Clear views: `php artisan view:clear`

## Code Style Guidelines
- **Architecture**: Template method pattern for monitoring services via AbstractMonitoringService
- **Naming**: PascalCase for classes, camelCase for methods/properties, snake_case for DB columns
- **PHP Types**: Always use type hints in methods (string, int, array, ?float for nullables)
- **Error Handling**: Use try/catch in services, return structured results with status & messages
- **Testing**: Use Mockery for mocks (`shouldAllowMockingProtectedMethods` for testing protected methods)
- **Interfaces**: Each service should implement MonitoringServiceInterface
- **Imports**: Group by PSR-4 namespace, Laravel before app classes
- **Documentation**: PHPDoc for all methods, especially those with complex parameters/returns
- **Dependency Injection**: Use constructor injection for services
- **Response Format**: Services return ['status', 'value', 'additional_data'] arrays