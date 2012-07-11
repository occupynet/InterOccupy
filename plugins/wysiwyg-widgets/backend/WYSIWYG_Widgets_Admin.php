<?php
if (!class_exists('WYSIWYG_Widgets_Admin')) {

    class WYSIWYG_Widgets_Admin {

        var $hook = 'wysiwyg-widgets';
        var $longname = 'WYSIWYG Widgets';
        var $shortname = 'WYSIWYG Widgets';
        var $plugin_url = 'http://dannyvankooten.com/wordpress-plugins/wysiwyg-widgets/';
        var $filename = 'wysiwyg-widgets/wysiwyg-widgets.php';
        private $version = '1.1';

        function __construct() {
            // Add settings link to plugin page
            add_filter("plugin_action_links_{$this->filename}", array(&$this, 'add_settings_link'));

            // Add DvK.com dashboard widget
            add_action('wp_dashboard_setup', array(&$this, 'widget_setup'));

            // Remove options upon deactivation
            register_deactivation_hook($this->filename, array(&$this, 'remove_options'));

            global $pagenow;

            // Only do stuff on widgets page
            if ($pagenow == 'widgets.php') {
                $this->check_usage_time();
                $this->add_hooks();
            }
        }

        /**
         * This function is called on the admin widget page
         * Adds the necessary hooks
         */
        function add_hooks() {
            #add_action("admin_head", array(&$this, "load_tiny_mce"));
            add_action('admin_print_scripts', array(&$this, "load_scripts"));
            add_action('admin_print_styles', array(&$this, 'load_styles'));

            if (isset($this->actions['show_donate_box']) && $this->actions['show_donate_box']) {
                add_action('admin_footer', array(&$this, 'donate_popup'));
            }
        }

        /**
         * Loads the necessary javascript files
         */
        function load_scripts() {
            add_thickbox();
            wp_enqueue_script('media-upload');
            wp_enqueue_script('wysiwyg-widgets', plugins_url('/backend/js/wysiwyg-widgets.js', dirname(__FILE__)), array('jquery', 'editor', 'thickbox', 'media-upload'), $this->version);
        }

        /**
         * Loads the necessary stylesheet files
         */
        function load_styles() {
            wp_enqueue_style('thickbox');
            wp_enqueue_style('wysiwyg-widgets', plugins_url('/backend/css/wysiwyg-widgets.css', dirname(__FILE__)));
        }

        /**
         * This is called when someone has been using the plugin for over 30 days
         * Renders a pop-up asking for a tweet or donation.
         */
        function donate_popup() {
            ?>
            <div id="dvk-donate-box">
                <div id="dvk-donate-box-content">
                    <img width="16" height="16" class="dvk-close" src="<?php echo plugins_url('/backend/img/close.png', dirname(__FILE__)); ?>" alt="X">
                    <h3>Like WYSIWYG Widgets?</h3>
                    <p>I noticed you've been using <?php echo $this->shortname; ?> for at least 30 days. This plugin cost me countless hours of work. If you use it, please donate a token of your appreciation!</p>

                    <form id="dvk_donate" action="https://www.paypal.com/cgi-bin/webscr" method="post">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
                               ">
                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                        <img alt="" border="0" src="https://www.paypalobjects.com/nl_NL/i/scr/pixel.gif" width="1" height="1">
                    </form>

                    <p>Alternatively, tweet about it so others find out about WYSIWYG Widgets.</p>

                    <div style="margin:10px 0; text-align:center;">
                        <a href="http://twitter.com/share" class="twitter-share-button" data-url="<?php echo $this->plugin_url; ?>" data-text="Showing my appreciation to @DannyvanKooten for his awesome #WordPress plugin: <?php echo $this->shortname; ?>" data-count="none">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
                    </div>

                    <a class="dvk-dontshow" href="widgets.php?dontshowpopup=1">(do not show me this pop-up again)</a>
                </div>
            </div>
            <?php
        }

        /**
         * Add link to DannyvanKooten.com to plugins overview page
         * @param array $links
         * @return array 
         */
        function add_settings_link($links) {
            $settings_link = '<a href="http://dannyvankooten.com">DannyvanKooten.com</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        /**
         * Check how long plugin has been installed on this blog
         * If over 30 days, show a pop-up asking for a donation
         * @return null 
         */
        function check_usage_time() {
            $opts = get_option('ww_options');

            // First-time use? (option does not exist)
            if (!$opts) {
                $opts['date_installed'] = strtotime('now');
                update_option('ww_options', $opts);
                return;
            }

            // User clicked don't show pop-up link, update option.
            if (isset($_GET['dontshowpopup']) && $_GET['dontshowpopup'] == 1) {
                $opts['dontshowpopup'] = 1;
                update_option('ww_options', $opts);
                return;
            }

            // Over 30 days? Not set to don't show? Show the damn thing.
            if (!isset($opts['dontshowpopup']) && $opts['date_installed'] < strtotime('-30 days')) {
                // plugin has been installed for over 30 days
                $this->actions['show_donate_box'] = true;
                wp_enqueue_style('dvk_donate', plugins_url('/backend/css/donate.css', dirname(__FILE__)));
                wp_enqueue_script('dvk_donate', plugins_url('/backend/js/donate.js', dirname(__FILE__)));
            }
        }

        function remove_options() {
            delete_option('ww_options');
        }

        /**
         * Adds the DvK.com dashboard widget, if user didn't remove it before.
         * @return type 
         */
        function dashboard_widget() {
            $options = get_option('dvkdbwidget');
            if (isset($_POST['dvk_removedbwidget'])) {
                $options['dontshow'] = true;
                update_option('dvkdbwidget', $options);
            }

            if (isset($options['dontshow']) && $options['dontshow']) {
                echo "If you reload, this widget will be gone and never appear again, unless you decide to delete the database option 'dvkdbwidget'.";
                return;
            }

            require_once(ABSPATH . WPINC . '/rss.php');
            if ($rss = fetch_rss('http://feeds.feedburner.com/dannyvankooten')) {
                echo '<div class="rss-widget">';
                echo '<a href="http://dannyvankooten.com/" title="Go to DannyvanKooten.com"><img src="http://static.dannyvankooten.com/images/dvk-64x64.png" class="alignright" alt="DannyvanKooten.com"/></a>';
                echo '<ul>';
                $rss->items = array_slice($rss->items, 0, 3);
                foreach ((array) $rss->items as $item) {
                    echo '<li>';
                    echo '<a target="_blank" class="rsswidget" href="' . clean_url($item['link'], $protocolls = null, 'display') . '">' . $item['title'] . '</a> ';
                    echo '<span class="rss-date">' . date('F j, Y', strtotime($item['pubdate'])) . '</span>';
                    echo '<div class="rssSummary">' . $this->text_limit($item['summary'], 250) . '</div>';
                    echo '</li>';
                }
                echo '</ul>';
                echo '<div style="border-top: 1px solid #ddd; padding-top: 10px; text-align:center;">';
                echo '<a target="_blank" style="margin-right:10px;" href="http://feeds.feedburner.com/dannyvankooten"><img src="' . get_bloginfo('wpurl') . '/wp-includes/images/rss.png" alt=""/> Subscribe by RSS</a>';
                echo '<a target="_blank" href="http://dannyvankooten.com/newsletter/"><img src="http://static.dannyvankooten.com/images/email-icon.png" alt=""/> Subscribe by email</a>';
                echo '<form class="alignright" method="post"><input type="hidden" name="dvk_removedbwidget" value="true"/><input title="Remove this widget" type="submit" value=" X "/></form>';
                echo '</div>';
                echo '</div>';
            }
        }

        function widget_setup() {
            $options = get_option('dvkdbwidget');
            if (!$options['dontshow'])
                wp_add_dashboard_widget('dvk_db_widget', 'Latest posts on DannyvanKooten.com', array(&$this, 'dashboard_widget'));
        }

        /**
         * Helper function to format text in dashboard widget
         * @param string $text
         * @param int $limit
         * @param string $finish
         * @return string 
         */
        function text_limit($text, $limit, $finish = '...') {
            if (strlen($text) > $limit) {
                $text = substr($text, 0, $limit);
                $text = substr($text, 0, - ( strlen(strrchr($text, ' ')) ));
                $text .= $finish;
            }
            return $text;
        }

    }

}