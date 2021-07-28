<?php
/*
Plugin Name: up-n-running reCaptcha Extras
Version: 1.0
Plugin URI: http://www.upnrunning.co.uk
Description: Adds a simple settings screen for wp-admin and webdev wrapper tools for setting up recaptcha
Author: John Milner
Author URI: http://www.upnrunning.co.uk
Text Domain: upnrunning
*/

/*
Copyright (c) 2020, John Milner

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* CONSTANTS DEFINITIONS */
//Version Number
define( 'UNR_RECAPTURE_PLUGIN_VERSION', 1.0 ); //self expanatory
define( 'UNR_RECAPTURE_PATH', plugin_dir_path( __FILE__ ) );
define( 'UNR_RECAPTURE_URL',  plugin_dir_url( __FILE__ ) );

define( 'UNR_RECAPTURE_GOOGLE_API_URL', 'https://www.google.com/recaptcha/api/siteverify' );

define( 'UNR_RECAPTURE_VERSION_NONE',  'none' );
define( 'UNR_RECAPTURE_VERSION_V2',  'v2' );
define( 'UNR_RECAPTURE_VERSION_V2_INVISIBLE',  'v2_invisible' );
define( 'UNR_RECAPTURE_VERSION_V3',  'v3' );

define( 'UNR_RECAPTURE_THEME_JS_RELATIVE_DIR', '/plugins/upnrunning-recapatcha/assets/js' );
define( 'UNR_RECAPTURE_PLUGIN_JS_RELATIVE_DIR', '/assets/js' );
define( 'UNR_RECAPTURE_THEME_JS_DIR', get_stylesheet_directory() . UNR_RECAPTURE_THEME_JS_RELATIVE_DIR );
define( 'UNR_RECAPTURE_PLUGIN_JS_DIR', UNR_RECAPTURE_PATH . UNR_RECAPTURE_PLUGIN_JS_RELATIVE_DIR );


require_once UNR_RECAPTURE_PATH . 'admin/includes/unr_admin_settings_utils.php';

class UnrRecaptchaPlugin {
    
    public function __construct() {
        //on initialisation setup the admin menu settings page
        add_action("admin_menu", array( $this, "create_plugin_admin_settings_page" ) );
        
        //now add the settings fields to the new settings page
        add_action( 'admin_init', array( $this, "initialise_plugin_admin_settings_fields" ) );
        
        //other plugins cal just call: do_action('unr_recaptcha_enqueue_scripts');
        //to trigger the recaptcha scripts being added to header
        add_action('unr_recaptcha_enqueue_scripts',  array( $this, "enque_frontend_header_scripts" ) );
        
        //filter to add async and defer to script tag for recaptcha .js
        add_filter('script_loader_tag', array( $this, "add_async_defer_attributes_to_recaptcha_script_tag" ), 10, 2);
    }
    
    
    public function create_plugin_admin_settings_page() {
        //Create the upnrunning admin menu section if another plugin hasnt already created it
        if ( empty ( $GLOBALS['admin_page_hooks']['upnrunning-options'] ) ) {
            add_menu_page(  "upnrunning Options", /* Page Title */
                            "upnrunning", /* Menu Title */
                            "manage_options", /* Capability */
                            "recaptcha-options", /* slug */
                            array( $this, "render_plugin_admin_settings_page" ), /* Callback */
                            'data:image/svg+xml;base64,' . base64_encode( file_get_contents( UNR_RECAPTURE_PATH . 'admin/assets/img/logo.svg' ) ), /* Icon */
                            100 /* Position */);
        }
        //add the reCaptcha settings to the upnrunning admin menu section
        add_submenu_page( "upnrunning-options", /* Parent slug */
                          "reCaptcha Options", /* Page Title */
                          "reCaptcha", /* Menu Title */
                          "manage_options", /* Capability */
                          "recaptcha-options", /* slug */
                          array( $this, "render_plugin_admin_settings_page" ) /* Callback */ );
    }
    
    public function render_plugin_admin_settings_page() { ?>
        <div class="wrap">
            <h1>reCaptcha Options</h1>
            <form method="post" action="options.php">
            <?php 
                settings_fields("header_section"); /* Submenu Page Slug */
                do_settings_sections("recaptcha-options"); /* Submenu Page Slug */
                submit_button(); 
            ?>          
            </form>
        </div>
        <?php 
    }
    
