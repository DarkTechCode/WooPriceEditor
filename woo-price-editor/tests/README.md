# Woo Price Editor Tests

This directory contains PHPUnit tests for the Woo Price Editor plugin using the WordPress test suite.

## Prerequisites

### Required Software

- **PHP**: 7.4 or higher
- **PHPUnit**: 7.0 or higher
- **MySQL/MariaDB**: For test database
- **WordPress Test Library**: Downloaded and configured
- **Composer** (optional): For dependency management

### WordPress Test Suite

The tests require the WordPress test suite to be installed. This provides the test framework and utilities needed to test WordPress plugins.

## Installation

### 1. Install WordPress Test Suite

You can install the WordPress test suite using the official installation script:

```bash
# Navigate to a temporary directory
cd /tmp

# Download the installation script
curl -O https://raw.githubusercontent.com/wp-cli/sample-plugin/master/bin/install-wp-tests.sh

# Make it executable
chmod +x install-wp-tests.sh

# Run the script
# Usage: install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
bash install-wp-tests.sh wordpress_test root '' localhost latest
```

**Parameters**:
- `wordpress_test` - Name of test database (will be created/dropped)
- `root` - MySQL username
- `''` - MySQL password (empty in this example)
- `localhost` - MySQL host
- `latest` - WordPress version to test against (or specific version like `6.4`)

**Important**: The test database will be created and destroyed during tests. Never use a production database!

### 2. Set Environment Variable

The tests need to know where the WordPress test suite is installed:

```bash
# Set WP_TESTS_DIR environment variable
export WP_TESTS_DIR=/tmp/wordpress-tests-lib

# Add to your shell profile to make it permanent
echo 'export WP_TESTS_DIR=/tmp/wordpress-tests-lib' >> ~/.bashrc
source ~/.bashrc
```

### 3. Install PHPUnit

#### Via Composer (Recommended)

```bash
# In your WordPress plugin directory
composer require --dev phpunit/phpunit:^9.0

# Run tests
vendor/bin/phpunit
```

#### Via PHAR

```bash
# Download PHPUnit
wget https://phar.phpunit.de/phpunit-9.phar
chmod +x phpunit-9.phar
mv phpunit-9.phar /usr/local/bin/phpunit

# Verify installation
phpunit --version
```

### 4. Install WooCommerce (for full functionality)

Some tests may require WooCommerce to be available:

```bash
# Navigate to WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins

# Clone or download WooCommerce
git clone https://github.com/woocommerce/woocommerce.git
```

## Running Tests

### Run All Tests

```bash
# From plugin root directory
cd /path/to/wp-content/plugins/woo-price-editor

# Run all tests
phpunit
```

### Run Specific Test File

```bash
# Run option defaults tests
phpunit tests/test-option-defaults.php

# Run AJAX permission tests
phpunit tests/test-ajax-permissions.php
```

### Run Specific Test Method

```bash
# Run a single test method
phpunit --filter test_default_options_structure tests/test-option-defaults.php
```

### With Verbose Output

```bash
# Show detailed test output
phpunit --verbose

# Show even more details
phpunit --debug
```

### With Code Coverage

```bash
# Generate code coverage report (requires Xdebug)
phpunit --coverage-html coverage/

# View report
open coverage/index.html
```

## Configuration

### phpunit.xml

The plugin includes a `phpunit.xml.dist` file with default test configuration. You can copy it to `phpunit.xml` and customize:

```bash
cp phpunit.xml.dist phpunit.xml
```

Example `phpunit.xml`:

```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    verbose="true"
>
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
            <exclude>tests/helpers</exclude>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">includes</directory>
        </whitelist>
    </filter>
</phpunit>
```

## Test Structure

### Test Files

- **`bootstrap.php`**: Test suite bootstrap file
  - Loads WordPress test environment
  - Loads plugin files
  - Sets up test database

- **`helpers/class-wpe-test-case.php`**: Base test case class
  - Extends `WP_UnitTestCase`
  - Provides helper methods for tests
  - Handles setup and teardown

- **`test-option-defaults.php`**: Tests for default options
  - Validates default option structure
  - Tests plugin activation behavior
  - Verifies option migration

- **`test-ajax-permissions.php`**: Tests for AJAX endpoint permissions
  - Tests authentication requirements
  - Tests nonce verification
  - Tests capability checks
  - Tests input validation

### Test Case Base Class

All tests extend `WPE_Test_Case`, which provides useful helper methods:

