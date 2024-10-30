<?php 


add_action( 'admin_init', 'cjaffiliate_registerSettings' );
function cjaffiliate_registerSettings(){
	register_setting( 'optionsExport_group', 'CJAffiliate_plugin_export', 'cjaffiliate_export_sanitize_callback' );
	register_setting( 'optionsExportTransfer_group', 'CJAffiliate_plugin_exportTransfer', 'cjaffiliate_export_sanitize_callback' );
}


function cjaffiliate_export_sanitize_callback( $options ){ 	
	if ( !isset( $options['cron_activate'] ) ) {
		wp_clear_scheduled_hook( 'cron_cj_export' );

	}else {
		foreach( $options as $name => & $val ){
		// Check cron activate and create schudule.
			if ( $name == "cron_activate" && $val == 1 ) {
			// Clear all event
				wp_clear_scheduled_hook( 'cron_cj_export' );

			// Create new event
				if ( $options['cron_schedules_time'] ) {
					$time = get_gmt_from_date( date( 'Y-m-d H:i:s', $options['cron_schedules_time'] ), 'U' ); 
				}else {
					$time = time();
				}
				$result = wp_schedule_event( $time, $options['cron_schedules'], 'cron_cj_export');
			}	
		}
		
	}

	return $options;
}	
