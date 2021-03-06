<?php
/**
 * WP-Piwik
 * Piwik API call test script revision 3
 */

/*****************
 * CONFIGURATION *
 *****************/

// PIWIK URL, e.g. http://www.website.example/piwik
$strPiwikURL = self::$aryGlobalSettings['piwik_url'];
// PIWIK AUTH TOKEN, e.g. 1234a5cd6789e0a12345b678cd9012ef
$strPiwikAuthToken = self::$aryGlobalSettings['piwik_token'];
// YOUR BLOG'S URL, e.g. http://www.website.example
$strPiwikYourBlogURL = get_bloginfo('url');

/* That's all, stop editing! */

/**
 * Get remote file
 * 
 * @param String $strURL Remote file URL
 */
function getRemoteFile($strURL, $strToken) {
	// Use cURL if available	
	if (function_exists('curl_init')) {
		// Init cURL
		$c = curl_init($strURL.$strToken);
		// Configure cURL CURLOPT_RETURNTRANSFER = 1
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		// Configure cURL CURLOPT_HEADER = 0 
		curl_setopt($c, CURLOPT_HEADER, 0);
		// Get result
		$strResult = curl_exec($c);
		// Close connection			
		curl_close($c);
	// cURL not available but url fopen allowed
	} elseif (ini_get('allow_url_fopen'))
		// Get file using file_get_contents
		$strResult = file_get_contents($strURL.$strToken);
	// Error: Not possible to get remote file
	else $strResult = serialize(array(
			'result' => 'error',
			'message' => 'Remote access to Piwik not possible. Enable allow_url_fopen or CURL.'
		));
	// Return result
	return $strResult;
}

if (substr($strPiwikURL, -1, 1) != '/' && substr($strPiwikURL, -10, 10) != '/index.php') 
	$strPiwikURL .= '/';
		
$aryURLs = array();		
$aryURLs['SitesManager.getSitesWithAtLeastViewAccess'] = $strPiwikURL.'?module=API&method=SitesManager.getSitesWithAtLeastViewAccess&format=XML';
$aryURLs['SitesManager.getSitesIdFromSiteUrl'] = $strPiwikURL.'?module=API&method=SitesManager.getSitesIdFromSiteUrl&url='.urlencode($strPiwikYourBlogURL).'&format=XML';
$strToken = '&token_auth='.$strPiwikAuthToken;
$intTest = 0;
?>
<textarea readonly="readonly" rows="13" cols="100">
<?php
foreach ($aryURLs as $strMethod => $strURL) {
	$intTest++;
	echo '*** Test '.$intTest.'/'.count($aryURLs).': '.$strMethod.' ***'."\n";
	echo 'Call: '.$strURL.'&token_auth= + TOKEN'."\n";
	$x = microtime(true);
	$strResult = getRemoteFile($strURL,$strToken);
	$x = microtime(true) - $x;
	echo 'Result:'."\n";
	echo htmlentities($strResult)."\n";
	echo 'Time: '.round($x,2).'s'.($intTest < count($aryURLs)?"\n\n":'');
}
?>
</textarea>