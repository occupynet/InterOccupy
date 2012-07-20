<?php
$jQueryAttachment = $this->getSetting("class-attachment");

$googleSocialActive = $this->getSetting("analytics-social");

$verticalOffset = $this->getSetting("vertical-offset");

$jQueryPrefix = $this->getSetting("jquery-prefix");

$debug = $this->getSetting("debug-active");

if(trim($jQueryPrefix)==""){ $jQueryPrefix = $this->jQueryDefaultPrefix; }
if(trim($verticalOffset)==""){ $verticalOffset = 10; }

print $this->getFooterComment();
print $this->getContent("footer");

if($debug){ if(isset($_GET["sr"]["hook"])){ $jQueryAttachment = $_GET["sr"]["hook"]; }}
?>
<script type="text/javascript">
<?php print $jQueryPrefix ?>(document).ready(function(){
	if(<?php print $jQueryPrefix ?>("#shareRail").length>=1){
		var attachmentContainer = <?php print $jQueryPrefix ?>("<?php print $jQueryAttachment ?>");
		var shareRailOrignalTop = <?php print $jQueryPrefix ?>("#shareRail").css("top");
		var shareRailOrignalLeft = <?php print $jQueryPrefix ?>("#shareRail").css("left");
		var shareRail = <?php print $jQueryPrefix ?>("#shareRail").html();
		<?php print $jQueryPrefix ?>("#shareRail").remove();
		if(<?php print $jQueryPrefix ?>(attachmentContainer).length>=1){
			<?php print $jQueryPrefix ?>(attachmentContainer).append('<div id="shareRail" />').css("position", "relative");
			<?php print $jQueryPrefix ?>("#shareRail").html(shareRail);
			var railOffset = <?php print $jQueryPrefix ?>("#shareRail").offset();
			var attachmentContainerOffset = <?php print $jQueryPrefix ?>(attachmentContainer).offset();
			<?php print $jQueryPrefix ?>(window).scroll(function () {
				var vPos = (<?php print $jQueryPrefix ?>(window).scrollTop() - (attachmentContainerOffset.top-<?php print $verticalOffset ?>));
				if(vPos>=0){
					<?php print $jQueryPrefix ?>("#shareRail").css("top", <?php print $verticalOffset ?>).css("left", railOffset.left).css("position", "fixed");
				}else{
					<?php print $jQueryPrefix ?>("#shareRail").css("top", shareRailOrignalTop).css("left", shareRailOrignalLeft).css("position", "absolute");
				}
			});
		}
	}
	<?php print $this->getContent("footerScript"); ?>
});
</script>