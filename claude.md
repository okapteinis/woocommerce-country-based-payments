# Country Based Payments for WooCommerce - Development Documentation

## Project Overview

Country Based Payments for WooCommerce is a WordPress plugin that enables WooCommerce store owners to control which payment gateways are available based on the customer's billing country. The plugin provides a simple yet powerful interface within WooCommerce settings to configure country-specific payment gateway availability.

### Core Functionality

- Restrict payment gateways to specific countries
- Multiple country selection per gateway
- Seamless WooCommerce integration
- Support for WPML WooCommerce Multilingual
- Compatible with WooCommerce High Performance Order Storage (HPOS)

### Version Information

- Current Version: 1.5
- WordPress Minimum: 5.0
- WooCommerce Minimum: 5.0
- WooCommerce Tested: 8.5.2
- PHP Minimum: 7.0

## Co-Authors

- **Claude** - code@claude.ai
- **Ojārs Kapteinis** - ojars@kapteinis.lv

## License

This project uses the **GPL-2.0 license** (GNU General Public License v2.0 or later).

The GPL-2.0 license ensures:
- Freedom to use the software for any purpose
- Freedom to study and modify the source code
- Freedom to redistribute copies
- Freedom to distribute modified versions

See the LICENSE file in the repository root for the complete license text.

**Note:** This project does NOT use CC BY-NC-ND 4.0 license. The correct license is GPL-2.0 as specified in the LICENSE file and plugin headers.

## Development Branch Strategy

### Active Development Branch: nightly

All active development work is performed on the **nightly** branch. This branch contains:
- Latest features and improvements
- Bug fixes and security patches
- Experimental changes and enhancements
- Work-in-progress code

### Branch Workflow

1. **nightly branch** - Active development (default branch)
   - All new features are developed here first
   - Daily commits and updates
   - May contain unstable or experimental code
   - All changes are published to nightly first

2. **main branch** - Stable releases only
   - Contains only tested, production-ready code
   - Receives merges from nightly after thorough testing
   - Tagged releases are created from main

### Development Guidelines

- All pull requests should target the nightly branch
- Code must pass review before merging to main
- Semantic versioning is used for releases
- Changelog is updated with each significant change

## Security Analysis Results

A comprehensive security review was conducted on November 17, 2025. The analysis examined all PHP files for common vulnerabilities including SQL injection, XSS, CSRF, input validation, and data sanitization.

### Critical Security Issues

#### 1. Unvalidated GET Parameter - pay_for_order

**Location:** woocommerce-country-based-payments.php:54

**Issue:** Direct use of $_GET['pay_for_order'] without sanitization

```php
if ( ! is_admin() && isset( $_GET['pay_for_order'] ) && true == $_GET['pay_for_order'] ) {
```

**Risk Level:** CRITICAL

**Impact:** While the strict comparison limits exploitation, this violates WordPress security best practices and could be exploited through type juggling.

**Recommendation:** Sanitize and validate the input:
```php
if ( ! is_admin() && isset( $_GET['pay_for_order'] ) && sanitize_text_field( $_GET['pay_for_order'] ) === 'true' ) {
```

#### 2. Unvalidated Order Key Parameter

**Location:** woocommerce-country-based-payments.php:116

**Issue:** Direct use of $_GET['key'] without sanitization or validation

```php
$order_id = wc_get_order_id_by_order_key( $_GET['key'] );
```

**Risk Level:** CRITICAL

**Impact:** Could potentially be exploited to access unauthorized orders or cause SQL injection if the WooCommerce function doesn't properly sanitize.

**Recommendation:** Sanitize, validate, and add error handling:
```php
$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
if ( empty( $order_key ) ) {
    return $payment_gateways;
}
$order_id = wc_get_order_id_by_order_key( $order_key );
```

#### 3. Missing Order Validation

**Location:** woocommerce-country-based-payments.php:117

**Issue:** Creating order object without validating order_id

```php
$order = new WC_Order( $order_id );
```

**Risk Level:** HIGH

**Impact:** Invalid order IDs could cause errors or unexpected behavior.

