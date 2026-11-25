<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
delete_option( 'ngaca_settings' ); delete_option( 'ngaca_queue' ); wp_clear_scheduled_hook( 'ngaca_cron' );
