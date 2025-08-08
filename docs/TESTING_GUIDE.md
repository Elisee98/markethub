# MarketHub Testing Guide

## Overview

This document provides comprehensive testing procedures for the MarketHub Multi-Vendor E-Commerce Platform. It covers all aspects of testing from unit tests to deployment verification.

## Testing Framework

### Test Runner
The platform includes a custom test runner located at `tests/TestRunner.php` that provides:
- Automated test execution
- Test result reporting
- Test data management
- Cleanup procedures

### Running Tests

```bash
# Run all tests
php tests/TestRunner.php

# Run specific test categories
php tests/TestRunner.php --category=database
php tests/TestRunner.php --category=security
php tests/TestRunner.php --category=performance
```

## Test Categories

### 1. Unit Tests

#### Database Tests
- **Connection Testing**: Verify database connectivity
- **CRUD Operations**: Test create, read, update, delete operations
- **Transaction Handling**: Verify transaction rollback and commit
- **Data Integrity**: Check foreign key constraints and data validation

```php
// Example database test
function testUserRegistration() {
    $runner = new TestRunner();
    $user = $runner->createTestUser('customer');
    $runner->assertNotNull($user['id'], 'User ID should not be null');
    return true;
}
```

#### API Tests
- **Endpoint Availability**: Test all API endpoints respond correctly
- **Input Validation**: Verify proper input sanitization
- **Authentication**: Test login/logout functionality
- **Authorization**: Verify role-based access control

#### Business Logic Tests
- **Order Processing**: Test complete order workflow
- **Payment Processing**: Verify payment calculations and status updates
- **Inventory Management**: Test stock updates and availability checks
- **User Management**: Test registration, profile updates, role assignments

### 2. Integration Tests

#### Multi-Component Tests
- **Cart to Checkout**: Test complete shopping flow
- **Vendor Dashboard**: Test vendor analytics and management
- **Admin Panel**: Test administrative functions
- **Email Integration**: Test notification systems

#### Third-Party Integration Tests
- **Payment Gateways**: Test payment processing (sandbox mode)
- **Email Services**: Test email delivery
- **File Upload**: Test image and document uploads

### 3. Security Tests

#### Authentication & Authorization
- **Password Security**: Test password hashing and verification
- **Session Management**: Test session security and timeout
- **Role-Based Access**: Verify proper access controls
- **Account Lockout**: Test brute force protection

#### Input Validation
- **SQL Injection**: Test prepared statement protection
- **XSS Protection**: Test output escaping
- **CSRF Protection**: Test token validation
- **File Upload Security**: Test file type and size restrictions

#### Configuration Security
- **File Permissions**: Verify proper file and directory permissions
- **Configuration Exposure**: Test sensitive file protection
- **Error Handling**: Verify error messages don't expose sensitive data

### 4. Performance Tests

#### Load Testing
- **Concurrent Users**: Test system under multiple simultaneous users
- **Database Performance**: Test query execution times
- **Memory Usage**: Monitor memory consumption
- **Response Times**: Measure page load times

#### Stress Testing
- **Peak Load**: Test system at maximum expected capacity
- **Resource Limits**: Test behavior when resources are exhausted
- **Recovery**: Test system recovery after stress conditions

### 5. User Interface Tests

#### Functionality Testing
- **Form Validation**: Test all form inputs and validations
- **Navigation**: Test all links and menu items
- **Search Functionality**: Test product search and filtering
- **Responsive Design**: Test on various screen sizes

#### Usability Testing
- **User Workflows**: Test common user journeys
- **Error Messages**: Verify helpful error messages
- **Accessibility**: Test screen reader compatibility
- **Browser Compatibility**: Test on different browsers

## Test Data Management

### Test Data Creation
```php
// Create test users
$customer = $runner->createTestUser('customer');
$vendor = $runner->createTestUser('vendor');
$admin = $runner->createTestUser('admin');

// Create test products
$product_id = $runner->createTestProduct($vendor['id']);

// Create test orders
$order_id = $runner->createTestOrder($customer['id'], $product_id);
```

### Test Data Cleanup
```php
// Automatic cleanup after tests
$runner->cleanup();
```

## Performance Testing

### Performance Optimizer
Use the performance optimizer tool to analyze system performance:

```bash
php tools/performance_optimizer.php
```

This tool analyzes:
- Database query performance
- Memory usage patterns
- File system performance
- Cache effectiveness