**Recommendation:** Add proper validation:
```php
if ( ! $order_id ) {
    return $payment_gateways;
}
$order = wc_get_order( $order_id );
if ( ! $order ) {
    return $payment_gateways;
}
```

### Medium Security Issues

#### 4. Loose Type Comparison

**Location:** woocommerce-country-based-payments.php:54

**Issue:** Using == instead of === for boolean comparison

**Risk Level:** MEDIUM

**Impact:** Could lead to unexpected type juggling behavior in PHP.

**Recommendation:** Use strict comparison === throughout.

### Security Strengths

The plugin demonstrates several security best practices:

1. **Direct File Access Protection** - All PHP files properly check for ABSPATH constant
2. **CSRF Protection** - Nonce verification implemented in settings update (WCCBPSettings.php:86)
3. **No Direct Database Queries** - Uses WordPress/WooCommerce APIs exclusively
4. **Safe API Usage** - Properly uses get_option(), WooCommerce customer methods
5. **Nonce Field Generation** - Proper nonce created for forms (WCCBPSettings.php:30)

### SQL Injection Risk: LOW

No direct database queries found. All data access uses WordPress/WooCommerce APIs.

### XSS Risk: LOW

Output is handled through WooCommerce admin fields which handle escaping. However, input sanitization should be improved.

### CSRF Risk: LOW

Proper nonce verification implemented for settings updates.

### Overall Security Rating: 6/10

The plugin has good foundational security practices but requires fixes for input validation and sanitization issues.

## WordPress Compatibility Status

### WordPress 5.0+ Compatibility: COMPATIBLE

The plugin is compatible with WordPress 5.0 and higher with some noted issues requiring attention.

### Compatible Features

1. **Plugin Headers** - Properly declares WordPress and WooCommerce requirements
2. **Modern WordPress APIs** - Uses wp_doing_ajax() (WP 4.7.0+)
3. **Text Domain** - Properly implements internationalization
4. **Hook System** - Correct use of WordPress action and filter hooks
5. **WooCommerce Settings API** - Proper integration with WooCommerce settings

### HPOS (High Performance Order Storage) Support: EXCELLENT

**Location:** woocommerce-country-based-payments.php:58-63

The plugin properly declares HPOS compatibility using the modern WooCommerce approach:

```php
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
```

This implementation:
- Uses the correct hook (before_woocommerce_init)
- Checks for class existence before declaring compatibility
- Properly declares support for custom_order_tables feature
- Follows WooCommerce HPOS best practices

### Deprecated Function Usage

#### 1. Deprecated WC_Order Constructor

**Location:** woocommerce-country-based-payments.php:117

**Issue:** Using `new WC_Order( $order_id )` (deprecated since WooCommerce 3.0)

**Current Code:**
```php
$order = new WC_Order( $order_id );
```

**Required Fix:**
```php
$order = wc_get_order( $order_id );
```

**Impact:** Will cause deprecation warnings in WooCommerce 3.0+ and may break in future versions.

#### 2. Deprecated get_address() Method

**Location:** woocommerce-country-based-payments.php:118

**Issue:** Using generic get_address() instead of specific getters

**Current Code:**
```php
$billing_address = $order->get_address();
$selected_country = $billing_address['country'];
```

**Required Fix:**
```php
$selected_country = $order->get_billing_country();
```

**Impact:** More efficient, follows WooCommerce 3.0+ best practices, reduces deprecation warnings.

### Plugin Requirements Check

**Location:** woocommerce-country-based-payments.php:134-141

Proper check for WooCommerce activation before initializing plugin:

```php
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    new WoocommerceCountryBasedPayment();
}
```

### Overall WordPress Compatibility Rating: 8/10

Compatible with WordPress 5.0+ but requires updates to use modern WooCommerce APIs.

## ClassicPress Compatibility Notes

### ClassicPress Background

ClassicPress is a community-led fork of WordPress based on the WordPress 4.9.x codebase (pre-Gutenberg). It maintains compatibility with most WordPress 4.9 plugins while avoiding WordPress 5.0+ features.

### Compatibility Status: HIGHLY COMPATIBLE