    public function initialise_plugin_admin_settings_fields() {
        
        add_settings_section("header_section", /* unique identifier for the section on settings page */
                             "Default reCaptcha version to use", /* Section Title on page */
                             array( $this, "render_section_before_fields_blank" ), /* Callback */
                             "recaptcha-options" /* Submenu Page Slug */);
        add_settings_field("unr_recaptcha_version", __("Default Version"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "header_section", 
            array(
                'uid' => 'unr_recaptcha_version',
                'type' => 'select',
                //'helper' => get_option('unr_recaptcha_version'), //used for debug
                'supplimental' => 'You can implement a recaptcha field without specifying the version and it will use this default version',
                'options' => array(
                    UNR_RECAPTURE_VERSION_NONE => 'Do not use reCaptcha',
                    UNR_RECAPTURE_VERSION_V2 => 'Use v2 Standard',
                    UNR_RECAPTURE_VERSION_V2_INVISIBLE => 'Use v2 Invisible Mode',
                    UNR_RECAPTURE_VERSION_V3 => 'Use v3',
                ),
                'default' => array( 'none' )
            )                
        );
        //Tell wordpress these fields are allowed to be saved in this page section
        register_setting("header_section", "unr_recaptcha_version");     


        add_settings_section("js_section", /* unique identifier for the section on settings page */
                             "HTML &lt;head&gt; JavaScript Includes", /* Section Title on page */
                             array( $this, "render_section_header_js_files" ), /* Callback */
                             "recaptcha-options" /* Submenu Page Slug */);
        //query filesystem
        $js_external_file_options = self::get_header_js_file_options_list(UNR_RECAPTURE_PLUGIN_JS_DIR, UNR_RECAPTURE_THEME_JS_DIR);
        add_settings_field("unr_recaptcha_external_js", __("External Script to use"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "js_section",
            array (
                'uid' => 'unr_recaptcha_external_js',
                'type' => 'select',
                'options' => $js_external_file_options,
                'taginject' => 'style="width: 100%"',
                'supplimental' => 'This is the external .js file included in the &lt;head&gt; tag before the inline code.',
                'default' =>  array( 'plugin_unr-recaptcha.min.js' )
            )                
        );
        $js_inline_file_options = self::get_header_js_file_options_list(UNR_RECAPTURE_PLUGIN_JS_DIR . DIRECTORY_SEPARATOR . 'inline', UNR_RECAPTURE_THEME_JS_DIR . DIRECTORY_SEPARATOR . 'inline');
        add_settings_field("unr_recaptcha_inline_js", __("Inline JavaScript Code"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "js_section",
            array (
                'uid' => 'unr_recaptcha_inline_js',
                'type' => 'select',
                'options' => $js_inline_file_options,
                'taginject' => 'style="width: 100%"',
                'helper' => 'in [above paths]<b>' . DIRECTORY_SEPARATOR . 'inline' . DIRECTORY_SEPARATOR . '<b>*.js</b></b>',
                'supplimental' => 'This is the inline code inserted immediately after the external <b>.js</b> file above, and immediately before <b>https://www.google.com/recaptcha/api.js</b> in the &lt;head&gt; tag.',
                'default' =>  array( 'plugin_unr-recaptcha.min.js' )
            )                
        );         
        register_setting("header_section", "unr_recaptcha_external_js");
        register_setting("header_section", "unr_recaptcha_inline_js");
        
        add_settings_section("version_two_section", /* unique identifier for the section on settings page */
                             "Version 2 Only Settings", /* Section Title on page */
                             array( $this, "render_section_header_recaptcha_key_help_text" ), /* Callback */
                             "recaptcha-options" /* Submenu Page Slug */);
        add_settings_field("unr_recaptcha_site_key_v2", __("V2 Invisible Mode Site Key"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "version_two_section",
            array (
                'uid' => 'unr_recaptcha_site_key_v2',
                'type' => 'text',
                'taginject' => 'style="width: 100%"',
                'supplimental' => 'For test mode, you can use google\'s test V2 site key:<br />6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
                'default' => ''
            )                
        );
        add_settings_field("unr_recaptcha_secret_key_v2", __("V2 Invisible Mode Secret Key"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "version_two_section",
            array (
                'uid' => 'unr_recaptcha_secret_key_v2',
                'type' => 'text',
                'taginject' => 'style="width: 100%"',
                'supplimental' => 'For test mode, you can use google\'s test V2 secret key:<br />6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
                'default' => ''
            )                
        );
        add_settings_field("unr_recaptcha_error_v2_please_tick", __("Error Message: Please Tick"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "version_two_section", 
            array (
                'uid' => 'unr_recaptcha_error_v2_please_tick',
                'type' => 'text',
                'taginject' => 'style="width: 100%"',
                'default' => 'Please tick the reCAPTCHA checkbox to show us you\'re not a robot',
                'supplimental' => 'V2 Only - does not apply to V2 Invisible: When the user submits the form without ticking the checkbox'
            )                
        );
        register_setting("header_section", "unr_recaptcha_site_key_v2");
        register_setting("header_section", "unr_recaptcha_secret_key_v2");
        register_setting("header_section", "unr_recaptcha_error_v2_please_tick");
           
        
        add_settings_section("version_three_section", /* unique identifier for the section on settings page */
                             "Version 3 Only Settings", /* Section Title on page */
                             array( $this, "render_section_header_recaptcha_key_help_text" ), /* Callback */
                             "recaptcha-options" /* Submenu Page Slug */);
        add_settings_field("unr_recaptcha_site_key_v3", __("V3 Site Key"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "version_three_section", 
            array(
                'uid' => 'unr_recaptcha_site_key_v3',
                'type' => 'text',
                'taginject' => 'style="width: 100%"',
                'default' => ''
            )                
        );
        add_settings_field("unr_recaptcha_secret_key_v3", __("V3 Secret Key"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "version_three_section", 
            array(
                'uid' => 'unr_recaptcha_secret_key_v3',
                'type' => 'text',
                'taginject' => 'style="width: 100%"',
                'default' => ''
            )                
        );
        add_settings_field("unr_recaptcha_v3_trust_threshold", __("V3 Trust Threshold"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "version_three_section",
            array(
                'uid' => 'unr_recaptcha_v3_trust_threshold',
                'type' => 'number',
                'taginject' => 'min="0.0" max="1.0" step="0.1"',
                'helper' => 'Enter a decimal number between 0.0 (allow all) and 1.0 (allow only perfect score)',
                'default' => '0.6'
            )                
        );
        add_settings_field("unr_recaptcha_error_v3_score_too_low", __("Error Message: Score too low"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "version_three_section", 
            array(
                'uid' => 'unr_recaptcha_error_v3_score_too_low',
                'type' => 'text',
                'taginject' => 'style="width: 100%"',
                'default' => 'We can\'t be sure you\'re not a robot. Please try again or contact us and we will resolve this issue for you.',
                'supplimental' => 'When the trust score assigned by google is below the thresgold set above'
            )                
        );
        //Tell wordpress these fields are allowed to be saved in this page section
        register_setting("header_section", "unr_recaptcha_site_key_v3");
        register_setting("header_section", "unr_recaptcha_secret_key_v3");
        register_setting("header_section", "unr_recaptcha_v3_trust_threshold"); //Why does version_three_section not work here!?
        register_setting("header_section", "unr_recaptcha_error_v3_score_too_low");

        add_settings_section("error_messages_section", /* unique identifier for the section on settings page */
                             "User-Friendly Error Messages", /* Section Title on page */
                             array( $this, "render_section_header_settings" ), /* Callback */
                             "recaptcha-options" /* Submenu Page Slug */);
        add_settings_field("unr_recaptcha_error_misconfigured", __("Setup Miscongifured"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "error_messages_section", 
            array(
                'uid' => 'unr_recaptcha_error_misconfigured',
                'type' => 'text',
                'taginject' => 'style="width: 100%"',
                'default' => 'This form relies on Google Recaptcha, which appears to be misconfigured. Please try again or contact us quoting the following error and we will resolve this issue for you:<br />[MISCONFIGURATION_REASON]',
                'supplimental' => 'Generally happens when the developer has misconfigured something<br />Use placeholder [MISCONFIGURATION_REASON] to get more info'
            )              
        );     
        add_settings_field("unr_recaptcha_error_other", __("Other Error Code"), array( $this, "render_setting_default_callback" ), "recaptcha-options", "error_messages_section", 
            array(
                'uid' => 'unr_recaptcha_error_other',
                'type' => 'text',
                'taginject' => 'style="width: 100%"',
                'default' => 'Something went wrong verifying reCaptcha this form. Please try again or contact us quoting "[GOOGLE_ERROR_CODE]" and we will resolve this issue for you.',
                'supplimental' => 'When google returns an error code not handled above<br />Use placeholder [GOOGLE_ERROR_CODE] to get exact error code'
            )              
        );
        register_setting("header_section", "unr_recaptcha_error_misconfigured");
        register_setting("header_section", "unr_recaptcha_error_other");

        
        
    }
    
    public function render_section_header_recaptcha_key_help_text() {
        echo __('<p>You need to <a target="_blank" href="https://www.google.com/recaptcha/admin" rel="external">register your domain</a> and get keys to make this plugin work.<br />Enter the key details below</p>');
    }
    public function render_section_header_settings() {
        echo __('<p>When validation runs on the server side and fails for some reason, this is the text the user will see on the front end<br />[PLACEHOLDERS] are available for some of these errors</p>');
    }
    public function render_section_header_js_files() {            
            echo __('<p>This plugin can use standard <b>.js</b> files from the core plugin or bespoke ones from your theme should you need to.<br />By default, use the plugin\'s <b>.min.js</b> option as it\'s most efficient. Use the plugin\'s <b>.js</b> file if you\'re debugging and want to step through the code, or use your own custom <b>.js</b> or <b>.min.js</b> files in your theme directory to gain full control.<br /><br />' );
            $docRoot = get_home_path();
            $plugin_dir = UNR_RECAPTURE_PLUGIN_JS_DIR;
            if (substr($plugin_dir, 0, strlen($docRoot)) === $docRoot) {
                $plugin_dir = substr($plugin_dir, strlen($docRoot));
            }
            echo __('<b>Plugin js dir : </b>' . $plugin_dir. DIRECTORY_SEPARATOR . '<b>*.js</b><br /><br />' );
            $theme_dir = UNR_RECAPTURE_THEME_JS_DIR;
            if (substr($theme_dir, 0, strlen($docRoot)) === $docRoot) {
                $theme_dir = substr($theme_dir, strlen($docRoot));
            }
            echo __('<b>Theme js dir: </b>' . $theme_dir . DIRECTORY_SEPARATOR . '<b>*.js</b><br />');
            echo __('<b>NOTE: </b>If you use a theme file then change the theme and your file is no longer found, it reverts to the plugin directory\'s <b>.min.js</b> file </p>');
    }
    public function render_section_before_fields_blank() {
    }
    
    private static function get_header_js_file_options_list( $pluginDir, $themeDir ) {  
        $js_file_options = array();
        //search plugin dir for .min.js files then for .js files
        if ( file_exists( $pluginDir ) ) {
            foreach (glob($pluginDir  . "/*.min.js") as $filename) {
                $js_file_options['plugin_'.basename($filename)] = 'Plugin: ' . basename($filename);
            } //.min.js files first
            foreach (array_filter( glob($pluginDir  . "/*.js"), function($v){return substr_compare($v,'.min.js',-7)!==0;} ) as $filename) {
                $js_file_options['plugin_'.basename($filename)] = 'Plugin: ' . basename($filename);
            } //Then all js files that are not .min.js
        }
        //now search theme dir for .min.js files then for .js files
        if ( file_exists( $themeDir ) ) {
            foreach (glob($themeDir  . "/*.min.js") as $filename) {
                $js_file_options['theme_'.basename($filename)] = 'Theme: ' . basename($filename);
            } //.min.js files first
            foreach (array_filter( glob($themeDir  . "/*.js"), function($v){return substr_compare($v,'.min.js',-7)!==0;} ) as $filename) {
                $js_file_options['theme_'.basename($filename)] = 'Theme: ' . basename($filename);
            } //Then all js files that are not .min.js
        }
        return $js_file_options;
    }
    
    public function render_setting_default_callback( $arguments )
    {
        UnrAdminSettingsUtils::render_field_callback( $arguments );
    }
    
    public static function get_recaptcha_version() {
        $versionOption = get_option( "unr_recaptcha_version" );
        $version = UNR_RECAPTURE_VERSION_NONE;
        if( !empty($versionOption) ) {
            $version = $versionOption;
        }
        return $version;
    }    
    
    public static function site_is_using_recaptcha() {
        return self::get_recaptcha_version() !== UNR_RECAPTURE_VERSION_NONE;
    }
    
    //called on do_action('unr_recaptcha_enqueue_scripts') from anywhere
    //see contstructor for where this is configured
    public function enque_frontend_header_scripts()
    {
        if( self::get_recaptcha_version() === UNR_RECAPTURE_VERSION_V2 || self::get_recaptcha_version() === UNR_RECAPTURE_VERSION_V2_INVISIBLE || self::get_recaptcha_version() === UNR_RECAPTURE_VERSION_V3 )
        {
            //we use explicit render method here
            $externalScriptUri = self::get_js_file_full_path( 'unr_recaptcha_external_js', UNR_RECAPTURE_PLUGIN_JS_DIR, UNR_RECAPTURE_THEME_JS_DIR, true );
            $inlineScript = file_get_contents( self::get_js_file_full_path( 'unr_recaptcha_inline_js', UNR_RECAPTURE_PLUGIN_JS_DIR . DIRECTORY_SEPARATOR . 'inline', UNR_RECAPTURE_THEME_JS_DIR . DIRECTORY_SEPARATOR . 'inline', false ) );
            
            $fullInlineBeforeScript = "var unr_recaptcha_site_key_v2 = '" . get_option('unr_recaptcha_site_key_v2') . "';\r\n";
            $fullInlineBeforeScript .= "var unr_recaptcha_site_key_v3 = '" . get_option('unr_recaptcha_site_key_v3') . "';\r\n";
            //$fullInlineBeforeScript .= $externalScript . "\n\n";
            $fullInlineBeforeScript .= $inlineScript . "\n";            
            
            wp_register_script("unr_recaptcha", $externalScriptUri, array(), UNR_RECAPTURE_PLUGIN_VERSION );
            wp_enqueue_script("unr_recaptcha");
            wp_register_script("recaptcha", "https://www.google.com/recaptcha/api.js?onload=unr_onloadCallback&render=explicit", array('unr_recaptcha'));
            wp_add_inline_script('recaptcha', $fullInlineBeforeScript, 'before');
            wp_enqueue_script("recaptcha");
        } 
    }
    
    //called on do_action('unr_recaptcha_enqueue_scripts') from anywhere
    //see contstructor for where this is configured
    private static function get_js_file_full_path( $option_name, $plugin_dir, $theme_dir, $getURINotFilePath = false )
    {
        $full_path = null;
        $uri_path = null;
        $optionVal = get_option($option_name);
        $attempts = 0; //first pass from get_option second pass from filesystem
        while( $attempts < 2 && !isset( $full_path ) && isset($optionVal) )
        {
            if (substr($optionVal, 0, 7 ) === 'plugin_') {
                $full_path = $plugin_dir . DIRECTORY_SEPARATOR . substr($optionVal, 7);
                $uri_path = rtrim(UNR_RECAPTURE_URL, '/') . UNR_RECAPTURE_PLUGIN_JS_RELATIVE_DIR . DIRECTORY_SEPARATOR . substr($optionVal, 7);
            }
            elseif (substr($optionVal, 0, 6 ) === 'theme_') {
                $full_path = $theme_dir . DIRECTORY_SEPARATOR . substr($optionVal, 6);
                $uri_path = get_stylesheet_directory_uri() . UNR_RECAPTURE_THEME_JS_RELATIVE_DIR . DIRECTORY_SEPARATOR . substr($optionVal, 6);
            }
            if( !file_exists( $full_path ) ) {
                $full_path = null;
                $uri_path = null;
                $optionVal = array_key_first( self::get_header_js_file_options_list($plugin_dir, $theme_dir) ); //first key is plugin's .min.js from filesystem or null if plugin dir is empty
            }
            $attempts++;
        }
        return $getURINotFilePath ? $uri_path : $full_path;
    }
    
    //this is the hack you have to do in wordpress to get the recaptcha js
    //include in the header to include async and defer in the script tag :(
    //in plugin's constructior this is hooked in with this code:
    //add_filter('script_loader_tag', array( $this, "add_async_defer_attributes_to_recaptcha_script_tag" ), 10, 2);
    function add_async_defer_attributes_to_recaptcha_script_tag($tag, $handle) {
        if ( 'recaptcha' !== $handle ) {
            return $tag;
        }
        return str_replace( ' src', ' async defer src', $tag );
    }
    
    /** *generates HTML required to add a v2 Non-Invisible recaptcha to the page. This HTML should be used inside the form tag.
     * 
     * @param $instance_name string A unique text string with no special characters used in the html element's name attribute, also used when validating server side so we which one to validate should there be more than one on the page. these must be unique for each captcha on any 1 page to avoid conflicts but two recaptchas on different pages can have the same instance name
     * @param $darkmode boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $compact_size boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible    
     * @param $tabindex boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @return string The HTML ready to output to the page in order to generate this reCaptcha (the actual rendering is done onload in the header js files )
     * @access public 
     */
    public static function render_frontend_div_html_v2( $instance_name, $darkmode = false, $compact_size = false, $tabindex = null ) {
        return self::render_frontend_div_html(UNR_RECAPTURE_VERSION_V2, $instance_name, null, false, null, null, $darkmode, $compact_size, $tabindex, null );
    }
    
    /** *generates HTML required to add a v2 Invisible recaptcha which is bound to a button (executed when submit button clicked as apposed to being executed programatically by js code) to the page. 
     * 
     * @param $instance_name string A unique text string with no special characters used in the html element's name attribute, also used when validating server side so we which one to validate should there be more than one on the page. these must be unique for each captcha on any 1 page to avoid conflicts but two recaptchas on different pages can have the same instance name
     * @param $existing_button_id_to_bind_to The HTML id of (eg &lt;input id="" .....&gt; ) the button which submits the form, this recaptcha will be executed when the button is clicked. if it has no id you cannot set the id of the button then please use render_frontend_div_html_v2_invisible_bound_programatically
     * @param $form_name string The name of the form we are submitting this recaptcha's data to. this recaptcha should be place inside the html form. if the form has no name="" attribute and you cant add one then use the form's id, or failing that use a class name owned by the form SO LONG AS no other forms have the same class!
     * @param $darkmode boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $tabindex boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $badge_position boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @return string The HTML ready to output to the page in order to generate this reCaptcha (the actual rendering is done onload in the header js files )
     * @access public 
     */
    public static function render_frontend_div_html_v2_invisible_bound_to_existing_button( $instance_name, $existing_button_id_to_bind_to, $darkmode = false, $tabindex = null, $badge_position = null ) {
        return self::render_frontend_div_html(UNR_RECAPTURE_VERSION_V2_INVISIBLE, $instance_name, $existing_button_id_to_bind_to, false, null, null, $darkmode, false, $tabindex, $badge_position );
    }
    
    /** *generates HTML required to add a v2 Invisible recaptcha which has to be executed programmatically (as opposed to being bound to a button) to the page. 
     * 
     * @param $instance_name string A unique text string with no special characters used in the html element's name attribute, also used when validating server side so we which one to validate should there be more than one on the page. these must be unique for each captcha on any 1 page to avoid conflicts but two recaptchas on different pages can have the same instance name
     * @param $auto_bind_to_form boolean if you are binding this element to the form (eg by adding  onsubmit="return unrExecuteRecaptchaIfManual(event, instanceName)"  to the form tag) then set this to false, if you want the program to automatically add the necessary code to the forms' onsubmit on page load then set to true. if this doesn't work try doing it manually
     * @param $form_name string The name of the form we are submitting this recaptcha's data to. this recaptcha should be place inside the html form. if the form has no name="" attribute and you cant add one then use the form's id, or failing that use a class name owned by the form SO LONG AS no other forms have the same class!
     * @param $darkmode boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $tabindex boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $badge_position boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @return string The HTML ready to output to the page in order to generate this reCaptcha (the actual rendering is done onload in the header js files )
     * @access public 
     */
    public static function render_frontend_div_html_v2_invisible_bound_programatically( $instance_name, $auto_bind_to_form, $form_name, $darkmode = false, $tabindex = null, $badge_position = null ) {
        return self::render_frontend_div_html(UNR_RECAPTURE_VERSION_V2_INVISIBLE, $instance_name, null, $auto_bind_to_form, $instance_name, $form_name, $darkmode, false, $tabindex, $badge_position );
    }

    /** *generates HTML required to add a v3 recaptcha to the page. together with defining the 'form' action handle should it be bound to a form
     * 
     * @param $instance_name string A unique text string with no special characters used in the html element's name attribute, also used when validating server side so we which one to validate should there be more than one on the page. these must be unique for each captcha on any 1 page to avoid conflicts but two recaptchas on different pages can have the same instance name
     * @param $auto_bind_to_form boolean v3 recaptchas do not have to be bound to a form and can be used for any event. If you are binding this element to a form however (eg by adding  onsubmit="return unrExecuteRecaptchaIfManual(event, instanceName)"  to the form tag) then set this to false, if you want the program to automatically add the necessary code to the forms existing onsubmit on page load then set to true. if this doesn't work try setting to false and doing it manually
     * @param $form_name string optional v3 recaptchas do not have to be bound to a form and can be executed manually any time.If you dont want to bind to  form then set null. but if you do want to bind to a form however then enter The name of the form we are submitting this recaptcha's data to. this recaptcha should be place inside the html form. if the form has no name="" attribute and you cant add one then use the form's id, or failing that use a class name owned by the form SO LONG AS no other forms have the same class!
     * @param $darkmode boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $tabindex boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $badge_position boolean Optional -  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @return string The HTML ready to output to the page in order to generate this reCaptcha (the actual rendering is done onload in the header js files )
     * @access public 
     */       
    public static function render_frontend_div_html_v3( $instance_name, $auto_bind_to_form, $action_name, $form_name, $darkmode = false, $compact_size = false, $tabindex = null ) {
        return self::render_frontend_div_html(UNR_RECAPTURE_VERSION_V3, $instance_name, null, $auto_bind_to_form, $action_name, $form_name, $darkmode, $compact_size, $tabindex, null );
    }

    /** *Looks at the default recaptcha type setting then generates HTML required to add a recaptcha of that type to the page. Will generate empty string if default setting is 'Do not use recaptcha' and not all parameters are used for all recaptcha types.
     * 
     * @param $instance_name string A unique text string with no special characters used in the html element's name attribute, also used when validating server side so we which one to validate should there be more than one on the page. these must be unique for each captcha on any 1 page to avoid conflicts but two recaptchas on different pages can have the same instance name
     * @param $existing_button_id_to_bind_to_v2i string (V2Invisible only) The HTML id (eg &lt;input id="" .....&gt; ) of the button which submits the form, if this param is set then this recaptcha will be executed when the button is clicked. if the button you want to bind to has no id and you cannot set the id of the button, then leave as null and you can bound to the form programmatically in the same way you do for v3 recaptchas
     * @param $auto_bind_to_form_v2i_v3 boolean For v3 widgets and non-button-bound v2 Invisible widgets . V3 widgets do not have to be bound to a form, v2 Invisible widgets do! when this widget does need to be bound to a form you can do it manually (eg by adding  onsubmit="return unrExecuteRecaptchaIfManual(event, instanceName)"  to the form tag) then set this param to false, if you want the program to automatically bind by adding additional code to the forms' existing onsubmit on page load then set to true. if this doesn't work try setting to false and binding it manually
     * @param $action_name_v3 string V3 only. Only used when autobinding. Any v3 action action needs to be a unique string for each validated action that can be performed on the page. IF you are binding this recaptcha to a form then put the action handle for the form's action here (no need to mention other non-form actions here as these are handled manually elsewhere on the page). One v3 recaptcha can be used in more than one place on the same page but each use must have its own action handle. See google docs: https://developers.google.com/recaptcha/docs/v3
     * @param $form_name_v2i_v3 string (optional) The name of the form we are submitting this recaptcha's data to. This recaptchas html (returned by this function) should ideally be placed inside the html form, in which you can set $form_name_v2i_v3 to null as it knows which form to use. If however you want to place it outside the form you want to bind it to, then you MUST set $form_name_v2i_v3. If set, and the form has no name="..." attribute (and if you cant add one) then use the form's id, or failing that use a class name belonging to the form SO LONG AS no other forms on the page have the same class!
     * @param $darkmode boolean Optional - See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $compact_size_v2 boolean Optional (V2 Only) - See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible    
     * @param $tabindex boolean Optional - See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @param $badge_position_v2i_v3 boolean Optional (V2 Invisible and V3 Only)-  See grecaptcha.render parameters here: https://developers.google.com/recaptcha/docs/invisible
     * @return string The HTML ready to output to the page in order to generate this reCaptcha (the actual rendering is done onload in the header js files )
     * @access public 
     */    
    public static function render_frontend_div_html_use_settings_version( $instance_name, $existing_button_id_to_bind_to_v2i, $auto_bind_to_form_v2i_v3, $action_name_v3, $form_name_v2i_v3, $darkmode = false, $compact_size_v2 = false, $tabindex = null, $badge_position_v2i_v3 = null ) {
        return self::render_frontend_div_html(self::get_recaptcha_version(), $instance_name, $existing_button_id_to_bind_to_v2i, $auto_bind_to_form_v2i_v3, $action_name_v3, $form_name_v2i_v3, $darkmode, $compact_size_v2, $tabindex, $badge_position_v2i_v3 );
    }   

    private static function render_frontend_div_html( $version, $instance_name, $existing_button_id_to_bind_to_v2i, $auto_bind_to_form_v2i_v3, $action_name_v3, $form_name_v2i_v3, $darkmode, $compact_size_v2, $tabindex, $badge_position_v2i_v3 ) {
        //sanitize arguments to be used in HTML rendering
        if( esc_attr( $instance_name ) !== $instance_name ) { 
            throw new InvalidArgumentException('$instance_name must be a valid html attribute value, not: ' . esc_attr( $instance_name ) ); 
        }
        if( isset( $existing_button_id_to_bind_to_v2i ) && esc_attr( $existing_button_id_to_bind_to_v2i ) !== $existing_button_id_to_bind_to_v2i ) { 
            throw new InvalidArgumentException('$existing_button_id_to_bind_to_v2i must be a valid html attribute value, not: ' . esc_attr( $existing_button_id_to_bind_to_v2i ) ); 
        }
        if( isset( $action_name_v3 ) && esc_attr( $action_name_v3 ) !== $action_name_v3 ) { 
            throw new InvalidArgumentException('$action_name_v3 must be a valid html attribute value, not: ' . esc_attr( $action_name_v3 ) ); 
        }
        if( isset( $form_name_v2i_v3 ) && esc_attr( $form_name_v2i_v3 ) !== $form_name_v2i_v3 ) { 
            throw new InvalidArgumentException('$form_name_v2i_v3 must be a valid html attribute value, not: ' . esc_attr( $form_name_v2i_v3 ) ); 
        }

        //we use explicit render method in this implementation
        $programmatically_executed = false;
        $recaptcha_div_name = null;
        $hdn_token_field_html = '';
        if( $version === UNR_RECAPTURE_VERSION_V2 )
        {
            $existing_button_id_to_bind_to_v2i = null; //may be passed in by render_frontend_div_html_use_settings_version even if we're not using v2i
            $recaptcha_div_name = 'unr_v2_recaptcha_div_'.$instance_name;
        }
        elseif( $version === UNR_RECAPTURE_VERSION_V2_INVISIBLE )
        {
            if( !isset( $existing_button_id_to_bind_to_v2i) ) {
                $programmatically_executed = true;
                $recaptcha_div_name = 'unr_v2i_recaptcha_div_'.$instance_name;
                $hdn_token_field_html = '<input type="hidden" name="unr_recaptcha_token_'.$instance_name.'" value="" />';
            }
        }
        elseif( $version === UNR_RECAPTURE_VERSION_V3 )
        {
            $existing_button_id_to_bind_to_v2i = null; //may be passed in by render_frontend_div_html_use_settings_version even if we're not using v2i
            $programmatically_executed = true;
            $recaptcha_div_name = 'unr_v3_recaptcha_div_'.$instance_name;
            $hdn_token_field_html = '<input type="hidden" name="unr_recaptcha_token_'.$instance_name.'" value="" />';
        }
        $recaptcha_div_html = ( $recaptcha_div_name === null ? '' : '<div id="'.$recaptcha_div_name.'"></div>' );
        $hdn_settings_field_html = self::render_frontend_hidden_html($version, $instance_name, $recaptcha_div_name, $existing_button_id_to_bind_to_v2i, $auto_bind_to_form_v2i_v3, $action_name_v3, $form_name_v2i_v3, $darkmode, $compact_size_v2, $tabindex, $badge_position_v2i_v3, $programmatically_executed );
        return $recaptcha_div_html . $hdn_token_field_html . $hdn_settings_field_html;
    }
    
    private static function render_frontend_hidden_html( $version, $instance_name, $recaptcha_div_name, $existing_button_id_to_bind_to_v2i, $auto_bind_to_form_v2i_v3, $action_name_v3, $form_name, $darkmode, $compact_size, $tabindex, $badge_position, $programmatically_executed ) {
        $hdn_value = sprintf("%s&quot;%s&quot;%s&quot;%s&quot;%d&quot;%s&quot;%s&quot;%s&quot;%s&quot;%s&quot;%s&quot;%d", 
                $version,
                $instance_name,
                isset( $recaptcha_div_name ) ? $recaptcha_div_name : '',
                isset( $existing_button_id_to_bind_to_v2i ) ? $existing_button_id_to_bind_to_v2i : '',
                $auto_bind_to_form_v2i_v3,
                isset( $action_name_v3 ) ? $action_name_v3 : '',
                isset( $form_name ) ? $form_name : '',
                ( $darkmode ? 'dark' : '' ), 
                ( $compact_size ? 'compact' : '' ), 
                isset( $tabindex ) ? $tabindex : '',
                isset( $badge_position ) ? $badge_position : '',
                $programmatically_executed );
        return '<input type="hidden" name="unr_recaptcha_settings" value="' . $hdn_value . '" />';
    }

    /*
    public static function render_frontend_div_html_v2( $instance_name, $darkmode = false, $compact_size = false, $tabindex = null ) {
        return self::render_frontend_div_html(UNR_RECAPTURE_VERSION_V2, $instance_name, null, null, $darkmode, $compact_size, $tabindex, null );
    }
    
    public static function render_frontend_div_html_v2_invisible_bound_to_existing_form_element( $instance_name, $existing_button_to_bind_to_name, $form_name, $darkmode = false, $tabindex = null, $badge_position = null ) {
        return self::render_frontend_div_html(UNR_RECAPTURE_VERSION_V2_INVISIBLE, $instance_name, $existing_button_to_bind_to_name, $form_name, $darkmode, false, $tabindex, $badge_position );
    }
    
    public static function render_frontend_div_html_v2_invisible_programmatically_executed( $instance_name, $form_name, $darkmode = false, $tabindex = null, $badge_position = null ) {
        return self::render_frontend_div_html(UNR_RECAPTURE_VERSION_V2_INVISIBLE, $instance_name, null, $form_name, $darkmode, false, $tabindex, $badge_position );
    }
    
    public static function render_frontend_div_html_v3( $instance_name, $form_name, $darkmode = false, $compact_size = false, $tabindex = null ) {
        return self::render_frontend_div_html(UNR_RECAPTURE_VERSION_V3, $instance_name, null, null, $darkmode, $compact_size, $tabindex, null );
    }
    */
    
    public static function validate_recaptcha_response_use_settings_version( $instance_name, $programaticallyExecutedNotBound_v2i, &$errorStringArray ) {
        return self::validate_recaptcha_response(self::get_recaptcha_version(), $instance_name, $programaticallyExecutedNotBound_v2i, $errorStringArray );
    }   
  
    public static function validate_recaptcha_response( $version, $instance_name, $programaticallyExecutedNotBound_v2i, &$errorStringArray )
    {

        $recaptcha_secret_key = null;
        $fieldName = null;
        if( $version === UNR_RECAPTURE_VERSION_V2 || $version === UNR_RECAPTURE_VERSION_V2_INVISIBLE ) {        
            $recaptcha_secret_key = get_option('unr_recaptcha_secret_key_v2');
            $fieldName = ( $version === UNR_RECAPTURE_VERSION_V2_INVISIBLE && $programaticallyExecutedNotBound_v2i ) ? 'unr_recaptcha_token_'.$instance_name : 'g-recaptcha-response';
        }
        elseif( $version === UNR_RECAPTURE_VERSION_V3 )
        {
            $recaptcha_secret_key = get_option('unr_recaptcha_secret_key_v3');
            $fieldName = 'unr_recaptcha_token_'.$instance_name;
        }
        
        $recaptchaFieldPOSTValue = filter_input(INPUT_POST, $fieldName, FILTER_SANITIZE_STRING);
        if (isset( $recaptchaFieldPOSTValue ) && !empty( $recaptcha_secret_key ) ) {
            
            if( $version === UNR_RECAPTURE_VERSION_V2 && empty( $recaptchaFieldPOSTValue ) ) {
                $errorStringArray[] = get_option('unr_recaptcha_error_v2_please_tick');
                return false;
            }
            
            $responseRaw = wp_remote_get( UNR_RECAPTURE_GOOGLE_API_URL . "?secret=". $recaptcha_secret_key ."&response=". $recaptchaFieldPOSTValue);
            $response = json_decode($responseRaw["body"], true);
            if (true == $response["success"]) {
                //v2 and v2i - it worked, v3 - one last check
                if ( $version === UNR_RECAPTURE_VERSION_V3 && $response['score'] < floatval( get_option('unr_recaptcha_v3_trust_threshold') ) ) {
                    $errorStringArray[] = get_option('unr_recaptcha_error_v3_score_too_low');
                    return false;
                }
            } else {
                $errorStringArray[] = str_replace("[GOOGLE_ERROR_CODE]", implode ( ", " , $response["error-codes"] ), get_option('unr_recaptcha_error_other'));
                return false;
            }
        }
        else {
            $error_reason = empty( $recaptcha_secret_key ) ? 'Version ' . $version . ' Secret Key Not Set - Please configure on settings screen<br />' : '';
            $error_reason .= !isset( $recaptchaFieldPOSTValue ) ? 'The form should have a hidden filed called "' . $fieldName . '" generated by recaptcha, with value set to the recaptcha token. Actual value: ' . $recaptchaFieldPOSTValue : '';
            $errorStringArray[] = str_replace("[MISCONFIGURATION_REASON]", $error_reason, get_option('unr_recaptcha_error_misconfigured'));
            return false;
        }

        return true;
    }
}
new UnrRecaptchaPlugin();