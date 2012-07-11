<?php
class Wdcab_AdminFormRenderer {

	function _get_option ($key=false, $pfx='wdcab') {
		$opts = ub_get_option($pfx);
		if (!$key) return $opts;
		return @$opts[$key];
	}

	function _create_checkbox ($name, $pfx='wdcab') {
		$opt = $this->_get_option($name, $pfx);
		$value = @$opt[$name];
		return
			"<input type='radio' name='{$pfx}[{$name}]' id='{$name}-yes' value='1' " . ((int)$value ? 'checked="checked" ' : '') . " /> " .
				"<label for='{$name}-yes'>" . __('Yes', 'wdcab') . "</label>" .
			'&nbsp;' .
			"<input type='radio' name='{$pfx}[{$name}]' id='{$name}-no' value='0' " . (!(int)$value ? 'checked="checked" ' : '') . " /> " .
				"<label for='{$name}-no'>" . __('No', 'wdcab') . "</label>" .
		"";
	}

	function _create_radiobox ($name, $value) {
		$opt = $this->_get_option($name);
		$checked = (@$opt == $value) ? true : false;
		return "<input type='radio' name='wdcab[{$name}]' id='{$name}-{$value}' value='{$value}' " . ($checked ? 'checked="checked" ' : '') . " /> ";
	}

	function create_enabled_box () {
		echo $this->_create_checkbox('enabled');
	}

	function create_title_box () {
		$value = $this->_get_option('title');
		echo "<input type='text' class='widefat' name='wdcab[title]' value='{$value}' />";
		_e('<p>If you\'d like to use an image instead of text, please paste the full URL of your image in the box (starting with <code>http://</code> - e.g. <code>http://example.com/myimage.gif</code>).</p><p>For best results, make sure your image has a transparent background and is no more than 28px high.</p>', 'ub');
	}

	function create_title_link_box () {
		$value = $this->_get_option('title_link');
		$custom_checked = true;
		$allowed = array(
			'network_site_url', 'site_url', 'admin_url'
		);
		if ('#' == $value) {
			$value = '';
			$custom_checked = false;
		}
		if (in_array($value, $allowed)) {
			$value = $value();
			$custom_checked = false;
		}
		if ($custom_checked) {
			$value = esc_url($value);
			$custom_checked = 'checked="checked"';
		}
		echo
			$this->_create_radiobox('title_link', '#') . '<label for="title_link-#">' . __('Nowhere, it is just a menu hub', 'ub') . '</label><br />'
		;
		if (is_multisite()) echo
			$this->_create_radiobox('title_link', 'network_site_url') . '<label for="title_link-network_site_url">' . __('Main site home URL', 'ub') . '</label><br />'
		;
		echo
			$this->_create_radiobox('title_link', 'site_url') . '<label for="title_link-site_url">' . __('Current site home URL', 'ub') . '</label><br />'
		;
		echo
			$this->_create_radiobox('title_link', 'admin_url') . '<label for="title_link-admin_url">' . __('Site Admin area', 'ub') . '</label><br />'
		;
		echo
			'<input type="radio" name="wdcab[title_link]" ' . $custom_checked . ' id="title_link-this_url-switch" /><label for="title_link-this_url-switch">' . __('This URL', 'ub') . ':</label> ' .
			"<input type='text' id='title_link-this_url' size='48' name='wdcab[title_link]' value='{$value}' disabled='disabled' /><br />"
		;
	}

	function create_links_box () {
		$steps = $this->_get_option('links');
		$steps = is_array($steps) ? $steps : array();

		echo "<ul id='wdcab_steps'>";
		$count = 1;
		foreach ($steps as $step) {
			echo '<li class="wdcab_step">' .
				'<h4>' .
					'<span class="wdcab_step_count">' . $count . '</span>' .
					':&nbsp;' .
					'<span class="wdcab_step_title">' . $step['title'] . '</span>' .
				'</h4>' .
				'<div class="wdcab_step_actions">' .
					'<a href="#" class="wdcab_step_delete">' . __('Delete', 'ub') . '</a>' .
					'&nbsp;|&nbsp;' .
					'<a href="#" class="wdcab_step_edit">' . __('Edit', 'ub') . '</a>' .
				'</div>' .
				'<input type="hidden" class="wdcab_step_url" name="wdcab[links][' . $count . '][url]" value="' . $step['url'] . '" />' .
				'<input type="hidden" class="wdcab_step_url_type" name="wdcab[links][' . $count . '][url_type]" value="' . $step['url_type'] . '" />' .
				'<input type="hidden" class="wdcab_step_title" name="wdcab[links][' . $count . '][title]" value="' . $step['title'] . '" />' .
			"</li>\n";
			$count++;
		}
		echo "</ul>";
		_e('<p>Drag and drop links to sort them into the order you want.</p>', 'ub');
	}

	function create_add_link_box () {
		// URL
		echo '<label for="wdcab_last_wizard_step_url">' . __('URL:', 'wdcab') . '</label><br />';
		echo '<select id="wdcab_last_wizard_step_url_type" name="wdcab[links][_last_][url_type]">';
		echo '<option value="admin">' . __('Administrative page (e.g. "post-new.php" or "themes.php")', 'ub') . '</option>';
		echo '<option value="site">' . __('Site page (e.g. "/" or "/2007-06-05/an-old-post")', 'ub') . '</option>';
		echo '<option value="external">' . __('External page (e.g. "http://www/example.com/2007-06-05/an-old-post")', 'ub') . '</option>';
		echo '</select> <span id="wdcab_url_preview">' . __('Preview:','ub') . ' <code></code></span><br />';
		echo "<input type='text' class='widefat' id='wdcab_last_wizard_step_url' name='wdcab[links][_last_][url]' /> <br />";

		// Title
		echo '<label for="wdcab_last_wizard_step_title">' . __('Title:', 'ub') . '</label>';
		echo "<input type='text' class='widefat' id='wdcab_last_wizard_step_title' name='wdcab[links][_last_][title]' /> <br />";

		echo "<input type='submit' value='" . __('Add', 'ub') . "' />";
	}

	function create_disable_box () {
		$_menus = array (
			'wp-logo' => __('WordPress menu', 'ub'),
			'site-name' => __('Site menu', 'ub'),
			'my-sites' => __('My Sites', 'ub'),
			'new-content' => __('Add New', 'ub'),
			'comments' => __('Comments', 'ub'),
			'updates' => __('Updates', 'ub'),
		);
		$disabled = $this->_get_option('disabled_menus');
		$disabled = is_array($disabled) ? $disabled : array();

		echo '<input type="hidden" name="wdcab[disabled_menus]" value="" />';
		foreach ($_menus as $id => $lbl) {
			$checked = in_array($id, $disabled) ? 'checked="checked"' : '';
			echo '' .
				"<input type='checkbox' name='wdcab[disabled_menus][]' id='wdcab-disabled_menus-{$id}' value='{$id}' {$checked}>" .
				"&nbsp;" .
				"<label for='wdcab-disabled_menus-{$id}'>{$lbl}</label>" .
			"<br />";
		}
	}
}