The code is fundamentally compatible with ClassicPress with minimal concerns.

### Compatible Code Patterns

All WordPress functions used in the plugin are available in ClassicPress:

1. **wp_doing_ajax()** - Introduced WP 4.7.0 - Available in ClassicPress
2. **load_plugin_textdomain()** - Core function - Available in ClassicPress
3. **is_plugin_active()** - Core function - Available in ClassicPress
4. **add_filter(), add_action()** - Core hooks - Available in ClassicPress
5. **All WooCommerce APIs** - Compatible with WooCommerce versions that support ClassicPress

### HPOS Declaration Compatibility

The HPOS compatibility declaration is safely wrapped in a class_exists() check:

```php
if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
}
```

This ensures no errors occur on ClassicPress where this WooCommerce class may not exist.

### WordPress 5.0+ Features NOT Used

The plugin does NOT use any WordPress 5.0+ specific features:
- No Gutenberg blocks
- No REST API v2 endpoints
- No Block Editor hooks
- No Site Health API
- No modern JavaScript build system

### Potential Compatibility Issues

#### 1. Plugin Header Requirements

**Location:** woocommerce-country-based-payments.php:13, readme.txt:5

**Issue:** Plugin headers declare "Requires at least: 5.0"

**Impact:** This may prevent installation on ClassicPress systems even though the code is compatible.

**Recommendation:** If ClassicPress support is desired:
- Lower minimum requirement to WordPress 4.9
- Test with WooCommerce versions compatible with ClassicPress
- Add ClassicPress to tested platforms in documentation

#### 2. WooCommerce Version Requirements

**Requirement:** WooCommerce 5.0+

**Consideration:** Verify that WooCommerce 5.0+ works with ClassicPress. Many ClassicPress users run older WooCommerce versions (3.x - 4.x range).

**Recommendation:** Test with WooCommerce versions commonly used on ClassicPress installations.

### Testing Recommendations for ClassicPress

1. Test with ClassicPress 1.x latest version
2. Test with WooCommerce 4.x series (most common on ClassicPress)
3. Verify payment gateway filtering works correctly
4. Test settings page functionality
5. Verify WPML integration if used
6. Test order processing with various payment gateways

### ClassicPress Support Strategy

**Option 1: Explicit Support**
- Update minimum requirements to WordPress 4.9
- Test and document ClassicPress compatibility
- Maintain compatibility in future updates

**Option 2: Implicit Compatibility**
- Keep current requirements (WP 5.0+)
- Document that code is ClassicPress-compatible
- Note that users can install manually if needed

**Option 3: Separate Branch**
- Maintain a classicpress-compatible branch
- Backport security fixes
- Separate release cycle

### Overall ClassicPress Compatibility Rating: 9/10

The code is fully compatible. Only the stated requirements in plugin headers could prevent installation.

## Code Quality Assessment

### Overall Code Quality Rating: 7/10

The plugin demonstrates good architecture and WordPress integration but has areas for improvement in error handling, security, and modern PHP practices.

### Code Structure: GOOD

**Strengths:**
- Clean object-oriented design
- Single Responsibility Principle followed
- Logical file organization (main plugin, admin settings, third-party SDK)
- Clear class and method naming
- Minimal code duplication

**Architecture:**
```
woocommerce-country-based-payments.php  - Main plugin class, core logic
includes/admin/WCCBPSettings.php        - Admin settings interface
includes/freemius/                      - Third-party SDK for analytics
```

### WordPress Coding Standards Compliance

#### Compliant Areas

1. **Indentation** - Uses tabs (WordPress standard)
2. **Naming Conventions** - PascalCase for classes, snake_case for functions
3. **File Organization** - Proper includes structure
4. **Direct Access Protection** - All files check for ABSPATH
5. **Hook Usage** - Proper use of add_action() and add_filter()

#### Non-Compliant Areas

**1. Inconsistent Indentation**

**Location:** woocommerce-country-based-payments.php:40-41

```php
if ( is_admin() && ! wp_doing_ajax() ) {
      $this->load_settings();  // Extra spaces - should use tabs
    }
```

