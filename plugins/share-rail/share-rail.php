<?php
/*
Plugin Name: Share Rail
Plugin URI: http://studio.bloafer.com/wordpress-plugins/share-rail/
Description: Use this plugin to apply floating shares to your posts and pages.
Version: 2.2
Author: Kerry James
Author URI: http://studio.bloafer.com/
*/

global $shareRail;
$shareRail = new shareRail();

class shareRail {
    var $pluginName             = "Share Rail";
    var $settingNamespace       = "share-rail";
    var $pluginNamespace        = "shareRail";
    var $version                = "2.2";
    var $gcX                    = "200";
    var $gcY                    = "003";
    var $nonceField             = "";
    var $jQueryDefaultPrefix    = "jQuery";
    var $outputs                = array();
    var $outputAreas            = array("header", "headerScript", "rail", "footer", "footerScript");
    function getPlugins(){
        $pluginPath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "plugin" . DIRECTORY_SEPARATOR;
        $itemOrder = get_option("itemorder");
        if(trim($itemOrder)!=""){
            $savedOrder = json_decode(stripslashes($itemOrder), true);
            foreach($savedOrder as $key=>$val){
                $plugins[$val] = $key;  
            }
        }
        if($handle = opendir($pluginPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $pluginFile = $pluginPath . $entry;
                    $filePathParts = explode(".", basename($pluginFile));
                    if(count($filePathParts)>=2){
                        $ext = array_pop($filePathParts);
                        $driverName = ucfirst(implode(".", $filePathParts));
                        $driverShortCode = strtolower($driverName) . "-active";
                        require_once $pluginFile;
                        $className = $this->pluginNamespace . "_" . $driverName;
                        if(class_exists($className)){
                            $tempClass = new $className;
                        }
                        if(method_exists($tempClass, "settings")){
                            $scriptSettings = $tempClass->settings();
                            if(is_array($scriptSettings)){
                                foreach ($scriptSettings as $settingName=>$settingSettings) {
                                    $this->addSetting($settingName, $settingSettings, strtolower($driverName));
                                }
                            }
                        }

                        $activePlugin = $this->getSetting($driverShortCode, strtolower($driverName));
                        if($activePlugin){
                            if(method_exists($tempClass, "enqueue_scripts")){
                                $tempClass->enqueue_scripts();
                            }
                            foreach($this->outputAreas as $outputArea){
                                if(method_exists($tempClass, $outputArea)){
                                    if($outputArea=="rail"){
                                        $plugins[strtolower($driverName)] = $tempClass->$outputArea(array(), $this);
                                    }else{
                                        $this->addContent($outputArea, $tempClass->$outputArea(array(), $this));
                                    }
                                }
                            }
                        }
                    }
                }
            }
            closedir($handle);
        }
        foreach($plugins as $plugin){
            $this->addContent("rail", $plugin);
        }
    }
    function addContent($key="rail", $content=null){
        if($content!==null){
            if(is_array($content)){
                $content = $content[0]->$content[1]();
            }
            $this->outputs[$key][] = $content;
        }
    }
    function getContent($key="rail", $implode=true){
        $rtn = "";
        if(isset($this->outputs[$key])){
            $rtn = $this->outputs[$key];
            if($implode){
                $rtn = implode(PHP_EOL, $this->outputs[$key]) . PHP_EOL;
            }
        }
        return $rtn;
    }
    function getFooterComment(){
        return "<!-- Share Rail v" . $this->version . " from Bloafer http://studio.bloafer.com/wordpress-plugins/share-rail/ (" . $this->gcX . "," . $this->gcY . ") -->" . PHP_EOL;

    }
    function getCurrentURL(){
        return "http" . (($_SERVER["SERVER_PORT"]==443)?"s":"") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    }
    private function addSetting($settingKey=false, array $args=array(), $settingCategory="settings"){
        if($settingKey){
            $this->editFields[$settingCategory][$this->getNamespaceKey($settingKey)] = $args;
        }
    }
    function getSetting($settingKey=false, $settingCategory="settings"){
        $rtn = false;
        //if(isset($this->editFields[$settingCategory][$this->getNamespaceKey($settingKey)])){
            //print $this->getNamespaceKey($settingKey) . "<br>";
            $rtn = get_option($this->getNamespaceKey($settingKey), $this->editFields[$settingCategory][$this->getNamespaceKey($settingKey)]["default"]);
        //}
        return $rtn;
    }
    private function getNamespaceKey($settingKey=false){
        return $this->settingNamespace . "-" . $settingKey;
    }
    function __construct(){
        $this->nonceField = sha1($this->pluginName . $this->version);

        $this->getPlugins();

        $this->addSetting("class-attachment", array("default"=>"#the_content", "label"=>"Element Class attachment", "type"=>"text", "description"=>"This is where the rail attaches to"));
        $this->addSetting("jquery-use-google", array("default"=>false, "label"=>"Use Google's jQuery", "type"=>"check", "description"=>"If you do not have jQuery installed you can use jQuery on Google by enabling this option"));
        $this->addSetting("jquery-prefix", array("default"=>$this->jQueryDefaultPrefix, "label"=>"jQuery prefix", "type"=>"drop", "description"=>"The jQuery prefix used for jQuery, On some installations you may need to change it to '$'", "data"=>array("$"=>"$", "jQuery"=>"jQuery")));
        $this->addSetting("show-on-pages", array("default"=>false, "label"=>"Show on pages", "type"=>"check", "description"=>"Do you want this to show on pages?"));
        $this->addSetting("show-on-posts", array("default"=>false, "label"=>"Show on posts", "type"=>"check", "description"=>"Do you want this to show on posts?"));
        $this->addSetting("show-on-homepage", array("default"=>false, "label"=>"Show on homepage", "type"=>"check", "description"=>"Do you want this to show on the homepage?"));
        $this->addSetting("vertical-offset", array("default"=>"10", "label"=>"Vertical Offset", "type"=>"text", "description"=>"How many pixels from the top of the screen do you want to start moving? default is 10"));
        $this->addSetting("custom-content", array("default"=>false, "label"=>"Custom content", "type"=>"textarea", "description"=>"You can add your own custom content to the bottom of the rail by using this box"));
        $this->addSetting("custom-css", array("default"=>false, "label"=>"Custom CSS", "type"=>"textarea", "description"=>"You can add your own CSS here"));
        $this->addSetting("google-analytics-social", array("default"=>false, "label"=>"Use Google Social Interaction Analytics", "type"=>"check", "description"=>"If you have Google Analytics installed you can use this to track social interactions"));
        $this->addSetting("debug-active", array("default"=>false, "label"=>"Debug Option", "type"=>"check", "description"=>"This option will allow Bloafer developers to debug your plugin, by default this off"));


        add_action('admin_init', array(&$this, 'hook_admin_init'));
        add_action('admin_menu', array(&$this, 'hook_admin_menu'));
        add_action('wp_footer', array(&$this, 'hook_wp_footer'));
        add_action('wp_head', array(&$this, 'hook_wp_head'));
        add_action('wp_enqueue_scripts', array(&$this, 'hook_wp_enqueue_scripts'));
    }
    function isVisible(){
        $returnVar = false;
        if(is_page() && $this->getSetting("show-on-pages")){
            $returnVar = true;
        }
        if(is_single() && $this->getSetting("show-on-posts")){
            $returnVar = true;
        }
        if(is_home() && $this->getSetting("show-on-homepage")){
            $returnVar = true;
        }
        return $returnVar;
    }
    function messageInfo($text, $type="updated"){
        return '<div id="message" class="' . $type . '"><p>' . $text . '</p></div>';
    }



    function hook_admin_menu(){
        if(current_user_can('manage_options')){
            add_menu_page('Share Rail', 'Share Rail', 7, 'share-rail/incs/settings.php', '', plugins_url('share-rail/img/share.png'));
            add_submenu_page( 'share-rail/incs/settings.php', 'Sort', 'Sort', 'manage_options', 'share-rail/incs/settings-sort.php' ); 
        }
    }
    function hook_wp_head(){
        if($this->isVisible()){
            include "incs/head.php";
        }
    }
    function hook_wp_footer(){
        if($this->isVisible()){
            include "incs/rail.php";
            include "incs/footer.php";
        }
    }
    function hook_admin_init(){
        require_once "plugin/twitter.php";
        require_once "plugin/facebook.php";
        require_once "plugin/linkedin.php";
        require_once "plugin/google.php";
        shareRail_Twitter::enqueue_scripts();
        shareRail_Facebook::enqueue_scripts();
        shareRail_Linkedin::enqueue_scripts();
        shareRail_Google::enqueue_scripts();
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('jquery-ui', plugins_url('share-rail/admin/ui/theme.css'));
    }
    function loadScript($library=false){
        if($library=="jquery"){
            wp_deregister_script( 'jquery' );
            wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js');
            wp_enqueue_script( 'jquery' );
        }
    }
    function hook_wp_enqueue_scripts() {
        $googlejQueryActive = $this->getSetting("jquery-use-google");
        wp_enqueue_script( 'jquery' );
        if($googlejQueryActive){ $this->loadScript("jquery"); }
    }    
}
?>