#!/bin/bash
# review-probe.sh - read-only snapshot of staging + live for the pre-Go-Live review.
P=/home/gabozvqd/wp.gabochiedesign.com
L=/home/gabozvqd/public_html

echo "########## OPTIONS ON STAGING CONTAINING localhost ##########"
wp --path=$P db query "SELECT option_id, option_name FROM wp_options WHERE option_value LIKE '%localhost%'" --allow-root
echo
echo "########## PMPro / misc domain-ish options on STAGING ##########"
wp --path=$P db query "SELECT option_name, option_value FROM wp_options WHERE option_name IN ('pmpro_domain','pmpro_license_key','pmpro_installed_version','pmpro_siteurl','siteurl','home')" --allow-root
echo
echo "########## STAGING PAGES (title | slug | status) ##########"
wp --path=$P post list --post_type=page --post_status=any --fields=ID,post_status,post_name,post_title --format=csv --allow-root
echo
echo "########## LIVE PAGES (title | slug | status) ##########"
wp --path=$L post list --post_type=page --post_status=any --fields=ID,post_status,post_name,post_title --format=csv --allow-root
echo
echo "########## UPLOADS ##########"
echo "--- staging uploads tree (top 2 levels) ---"
find $P/wp-content/uploads -maxdepth 2 -type d 2>/dev/null | sort | head -40
echo "staging uploads file count: $(find $P/wp-content/uploads -type f 2>/dev/null | wc -l)"
echo "--- live uploads tree (top 2 levels) ---"
find $L/wp-content/uploads -maxdepth 2 -type d 2>/dev/null | sort | head -40
echo "live uploads file count: $(find $L/wp-content/uploads -type f 2>/dev/null | wc -l)"
echo
echo "########## ACTIVE PLUGINS COMPARISON ##########"
echo "--- staging active ---"
wp --path=$P plugin list --status=active --field=name --allow-root | sort
echo "--- live active ---"
wp --path=$L plugin list --status=active --field=name --allow-root | sort