**2. Missing Type Hints**

Modern PHP 7.0+ type hints are not used:

```php
// Current
public function available_payment_gateways( $payment_gateways ) {

// Should be
public function available_payment_gateways( array $payment_gateways ) : array {
```

**3. Spacing Inconsistencies**

Some areas have inconsistent spacing around operators and parentheses.

### Documentation Quality: MODERATE

#### Good Documentation

1. **PHPDoc Blocks** - Present for all classes and public methods
2. **Inline Comments** - Explain complex logic
3. **README Files** - Comprehensive user documentation
4. **Changelog** - Detailed version history

#### Documentation Improvements Needed

**1. Missing @since Tags**

PHPDoc blocks lack version information:

```php
/**
 * Create input field for every available payment gateway
 *
 * @return array List of field configuration arrays
 */
```

Should include:
```php
/**
 * Create input field for every available payment gateway
 *
 * @since 1.0
 * @return array List of field configuration arrays
 */
```

**2. Spelling Error**

**Location:** WCCBPSettings.php:35

```php
/**
 * Cerate input field for every available payment gateway
```

Should be "Create" not "Cerate".

**3. Incomplete Type Documentation**

Return types in PHPDoc often just say "array" without describing structure.

**4. Missing Developer Documentation**

No documentation for developers who want to extend the plugin or integrate with it.

### Error Handling: MINIMAL

#### Issues Found

**1. No Error Logging**

The plugin has no logging mechanism for debugging production issues.

**2. Silent Failures**

Functions return without indicating why operations failed:

```php
public function available_payment_gateways_after_cancelation( $payment_gateways ) {
    $order_id = wc_get_order_id_by_order_key( $_GET['key'] );
    $order = new WC_Order( $order_id );
    // No validation if $order_id is false or $order is invalid
```

**3. No Exception Handling**

No try-catch blocks for potentially failing operations.

**Recommendation:** Add error logging:

```php
if ( ! $order_id ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'WCCBP: Invalid order key provided' );
    }
    return $payment_gateways;
}
```

### Performance Considerations

#### Potential Optimizations

**1. Multiple get_option() Calls**

**Locations:** Lines 98 and 122

Each payment gateway triggers a separate get_option() call. For stores with many gateways, this could be optimized.

**Current:**
```php
foreach ( $payment_gateways as $key => $value ) {
    $gateway_availability = get_option( $this->id . '_' . $gateway_id );
    // ...
}
```

**Optimization:** Cache all options in a single call or use class property.

**2. No WordPress Object Cache Usage**

The plugin doesn't utilize WordPress object cache for frequently accessed data.

**3. Hook Priorities**

- Line 50: Priority 10 (default) - Appropriate
- Line 48: Priority 90 (for WCML compatibility) - Well thought out

### WooCommerce Integration: GOOD

**Strengths:**
1. Proper use of WooCommerce filters
2. Correct integration with WooCommerce Settings API
3. Uses WooCommerce multi-select countries field type
4. Checks for WooCommerce activation before loading
5. HPOS compatibility properly declared

**Integration Points:**
- woocommerce_available_payment_gateways filter
- wcml_supported_currency_payment_gateways filter (WPML support)
- woocommerce_settings_tabs_array filter
- WooCommerce settings page hooks

### Third-Party Dependencies: CONCERNS

#### Freemius SDK Integration

**Location:** includes/freemius/

**Observations:**
1. Large third-party dependency (60+ files)
2. Used for analytics and licensing
3. Collects usage data (GDPR implications noted in changelog)
4. Appears to be up-to-date version

**Concerns:**
1. **Privacy** - Data collection requires clear user consent
2. **Security** - Dependency on third-party code security
3. **Bloat** - Large SDK for a simple plugin
4. **Updates** - Requires keeping SDK updated for security

**Recommendations:**
1. Document exactly what data Freemius collects
2. Ensure users can opt-out
3. Keep SDK updated with security patches
4. Consider if all Freemius features are necessary

### Testing: NOT FOUND

No automated tests found in the repository:
- No PHPUnit tests
- No integration tests
- No JavaScript tests

