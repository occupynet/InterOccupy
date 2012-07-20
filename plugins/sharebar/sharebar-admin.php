<?php
	/*  Copyright 2010  Monjurul Dolon  (email : md@devgrow.com)
	      Author Homepage: http://mdolon.com/   Author Blog: http://devgrow.com/

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as 
		published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/
	if(!function_exists('sharebar') || !is_admin()) {
		echo "You are not authorized to access this file.";
		exit();
	}
	$id = sanitize($_GET['id'] ? $_GET['id'] : $_POST['id']);
	$pos = sanitize($_GET['pos'] ? $_GET['pos'] : $_POST['pos']);
	$status = sanitize($_GET['status'] ? $_GET['status'] : $_POST['status']);
	$task = sanitize($_GET['t'] ? $_GET['t'] : $_POST['t']);
	$do = sanitize($_POST['do']);
	
	if($id)	$item = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."sharebar WHERE id=%d", $id));

	if($do == 'update') $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."sharebar SET enabled='%d', position='%d', name='%s', big='". $_POST['big'] ."', small='". $_POST['small'] ."' WHERE id='%d'", sanitize($_POST['enabled']), sanitize($_POST['position']), sanitize($_POST['name']), $id));
	elseif($do == 'add') $wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."sharebar (position, name, big, small) VALUES('%d','%s', '". $_POST['big'] ."', '". $_POST['small'] ."')", sanitize($_POST['position']), sanitize($_POST['name'])));
	elseif($do == 'delete') $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."sharebar WHERE id=%d LIMIT 1", $id));
	elseif($do == 'reset') sharebar_reset();
	elseif($do == 'settings'){
		$binaries = array("auto_posts","auto_pages","horizontal","credit");
		foreach($binaries as $binary) $_POST[$binary] = $_POST[$binary] ? 1:0;
		$_POST['width'] = $_POST['width'] ? $_POST['width']:1000;
		sharebar_settings($_POST);
		foreach($sharebar_options as $option) $$option = get_option('sharebar_'.$option);
	}elseif($do == 'update-all'){
		$buttons = $_POST['buttons'];
		$uptask = $_POST['update-task'];
		if($buttons){
			foreach ($buttons as $button)
				sharebar_update_button($button,$uptask);
			$status = "Buttons have been ".$uptask."d";
		}else
			$status = "No buttons selected.";
	}
	if($task == "linkback"){
		if($credit){
			$current = "disabled";
			update_option('sharebar_credit','0');
		}else{
			$current = "enabled";
			update_option('sharebar_credit','1');
		}
		$status = 'Linkback '.$current;
		$credit = get_option('sharebar_credit');
	}
	
	if($pos == 'moveup') $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."sharebar SET position=position-1 WHERE id='%d'", $id));
	if($pos == 'movedown') $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."sharebar SET position=position+1 WHERE id='%d'", $id));
	if($pos) $status = "Position Updated!";
?>
<style>
	.wrap { width: 700px; }
	.h4title { margin:0 0 20px;overflow:hidden; }
	.wrap form label.wide { width: 100%; float: left; padding: 2px; font-weight: bold; }
	.wrap form .text { width: 400px; }
	.wrap form .mediumtext { width: 160px; }
	.wrap form .smalltext { width: 120px; }
	.wrap form .minitext { width: 50px; margin-right: 5px; }
	.wrap form .checkbox { margin-right: 5px; }
	.wrap form .checkfield { margin: 32px 0 0 15px; }
	.wrap form p.mediumtext { margin-right: 20px; }
	.thebutton { text-align: center; overflow: hidden; background: #fff; padding: 10px; border: 1px solid #ccc; }
	.thebutton td { padding: 0 15px; }
	.thebutton .name { padding: 0 0 10px; }
	.right-button { margin: 15px 0 0 15px; }
	.info-box { width: 400px; float: left; padding: 0 10px; background: #fff; border: 1px solid #ccc; }
	.info-box-right { width: 250px; float: right; padding: 0 10px; background: #fff; border: 1px solid #ccc; }
	.info-box-right p { font-size: 11px; }
	.info-box-right ul { font-size: 11px; list-style-type: square; list-style-position: inside; }
	.info-box-right ul li { margin: 0 0 15px; }
	.sb-divider { clear: both; width: 100%: float: left; border-bottom: 5px solid #ddd; display: block; height: 20px;}

	#sharebar-tl{width:700px;margin:0;padding:0;}
	#sharebar-tl caption{width:700px;font:italic 11px "Trebuchet MS", Verdana, Arial, Helvetica, sans-serif;text-align:right;padding:0 0 5px;}
	#sharebar-tl th{font:bold 11px "Trebuchet MS", Verdana, Arial, Helvetica, sans-serif;color:#4f6b72;border-right:1px solid #C1DAD7;border-bottom:1px solid #C1DAD7;border-top:1px solid #C1DAD7;letter-spacing:2px;text-transform:uppercase;text-align:left;background:#CAE8EA no-repeat;padding:6px 6px 6px 12px;}
	#sharebar-tl th.nobg{border-top:0;border-left:0;border-right:1px solid #C1DAD7;background:none;}
	#sharebar-tl td{border-right:1px solid #C1DAD7;border-bottom:1px solid #C1DAD7;background:#fff;color:#4f6b72;padding:6px 6px 6px 12px;}
	#sharebar-tl td.alt{background:#F5FAFA;color:#797268;}
	#sharebar-tl th.spec{border-left:1px solid #C1DAD7;border-top:0;background:#fff no-repeat;font:bold 10px "Trebuchet MS", Verdana, Arial, Helvetica, sans-serif;}
	#sharebar-tl th.specalt{border-left:1px solid #C1DAD7;border-top:0;background:#f5fafa no-repeat;font:bold 10px "Trebuchet MS", Verdana, Arial, Helvetica, sans-serif;color:#797268;}
	#sharebar-tl td, #sharebar-tl th { text-align: center; }
	#sharebar-tl tr { margin-bottom: 10px; }
	#sharebar-tl tr.disabled td { background: #f2f2f2; }
	#sharebar-tl .leftj { text-align: left; }
	.sharebar-button { font-size: 11px; font-family: Verdana, Arial; padding: 2px 4px; background: #f7f7f7; color: #444; border: 1px solid #ddd; display: block; }
	.sharebar-button:hover { border-color: #aaa; }
	.FBConnectButton_Small{background-position:-5px -232px !important;border-left:1px solid #1A356E;}
	.FBConnectButton_Text{margin-left:12px !important ;padding:2px 3px 3px !important;}
</style>
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery('.toggle-all').click(function(){
		var checkboxes = jQuery('form').find(':checkbox');
		checkboxes.attr('checked', !checkboxes.attr('checked'));
		return false;
	});
});
</script>

<div class="wrap">

<?php if($status){?><div id="message" class="updated fade"><?php echo $status; ?></div><?php } ?>

<h2>Custom Sharebar</h2>

<h4 class="h4title"><div class="alignleft">By <a href="http://mdolon.com/" target="_blank">Monjurul Dolon</a> of <a href="http://devgrow.com/" target="_blank">DevGrow</a></div><div class="alignright"><a href="?page=Sharebar">Home</a> - <a href="?page=Sharebar&t=settings">Settings</a> - <a href="../wp-content/plugins/sharebar/readme.txt" target="_blank">Changelog</a> - <a href="http://devgrow.com/sharebar-wordpress-plugin/">Support</a> - <a href="?page=Sharebar&t=donate">Donate</a></div></h4>

<?php if($task == 'edit' || $task == 'new'){?>

	<h3><?php if($task == 'edit') echo "Edit"; else echo "Add New"; ?> Button</h3>
	<p>You can use HTML, Javascript or inline CSS for the button code.  Additionally, the following variables are automatically extracted from the post and can be used in your code: <strong>[url]</strong>, <strong>[title]</strong>, and <strong>[author]</strong>.</p>
	<p>If you have set your Twitter username in the <a href="?page=Sharebar&t=settings">settings</a>, that will also be available using <strong>[twitter]</strong>.</p>
	<?php
		if($task == 'edit'){
			echo '<table class="thebutton">';
			echo "<tr><th class='name'><strong>".$item->name.":</strong></th></tr>";
			echo "<tr><td>".$item->big."</td>";
			echo "<td>".$item->small."</td></tr>";
			echo '</table>';
		}
		if($item->enabled) $enabled = " checked='true'";
	?>
	<form action="?page=<?php echo $_GET['page']; ?>" method="post">
		<p class="mediumtext alignleft">
			<label for="name" class="wide">Name:</label>
			<input type="text" name="name" id="name" value="<?php echo $item->name; ?>" class="mediumtext" />
		</p>
		<p class="smalltext alignleft">
			<label for="position" class="wide">Position:</label>
			<input type="text" name="position" id="position" value="<?php echo $item->position; ?>" class="smalltext" />
		</p>
		<p class="checkfield alignleft">
			<input type="hidden" name="enabled" value="0" />
			<input type="checkbox" name="enabled" id="enabled" value="1" <?php echo $enabled; ?> /> <label for="enabled">Enabled?</label>
		</p>
		<div style="clear:both;"></div>
		<p>
			<label for="big" class="wide">Big Button:</label>
			<textarea name="big" id="big" class="text" rows=5><?php echo $item->big; ?></textarea>
		</p>
		<p>
			<label for="small" class="wide">Small Button:</label>
			<textarea name="small" id="small" class="text" rows=5><?php echo $item->small; ?></textarea>
		</p>
		<input type="hidden" name="do" value="<?php if($task == 'edit') echo "update"; else echo "add"; ?>" />
		<input type="hidden" name="id" value="<?php echo $item->id; ?>" />
		<input type="hidden" name="status" value="Share button has been <?php if($task == 'edit') echo "updated"; else echo "added"; ?>." />
		<input type="submit" value="<?php if($task == 'edit') echo "Update Button"; else echo "Add Button"; ?>" class="alignleft button-primary" />
	</form>
	<a href="?page=<?php echo $_GET['page']; ?>" class="alignleft" style="margin: 2px 0 0 10px;">Cancel</a>
		
<?php }elseif($task == 'delete'){ ?>

	<h3>Delete Button?</h3>
	<?php
		echo '<table class="thebutton">';
		echo "<tr><th class='name'><strong>".$item->name.":</strong></th></tr>";
		echo "<tr><td>".$item->big."</td>";
		echo "<td>".$item->small."</td></tr>";
		echo '</table>';
	?>
	<p>Are you sure you want to delete this button?</p>
	<form action="?page=<?php echo $_GET['page']; ?>" method="post">
		<input type="hidden" name="do" value="delete" />
		<input type="hidden" name="id" value="<?php echo $item->id; ?>" />
		<input type="hidden" name="status" value="Button has been deleted." />
		<input type="submit" value="Delete" class="alignleft button-primary" />
	</form>
	<a href="?page=<?php echo $_GET['page']; ?>" class="alignleft" style="margin: 2px 0 0 10px;">Cancel</a>
		
<?php }elseif($task == 'reset'){ ?>

	<h3>Reset Buttons?</h3>
	<p>Are you sure you want to reset <strong>ALL</strong> share buttons?  This cannot be undone and you will lose any customizations - all buttons will be reset to defaults.</p>
	<form action="?page=<?php echo $_GET['page']; ?>" method="post">
		<input type="hidden" name="do" value="reset" />
		<input type="hidden" name="status" value="All buttons have been reset to inital configuration." />
		<input type="submit" value="Reset ALL Buttons" class="alignleft button-primary" />
	</form>
	<a href="?page=<?php echo $_GET['page']; ?>" class="alignleft" style="margin: 2px 0 0 10px;">Cancel</a>
		
<?php }elseif($task == 'settings'){ ?>

	<h3>Sharebar Settings</h3>
	<form action="?page=<?php echo $_GET['page']; ?>&t=settings" method="post">
		<h4>Add Sharebar</h4>
		<p>The following settings allow you to automatically add the Sharebars to your posts and pages.  If you would like to add them manually, make sure that both are unchecked and paste the PHP code into your template instead.</p>
		<p>
			<input type="checkbox" name="auto_posts" id="auto_posts" value="true" class="checkbox" <?php if($auto_posts) echo "checked"; ?> /><label for="auto_posts">Automatically add Sharebar to posts? (only affects single posts)</label>
		</p>
		<p>
			<input type="checkbox" name="auto_pages" id="auto_pages" value="true" class="checkbox" <?php if($auto_pages) echo "checked"; ?> /><label for="auto_pages">Automatically add Sharebar to pages? (only affects pages)</label>
		</p>
		<h4>Display Options</h4>
		<p>
			<input type="checkbox" name="horizontal" value="true" id="horizontal" class="checkbox" <?php if($horizontal) echo "checked"; ?> /><label for="horizontal">Display horizontal Sharebar if the page is resized to less than <em><?php echo $width; ?>px</em>?</label>
		</p>
		<p>
			<input type="checkbox" name="credit" value="true" id="credit" class="checkbox" <?php if($credit) echo "checked"; ?> /><label for="credit">Display credit link back to the Sharebar plugin? If disabled, please consider <a href="?page=Sharebar&t=donate">donating</a>.</label>
		</p>
		<p>
			<select name="position" id="position">
				<option value="left"<?php if($position == 'left') echo " selected"; ?>>Left </option>
				<option value="right"<?php if($position == 'right') echo " selected"; ?>>Right </option>
			</select>
			<label for="position">Sharebar Position</label>
		</p>
		<p>
			<input type="text" name="leftoffset" id="leftoffset" class="minitext" value="<?php echo $leftoffset; ?>" /><label for="leftoffset">Left Offset (used when positioned to left)</label>
		</p>
		<p>
			<input type="text" name="rightoffset" id="rightoffset" class="minitext" value="<?php echo $rightoffset; ?>" /><label for="rightoffset">Right Offset (used when positioned to right)</label>
		</p>
		<p>
			<input type="text" name="width" id="width" class="minitext" value="<?php echo $width; ?>" /><label for="width">Minimum width in pixels required to show vertical Sharebar to the left of post (cannot be blank)</label>
		</p>
		<h4>Customize</h4>
		<p>
			<label for="swidth">Sharebar Width:</label>
			<input type="text" name="swidth" id="swidth" class="minitext" value="<?php echo $swidth; ?>" />
		</p>
		<p>
			<label for="twitter_username">Twitter Username:</label>
			<input type="text" name="twitter_username" id="twitter_username" class="smalltext" value="<?php echo $twitter_username; ?>" />
		</p>
		<p>
			<label for="twitter_username">Sharebar Background Color:</label>
			<input type="text" name="sbg" id="sbg" class="smalltext" value="<?php echo $sbg; ?>" />
		</p>
		<p>
			<label for="twitter_username">Sharebar Border Color:</label>
			<input type="text" name="sborder" id="sborder" class="smalltext" value="<?php echo $sborder; ?>" />
		</p>
		<br />
		<input type="hidden" name="do" value="settings" />
		<input type="hidden" name="status" value="Sharebar settings updated." />
		<input type="submit" value="Update Settings" class="alignleft button-primary" />
	</form>
	<a href="?page=<?php echo $_GET['page']; ?>" class="alignleft" style="margin: 2px 0 0 10px;">Cancel</a>
		
<?php }elseif($task == 'donate'){ ?>

	<h3>Donate</h3>
	<p>Sharebar is created by and maintained by just one person - <a href="http://twitter.com/mdolon">@mdolon</a>.  If you like the plugin, please consider donating a buck or two by clicking the button below:</p>
	<p>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_donations">
		<input type="hidden" name="business" value="mdolon@gmail.com">
		<input type="hidden" name="lc" value="US">
		<input type="hidden" name="item_name" value="Sharebar WordPress Plugin">
		<input type="hidden" name="no_note" value="0">
		<input type="hidden" name="currency_code" value="USD">
		<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest">
		<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>
	</p>

<?php }else{ ?>

	<div class="info-box">
		<p><strong>Sharebar</strong> adds a dynamic and fully customizable vertical box to the left of a blog post that contains links/buttons to popular social networking sites.</p>
		<p><strong>Big Buttons</strong> are used in the vertical Sharebar to the left of the post, while the <strong>Small Buttons</strong> are used in the horizontal Sharebar that appears under the post title (by default) if the width of the page is less than <strong><?php echo $width; ?>px</strong>.</p>
		<?php if($auto_posts || $auto_pages){
				$amsg .= "<p><strong>Auto mode is ON</strong> - Sharebar will be automatically added to ";
				if($auto_posts) $amsg .= "posts";
				if($auto_posts && $auto_pages) $amsg .= " and ";
				if($auto_pages) $amsg .= "pages";
				$amsg .= ".";
			}else
				$amsg .= "<p><strong>Auto mode is OFF</strong>, so you must manually add the following code for the horizontal and vertical bars:</p>
							<blockquote><strong>Vertical (next to post) Sharebar:</strong>
							<code>&lt;?php sharebar(); ?&gt;</code><br />
							<strong>Horizontal Sharebar:</strong>
							<code>&lt;?php sharebar_horizontal(); ?&gt;</code></blockquote>";
			echo $amsg;
		?>
		<p>You can also call an individual button in any template by using the following code (where size is either <em>big</em> or <em>small</em>):</p>
		<p><code>&lt;?php sharebar_button('name','size'); ?&gt;</code></p>
	</div>
	<div class="info-box-right">
		<h3>Support Us</h3>
		<p>If you like Sharebar and find it useful, please consider showing your support by:</p>
		<?php
			$current = $credit ? 'Disable':'Enable';
		?>
		<ul>
			<li>Link to us in the Sharebar <a href="?page=<?php echo $_GET['page']; ?>&t=linkback" class="button"><?php echo $current; ?></a></li>
			<li>Send out a <a href="http://twitter.com/home?status=Just installed the Sharebar plugin by @mdolon - check it out! http://bit.ly/c98znW" target="_blank" class="button">Tweet</a> to your friends</li>
			<li>Give us a <a href="http://wordpress.org/extend/plugins/sharebar/" target="_blank" class="button">perfect rating</a></li>
			<li>Buy us a coffee by <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2G5ZENMCH62CN" class="button">donating</a></li>
			<li>Follow us on <a href="http://twitter.com/ThinkDevGrow" target="_blank">Twitter</a> &amp; visit our <a href="http://devgrow.com/" target="_blank">blog</a></li>
		</ul>
	</div>
	<div class="sb-divider"></div>
	
	<h3 class="alignleft">Available Buttons:</h3>
	<div class="alignright">
		<a href="?page=<?php echo $_GET['page']; ?>&t=reset" class="alignleft button right-button">Reset Buttons</a><a href="?page=Sharebar&t=new" class="button-primary alignleft right-button">Add New Button</a>
	</div>
	
	<form action="?page=<?php echo $_GET['page']; ?>" method="post">
	<table id="sharebar-tl">
		<thead><tr><th><a href="/" class="toggle-all">All</a></th><th class='leftj'>Name</th><th>Position</th><th>Big Button</th><th>Small Button</th><th>Actions</th></tr></thead>
		<tbody>	
		<?php $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."sharebar ORDER BY position, id ASC")); echo "\n";
		foreach($results as $result){
			if(!$result->enabled){
				$dis = " class='disabled'";
				$name = '<em>'.$result->name.'</em>';
			}else{
				$dis = "";
				$name = $result->name;
			}
			echo "\t\t<tr$dis><td><input type='checkbox' name='buttons[]' id='buttons' value='".$result->id."' class='checkbox c23' /></td><td class='leftj'>".$name."</td><td>".$result->position."<a href='?page=Sharebar&pos=moveup&id=".$result->id."'><img src='../wp-content/plugins/sharebar/images/up.gif'/></a><a href='?page=Sharebar&pos=movedown&id=".$result->id."'><img src='../wp-content/plugins/sharebar/images/down.gif'/></a></td><td>".$result->big."</td><td>".$result->small."</td><td><a href='?page=".$_GET['page']."&t=edit&id=".$result->id."'>Edit</a> | <a href='?page=".$_GET['page']."&t=delete&id=".$result->id."'>Delete</a></td></tr>\n";
		} ?>
		</tbody>
	</table>
	<div class="alignleft">
		<p>
			<label for="update-task">With Selected:</label>
			<select id="update-task" name="update-task">
				<option value="enable">Enable</option>
				<option value="disable">Disable</option>
				<option value="delete">Delete</option>
			</select>
			<input type="hidden" name="do" value="update-all">
			<input type="submit" class="button" value="Update" />
		</p>
	</div>
	<div class="alignright">
		<p><small style="color:#aaa;">grey = disabled / white = enabled</small></p>
	</div>
	</form>
<?php } ?>

</div>