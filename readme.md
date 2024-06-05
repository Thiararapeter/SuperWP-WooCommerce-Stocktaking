# Superwp WooCommerce Stocktaking

## Overview

Superwp WooCommerce Stocktaking is a WordPress plugin designed to facilitate stocktaking processes for WooCommerce products. It allows users with appropriate permissions to manage stock counts, view variances, and update stock levels easily through a user-friendly interface.

## Features

- View stock on hand (SOH) and count stock (CO) for each product.
- Calculate variance (CO - SOH) to identify discrepancies.
- Filter products and search functionality for easy navigation.
- Save and update stock counts efficiently.
- Download stock data as CSV for further analysis.
- Customizable display fields and access roles.

## Installation

1. Download the plugin ZIP file from [GitHub](https://github.com/Thiararapeter/SuperWP-WooCommerce-Stocktaking).
2. Extract the ZIP file and upload the `superwp-woocommerce-stocktaking` folder to the `wp-content/plugins/` directory of your WordPress installation.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Configure access roles and display fields in the plugin settings.

## Usage

1. Access the 'Stocktaking' menu item in the WordPress admin panel.
2. Navigate through products, view current stock, and input counted stock.
3. Save or update stock counts as necessary.
4. Download stock data for further analysis if needed.

## Screenshots

### Stocktaking Page

![Stocktaking Page](screenshots/stocktaking-page.png)

### Stocktaking Settings

![Stocktaking Settings](screenshots/stocktaking-settings.png)

## Hooks and Actions

### Actions

- `SUPERWPWOO/plugin_loaded`: Fired after the plugin is successfully loaded.

### Filters

- `wc_stocktaking_access_roles`: Modify the roles that can access the stocktaking dashboard.

## Support

For support, feature requests, or bug reports, please open an issue on [GitHub](https://github.com/Thiararapeter/SuperWP-WooCommerce-Stocktaking/issues).

## Contributions

Contributions are welcome! If you'd like to contribute to the plugin, please fork the repository and submit a pull request.

## License

This plugin is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html).