**Recommendation:** Add test coverage for critical functionality:
- Payment gateway filtering logic
- Settings saving and retrieval
- WPML compatibility
- Edge cases (no WC_Customer object, invalid order IDs)

### Code Quality Strengths

1. Clean, readable code
2. Proper WordPress/WooCommerce API usage
3. Good separation of concerns
4. Minimal dependencies (besides Freemius)
5. Well-structured class design
6. Good use of WordPress hooks and filters

### Code Quality Weaknesses

1. Security vulnerabilities (unsanitized input)
2. Minimal error handling and logging
3. Deprecated WooCommerce functions
4. Missing modern PHP features (type hints, return types)
5. No automated testing
6. Inconsistent code formatting
7. Limited inline documentation

### Priority Code Quality Improvements

1. Fix all security issues (sanitize inputs)
2. Replace deprecated WooCommerce functions
3. Add comprehensive error handling
4. Implement logging for debugging
5. Add type hints throughout
6. Create automated tests
7. Improve documentation

## Recommendations

### High Priority - Security

1. **Sanitize all GET/POST inputs** - Critical security fix
   - Sanitize $_GET['pay_for_order'] at line 54
   - Sanitize $_GET['key'] at line 116
   - Add validation for all user inputs

2. **Replace deprecated WooCommerce functions**
   - Replace `new WC_Order()` with `wc_get_order()` at line 117
   - Replace `get_address()` with `get_billing_country()` at line 118

3. **Add input validation**
   - Validate order IDs before use
   - Check if order objects are valid
   - Handle edge cases gracefully

4. **Implement error logging**
   - Add WP_DEBUG logging for troubleshooting
   - Log security-related events
   - Track failed operations

### Medium Priority - Compatibility

1. **Update for WordPress 5.0+ best practices**
   - Add type hints to all methods
   - Use strict comparisons (===) throughout
   - Follow modern PHP coding standards

2. **Clarify ClassicPress support**
   - Document whether ClassicPress is officially supported
   - Test with ClassicPress if supporting it
   - Update minimum requirements if needed

3. **Test with latest WooCommerce versions**
   - Verify compatibility with WooCommerce 8.x+
   - Test HPOS functionality
   - Ensure no deprecation warnings

### Low Priority - Code Quality

1. **Improve documentation**
   - Add @since tags to all PHPDoc blocks
   - Fix spelling errors
   - Add developer documentation
   - Document Freemius data collection

2. **Add automated testing**
   - Create PHPUnit tests for core functionality
   - Add integration tests
   - Set up continuous integration

3. **Optimize performance**
   - Cache get_option() results
   - Implement WordPress object cache
   - Profile and optimize hot paths

4. **Code formatting cleanup**
   - Fix inconsistent indentation
   - Standardize spacing
   - Run through PHP_CodeSniffer with WordPress ruleset

### Freemius SDK Recommendations

1. Document what data is collected in privacy policy
2. Ensure GDPR compliance with opt-in/opt-out
3. Keep SDK updated for security patches
4. Consider if full SDK is needed vs. minimal integration

### Future Enhancement Ideas

1. Add unit tests for all public methods
2. Implement caching for gateway availability checks
3. Add admin notices for configuration issues
4. Create developer hooks for extensibility
5. Add multisite compatibility enhancements
6. Support for shipping country in addition to billing

## File Structure

### Core Plugin Files

```
woocommerce-country-based-payments/
├── woocommerce-country-based-payments.php  [Main plugin file - 189 lines]
│   ├── WoocommerceCountryBasedPayment class
│   ├── HPOS compatibility declaration
│   ├── Freemius SDK initialization
│   └── Plugin initialization logic
│
├── includes/
│   ├── admin/
│   │   └── WCCBPSettings.php              [Admin settings - 92 lines]
│   │       └── WCCBPSettings class
│   │           ├── Settings tab registration
│   │           ├── Field generation
│   │           └── Options update with nonce verification
│   │
│   └── freemius/                          [Third-party SDK - 60+ files]
│       ├── start.php
│       ├── includes/
│       ├── templates/
│       └── [Full Freemius SDK structure]
│
├── README.md                               [Repository documentation]
├── readme.txt                              [WordPress.org plugin readme]
├── LICENSE                                 [GPL-2.0 license]
└── languages/                              [Internationalization files]
```