```php
// Create test user with or without capability
$user_id = $this->create_test_user($has_capability = true);

// Create test product
$product_id = $this->create_test_product([
    'post_title' => 'My Test Product',
]);

// Mock AJAX request
$this->mock_ajax_request('wpe_get_products', [
    'nonce' => $nonce,
    'page'  => 1,
]);

// Clean up AJAX request data
$this->cleanup_ajax_request();

// Get AJAX response from output buffer
$response = $this->get_ajax_response();
```

## Writing New Tests

### Example Test

```php
<?php
/**
 * Tests for my new feature
 */
class Test_My_Feature extends WPE_Test_Case {
    
    /**
     * Test something
     */
    public function test_something() {
        // Arrange
        $expected = 'expected value';
        
        // Act
        $actual = my_function();
        
        // Assert
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Test with setup
     */
    public function test_with_setup() {
        // Create test user
        $user_id = $this->create_test_user(true);
        wp_set_current_user($user_id);
        
        // Create test product
        $product_id = $this->create_test_product();
        
        // Test functionality
        $result = some_product_function($product_id);
        
        // Assert
        $this->assertTrue($result);
    }
}
```

### Naming Conventions

- Test files: `test-{feature}.php`
- Test classes: `Test_{Feature}` (extends `WPE_Test_Case`)
- Test methods: `test_{what_it_tests}`

### Assertions

Common PHPUnit assertions used in tests:

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual); // Strict comparison

// Types
$this->assertIsArray($value);
$this->assertIsString($value);
$this->assertIsBool($value);

// Arrays
$this->assertArrayHasKey('key', $array);
$this->assertContains($needle, $haystack);
$this->assertNotEmpty($array);

// Strings
$this->assertStringContainsString($needle, $haystack);

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Null
$this->assertNull($value);
$this->assertNotNull($value);
```

## Continuous Integration

### GitHub Actions Example

Create `.github/workflows/test.yml`:

```yaml
name: Run Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: mbstring, xml, mysqli
          
      - name: Install WordPress Test Suite
        run: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest
        
      - name: Run tests
        run: phpunit
```

## Troubleshooting

### "Could not find WordPress tests directory"

**Problem**: `WP_TESTS_DIR` environment variable not set or incorrect.

**Solution**:
```bash
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
```

### "Database connection failed"

**Problem**: Test database configuration incorrect.

**Solution**: Check database credentials in `wp-tests-config.php`

### "Class WP_UnitTestCase not found"

**Problem**: WordPress test suite not installed correctly.

**Solution**: Re-run the installation script:
```bash
bash install-wp-tests.sh wordpress_test root '' localhost latest true
```

### "Headers already sent" warnings

**Problem**: Output before wp_send_json or exit in tests.

**Solution**: Use output buffering in tests:
```php
ob_start();
// Run code that outputs
$response = $this->get_ajax_response();
```

### Tests fail with "Call to undefined function wc_get_product"

**Problem**: WooCommerce not loaded in test environment.

**Solution**: Ensure WooCommerce is loaded in `bootstrap.php`:
```php
function _manually_load_plugin() {
    require '/path/to/woocommerce/woocommerce.php';
    require WPE_PLUGIN_DIR . 'woo-price-editor.php';
}
```

## Best Practices

1. **Isolate Tests**: Each test should be independent and not rely on other tests
2. **Clean Up**: Use `setUp()` and `tearDown()` to reset state between tests
3. **Mock External Services**: Don't make real API calls in tests
4. **Test Edge Cases**: Test not just happy paths but also error conditions
5. **Clear Assertions**: Use descriptive assertion messages
6. **Fast Tests**: Keep tests fast by using test doubles when possible

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Plugin Unit Tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- [WP_UnitTestCase Reference](https://developer.wordpress.org/reference/classes/wp_unittestcase/)
- [WordPress Test Suite on GitHub](https://github.com/WordPress/wordpress-develop)

## Contributing

When adding new features to the plugin:

1. Write tests for your new feature
2. Ensure all existing tests still pass
3. Run tests before submitting pull requests
4. Aim for high code coverage (80%+ is good)

## Test Coverage

Current test coverage:

- **Option Defaults**: ✓ Comprehensive
- **AJAX Permissions**: ✓ Comprehensive
- **Product Updates**: ⚠ Partial (extend as needed)
- **Settings Validation**: ⚠ Partial (extend as needed)

To improve coverage, add tests for:
- Product field sanitization
- Settings page validation
- Security class methods
- Error handling scenarios