### Performance Benchmarks
- **Page Load Time**: < 2 seconds for product pages
- **Database Queries**: < 100ms for simple queries
- **Memory Usage**: < 128MB for typical requests
- **Concurrent Users**: Support 100+ simultaneous users

## Security Testing

### Security Scanner
Run the security scanner to identify vulnerabilities:

```bash
php tools/security_scanner.php
```

The scanner checks for:
- File permission issues
- Configuration vulnerabilities
- Input validation weaknesses
- Authentication security
- Session security

### Security Checklist
- [ ] All user inputs are sanitized
- [ ] SQL injection protection is active
- [ ] XSS protection is implemented
- [ ] CSRF tokens are used on forms
- [ ] File uploads are restricted and validated
- [ ] Sensitive files are protected
- [ ] Error messages don't expose sensitive data
- [ ] Session security is properly configured
- [ ] Password policies are enforced

## Deployment Testing

### Pre-Deployment Checklist
- [ ] All tests pass
- [ ] Security scan shows no critical issues
- [ ] Performance benchmarks are met
- [ ] Database migrations are tested
- [ ] Configuration files are updated
- [ ] Backup procedures are verified

### Deployment Verification
```bash
php deploy/deploy.php staging
```

Post-deployment tests verify:
- [ ] Application is accessible
- [ ] Database connectivity works
- [ ] File permissions are correct
- [ ] Critical functionality works
- [ ] Performance is acceptable

## Continuous Integration

### Automated Testing Pipeline
1. **Code Commit**: Trigger tests on code changes
2. **Unit Tests**: Run all unit tests
3. **Integration Tests**: Run integration test suite
4. **Security Scan**: Run security vulnerability scan
5. **Performance Test**: Run performance benchmarks
6. **Deployment**: Deploy to staging if all tests pass

### Test Reporting
- Test results are saved to `test_report.json`
- Performance results are saved to `performance_report.json`
- Security results are saved to `security_report.json`
- Deployment logs are saved to `deployment.log`

## Manual Testing Procedures

### User Acceptance Testing
1. **Customer Journey**:
   - Register new account
   - Browse and search products
   - Add items to cart
   - Complete checkout process
   - Track order status

2. **Vendor Journey**:
   - Apply for vendor account
   - Set up store profile
   - Add products
   - Manage inventory
   - Process orders
   - View analytics

3. **Admin Journey**:
   - Review vendor applications
   - Manage users and products
   - Monitor platform performance
   - Generate reports

### Browser Testing
Test on the following browsers:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

### Device Testing
Test on various devices:
- Desktop computers
- Tablets (iPad, Android tablets)
- Mobile phones (iPhone, Android phones)
- Different screen resolutions

## Test Environment Setup

### Local Testing Environment
1. Install XAMPP/WAMP/MAMP
2. Import database schema
3. Configure environment variables
4. Run test suite

### Staging Environment
1. Deploy to staging server
2. Run full test suite
3. Perform manual testing
4. Verify performance benchmarks

### Production Environment
1. Deploy using deployment script
2. Run smoke tests
3. Monitor system performance
4. Verify all functionality

## Troubleshooting

### Common Test Failures
- **Database Connection**: Check database credentials and server status
- **File Permissions**: Verify upload and cache directories are writable
- **Memory Limits**: Increase PHP memory limit if needed
- **Timeout Issues**: Increase script execution time for long-running tests

### Debug Mode
Enable debug mode for detailed error information:
```php
define('DEBUG_MODE', true);
```

### Log Files
Check log files for detailed error information:
- `logs/error.log` - Application errors
- `logs/access.log` - Access logs
- `deployment.log` - Deployment logs
- `test_report.json` - Test results

## Best Practices

### Test Development
- Write tests before implementing features (TDD)
- Keep tests simple and focused
- Use descriptive test names
- Clean up test data after each test
- Mock external dependencies

### Test Maintenance
- Update tests when features change
- Remove obsolete tests
- Refactor duplicate test code
- Keep test data current
- Document test procedures

### Performance Considerations
- Run performance tests regularly
- Monitor database query performance
- Optimize slow queries
- Use caching where appropriate
- Monitor memory usage

## Conclusion

Comprehensive testing ensures the MarketHub platform is reliable, secure, and performant. Follow this guide to maintain high quality standards and deliver a robust e-commerce solution.

For questions or issues with testing procedures, consult the development team or refer to the technical documentation.
