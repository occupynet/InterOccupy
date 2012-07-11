<form method="post" action="options.php">


<?php settings_fields('wp_vanilla_connect_group'); ?>

<?php $options = get_option('wp_vanilla_connect_option'); ?>

	<div class="wrap">

		<div class="table-header">
		
		<?php _e('<h2>WP Vanilla Connect</h2>'); ?>
	
		<?php _e('WordPress users logon seamlessly via jsConnect and SSO into your Vanilla Forums. Just install <a href="http://vanillaforums.org/addon/jsconnect-plugin" target=0>jsConnect</a>, then copy all the links and codes from WP to Vanilla jsConnect and your linked up. Takes about 5 minutes to download and install.'); ?>
	
		<?php _e('<h4>These settings must be copied to jsConnect addon in Vanilla</h4>
			<p>You can change any of the values if you would rather make them more
				random.</p>'); ?>
		
		</div>
		
		<table class="form-table">

			<tr valign="top">
				<th scope="row">Client ID</th>
				<td><input class="textbox" type="text"
					name="wp_vanilla_connect_option[clientid]" size="10px"
					value="<?php echo $options['clientid']; ?>" /></td>
			</tr>
			
			<tr valign="top">
				<th scope="row">Secret</th>
				<td><input class="textbox" type="text"
					name="wp_vanilla_connect_option[secret]" size="40px"
					value="<?php echo $options['secret']; ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row">Vanilla URL and Cookie Settings - What is the URL of your Vanilla Forums?<br />
				<span>( http:// or https:// )</span>
				
			
				</th>
				<td><input class="textbox" type="text"
					name="wp_vanilla_connect_option[vanilla_url]" size="40px"
					value="<?php echo (isset($options['vanilla_url']) && !empty($options['vanilla_url']) ? $options['vanilla_url'] : 'http:// ( Add your Vanilla Root URL )' ); ?>" />
					<div id="wp-vanilla-connect-slash">/</div><br />Note: do not include the trailing slash
					
				
					  
					   
					   <br /><br />
			
					   <div id="form_sub_boxes">
						<div>
						
					   	<span>Name</span>
					   
					   <input class="textbox" type="text"
					   name="wp_vanilla_connect_option[vcname]" size="10px"
					   value="<?php echo (isset($options['vcname']) && !empty($options['vcname']) ? $options['vcname'] : 'Vanilla'); ?>" />
					   
				
					   
					   </div>
					   
					   
						
					   <div>
					 
					   
					  	   <span>Path</span>
					   
					   <input class="textbox" type="text"
					   name="wp_vanilla_connect_option[vcpath]" size="10px"
					   value="<?php echo (isset($options['vcpath']) && !empty($options['vcpath']) ? $options['vcpath'] : '/'); ?>" />
					
					   
					  
					   </div>
					   
					   <div>
					      
					   <span>Domain</span>
					   <input class="textbox" type="text"
					   name="wp_vanilla_connect_option[vcdomain]" size="10px"
					   value="<?php echo (isset($options['vcdomain']) ? $options['vcdomain'] : ''); ?>" />
					 
					   
					   
					   </div>
					   
					   <br style="clear:both" />
					   <strong>Vanilla Cookie Settings</strong>
					   If you don't know the cookie name and path the defaults will be used. I would leave domain blank. <br />
					   
					</div>
			
				</td>
	
			</tr>

			<tr valign="top">
				<th scope="row">Gravatar Test <br /><span>You must sign up with <a href="http://www.gravatar.com" target=0 />Gravatar.com</a> first.</span></th>
				<td><img
					src="<?php echo $this->get_gravatar_url($current_user->user_email, $options); ?>" />
					<br /> Your logged on email address is set to: <strong><?php echo $current_user->user_email; ?></strong>
					<br /> Your Gravatar URL: <strong><a href="<?php echo $this->get_current_user_gravatar(); ?>" target=0> <?php echo $this->get_current_user_gravatar(); ?></a></strong>
				</td>
			</tr>
			
			<tr valign="top">

				<th scope="row">Enable Gravatar SSL <br /> <span>(Enable if your
						Vanilla is served over SSL)</span>
				</th>
				<td><input class="checkbox"
					name="wp_vanilla_connect_option[gravatar_ssl]" type="checkbox"
					value="1"  <?php if(isset($options['gravatar_ssl'])) checked('1', $options['gravatar_ssl']); ?> /></td>
			</tr>

			<tr valign="top">
				<th scope="row">Enable Test Mode  
					<span> <br />
							When enabled you will get extended results.
					</span>
				</th>

				<td><input class="checkbox"
					name="wp_vanilla_connect_option[test_mode]" type="checkbox"
					value="1"  <?php if(isset($options['test_mode'])) checked('1', $options['test_mode'] ); ?> />

					<div>Testing WordPress: You should get a json object with your current user name and photourl.</div>
					<div id="test_response">Go ahead and run a <a id='test_link'
							href="<?php echo $this->getURL() . '?client_id=' . $this->get_clientID() ;?>"
							target=0>Test!</a></div>
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row">Protect Database Option <br /><span>Will not get deleted in case you accidently remove the plug-in</span></th>
				<td>
					<input class="checkbox"
					name="wp_vanilla_connect_option[protect]" type="checkbox"
					value="1"  <?php if(isset($options['protect'])) checked('1', $options['protect']); ?> />
				</td>
			</tr>

		</table>

		<p class="submit">
			<input type="submit" class="button-primary"
				value="<?php _e('Save Changes') ?>" /> <input type="reset"
				class="button-primary" value="<?php _e('Reset Changes') ?>" />
		</p>

</form>

<div id="wp-vanilla-connect-urls">

	<div>
	
		<?php
		_e('<h4>Authenticate URL</h4>');
		_e('This is your WP Vanilla Connect Authenticate URL');
		?>
		
		<?php echo '<pre>' . $this->getURL() . '</pre>'; ?>
	
	</div>

	<div>

		<?php
			_e('<h4>Sign-in URL</h4>'); 
			_e('This is your WordPress Login with Vanilla Redirect URL');
		?>

		<?php echo '<pre>' . $this->getRedirectURL() . '</pre>'; ?>
	
	</div>

	<div>

		<?php _e('<h4>Registration URL</h4>'); ?>

		<?php echo '<pre>' . $this->getRegisterURL() . '</pre>'; ?>
		
	</div>

	<div>

		<?php 
			_e('<h4>Vanilla URL for already logged on WordPress users</h4>'); 
			_e('Use this URL from a section of your Website if you already know your users are logged in.');
		?>
	
		<?php echo '<pre>' . $this->getVanillaAutoLoginURL() . '</pre>'; ?>
		
		<?php if(isset($this->options['embedded'])) echo "<strong>Embedded URL: </strong>This feature is not supported, so keep this in mind when you are rating its usefulness. Embedded at the time of this plugin is not supported in jsConnect or WP Vanilla Connect." ;?>
		
	</div>

</div>

	<div class="table-header">
		<?php _e('<h4>Regenerate Hashes and URL\'s</h4>'); ?>
		<?php _e('If you need to start over with new hashes. Note: This will save a new hash set.'); ?>
		
	</div>
	
<form method="post" action="">
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Regenerate and Overwrite Hashes') ?>" /> 
		<input type="hidden" name="regen" value=1 />
	</p>
</form>

</div>

