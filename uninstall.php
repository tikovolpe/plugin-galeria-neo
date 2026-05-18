<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

delete_option( 'galeria_neo_settings' );
delete_option( 'galeria_neo_version' );
delete_transient( 'galeria_neo_update_check' );
