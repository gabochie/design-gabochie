=== Paid Memberships Pro - Update Manager ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, update manager, update, upgrade, Add Ons, plugins
Requires at least: 5.4
Tested up to: 7.0
Stable tag: 1.0.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Manage downloads and updates for all official Paid Memberships Pro Add Ons, themes, and translation files.

== Description ==

Manage downloads and updates for all official Paid Memberships Pro Add Ons, themes, and translation files.

== Installation ==

= Prerequisites =
1. You must have Paid Memberships Pro installed and activated on your site.

= Download, Install and Activate! =
1. Download the latest version of the plugin.
1. Unzip the downloaded file to your computer.
1. Upload the /pmpro-update-manager/ directory to the /wp-content/plugins/ directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

View full documentation at: https://www.paidmembershipspro.com/add-ons/update-manager/

== Changelog ==
= 1.0.2 - 2026-07-30 =
* BUG FIX: Fixed PMPro core and Add Ons never auto-updating in the background because the update hooks only registered on `admin_init`, which does not fire during WP-Cron where automatic updates run. #18 (@dalemugford)
* BUG FIX: Fixed a potential fatal error when the Add On update check runs outside of the WordPress admin, such as during WP-Cron. #18 (@dparker1005)
* BUG FIX: Fixed a fatal error when the PMPro License Server could not be reached while Paid Memberships Pro was not active. #18 (@dparker1005)
* BUG FIX: Set `new_version` on `no_update` entries so up-to-date Add Ons no longer trigger an undefined-property warning in WP-CLI and other consumers of the update transient. #18 (@dalemugford)

= 1.0.1 - 2025-11-12 =
* BUG FIX: Fixed a deprecation warning when installing Add Ons from the Membership > Add Ons screen. #16 (@dparker1005)

= 1.0 - 2025-10-20 =
* ENHANCEMENT: Added class `PMProUM_AddOns` based off the core `PMPro_AddOns` class to better manage Add On downloads, activations, and updates. #13 (@dparker1005)

= 0.2.2 - 2025-10-15 =
* ENHANCEMENT: Added plugin row meta on the installed plugins page linking to documentation and support. #12 (@ipokkel)
* BUG FIX: Fixed an issue where theme update data would not be fetched properly and cause a fatal error on some installations. #15 (@andrewlimaza)

= 0.2.1 - 2025-04-23 =
* BUG FIX: Fixed an issue where memberlite theme would show an update when not installed.

= 0.2 - 2025-04-21 =
* FEATURE: Added support for downloading and updating Themes from the Stranger Studios servers, e.g. Memberlite.
* FEATURE: Added support for downloading and updating translations from the translate.strangerstudios.com.
* BUG FIX: Fixed fatal errors caused when the Update Manager and PMPro core both were trying to load the same functions.

= 0.1 - 2024-10-18 =
* Initial release.
