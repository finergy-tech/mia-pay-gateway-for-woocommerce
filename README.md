# MIA POS Payment Gateway for WooCommerce

Accept payments in your WooCommerce store using MIA POS payment system.

## Description

This plugin adds MIA POS as a payment method to your WooCommerce store. MIA POS is a payment system provided by Finergy Tech that allows you to accept payments via QR codes and direct payment requests.

### Features

- Accept payments via QR codes
- Support for Request to Pay (RTP) payments
- Automatic order status updates
- Secure payment processing
- Multiple language support (RO, RU, EN)
- Test mode for development and testing

## Requirements

- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.2 or higher
- SSL certificate installed
- MIA POS merchant account

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now" and then "Activate"
5. Go to WooCommerce > Settings > Payments
6. Find "MIA POS Payment" and click "Manage"
7. Configure the plugin settings:
   - Enter your Merchant ID
   - Enter your Secret Key
   - Enter your Terminal ID
   - Configure other optional settings
8. Save changes

## Configuration

### Required Settings

- **Merchant ID**: Your unique merchant identifier (provided by MIA POS)
- **Secret Key**: Your secret key for API authentication (provided by MIA POS)
- **Terminal ID**: Your terminal identifier (provided by MIA POS)
- **API Base URL**: MIA POS API endpoint URL

### Optional Settings

- **Test Mode**: Enable for testing payments
- **Payment Type**: Choose between QR or RTP payment methods
- **Language**: Select default payment page language
- **Title**: Payment method title displayed to customers
- **Description**: Payment method description displayed to customers

## Testing

1. Enable Test Mode in plugin settings
2. Use test credentials provided by MIA POS
3. Make test purchases to verify the payment flow
4. Check order status updates
5. Verify callback handling

## Support

For support and questions, please contact:
- Website: [https://finergy.md/](https://finergy.md/)
- Email: [info@finergy.md](mailto:info@finergy.md)
