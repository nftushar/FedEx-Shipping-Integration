# FedEx WordPress Plugin v2.0

**Production-ready FedEx shipping integration for WordPress with OAuth 2.0, Service Container, and Proper Singleton Pattern.**

## ✨ Features

✅ **Complete OOP Architecture** - Full namespaces & PSR-4 autoloading  
✅ **Singleton Pattern** - Proper service container implementation  
✅ **Composer Support** - Optional PSR-4 autoloader via Composer  
✅ **OAuth 2.0** - Secure authentication with token caching  
✅ **Real-time Rates** - Get shipping quotes from FedEx API  
✅ **WooCommerce Ready** - Seamless integration with your store  
✅ **Sandbox & Production** - Test before going live  
✅ **Exception Handling** - Proper error management  

## 🚀 Quick Start

### 1. Install Plugin
```bash
# Extract plugin
wp-content/plugins/FedEx/
```

### 2. Setup (Choose One)

#### Option A: With Composer (Recommended)
```bash
cd wp-content/plugins/FedEx/
composer install
```

#### Option B: Without Composer (Fallback Autoloader)
```
Just activate the plugin! It works without Composer.
```

### 3. Activate
WordPress Admin → Plugins → Activate **FedEx Shipping**

### 4. Configure
1. Go to **FedEx Shipping → Settings**
2. Enter Client ID & Secret
3. Select **Sandbox** (for testing)
4. Click **Test OAuth Connection**

### 5. Test with WooCommerce
Add product → View checkout → See FedEx rates! ✅

## 📁 Complete Plugin Structure

```
FedEx/
├── fedex-plugin.php              ← Main entry point
├── composer.json                 ← Composer configuration
├── SETUP.txt                     ← Installation & Composer guide
├── USER-GUIDE.md                 ← Complete user documentation
├── README.md                     ← This file
├── EXAMPLES.php                  ← Code examples
├── vendor/                       ← Composer packages (if installed)
│   ├── autoload.php              ← Auto-generated PSR-4 autoloader
│   └── composer/                 ← Composer metadata
└── includes/
    ├── Autoloader.php            ← Fallback PSR-4 autoloader
    ├── Container.php             ← Service Container (Singleton)
    ├── Config/
    │   └── Configuration.php      ← Config & endpoints
    ├── Services/
    │   ├── OAuthService.php       ← OAuth token management
    │   └── ApiService.php         ← FedEx API client
    ├── Exceptions/
    │   └── FedExException.php     ← Exception hierarchy
    ├── Admin/
    │   └── SettingsPage.php       ← Admin settings UI
    ├── WooCommerce/
    │   └── ShippingMethod.php     ← WooCommerce integration
    ├── Core/
    │   └── Bootstrap.php          ← Plugin lifecycle
    └── Helpers/
        └── Utilities.php          ← Utility functions
```

## 🔧 Architecture Highlights

### Singleton Pattern
```php
use FedEx\Container;

// Get the singleton instance
$container = Container::getInstance();

// Get services
$config = $container->get('config');
$oauth = $container->get('oauth');
$api = $container->get('api');
```

**Safety Features:**
- Private constructor (prevents direct instantiation)
- Private `__clone()` (prevents cloning)
- Static `getInstance()` (ensures single instance)
- Thread-safe implementation

### Service Container
Manages all services & dependencies:
- `'config'` → Configuration service
- `'oauth'` → OAuth token manager  
- `'api'` → FedEx API client

### PSR-4 Autoloading
Two options (automatically selects best):
1. **Composer PSR-4** - `vendor/autoload.php` (recommended)
2. **Fallback Autoloader** - `includes/Autoloader.php` (built-in)

## 📝 Configuration

### Admin Settings
**Location:** FedEx Shipping → Settings

- **Client ID** - OAuth2 Client ID
- **Client Secret** - OAuth2 Client Secret
- **Environment** - Sandbox or Production
- **Account Number** (optional)
- **Test OAuth Connection** button

### Endpoints

#### Sandbox (Testing)
```
OAuth Token: https://apis-sandbox.fedex.com/oauth/token
API Base:    https://apis-sandbox.fedex.com
```

#### Production (Live)
```
OAuth Token: https://apis.fedex.com/oauth/token
API Base:    https://apis.fedex.com
```

## 💻 Code Examples

### Get Shipping Rates
```php
use FedEx\Container;

$api = Container::getInstance()->get('api');

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

try {
    $rates = $api->get_rates($shipment_data);
    foreach ($rates as $rate) {
        echo $rate['service'] . ': $' . $rate['cost'];
    }
} catch (\FedEx\Exceptions\ApiException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Track a Shipment
```php
$api = Container::getInstance()->get('api');

try {
    $tracking = $api->track_shipment('7948238934');
    echo "Status: " . $tracking['status'];
} catch (\FedEx\Exceptions\ApiException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Check Configuration
```php
$config = Container::getInstance()->get('config');

if ($config->is_configured()) {
    echo "Environment: " . $config->get_environment();
    echo "OAuth: " . $config->get_oauth_endpoint();
}
```

More examples in **EXAMPLES.php**

## 🔐 Security

✅ OAuth 2.0 authentication  
✅ Token expiration (1 hour)  
✅ Token caching with transients  
✅ Input validation & sanitization  
✅ Output escaping  
✅ HTTPS enforcement  
✅ Nonce verification  
✅ Capability checks  

## 📊 Requirements

| Component | Version |
|-----------|---------|
| PHP | 7.4+ |
| WordPress | 5.0+ |
| WooCommerce | 3.0+ (optional) |
| FedEx API | v1 (OAuth 2.0) |

## 🐛 Troubleshooting

### OAuth Connection Failed
1. Verify Client ID & Secret
2. Check environment matches credentials
3. See USER-GUIDE.md for detailed troubleshooting

### No Rates Returned
1. OAuth connection working?
2. Shipment data complete?
3. Weight & dimensions valid?
4. See USER-GUIDE.md for data format

## 📚 Documentation

| File | Purpose |
|------|---------|
| **SETUP.txt** | Installation & Composer setup guide |
| **USER-GUIDE.md** | Complete reference (all features & code) |
| **EXAMPLES.php** | Ready-to-use code examples |
| **composer.json** | Composer configuration |

**Start with:** SETUP.txt → USER-GUIDE.md

## ✅ All PHP Files Validated

No parse errors! ✅

```
✓ fedex-plugin.php
✓ includes/Autoloader.php
✓ includes/Container.php
✓ includes/Config/Configuration.php
✓ includes/Services/OAuthService.php
✓ includes/Services/ApiService.php
✓ includes/Admin/SettingsPage.php
✓ includes/WooCommerce/ShippingMethod.php
✓ includes/Exceptions/FedExException.php
✓ includes/Core/Bootstrap.php
✓ includes/Helpers/Utilities.php
```

## 🎯 Next Steps

1. Read **SETUP.txt** - Installation & Composer guide
2. Install plugin - Extract & activate
3. Run Composer (optional) - `composer install`
4. Configure - Enter FedEx credentials
5. Test - Click "Test OAuth Connection"
6. Use - Add to WooCommerce shipping zones
7. Extend - See USER-GUIDE.md for customization

## 📄 License

GPL v2 or later - Free software

---

**Status:** ✅ Production Ready  
**Version:** 2.0.0  
**Last Updated:** March 2026
