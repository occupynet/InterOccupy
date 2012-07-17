<?php


/* Sicherheitsabfrage */
if ( !class_exists('Antispam_Bee') ) {
	die();
}


/**
* Antispam_Bee_GUI
*
* @since  2.4
*/

class Antispam_Bee_GUI extends Antispam_Bee {
		
		
	/**
	* Speicherung der GUI
	*
	* @since   0.1
	* @change  2.4.2
	*/
	
	public static function save_changes()
	{
		/* Kein POST? */
		if ( empty($_POST) ) {
			wp_die(__('Cheatin&#8217; uh?'));
		}
		
		/* Referer prüfen */
		check_admin_referer(self::$short);
		
		/* Optionen ermitteln */
		$options = array(
			'flag_spam' 		=> (int)(!empty($_POST['ab_flag_spam'])),
			'email_notify' 		=> (int)(!empty($_POST['ab_email_notify'])),
			'cronjob_enable' 	=> (int)(!empty($_POST['ab_cronjob_enable'])),
			'cronjob_interval'	=> (int)self::get_key($_POST, 'ab_cronjob_interval'),
			
			'no_notice' 		=> (int)(!empty($_POST['ab_no_notice'])),
			
			'dashboard_count' 	=> (int)(!empty($_POST['ab_dashboard_count'])),
			'dashboard_chart' 	=> (int)(!empty($_POST['ab_dashboard_chart'])),
			'advanced_check' 	=> (int)(!empty($_POST['ab_advanced_check'])),
			'spam_ip' 			=> (int)(!empty($_POST['ab_spam_ip'])),
			'already_commented'	=> (int)(!empty($_POST['ab_already_commented'])),
			'always_allowed' 	=> (int)(!empty($_POST['ab_always_allowed'])),
			
			'ignore_pings' 		=> (int)(!empty($_POST['ab_ignore_pings'])),
			'ignore_filter' 	=> (int)(!empty($_POST['ab_ignore_filter'])),
			'ignore_type' 		=> (int)self::get_key($_POST, 'ab_ignore_type'),
			'ignore_reasons' 	=> (array)self::get_key($_POST, 'ab_ignore_reasons'),

			'honey_pot' 		=> (int)(!empty($_POST['ab_honey_pot'])),
			'honey_key'			=> sanitize_text_field(self::get_key($_POST, 'ab_honey_key')),

			'country_code' 		=> (int)(!empty($_POST['ab_country_code'])),
			'country_black'		=> sanitize_text_field(self::get_key($_POST, 'ab_country_black')),
			'country_white'		=> sanitize_text_field(self::get_key($_POST, 'ab_country_white')),

			'translate_api' 	=> (int)(!empty($_POST['ab_translate_api'])),
			'translate_lang'	=> sanitize_text_field(self::get_key($_POST, 'ab_translate_lang')),
			
			'tab_index' 		=> (int)self::get_key($_POST, 'ab_tab_index')
		);

		/* Kein Tag eingetragen? */
		if ( empty($options['cronjob_interval']) ) {
			$options['cronjob_enable'] = 0;
		}

		/* Honey Key reinigen */
		if ( !empty($options['honey_key']) ) {
			$options['honey_key'] = preg_replace(
				'/[^a-z]/',
				'',
				strtolower($options['honey_key'])
			);
		}
		if ( empty($options['honey_key']) ) {
			$options['honey_pot'] = 0;
		}

		/* Translate API */
		if ( !empty($options['translate_lang']) ) {
			if ( !preg_match('/^(de|en|fr|it|es)$/', $options['translate_lang']) ) {
				$options['translate_lang'] = '';
			}
		}
		if ( empty($options['translate_lang']) ) {
			$options['translate_api'] = 0;
		}


		/* Blacklist reinigen */
		if ( !empty($options['country_black']) ) {
			$options['country_black'] = preg_replace(
				'/[^A-Z ]/',
				'',
				strtoupper($options['country_black'])
			);
		}

		/* Whitelist reinigen */
		if ( !empty($options['country_white']) ) {
			$options['country_white'] = preg_replace(
				'/[^A-Z ]/',
				'',
				strtoupper($options['country_white'])
			);
		}

		/* Leere Listen? */
		if ( empty($options['country_black']) && empty($options['country_white']) ) {
			$options['country_code'] = 0;
		}


		/* Cron stoppen? */
		if ( $options['cronjob_enable'] && !self::get_option('cronjob_enable') ) {
			self::init_scheduled_hook();
		} else if ( !$options['cronjob_enable'] && self::get_option('cronjob_enable') ) {
			self::clear_scheduled_hook();
		}

		/* Optionen speichern */
		self::update_options($options);
		
		/* Redirect */
		wp_safe_redirect(
			add_query_arg(
				array(
					'updated' => 'true'
				),
				wp_get_referer()
			)
		);

		die();
	}
	
	
	/**
	* Der aktive Tab
	*
	* @since   2.4
	* @change  2.4
	*/
	
