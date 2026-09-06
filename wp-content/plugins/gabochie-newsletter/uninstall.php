<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'gabochie_newsletter_enabled' );
delete_option( 'gabochie_newsletter_daily_cap' );
delete_option( 'gabochie_newsletter_run_cap' );
delete_option( 'gabochie_newsletter_secret' );
delete_option( 'gabochie_newsletter_unsubs' );
delete_option( 'gabochie_newsletter_log' );
delete_option( 'gabochie_newsletter_usage' );
delete_post_meta_by_key( '_gab_nl_sent' );
