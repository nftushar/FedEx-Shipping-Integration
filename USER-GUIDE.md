# FedEx WordPress Plugin - Complete User Guide

## 📖 Table of Contents
1. [Overview](#overview)
2. [Installation & Setup](#installation--setup)
3. [Configuration](#configuration)
4. [Features & Endpoints](#features--endpoints)
5. [Using with WooCommerce](#using-with-woocommerce)
6. [Code Examples](#code-examples)
7. [Architecture](#architecture)
8. [Extending the Plugin](#extending-the-plugin)
9. [Troubleshooting](#troubleshooting)
10. [FAQs](#faqs)

---

## Overview

**FedEx Shipping Plugin v2.0** - A complete, production-ready WordPress plugin for integrating FedEx shipping into your WooCommerce store. Built with modern PHP OOP, fully namespaced, scalable architecture, and both sandbox/production support.

### Key Features
✅ OAuth 2.0 authentication  
✅ Real-time shipping rates  
✅ Shipment tracking & creation  
✅ WooCommerce integration  
✅ Sandbox & Production environments  
✅ Admin settings panel  
✅ Token caching (59 minutes)  
✅ Multiple FedEx services supported  

### Requirements
- **PHP**: 7.4 or higher
- **WordPress**: 5.0 or higher
- **WooCommerce**: 3.0+ (optional, for shipping rates)
- **FedEx Account**: With API credentials

---

## Installation & Setup

### Step 1: Extract Plugin
```
Extract the FedEx folder to:
wp-content/plugins/FedEx/
```

### Step 2: Activate Plugin
1. Go to WordPress Admin → Plugins
2. Find "FedEx Shipping"
3. Click **Activate**

### Step 3: Get FedEx Credentials
1. Log in to FedEx Developer Portal
2. Create an application project
3. Get **Client ID** and **Client Secret**
4. Note down your **Account Number** (optional)

### Step 4: Configure Plugin
1. Go to **FedEx Shipping → Settings**
2. Enter your **Client ID**
3. Enter your **Client Secret**
4. Select **Environment**:
   - **Sandbox** (for testing) ← Start here
   - **Production** (for live shipments)
5. Click **Test OAuth Connection**
6. If successful, you're ready!

### Step 5: Enable in WooCommerce
1. Go to **WooCommerce → Settings → Shipping**
2. Click **Add shipping zone**
3. Add your zones
4. Add **FedEx Shipping** method to each zone
5. Configure options (markup, free shipping, etc.)

---

## Configuration

### Admin Settings

#### Credentials
- **Client ID**: OAuth2 application ID from FedEx
- **Client Secret**: OAuth2 application secret from FedEx
- **Account Number** (optional): Your FedEx account number
- **Zip Code** (optional): Shipper location zip code

#### Environment Selection
- **Sandbox**: `https://apis-sandbox.fedex.com`
  - Use for: Testing, development, QA
  - Data: Not used for real shipments
  - Default: Yes (recommended for initial setup)

- **Production**: `https://apis.fedex.com`
  - Use for: Live shipments, real charges
  - Data: Creates actual shipments & charges
  - Requires: Live FedEx account with funds

#### Test Connection
- Click **"Test OAuth Connection"** button
- This verifies credentials are correct
- Shows token validity in response

### WordPress Options Storage
All settings stored in WordPress database:
```php
// Retrieve settings in code
$client_id = get_option('fedex_client_id');
$environment = get_option('fedex_environment'); // 'sandbox' or 'production'
$account = get_option('fedex_shipper_account');
$zipcode = get_option('fedex_shipper_zipcode');
```

---

## Features & Endpoints

### Supported FedEx Services
- FedEx Ground
- FedEx 2-Day
- FedEx Overnight
- FedEx Priority Overnight
- FedEx Express Saver
- FedEx International Economy
- All other FedEx services

### API Endpoints

#### OAuth Token Endpoint
```
Sandbox:     https://apis-sandbox.fedex.com/oauth/token
Production:  https://apis.fedex.com/oauth/token
```
- Obtains access token for API calls
- Cached for 59 minutes
- Auto-refreshes when expires

#### Shipping Endpoints
```
Base URL Sandbox:    https://apis-sandbox.fedex.com
Base URL Production: https://apis.fedex.com
```

**Available Operations**:
- `POST /ship/v1/shipments` - Create/estimate shipment
- `GET /track/v1/tracked-packages` - Track shipment
- `DELETE /ship/v1/shipments/{id}` - Cancel shipment
- `GET /ship/v1/shipments/{id}/rates` - Get rates

#### Request Format
All API requests use:
- **Method**: POST or GET
- **Headers**: `Authorization: Bearer {token}`
- **Content-Type**: `application/json`
- **Body**: JSON payload

---

## Using with WooCommerce

### Add Shipping Zone
1. **WooCommerce → Settings → Shipping → Shipping zones**
2. Click **Add shipping zone**
3. Enter zone name and region
4. Click **Add shipping method**
5. Select **FedEx Shipping**
6. Configure:
   - **Markup %**: Add to all rates (e.g., 10%)
   - **Free Shipping At**: Order total for free shipping
   - **Enabled Services**: Which services to display

### Customer Experience
1. Customer adds products to cart
2. Enters shipping address at checkout
3. Plugin calculates FedEx rates in real-time
4. Customer selects preferred service
5. Order proceeds with selected service

### Example Shipment Data Sent to FedEx
```json
{
  "origin": {
    "street": "123 Main St",
    "city": "Memphis",
    "state": "TN",
    "zip": "38103"
  },
  "destination": {
    "street": "456 Oak Ave", 
    "city": "Los Angeles",
    "state": "CA",
    "zip": "90001"
  },
  "packages": [
    {
      "weight": {"value": 5, "units": "LB"},
      "dimensions": {
        "length": 10,
        "width": 8,
        "height": 6,
        "units": "IN"
      }
    }
  ]
}
```

---

## Code Examples

### Example 1: Get Shipping Rates (Developer)

```php
<?php
use FedEx\Container;

// Get the API service
$api = Container::getInstance()->get('api');

// Prepare shipment data
$shipment_data = [
    'origin' => [
        'street' => '123 Main St',
        'city' => 'Memphis',
        'state' => 'TN',
        'zip' => '38103'
    ],
    'destination' => [
        'street' => '456 Oak Ave',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'zip' => '90001'
    ],
    'packages' => [
        [
            'weight' => ['value' => 5, 'units' => 'LB'],
            'dimensions' => [
                'length' => 10,
                'width' => 8,
                'height' => 6,
                'units' => 'IN'
            ]
        ]
    ]
];

// Get rates
try {
    $rates = $api->get_rates($shipment_data);
    
    foreach ($rates as $rate) {
        echo $rate['service'] . ': $' . $rate['cost'];
    }
} catch (\FedEx\Exceptions\ApiException $e) {
    echo "Error getting rates: " . $e->getMessage();
}
```

### Example 2: Track a Shipment

```php
<?php
use FedEx\Container;

$api = Container::getInstance()->get('api');

try {
    $tracking = $api->track_shipment('7948238934');
    
    echo "Status: " . $tracking['status'];
    echo "Last Location: " . $tracking['location'];
    echo "Delivery Date: " . $tracking['delivery_date'];
} catch (\FedEx\Exceptions\ApiException $e) {
    echo "Tracking Error: " . $e->getMessage();
}
```

### Example 3: Create a Shipment

```php
<?php
use FedEx\Container;

$api = Container::getInstance()->get('api');

$shipment = [
    'shipper' => [
        'name' => 'Your Company',
        'street' => '123 Main St',
        'city' => 'Memphis',
        'state' => 'TN',
        'zip' => '38103',
        'phone' => '9015551234'
    ],
    'recipient' => [
        'name' => 'Customer Name',
        'street' => '456 Oak Ave',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'zip' => '90001',
        'phone' => '2125559999'
    ],
    'packages' => [
        [
            'weight' => 5,
            'dimensions' => ['length' => 10, 'width' => 8, 'height' => 6]
        ]
    ],
    'service_type' => 'FEDEX_GROUND',
    'payment_type' => 'SENDER'
];

try {
    $result = $api->create_shipment($shipment);
    echo "Shipment Created: " . $result['tracking_number'];
} catch (\FedEx\Exceptions\ApiException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Example 4: Check Configuration

```php
<?php
use FedEx\Container;

$config = Container::getInstance()->get('config');

// Check if plugin is configured
if ($config->is_configured()) {
    echo "Environment: " . $config->get_environment();
    echo "OAuth Endpoint: " . $config->get_oauth_endpoint();
    echo "API Endpoint: " . $config->get_api_endpoint();
} else {
    echo "Plugin needs configuration in admin panel";
}
```

### Example 5: Get OAuth Token (Direct)

```php
<?php
use FedEx\Container;

$oauth = Container::getInstance()->get('oauth');

try {
    $token = $oauth->get_token();
    
    echo "Access Token: " . $token['access_token'];
    echo "Expires In: " . $token['expires_in'] . " seconds";
    echo "Token Type: " . $token['token_type']; // Usually "Bearer"
} catch (\FedEx\Exceptions\OAuthException $e) {
    echo "Token Error: " . $e->getMessage();
}
```

### Example 6: Handle Errors

```php
<?php
use FedEx\Container;
use FedEx\Exceptions\{OAuthException, ApiException, ConfigException};

$api = Container::getInstance()->get('api');

try {
    $rates = $api->get_rates($data);
} catch (OAuthException $e) {
    // Token/authentication error
    echo "Auth failed: " . $e->getMessage();
    // Solution: Re-check credentials in admin
} catch (ApiException $e) {
    // API request error
    echo "API error: " . $e->getMessage();
    // Solution: Check shipment data, address validation
} catch (ConfigException $e) {
    // Configuration error
    echo "Config error: " . $e->getMessage();
    // Solution: Go to admin and complete setup
} catch (\Exception $e) {
    // Fallback for unexpected errors
    echo "Unexpected error: " . $e->getMessage();
}
```

---

## Architecture

### Service Container Pattern
The plugin uses a **Service Container** for dependency injection:

```php
use FedEx\Container;

// Get the Container instance
$container = Container::getInstance();

// Retrieve services
$config = $container->get('config');     // Configuration service
$oauth = $container->get('oauth');       // OAuth service
$api = $container->get('api');           // API service
```

### Class Hierarchy
```
FedEx/
├── Config/Configuration          - Environment & settings
├── Services/
│   ├── OAuthService              - Token management
│   └── ApiService                - FedEx API calls
├── Exceptions/
│   ├── FedExException            - Base exception
│   ├── OAuthException            - OAuth errors
│   ├── ApiException              - API errors
│   └── ConfigException           - Config errors
├── Admin/SettingsPage            - Admin interface
└── WooCommerce/ShippingMethod    - WC integration
```

### File Structure
```
FedEx/
├── fedex-plugin.php              (Entry point - loads all classes)
├── includes/
│   ├── Autoloader.php            (PSR-4 Auto-loading)
│   ├── Container.php             (Service Container)
│   ├── Config/Configuration.php  (Settings & endpoints)
│   ├── Services/
│   │   ├── OAuthService.php      (Token management)
│   │   └── ApiService.php        (FedEx API client)
│   ├── Exceptions/FedExException.php (Exception classes)
│   ├── Admin/SettingsPage.php    (Admin page)
│   ├── WooCommerce/ShippingMethod.php (WC integration)
│   └── Helpers/Utilities.php     (Utility functions)
├── USER-GUIDE.md                 (This file)
└── EXAMPLES.php                  (Code examples)
```

### Namespaces
All classes use namespaces for organization:
```
FedEx\Config\Configuration
FedEx\Services\OAuthService
FedEx\Services\ApiService
FedEx\Exceptions\FedExException
FedEx\Admin\SettingsPage
FedEx\WooCommerce\ShippingMethod
FedEx\Helpers\Utilities
```

---

## Extending the Plugin

### Add Custom Functionality

#### Option 1: Use Hooks
```php
// Hook into rate calculation
add_filter('fedex_shipping_rates', function($rates) {
    // Modify rates
    return $rates;
});

// Hook into shipment creation
add_action('fedex_shipment_created', function($shipment_id) {
    // Do something after shipment created
});
```

#### Option 2: Create Custom Handler
```php
<?php
use FedEx\Container;
use FedEx\Exceptions\ApiException;

class CustomFedExHandler {
    private $api;
    
    public function __construct() {
        $this->api = Container::getInstance()->get('api');
    }
    
    public function custom_rate_calc($shipment_data) {
        try {
            $rates = $this->api->get_rates($shipment_data);
            // Custom processing
            return $this->process_rates($rates);
        } catch (ApiException $e) {
            // Handle error
            return [];
        }
    }
    
    private function process_rates($rates) {
        // Your custom logic here
        return $rates;
    }
}

// Use it
$handler = new CustomFedExHandler();
$custom_rates = $handler->custom_rate_calc($data);
```

#### Option 3: Extend ApiService
```php
<?php
namespace MyPlugin;

use FedEx\Services\ApiService;

class ExtendedApiService extends ApiService {
    
    public function get_bulk_rates($shipments) {
        $rates = [];
        foreach ($shipments as $shipment) {
            try {
                $rates[] = $this->get_rates($shipment);
            } catch (\Exception $e) {
                $rates[] = [];
            }
        }
        return $rates;
    }
}
```

### Logging & Debugging

```php
use FedEx\Helpers\Utilities;

// Log messages
Utilities::log('My custom message');
Utilities::log('Error occurred', ['error' => $e->getMessage()]);

// View logs
// Check: wp-content/debug.log (if WP_DEBUG_LOG enabled)
```

### Enable Debug Logging
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check logs at: `wp-content/debug.log`

---

## Troubleshooting

### Problem: "OAuth Connection Failed"

**Check**:
1. **Credentials**: Verify Client ID & Secret are correct
   - Get them from FedEx Developer Portal
2. **Environment**: Ensure sandbox/production matches credentials
3. **Network**: Is server able to reach FedEx servers?
   - Check firewall/proxy settings
4. **Logs**: Check `wp-content/debug.log`

**Solution**:
- Go to **FedEx Shipping → Settings**
- Re-enter credentials carefully
- Click **Test OAuth Connection**

---

### Problem: "No Rates Returned"

**Check**:
1. **OAuth Works**: Click "Test OAuth Connection" first
2. **Shipment Data**:
   - Origin address complete?
   - Destination address complete?
   - Package weight & dimensions? 
3. **FedEx Account**: Do you have shipping services enabled?
4. **Weight Units**: In pounds (LB) and inches (IN)?

**Solution**:
```php
// Verify your shipment data structure
$data = [
    'origin' => [
        'street' => '...',      // Required
        'city' => '...',        // Required
        'state' => '...',       // Required
        'zip' => '...'          // Required
    ],
    'destination' => [
        'street' => '...',
        'city' => '...',
        'state' => '...',
        'zip' => '...'
    ],
    'packages' => [
        [
            'weight' => ['value' => 5, 'units' => 'LB'],    // Required
            'dimensions' => [                               // Required
                'length' => 10,
                'width' => 8,
                'height' => 6,
                'units' => 'IN'
            ]
        ]
    ]
];
```

---

### Problem: "Rates Not Appearing in Checkout"

**Check**:
1. **FedEx Plugin Activated**: Plugins → Check if active
2. **WooCommerce Active**: Is WooCommerce installed?
3. **Shipping Zone**: Is shipping zone configured?
4. **FedEx Method Enabled**: In zone settings, is FedEx added?
5. **Address Format**: Does cart have valid address?

**Solution**:
1. Go to **WooCommerce → Settings → Shipping**
2. Create/edit shipping zone
3. Add **FedEx Shipping** method
4. Save settings
5. Add product to cart & test

---

### Problem: "Plugin Won't Activate"

**Check**:
1. **PHP Version**: Need PHP 7.4+
   - Check: **FedEx Shipping** page (if visible)
   - Or: php -v in terminal
2. **WordPress Version**: Need 5.0+
   - Check: **WordPress** → **About**
3. **Disk Space**: Enough space to extract files?
4. **File Permissions**: Can WordPress write to plugins folder?

**Solution**:
- Upgrade PHP to 7.4 or higher
- Or upgrade WordPress to 5.0+
- Or check disk space/permissions

---

### Problem: "Error Logs Show Blank/Vague Messages"

**Enable Detailed Logging**:
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**View Logs**:
1. SSH into server: `tail -f wp-content/debug.log`
2. Or SFTP: Download `wp-content/debug.log`
3. Or WP Dashboard: Use a "View Logs" plugin

**Common Error Messages**:
| Error | Meaning | Solution |
|-------|---------|----------|
| "Invalid credentials" | Client ID/Secret wrong | Re-check in admin |
| "Unauthorized" | Token expired/invalid | Click "Test Connection" |
| "Invalid shipment" | Missing fields in data | Check address/weight/dims |
| "Connection timeout" | Can't reach FedEx servers | Check firewall/proxy |
| "Invalid address" | Bad origin/destination | Validate address format |

---

## FAQs

### Q: Which environment should I use?
**A**: Use **Sandbox** for testing (recommended). When you're ready for real shipments, switch to **Production** in admin settings.

### Q: How often is the OAuth token refreshed?
**A**: Token is cached for 59 minutes. After that, a new token is automatically requested. You don't need to do anything - it's automatic.

### Q: Can I have multiple FedEx accounts?
**A**: Currently, the plugin supports one account per WordPress install. For multiple accounts, you'd need multiple WordPress sites or custom code.

### Q: What if my origin address is different for each product?
**A**: Use the Shipper Zip Code option in settings. You can also extend the plugin with custom code to use different origins.

### Q: Does the plugin create actual shipments?
**A**: No, the plugin only gets rates. To create actual shipments, you'd need additional integration or manual processing via FedEx website.

### Q: Can I hide certain FedEx services from customers?
**A**: Yes, in WooCommerce shipping zone settings for FedEx method, you can select which services to display.

### Q: What's the difference between Sandbox and Production?
**A**:
- **Sandbox**: For testing, no real charges, uses test data
- **Production**: For live shipments, real charges, requires FedEx funds

### Q: How do I test in sandbox?
**A**: 
1. Use sandbox credentials from FedEx (different from production)
2. Select "Sandbox" in Settings
3. Add test product
4. Go to checkout
5. See test rates (not real)

### Q: Can this work without WooCommerce?
**A**: Yes! The plugin works standalone. You can use the PHP API directly in your code with the Service Container.

### Q: How do I debug rate calculation?
**A**:
1. Enable WP_DEBUG_LOG in wp-config.php
2. Check wp-content/debug.log
3. Or use code example #4 in Code Examples section

### Q: What if FedEx API returns an error?
**A**: The plugin throws an exception. Use try-catch blocks (see Code Examples) to handle errors gracefully.

### Q: Can I modify the rates returned?
**A**: Yes, use the Filter Hooks or extend the ApiService class (see Extending section).

### Q: Is there a REST API?
**A**: Not built-in, but you can create custom endpoints using WordPress REST API that use our services.

### Q: How is data secured?
**A**: Credentials stored in WordPress database, OAuth tokens temporary (1 hour), HTTPS enforced for API, input validation & output escaping.

### Q: What billing options does it support?
**A**: Currently "Bill Shipper". Other options can be added via custom code.

### Q: Can I add custom fields?
**A**: Yes, extend the ApiService or use hooks to add custom fields to shipment requests.

---

## Support Resources

### Documentation Files
- **EXAMPLES.php** - Ready-to-use code examples
- **README.md** - Plugin overview
- **QUICKSTART.md** - Quick setup guide
- **CONFIGURATION.md** - Settings reference

### Check These First
1. This USER-GUIDE.md (complete reference)
2. Code examples above
3. Troubleshooting section
4. FAQs
5. Log files (wp-content/debug.log)

### When Stuck
1. Check Troubleshooting section
2. Review relevant code example
3. Enable logging & check logs
4. Review your shipment data structure
5. Test in Sandbox first

---

## Version & Compatibility

| Component | Version |
|-----------|---------|
| Plugin | 2.0.0 |
| PHP | 7.4+ (required) |
| WordPress | 5.0+ (required) |
| WooCommerce | 3.0+ (optional) |
| FedEx API | v1 (OAuth 2.0) |

---

## What's New in v2.0

✨ **Complete OOP Refactoring**
- All code now uses namespaces
- Service Container pattern for dependency injection
- Better error handling with custom exceptions

✨ **Improved Architecture**
- Modular design for easy extension
- Automatic class loading (PSR-4)
- Clear separation of concerns

✨ **Better Scalability**
- Service-based architecture
- Easy to add custom functionality
- Test-ready code structure

✨ **Dynamic Endpoints**
- Explicit sandbox/production switching
- Configuration management
- Environment-aware API calls

---

## Quick Reference

### Common Tasks

**Get Rates**:
```php
$api = Container::getInstance()->get('api');
$rates = $api->get_rates($shipment_data);
```

**Track Shipment**:
```php
$tracking = $api->track_shipment('tracking_number');
```

**Get Config**:
```php
$config = Container::getInstance()->get('config');
$env = $config->get_environment();
```

**Test Connection**:
```php
$oauth = Container::getInstance()->get('oauth');
$token = $oauth->get_token();
```

### Settings Location
**WordPress Admin → FedEx Shipping → Settings**

### Log File
**wp-content/debug.log** (if WP_DEBUG_LOG enabled)

### Credentials Storage
**WordPress wp_options table** with `fedex_` prefix

---

**Last Updated**: March 2026  
**Status**: ✅ Production Ready  
**Support**: Complete documentation included  

For code examples, see **EXAMPLES.php** file.
