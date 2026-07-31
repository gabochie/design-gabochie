=== Gabochie Sync ===
Contributors: gabochiedesign
Tags: sync, deployment, git, push-pull, migration
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Push/pull content and code between local development and live production environments.

== Description ==

Gabochie Sync provides two-way synchronization between your local WordPress development environment and live production server.

Features:
* **Content Sync**: Push or pull posts, pages, and custom post types via REST API
* **Git Deploy**: Pull code from GitHub (or any Git remote) on your live server
* **Diff Detection**: See what's changed before syncing
* **Selective Sync**: Choose which post types to include
* **Sync Log**: Track all sync operations with timestamps
* **Application Password Auth**: Uses WordPress built-in Application Passwords for security

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/gabochie-sync/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Gabochie Sync to configure

== Changelog ==

= 1.0.0 =
* Initial release
* Content push/pull via REST API
* Git deploy integration
* Diff detection
* Admin dashboard with sync log
