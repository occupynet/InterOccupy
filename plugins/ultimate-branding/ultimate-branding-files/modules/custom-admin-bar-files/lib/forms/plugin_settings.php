<div class="postbox">
	<h3 class="hndle" style='cursor:auto;'><span><?php _e('Custom Admin Bar','ub'); ?></span></h3>
	<div class="inside">
			<?php //settings_fields('wdcab_options'); ?>
			<?php do_settings_sections('wdcab_options'); ?>
	</div>
</div>

<div id="wdcab_step_edit_dialog" style="display:none">
	<p>
		<span>Type</span>
			<b class="widefat" id="wdcab_step_edit_dialog_url_type"></b>
	</p>
	<p>
		<label>Title</label>
			<input class="widefat" id="wdcab_step_edit_dialog_title" />
	</p>
	<p>
		<label>URL</label>
			<input class="widefat" id="wdcab_step_edit_dialog_url" />
	</p>
</div>

<style type="text/css">
.wdcab_step {
	width: 400px;
	height: 50px;
	background: #eee;
	margin-bottom: 1em;
	cursor: move;
}
.wdcab_step h4 {
	margin: 0;
	float: left;
}
.wdcab_step .wdcab_step_actions {
	float: right;
}
</style>
<script type="text/javascript">
(function ($) {
$(function () {

function titleUrlSwitch () {
	if ($("#title_link-this_url-switch").is(":checked")) $("#title_link-this_url").attr("disabled", false);
	else $("#title_link-this_url").attr("disabled", true);
}
$('[name="wdcab[title_link]"]').change(titleUrlSwitch);
titleUrlSwitch();

function updateUrlPreview () {
	var type = false;
	switch ($("#wdcab_last_wizard_step_url_type").val()) {
		case "admin": type = "<?php echo admin_url(); ?>"; break;
		case "site": type = "<?php echo site_url(); ?>"; break;
		case "external": type = ""; break;
	}
	var url = $("#wdcab_last_wizard_step_url").val();

	var preview = type + url;

	$("#wdcab_url_preview code").text(preview);

	return true;
}

$("#wdcab_steps")
	.sortable({
		"update": function () {
			$("#wdcab_steps li").each(function (idx) {
				$(this).find('h4 .wdcab_step_count').html(idx+1);
			});
		}
	})
	.disableSelection()
;

$(".wdcab_step_delete").click(function () {
	$(this).parents('li.wdcab_step').remove();
	return false;
});

$("#wdcab_last_wizard_step_url_type").change(updateUrlPreview);
$("#wdcab_last_wizard_step_url").keyup(updateUrlPreview);

$(".wdcab_step_edit").click(function () {
	var $parent = $(this).parents('li.wdcab_step');
	var $url = $parent.find('input:hidden.wdcab_step_url');
	var $title = $parent.find('input:hidden.wdcab_step_title');
	var $titleSpan = $parent.find('h4 .wdcab_step_title');

	var $urlType = $parent.find('input:hidden.wdcab_step_url_type');

	$("#wdcab_step_edit_dialog_title").val($title.val());
	$("#wdcab_step_edit_dialog_url").val($url.val());

	$("#wdcab_step_edit_dialog_url_type").text($urlType.val());

	$("#wdcab_step_edit_dialog").dialog({
		"title": $title.val(),
		"modal": true,
		"width": 600,
		"close": function () {
			$title.val($("#wdcab_step_edit_dialog_title").val());
			$titleSpan.html($("#wdcab_step_edit_dialog_title").val());
			$url.val($("#wdcab_step_edit_dialog_url").val());
		}
	});

	return false;
});

updateUrlPreview();

});
})(jQuery);
</script>