### Main Plugin File: woocommerce-country-based-payments.php

**Purpose:** Core plugin initialization and payment gateway filtering logic

**Key Components:**

1. **Plugin Headers** (Lines 2-15)
   - Plugin metadata
   - Version information
   - WooCommerce compatibility

2. **WoocommerceCountryBasedPayment Class** (Lines 24-129)
   - Constructor: Initializes hooks and settings
   - load_plugin_textdomain(): Internationalization
   - load_settings(): Admin settings loader
   - available_payment_gateways(): Main filtering logic
   - available_payment_gateways_after_cancelation(): Payment retry handling

3. **HPOS Compatibility** (Lines 58-63)
   - Declares High Performance Order Storage support

4. **Freemius Integration** (Lines 143-189)
   - SDK initialization
   - Settings page integration

### Admin Settings File: includes/admin/WCCBPSettings.php

**Purpose:** WooCommerce admin settings interface

**Key Components:**

1. **WCCBPSettings Class** (Lines 11-92)
   - init(): Register WordPress hooks
   - add_settings_settings_tab(): Create WCCBP tab in WooCommerce settings
   - settings_page(): Render settings form with nonce
   - create_fields(): Generate country selection fields for each payment gateway
   - create_tab_section(): Build complete settings section
   - update_options(): Save settings with nonce verification

### Freemius SDK: includes/freemius/

**Purpose:** Third-party analytics and licensing framework

**Components:**
- Complete Freemius SDK (version 2.5.10+)
- Handles plugin analytics
- Opt-in data collection
- Account management
- License validation (if used)

**Note:** This is a complete third-party SDK. Developers should review Freemius documentation for SDK-specific functionality.

## Configuration

### Plugin Settings Location

WooCommerce → Settings → WCCBP tab

### Configuration Steps

1. **Navigate to Settings**
   - Go to WordPress admin dashboard
   - Click WooCommerce → Settings
   - Click the "WCCBP" tab

2. **Configure Payment Gateways**
   - Each installed payment gateway appears as a separate option
   - Click on the country selector for each gateway
   - Select countries where the gateway should be available
   - Leave empty to make gateway available worldwide

3. **Save Settings**
   - Click "Save changes" button
   - Settings are saved with CSRF protection (nonce verification)

### How It Works

**Default Behavior:**
- If no countries are selected for a payment gateway, it appears for all countries
- This is the "available worldwide" default

**Restricted Behavior:**
- When countries are selected, gateway only appears for those countries
- Based on customer's billing country during checkout

**Technical Implementation:**

1. Settings are stored as WordPress options:
   - Option name format: `wccbp_{gateway_id}`
   - Value: Array of country codes (e.g., ['US', 'UK', 'CA'])

2. Filtering happens via WordPress filter:
   - Hook: `woocommerce_available_payment_gateways`
   - Priority: 10 (default)
   - Checks customer billing country against saved options

3. Special handling for payment retries:
   - Hook: `woocommerce_available_payment_gateways` (after cancelation)
   - Uses order's billing country instead of session
   - Handles pay_for_order scenarios

### Configuration Examples

**Example 1: Regional Payment Gateways**

Scenario: Offer different gateways in different regions

Configuration:
- PayPal: United States, Canada, United Kingdom, Australia
- Stripe: United States, Canada, United Kingdom, European Union countries
- Local Bank Transfer: [Your country only]
- Cash on Delivery: [Leave empty for worldwide]

Result:
- US customers see: PayPal, Stripe, Cash on Delivery
- Local customers see: Local Bank Transfer, Cash on Delivery
- Other countries see: Cash on Delivery

**Example 2: Compliance-Based Restrictions**

Scenario: Comply with payment processing regulations

Configuration:
- Credit Card Gateway: All countries except those with restrictions
- Alternative Payment: Countries where credit cards are less common
- Wire Transfer: Business customers in specific countries

**Example 3: Cost Optimization**

