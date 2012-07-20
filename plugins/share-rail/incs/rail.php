<?php
$customContent = $this->getSetting("custom-content");
$railRows = $this->getContent("rail", false);

if($railRows==""){ $railRows=array(); }

if(count($railRows)>=1){
	print '<div id="shareRail">' . PHP_EOL;
	print '  <div class="railRow">' . implode('</div>' . PHP_EOL . '  <div class="railRow">', $railRows) . '</div>' . PHP_EOL;
	if(trim($customContent)!=""){
		print '  <div class="railRow">' . stripslashes($customContent) . '</div>' . PHP_EOL;
	}
	print '</div>' . PHP_EOL;
}
?>