	private static function _tab_index()
	{
		echo ( empty($_GET['updated']) ? 0 : (int)self::get_option('tab_index') );
	}
	
	
	/**
	* Anzeige der GUI
	*
	* @since   0.1
	* @change  2.4.2
	*/

	function options_page() { ?>
		<div class="wrap" id="ab_main">
			<form action="<?php echo admin_url('admin-post.php') ?>" method="post">
				<?php $options = self::get_options() ?>
				
				<?php wp_nonce_field(self::$short) ?>
				
				<input type="hidden" name="action" value="ab_save_changes" />
				<input type="hidden" name="ab_tab_index" id="ab_tab_index" value="<?php self::_tab_index() ?>" />
				
				<?php screen_icon('ab') ?>
				
				<ul class="nav-tab-wrapper">
					<li class="ui-tabs-selected">
						<h2><a href="#ab-tab-general" class="nav-tab"><?php esc_html_e('General', self::$short) ?></a></h2>
					</li>
					<li>
						<h2><a href="#ab-tab-filter" class="nav-tab"><?php esc_html_e('Filter', self::$short) ?></a></h2>
					</li>
					<li>
						<h2><a href="#ab-tab-advanced" class="nav-tab"><?php esc_html_e('Advanced', self::$short) ?></a></h2>
					</li>
				</ul>
				
				<!-- Allgemein -->
				<div class="table ui-tabs-hide" id="ab-tab-general">
					<table class="form-table">
						<tr>
							<th>
								<label for="ab_advanced_check">
									<?php esc_html_e('Stricter inspection for comments and pings', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_advanced_check" id="ab_advanced_check" value="1" <?php checked($options['advanced_check'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_spam_ip">
									<?php esc_html_e('Consider comments which are already marked as spam', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_spam_ip" id="ab_spam_ip" value="1" <?php checked($options['spam_ip'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_already_commented">
									<?php esc_html_e('Do not check if the comment author has already approved', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_already_commented" id="ab_already_commented" value="1" <?php checked($options['already_commented'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_ignore_pings">
									<?php esc_html_e('Do not check trackbacks / pingbacks', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_ignore_pings" id="ab_ignore_pings" value="1" <?php checked($options['ignore_pings'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_always_allowed">
									<?php esc_html_e('Comment form used outside of posts', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_always_allowed" id="ab_always_allowed" value="1" <?php checked($options['always_allowed'], 1) ?> />
							</td>
						</tr>
					</table>
					
					<p class="hr"></p>

					<table class="form-table">
						<tr>
							<th>
								<label for="ab_dashboard_chart">
									<?php esc_html_e('Statistics on the dashboard', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_dashboard_chart" id="ab_dashboard_chart" value="1" <?php checked($options['dashboard_chart'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_dashboard_count">
									<?php esc_html_e('Spam counter on the dashboard', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_dashboard_count" id="ab_dashboard_count" value="1" <?php checked($options['dashboard_count'], 1) ?> />
							</td>
						</tr>
					</table>
				</div>
				
				
				<!-- Filter -->
				<div class="table ui-tabs-hide" id="ab-tab-filter">
					<!-- IP info DB -->
					<table class="form-table related">
						<tr>
							<th>
								<label for="ab_country_code">
									<?php esc_html_e('Block comments and pings from specific countries', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_country_code" id="ab_country_code" value="1" <?php checked($options['country_code'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_country_black">
									<?php esc_html_e('Blacklist', self::$short) ?> <?php esc_html_e('as', self::$short) ?> <a href="http://www.iso.org/iso/country_names_and_code_elements" target="_blank"><?php esc_html_e('iso codes', self::$short) ?></a>
								</label>
							</th>
							<td>
								<input type="text" name="ab_country_black" id="ab_country_black" value="<?php echo esc_attr($options['country_black']); ?>" class="maxi-text code" />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_country_white">
									<?php esc_html_e('Whitelist', self::$short) ?> <?php esc_html_e('as', self::$short) ?> <a href="http://www.iso.org/iso/country_names_and_code_elements" target="_blank"><?php esc_html_e('iso codes', self::$short) ?></a>
								</label>
							</th>
							<td>
								<input type="text" name="ab_country_white" id="ab_country_white" value="<?php echo esc_attr($options['country_white']); ?>" class="maxi-text code" />
							</td>
						</tr>
					</table>
					
					<p class="hr"></p>
				
					<!-- Translate API -->
					<table class="form-table related">
						<tr>
							<th>
								<label for="ab_translate_api">
									<?php esc_html_e('Allow comments only in certain language', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_translate_api" id="ab_translate_api" value="1" <?php checked($options['translate_api'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_translate_lang">
									<?php esc_html_e('Language', self::$short) ?>
								</label>
							</th>
							<td>
								<select name="ab_translate_lang" class="maxi-select">
									<?php foreach(array('de' => 'German', 'en' => 'English', 'fr' => 'French', 'it' => 'Italian', 'es' => 'Spanish') as $k => $v) { ?>
										<option <?php selected($options['translate_lang'], $k); ?> value="<?php echo esc_attr($k) ?>"><?php esc_html_e($v, self::$short) ?></option>
									<?php } ?>
								</select>
							</td>
						</tr>
					</table>
					
					<p class="hr"></p>
					
					<!-- Honey Pot -->
					<table class="form-table related">
						<tr>
							<th>
								<label for="ab_honey_pot">
									<?php esc_html_e('Search comment spammers in the Project Honey Pot', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_honey_pot" id="ab_honey_pot" value="1" <?php checked($options['honey_pot'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_honey_key">
									Project Honey Pot <a href="http://www.projecthoneypot.org/httpbl_configure.php" target="_blank">API Key</a>
								</label>
							</th>
							<td>
								<input type="text" name="ab_honey_key" id="ab_honey_key" value="<?php echo esc_attr($options['honey_key']); ?>" class="maxi-text code" />
							</td>
						</tr>
					</table>
				</div>
				
				
				<!-- Erweitert -->
				<div class="table ui-tabs-hide" id="ab-tab-advanced">
					<table class="form-table related">
						<tr>
							<th>
								<label for="ab_flag_spam">
									<?php esc_html_e('Mark as Spam, do not delete', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_flag_spam" id="ab_flag_spam" value="1" <?php checked($options['flag_spam'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_email_notify">
									<?php esc_html_e('Notification by email', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_email_notify" id="ab_email_notify" value="1" <?php checked($options['email_notify'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<?php echo sprintf(esc_html__('Spam will be automatically deleted after %s days', self::$short), '<input type="text" name="ab_cronjob_interval" value="' .esc_attr($options['cronjob_interval']). '" class="small-text" />') ?>
								<?php if ( $options['cronjob_enable'] && $options['cronjob_timestamp'] ) {
									echo sprintf(
										'<br /><small>%s @ %s</small>',
										esc_html__('Last check', self::$short),
										date_i18n('d.m.Y H:i:s', ($options['cronjob_timestamp'] + get_option('gmt_offset') * 3600))
									);
								} ?>
							</th>
							<td>
								<input type="checkbox" name="ab_cronjob_enable" id="ab_cronjob_enable" value="1" <?php checked($options['cronjob_enable'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<label for="ab_no_notice">
									<?php esc_html_e('Hide the &quot;MARKED AS SPAM&quot; note', self::$short) ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="ab_no_notice" id="ab_no_notice" value="1" <?php checked($options['no_notice'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th>
								<?php esc_html_e('Limit on', self::$short) ?> <select name="ab_ignore_type" class="mini-select"><?php foreach(array(1 => 'Comments', 2 => 'Pings') as $key => $value) {
									echo '<option value="' .esc_attr($key). '" ';
									selected($options['ignore_type'], $key);
									echo '>' .esc_html__($value). '</option>';
								} ?>
								</select>
							</th>
							<td>
								<input type="checkbox" name="ab_ignore_filter" id="ab_ignore_filter" value="1" <?php checked($options['ignore_filter'], 1) ?> />
							</td>
						</tr>
						
						<tr>
							<th style="vertical-align: top">
								<label for="ab_ignore_reasons">
									<?php esc_html_e('Delete comments by spam reasons', self::$short) ?>
									<small><?php esc_html_e('Multiple choice or deselection by pressing Ctrl/CMD', self::$short) ?></small>
								</label>
							</th>
							<td>
								<select name="ab_ignore_reasons[]" id="ab_ignore_reasons" size="2" multiple="multiple" class="maxi-select">
									<?php foreach ( self::$default['reasons'] as $k => $v) { ?>
										<option <?php selected(in_array($k, $options['ignore_reasons']), true); ?> value="<?php echo $k ?>"><?php esc_html_e($v, self::$short) ?></option>
									<?php } ?>
								</select>
							</td>
						</tr>
					</table>
				</div>

				
				<p class="submit">
					<?php if ( get_locale() == 'de_DE' ) { ?>
						<a href="http://playground.ebiene.de/antispam-bee-wordpress-plugin/" class="help" target="_blank">
							Dokumentation
						</a>
					<?php } ?>
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</form>
		</div>
	<?php }
}