Scenario: Use cost-effective gateways per region

Configuration:
- Low-fee gateway: High-volume countries
- Standard gateway: Other countries
- Premium gateway: High-value transaction countries

### WPML WooCommerce Multilingual Compatibility

The plugin includes special handling for WPML:

**Filter:** `wcml_supported_currency_payment_gateways`
**Priority:** 90
**Purpose:** Ensures currency-specific gateways work with country restrictions

### Settings Storage

All settings are stored in WordPress options table:

```php
Option Name: wccbp_{gateway_id}
Option Value: array( 'US', 'CA', 'GB', ... ) or false
Autoload: yes
```

### Programmatic Access

Developers can access settings:

```php
// Get country restrictions for a gateway
$countries = get_option( 'wccbp_paypal' );

// Returns array of country codes or false if unrestricted
if ( $countries && is_array( $countries ) ) {
    // Gateway is restricted to these countries
} else {
    // Gateway is available worldwide
}
```

### Debugging Configuration Issues

1. **Gateway not appearing:**
   - Check if customer's billing country is in selected list
   - Verify WooCommerce customer object exists
   - Check for JavaScript errors preventing checkout

2. **Settings not saving:**
   - Check for CSRF/nonce errors in browser console
   - Verify user has capability to manage WooCommerce settings
   - Check server error logs

3. **Conflicts with other plugins:**
   - Check hook priorities
   - Look for other plugins filtering payment gateways
   - Test with minimal plugin set to isolate conflict

## Testing Checklist

### WordPress Testing

#### Installation & Activation
- [ ] Plugin installs without errors
- [ ] Plugin activates successfully
- [ ] WooCommerce dependency check works
- [ ] Settings tab appears in WooCommerce settings
- [ ] No PHP errors in error log
- [ ] No JavaScript console errors

#### Settings Page Testing
- [ ] WCCBP tab appears in WooCommerce → Settings
- [ ] All installed payment gateways appear in settings
- [ ] Country selector works for each gateway
- [ ] Multiple countries can be selected
- [ ] Settings save successfully
- [ ] Saved settings persist after page reload
- [ ] Nonce verification prevents CSRF attacks

#### Frontend Functionality
- [ ] Payment gateways filter based on billing country
- [ ] Unrestricted gateways appear for all countries
- [ ] Restricted gateways only appear for selected countries
- [ ] Billing country change updates available gateways
- [ ] AJAX updates work correctly
- [ ] No JavaScript errors during checkout

#### Payment Gateway Testing
- [ ] Test with default WooCommerce gateways (BACS, Check, COD)
- [ ] Test with Stripe (if available)
- [ ] Test with PayPal Standard (if available)
- [ ] Test with at least one third-party gateway
- [ ] Verify known incompatible gateways (Amazon, PayPal Checkout)

#### Order Processing
- [ ] Orders process correctly with filtered gateways
- [ ] Payment cancellation and retry works (pay_for_order)
- [ ] Order billing country is correctly identified
- [ ] Payment gateway selection persists in order

#### HPOS (High Performance Order Storage)
- [ ] Enable HPOS in WooCommerce settings
- [ ] Verify plugin still functions with HPOS enabled
- [ ] Test order creation with HPOS
- [ ] Test payment retry with HPOS orders
- [ ] Check for deprecation warnings

#### WPML Compatibility (if applicable)
- [ ] Install WPML and WooCommerce Multilingual
- [ ] Verify settings work in multiple languages
- [ ] Test currency-specific gateway filtering
- [ ] Confirm country restrictions work with WPML

#### Multisite Testing (if applicable)
- [ ] Plugin activates on network
- [ ] Settings work per-site
- [ ] No cross-site data leakage

#### WordPress Version Compatibility
- [ ] Test with WordPress 5.0
- [ ] Test with WordPress 5.9
- [ ] Test with WordPress 6.0+
- [ ] Test with latest WordPress version
- [ ] Check for deprecated function warnings

#### Performance Testing
- [ ] Test with 5+ payment gateways
- [ ] Test with 10+ payment gateways
- [ ] Monitor database queries during checkout
- [ ] Check page load times
- [ ] Test with high-traffic simulation

