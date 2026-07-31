BOQ Construction Suite WordPress Plugin
===================================

**Version:** 1.0.0
**Author:** BuildSuite Pro (custom development)

## Description

The **BOQ Construction Suite** plugin provides a complete Bill‑of‑Quantities (BOQ) workflow for WordPress:
- CSV/Excel import of BOQ items (Excel requires the optional `phpoffice/phpspreadsheet` library).
- Automatic creation of a custom post type **BOQ Estimate**.
- Material take‑off calculations and cost estimation.
- REST API endpoint `wp-json/boq-suite/v1/import-boq` for programmatic imports.

All import actions are secured with capability checks (`manage_options`) and nonce verification, and file uploads are validated for MIME type and size (max 5 MB).

## Installation
1. Download the plugin zip (`boq-construction-suite.zip`).
2. In the WordPress admin, go to **Plugins → Add New → Upload Plugin** and select the zip.
3. Activate the plugin.
4. Navigate to **BOQ Suite → Import** in the admin menu to upload a CSV file.
5. (Optional) If you need Excel (`.xlsx/.xls`) support, install Composer dependencies on the server:
   ```bash
   cd wp-content/plugins/boq-construction-suite
   composer install --no-dev --optimize-autoloader
   ```
   The plugin will automatically detect the library and enable Excel import.

## Usage
- **Admin UI:** Use the **Import** page to upload a CSV file. The importer validates the file and creates/updates items in the `wp_boq_rates` table.
- **REST API:** Send a POST request to `wp-json/boq-suite/v1/import-boq` with a valid nonce and either `csv_text` (string) or a file upload. Only users with `manage_options` can call this endpoint.

## Security & Hardening
- Capability check (`current_user_can('manage_options')`).
- Nonce verification (`wp_verify_nonce`).
- MIME‑type & size validation for uploads (allowed: `text/csv`, `application/vnd.ms-excel`, `application/vnd.openxmlformats‑officedocument.spreadsheetml.sheet`).
- Database operations are wrapped in a transaction (`START TRANSACTION … COMMIT`).

## Internationalisation (i18n)
All user‑facing strings use `__( '…', 'boq-construction-suite' )`. A language folder exists (`languages/`). Run:
```bash
wp i18n make-pot . languages/boq-construction-suite.pot
```
to generate a POT file for translators.

## Development
- **Composer:** `composer.json` defines an optional dependency on `phpoffice/phpspreadsheet`.
- **Unit Tests:** See the `tests/` directory for PHPUnit tests.
- **Coding Standards:** PSR‑4 autoloading for classes under the `includes/` namespace.

## Changelog
- **1.0.0** – Initial release with CSV import, REST API, security hardening, i18n support.

## License
GPLv2 or later.
