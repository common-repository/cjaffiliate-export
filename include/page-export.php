<h1><?php echo get_admin_page_title(); ?></h1>
<div class="card-box">
	<div class="inline-wrapper">
		<h2>Options</h2>
		<p></p>
		<form action="options.php" method="POST" class="cj-form">
			<?php 
				settings_fields( 'optionsExport_group' );
				$options = get_option('CJAffiliate_plugin_export');
			?>
			<label>CID
				<input type="text" name="CJAffiliate_plugin_export[cid]" value="<?php echo esc_attr( $options['cid'] ); ?>" />
			</label>
			<label>SUBID
				<input type="text" name="CJAffiliate_plugin_export[subid]" value="<?php echo esc_attr( $options['subid'] ); ?>" />
			</label>
			<label>AID
				<input type="text" name="CJAffiliate_plugin_export[aid]" value="<?php echo esc_attr( $options['aid'] ); ?>" />
			</label>
			<label>PROCESSTYPE
				<select name="CJAffiliate_plugin_export[processtype]">
					<option value="overwrite" <?php selected( $options['processtype'], 'overwrite' ); ?>>Overwrite</option>
					<option value="update" <?php selected( $options['processtype'], 'update' ); ?>>Update</option>
				</select>
			</label>
			<div class="cron-group">
				<h4>Cron options</h4>
				<label for="cron_activate">Activate cron?</label>
					<input type="checkbox" id="cron_activate" name="CJAffiliate_plugin_export[cron_activate]" value="1" <?php checked( $options['cron_activate'] ); ?>>
				
				<?php if ( isset( $options['cron_activate'] ) ): ?>
					<div class="cron-sub-group">
						<label>Set schedule
							<select name="CJAffiliate_plugin_export[cron_schedules]">
								<?php if ( $schedules = wp_get_schedules() ): ?>
									<?php foreach ($schedules as $key => $value): ?>
										<option value="<?php echo $key; ?>"  <?php selected( $options['cron_schedules'], $key ); ?> ><?php echo $value['display']; ?></option>
									<?php endforeach; ?>
								<?php endif ?>
							</select>
						</label>
						<label>Set time ( When script will be run )
							<input type="time" step="1" placeholder="HH:MM:SS" id="next_run_time" name="CJAffiliate_plugin_export[cron_schedules_time]" value="<?php echo $options['cron_schedules_time'] ?>" maxlength="8" pattern="\d{2}:\d{2}:\d{2}">
						</label>
					</div>
				<?php endif; ?>
			</div>
		<!-- 	<label>
				
			</label> -->
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
			</p>
		</form>
	</div>

	<div class="inline-wrapper">
		<h2>Transfer options</h2>
		<p></p>
		<form action="options.php" method="POST" class="cj-form js-transfer-validate">
			<?php 
				settings_fields( 'optionsExportTransfer_group' );
				$options_transfer = get_option('CJAffiliate_plugin_exportTransfer');
			?>
			<label>Host
				<input type="text" name="CJAffiliate_plugin_exportTransfer[ftp_host]" value="<?php echo esc_attr( $options_transfer['ftp_host'] ); ?>" />
			</label>
			<label>Login
				<input type="text" name="CJAffiliate_plugin_exportTransfer[ftp_login]" value="<?php echo esc_attr( $options_transfer['ftp_login'] ); ?>" />
			</label>
			<label>Password
				<input type="password" name="CJAffiliate_plugin_exportTransfer[ftp_pwd]" value="<?php echo esc_attr( $options_transfer['ftp_pwd'] ); ?>" />
			</label>		
			<label>Port
				<input type="number" name="CJAffiliate_plugin_exportTransfer[ftp_port]" value="<?php echo esc_attr( $options_transfer['ftp_port'] ); ?>" />
			</label>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
			</p>
		</form>
	</div>
	

	<h2>Export</h2>
	<p></p>
	<form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="form-cjExport js-cjExport" method="POST">
	<?php wp_nonce_field( 'exportCJ_plugin' ); ?>
		<label>Please select the file format for exporting
			<select name="file_type">
				<option value="xml">XML</option>
				<option value="csv">CSV</option>
			</select>
		</label>
		<label>Select how you want transfer export
			<select name="transfer_options" class="js-transfer-select">
				<option value="download">Download</option>
				<option value="ftp">FTP</option>
				<option value="email">Email</option>
			</select>
		</label>
		<div class="email-group">
			<input type="email" name="send_to_email" placeholder="Your Email">
		</div>
		<button type="submit" class="button button-primary js-cjExport-btn btn-success">Export</button>
		<a href="" download class="js-save-file save-file-link"><span class="dashicons dashicons-download"></span>Download</a>
		<div class="cj-loader">
			<div class="cj-spinner">
			  <div class="rect1"></div>
			  <div class="rect2"></div>
			  <div class="rect3"></div>
			  <div class="rect4"></div>
			  <div class="rect5"></div>
			</div>
		</div>
	</form>
</div>	