#### Security Testing
- [ ] Attempt CSRF attack on settings page (should fail)
- [ ] Try to access settings without admin privileges (should fail)
- [ ] Test direct file access to PHP files (should be blocked)
- [ ] Verify nonce validation works
- [ ] Test with malformed input data

#### Uninstall Testing
- [ ] Deactivate plugin cleanly
- [ ] Verify options are retained after deactivation
- [ ] Check that gateways return to normal after deactivation

### ClassicPress Testing

#### Installation & Activation
- [ ] Plugin installs on ClassicPress (may require manual installation)
- [ ] Plugin activates without errors on ClassicPress
- [ ] No PHP compatibility errors
- [ ] WooCommerce dependency check works

#### Core Functionality
- [ ] Settings page renders correctly
- [ ] Country selection works
- [ ] Payment gateway filtering functions
- [ ] Settings save and load correctly

#### WooCommerce Compatibility
- [ ] Test with WooCommerce 3.9.x (common on ClassicPress)
- [ ] Test with WooCommerce 4.x series
- [ ] Test with latest ClassicPress-compatible WooCommerce
- [ ] Verify no deprecated function warnings

#### ClassicPress-Specific
- [ ] No Gutenberg-related errors (ClassicPress uses classic editor)
- [ ] Admin interface works with ClassicPress styling
- [ ] No WordPress 5.0+ function calls that fail
- [ ] HPOS declaration doesn't cause errors (should skip gracefully)

#### Payment Processing
- [ ] Complete test order with filtered gateway
- [ ] Test payment retry functionality
- [ ] Verify order data integrity
- [ ] Test with multiple payment gateways

#### Version Compatibility
- [ ] Test with ClassicPress 1.x
- [ ] Test with latest ClassicPress version
- [ ] Verify long-term compatibility

### Regression Testing

After any code changes, verify:
- [ ] All settings functionality still works
- [ ] Payment gateway filtering still functions
- [ ] No new PHP errors introduced
- [ ] No new JavaScript errors introduced
- [ ] Performance hasn't degraded
- [ ] Security improvements don't break functionality

### Browser Compatibility

Test checkout and settings in:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

### Accessibility Testing
- [ ] Keyboard navigation works in settings
- [ ] Screen reader compatibility
- [ ] Color contrast meets WCAG standards
- [ ] Form labels are properly associated

### Documentation Testing
- [ ] README.md is accurate
- [ ] Installation instructions work
- [ ] Configuration examples are correct
- [ ] Known limitations are documented

### Developer Testing
- [ ] PHPDoc comments are accurate
- [ ] Code follows WordPress coding standards
- [ ] No hardcoded values that should be configurable
- [ ] Proper use of WordPress hooks and filters

## Changelog

See readme.txt for complete version history.

Latest version (1.5) includes:
- HPOS compatibility declaration
- Freemius SDK update
- Improved WordPress and WooCommerce compatibility

## Support & Contributing

### Reporting Issues

For security issues found during this review, please address before public release.

For general issues, use the GitHub issue tracker or WordPress.org support forums.

### Development

Active development happens on the nightly branch. All contributions should be made via pull requests to nightly.

## Conclusion

This plugin provides valuable functionality for WooCommerce stores operating in multiple countries. The codebase is well-structured and follows WordPress best practices in most areas. However, several security issues require immediate attention before the plugin should be used in production.

**Critical Action Items:**
1. Fix all security vulnerabilities (input sanitization)
2. Replace deprecated WooCommerce functions
3. Add comprehensive error handling
4. Improve documentation

**Long-term Improvements:**
1. Add automated testing
2. Optimize performance
3. Enhance error logging
4. Consider ClassicPress support strategy

Once security issues are addressed, this plugin will be a solid, reliable solution for country-based payment gateway management in WooCommerce.

---

Documentation generated: November 17, 2025
Review conducted by: Claude Code (code@claude.ai)
Repository: https://github.com/okapteinis/woocommerce-country-based-payments
Branch reviewed: nightly (via local development branch)
