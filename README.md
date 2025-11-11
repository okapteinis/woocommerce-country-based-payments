# Country Based Payments for WooCommerce

Country Based Payments for WooCommerce - independent development repository.

## About This Repository

This is an independent development repository for the Country Based Payments for WooCommerce plugin (version 1.5).

**Original Plugin:** [Country Based Payments for WooCommerce](https://wordpress.org/plugins/woocommerce-country-based-payments/)
**Original Authors:** ivan_paulin, mensmaximus
**Original License:** GPLv2 or later

## Description

This plugin gives you the option to choose which payment gateway will be available in certain country or countries.

Choose which payment gateway will be available based on customer's billing country during WooCommerce checkout.

## Features

- **Country-Specific Payment Gateways** - Enable/disable payment methods per country
- **Multiple Country Selection** - Choose multiple countries for each payment gateway
- **WooCommerce Integration** - Seamless integration with WooCommerce settings
- **Flexible Configuration** - If no country is set for a payment gateway, it's available in all countries
- **Simple Management** - Easy-to-use interface in WooCommerce settings

## Requirements

- **WordPress:** 5.0 or higher
- **WooCommerce:** 8.5.2+ (compatible)
- **PHP:** 7.0 or higher
- **Tested up to:** WordPress 6.4.2

## Installation

1. Upload the plugin files to `/wp-content/plugins/woocommerce-country-based-payments/`
2. Activate "Country Based Payments for WooCommerce" through the 'Plugins' menu in WordPress
3. Go to WooCommerce → Settings → WCCBP tab to configure

## Configuration

1. Navigate to **WooCommerce → Settings**
2. Click the **WCCBP** tab
3. Select payment gateways and choose countries where they should be available
4. Save your settings

## How It Works

- The plugin filters available payment gateways based on the customer's billing country
- If a payment gateway has no country restrictions, it's available worldwide
- If specific countries are selected, the gateway only appears for those countries

## Known Limitations

This plugin may not work with all payment gateways. Known incompatible gateways:

1. Amazon Payments
2. PayPal Checkout

**Note:** The plugin comes as-is without guarantee of compatibility with all payment gateways, especially premium versions.

## Version Information

- **Current Version:** 1.5
- **Stable Tag:** 1.5
- **WooCommerce Compatibility:** 8.5.2+

## Development

This repository is maintained as an independent development fork.

- **Main branch:** Stable releases only
- **Nightly branch:** Active development (default)

All development work happens on the `nightly` branch. Only tested, stable changes are merged to `main`.

## Usage Example

**Scenario:** You want PayPal to be available only in the US, UK, and Canada, while Stripe should be available worldwide.

**Configuration:**
1. For PayPal: Select United States, United Kingdom, Canada
2. For Stripe: Leave empty (available in all countries)

**Result:**
- Customers from US, UK, or Canada see both PayPal and Stripe
- Customers from other countries only see Stripe

## Important Notes

- **Backup Before Updating:** Always backup your website before updating the plugin
- **Test on Staging:** Test new versions on a staging server before deploying to production
- **Payment Gateway Testing:** Not all payment gateways have been tested; use with caution
- **Default Behavior:** If no countries are selected for a gateway, it remains available worldwide

## Documentation

For original plugin documentation, visit:
- [WordPress.org Plugin Page](https://wordpress.org/plugins/woocommerce-country-based-payments/)

## License

GPLv2 or later - Same as the original plugin

See LICENSE file for details.

## Credits

- Original plugin by ivan_paulin and mensmaximus
- Independent development by Ojārs Kapteinis
- Co-developed with Claude AI assistance

## Support

For official plugin support, visit the [WordPress.org support forum](https://wordpress.org/support/plugin/woocommerce-country-based-payments/).

For issues specific to this fork, use the GitHub issue tracker.

## Disclaimer

This is an independent development repository. For the official version and support, please visit the [original plugin page](https://wordpress.org/plugins/woocommerce-country-based-payments/).

## Screenshots

The original plugin includes screenshots showing:
1. WCCBP settings tab in WooCommerce
2. Multiple country selection interface
