<?php

define('T17UNR_CHECKBOX_PRIVACY', TRUE); //self expanatory
define('T17UNR_CHECKBOX_WAIVER', FALSE); //self expanatory
define('T17UNR_CHECKBOX_NEWSLETTER', FALSE); //self expanatory

// Enqueue parent theme styles and child theme stylesheet
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style') );
}

// Enqueue child them js file
add_action( 'wp_enqueue_scripts', 'child_theme_js' );
function child_theme_js() {
    wp_enqueue_script( 'child-theme-js' , get_stylesheet_directory_uri() . '/child-theme-js.js' , array( 'twentyseventeen-global' ) , false , true );
    //wp_enqueue_script( 'upnrunning-calenderize' , get_stylesheet_directory_uri() . '/assets/js/upnrunning-calenderize.js' , array( 'twentyseventeen-global' ) , false , true );
    //wp_enqueue_script( 'upnrunning-countdown-timer' , get_stylesheet_directory_uri() . '/assets/js/upnrunning-countdown-timer.js' , array( 'twentyseventeen-global' ) , false , true );
}


//Set the users Public Display Name on new user registration
function set_default_display_name( $user_id ) {
  $user = get_userdata( $user_id );
  $name = sprintf( '%s %s', $user->first_name, substr($user->last_name, 0, 1) );
  $args = array(
    'ID'           => $user_id,
    'display_name' => $name,
    'nickname'     => $user->first_name
  );
  wp_update_user( $args );
}
add_action( 'user_register', 'set_default_display_name' );


//Switch off the admin bar for all users except admin
add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
    if (!current_user_can('administrator') && !is_admin() && !current_user_can('shop_manager')) {
      show_admin_bar(false);
    }
}

/* 
 * upnrunning: Blog Post Excerpt 'read more' fix
 */
function new_excerpt_more($more) {
    return '';
}
add_filter('excerpt_more', 'new_excerpt_more', 21 );

function the_excerpt_more_link( $excerpt ){
    $post = get_post();
    $excerpt .= '<a href="'. get_permalink($post->ID) . '">See Full Article...</a>';
    return $excerpt;
}
add_filter( 'the_excerpt', 'the_excerpt_more_link', 21 );

/**
 * Uses Custom Fields on Page level to overwrite the two-column / one column theme setting
 * this will run after twentyseventeen_body_classes in template-functions.php
 * maintaining array used to set class list on body tag
 * 
 * Looks for custom field called column_layout_override on page and uses it instead of global theme setting if found
 * @param array $classes Classes for the body element.
 * @return array
 */
function upnrunning_body_class_override( $classes ) {
    global $post;
     
    //query custom fields on page - this one for 1 or 2 column mode
    $override_class = $post === null ? null : get_post_meta($post->ID, 'column_layout_override', true);
    if( !$override_class || $override_class == '' || $override_class == 'inherit' )
    {
        $override_class = null;
    }
    if( is_null($override_class) && is_category('faqs') ) //make the faqs page one column
    {    //This might be a good alternative too:    is_page_template( 'page-halfhalf.php' ), or maybe $slug
        $override_class = 'page-one-column';
    }
    if (!is_null($override_class))
    {
        $classes = array_diff($classes, array('page-one-column', 'page-two-column'));
        $classes[] = $override_class;
    }

    //query custom fields on page - this one for hiding featured image
    $override_class = null;
    if( isset($post) )
    {
        $override_class = get_post_meta($post->ID, 'hide_featured_image', true);
    }
    if( !$override_class || $override_class == '' || $override_class == 'no' )
    {
        $override_class = null;
    }
    if (!is_null($override_class))
    {
        $classes[] = 'hide-featured-image';
    }    
    
    return $classes;
}
add_filter( 'body_class', 'upnrunning_body_class_override', 15 ); //the priority of >10 tells it to go after twentyseventeen_body_classes in template-functions.php



//Definitions for additional color pickers on Appearance --> Customise --> Colors screen
$upnrunning_theme_colors = array();
$upnrunning_theme_colors['upnrunning_theme_header_site_title_color'] = [
    'id' => '1',
    'settings'=>'upnrunning_theme_header_site_title_color', 
    'label' => 'Override Site Title Color in Header',
    'lightness' => 100,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_general_text_color'] = [
    'id' => '1a',
    'settings'=>'upnrunning_theme_general_text_color', 
    'label' => 'Override General Site Wide Text Color',
    'lightness' => 13,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_page_headings_color'] = [
    'id' => '4z',
    'settings'=>'upnrunning_theme_page_headings_color', 
    'label' => 'Page Headings Color',
    'lightness' => 13,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_navmenu_text_color'] = [
    'id' => '5',
    'settings'=>'upnrunning_theme_navmenu_text_color', 
    'label' => 'Main Nav Menu Text Color',
    'lightness' => 13,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_navmenu_scrolldown_color'] = [
    'id' => '5a',
    'settings'=>'upnrunning_theme_navmenu_scrolldown_color', 
    'label' => 'Main Nav Menu Scrolldown Arrow (right)',
    'lightness' => 30,
    'saturation' => 0.8
];
$upnrunning_theme_colors['upnrunning_theme_navmenu_bg_color'] = [
    'id' => '5b',
    'settings'=>'upnrunning_theme_navmenu_bg_color', 
    'label' => 'Main Nav Menu Background Color',
    'lightness' => 100,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_nav_hover_text_color'] = [
    'id' => '6',
    'settings'=>'upnrunning_theme_nav_hover_text_color', 
    'label' => 'Main Nav Menu Hover-over Text Color',
    'lightness' => 46,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_nav_hover_bg_color'] = [
    'id' => '6a',
    'settings'=>'upnrunning_theme_nav_hover_bg_color', 
    'label' => 'Main Nav Menu Hover-over Background Color',
    'lightness' => 90,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_main_menu_border_color'] = [
    'id' => '6b',
    'settings'=>'upnrunning_theme_main_menu_border_color', 
    'label' => 'Main Nav Menu Border Color',
    'lightness' => 93,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_nav_popup_text_color'] = [
    'id' => '6ba',
    'settings'=>'upnrunning_theme_nav_popup_text_color', 
    'label' => 'Nav Popup Menu Text',
    'lightness' => 13,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_nav_popup_bg_color'] = [
    'id' => '6bb',
    'settings'=>'upnrunning_theme_nav_popup_bg_color', 
    'label' => 'Nav Popup Menu Background Color',
    'lightness' => 13,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_nav_popup_hover_bg_color'] = [
    'id' => '6c',
    'settings'=>'upnrunning_theme_nav_popup_hover_bg_color', 
    'label' => 'Nav Popup Menu Hover-over Background',
    'lightness' => 70,
    'saturation' => 0.8
];
$upnrunning_theme_colors['upnrunning_theme_nav_popup_hover_text_color'] = [
    'id' => '6d',
    'settings'=>'upnrunning_theme_nav_popup_hover_text_color', 
    'label' => 'Nav Popup Menu Hover-over Text',
    'lightness' => 46,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_nav_popup_border_color'] = [
    'id' => '6e',
    'settings'=>'upnrunning_theme_nav_popup_border_color', 
    'label' => 'Nav Popup Menu Border Color',
    'lightness' => 73,
    'saturation' => 0.8
];
$upnrunning_theme_colors['upnrunning_theme_hyperlink_color'] = [
    'id' => '3-1',
    'settings'=>'upnrunning_theme_hyperlink_color', 
    'label' => 'Standard Hyperlink Color',
    'lightness' => 6,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_hyperlink_hover_color'] = [
    'id' => '2',  //MAIN: Hover Over Colour For Hyperlinks
    'settings'=>'upnrunning_theme_hyperlink_hover_color', 
    'label' => 'Hyperlink Hover-over Color',
    'lightness' => 0,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_heading_hyperlink_color'] = [
    'id' => '3-3',
    'settings'=>'upnrunning_theme_heading_hyperlink_color', 
    'label' => 'Heading Hyperlink Color',
    'lightness' => 6,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_heading_hyperlink_hover_color'] = [
    'id' => '3-4',
    'settings'=>'upnrunning_theme_heading_hyperlink_hover_color', 
    'label' => 'Heading Hyperlink Hover-over Color',
    'lightness' => 0,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_button_background_color'] = [
    'id' => '4',
    'settings'=>'upnrunning_theme_button_background_color', 
    'label' => 'Button Background Color',
    'lightness' => 13,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_button_hover_background_color'] = [
    'id' => '4a',
    'settings'=>'upnrunning_theme_button_hover_background_color', 
    'label' => 'Button Hover-Over Background Color',
    'lightness' => 46,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_button_text_color'] = [
    'id' => '4b',
    'settings'=>'upnrunning_theme_button_text_color', 
    'label' => 'Button Text Color',
    'lightness' => 100,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_blog_link_hover_color'] = [
    'id' => '7',
    'settings'=>'upnrunning_theme_blog_link_hover_color', 
    'label' => 'Blog Hyperlink Hover Underline Color',
    'lightness' => 13,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_blog_default_text_color'] = [
    'id' => '8',
    'settings'=>'upnrunning_theme_blog_default_text_color', 
    'label' => 'Blog Mini Heading Text Color',
    'lightness' => 20,
    'saturation' => 0.8
];
$upnrunning_theme_colors['upnrunning_theme_input_text_color'] = [
    'id' => '9',
    'settings'=>'upnrunning_theme_input_text_color', 
    'label' => 'Input Field Text Color',
    'lightness' => 40,
    'saturation' => 1
];
$upnrunning_theme_colors['upnrunning_theme_input_border_color'] = [
    'id' => '9a',
    'settings'=>'upnrunning_theme_input_border_color', 
    'label' => 'Input Field Border Color',
    'lightness' => 73,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_input_focus_border_color'] = [
    'id' => '10',
    'settings'=>'upnrunning_theme_input_focus_border_color', 
    'label' => 'Input Field (Selected) Border Color',
    'lightness' => 20,
    'saturation' => 0.8
];
$upnrunning_theme_colors['upnrunning_theme_page_seperator_line_color'] = [
    'id' => '11',
    'settings'=>'upnrunning_theme_page_seperator_line_color', 
    'label' => 'Page Section Divider Line',
    'lightness' => 87,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_table_header_underline_color'] = [
    'id' => '12',
    'settings'=>'upnrunning_theme_table_header_underline_color', 
    'label' => 'Table Header Underline Color',
    'lightness' => 73,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_social_nav_text_color'] = [
    'id' => '96',
    'settings'=>'upnrunning_theme_social_nav_text_hover_color', 
    'label' => 'Social Meida Links Icon Color',
    'lightness' => 100,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_social_nav_bg_color'] = [
    'id' => '97',
    'settings'=>'upnrunning_theme_social_nav_bg_color', 
    'label' => 'Social Meida Links Disc Color',
    'lightness' => 46,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_social_nav_text_hover_color'] = [
    'id' => '98',
    'settings'=>'upnrunning_theme_social_nav_text_hover_color', 
    'label' => 'Social Meida Links Icon Hover-over Color',
    'lightness' => 100,
    'saturation' => 1.0
];
$upnrunning_theme_colors['upnrunning_theme_social_nav_hover_color'] = [
    'id' => '99',
    'settings'=>'upnrunning_theme_social_nav_hover_color', 
    'label' => 'Social Meida Links Disc Hover-over Color',
    'lightness' => 20,
    'saturation' => 0.8
];


// function to add additional colour picker controls to Appearance --> Customise --> Color Screen
add_action('customize_register','upnrunning_additional_color_options');
/*
 * Add in our custom Accent Color setting and control to be used in the Customizer in the Colors section
 *
 */
function upnrunning_additional_color_options( $wp_customize ) {
    global $upnrunning_theme_colors;

    //setting to default back to old scrappy standard scrolling on homepage
    $wp_customize->add_setting('upnrunning_theme_override_home_page_parralax_scroll', array(
        'default'        => 'No',
        'capability'     => 'edit_theme_options',
        'type'           => 'option',
    ));
 
    $wp_customize->add_control('upnrunning_theme_override_home_page_parralax_scroll_conrtol', array(
        'label'      => __('up-n-running: Switch off fancy \'parallax\' scrolling on home page', 'twentyseventeen'),
        'section'    => 'static_front_page',
        'settings'   => 'upnrunning_theme_override_home_page_parralax_scroll',
        'type'       => 'radio',
        'choices'    => array(
            'value1' => 'Yes',
            'value2' => 'No',
        ),
    ));    
    
    $wp_customize->add_setting('upnrunning_theme_override_colors', array(
        'default'        => 'No',
        'capability'     => 'edit_theme_options',
        'type'           => 'option',
    ));
 
    $wp_customize->add_control('upnrunning_theme_using_override_control', array(
        'label'      => __('Use up-n-running Override Colours Below? (Custom Color Mode Only)', 'twentyseventeen'),
        'section'    => 'colors',
        'settings'   => 'upnrunning_theme_override_colors',
        'type'       => 'radio',
        'choices'    => array(
            'value1' => 'Yes',
            'value2' => 'No',
        ),
    ));
   
    //ADD SETTINGS AND CONTROLS TO Customise-->Appearance-->Color SECTION FOR EACH OF THE CUSTOM COLOURS
    $hue = absint( get_theme_mod( 'colorscheme_hue', 250 ) );
    $saturation = absint( apply_filters( 'twentyseventeen_custom_colors_saturation', 50 ) );
    foreach( $upnrunning_theme_colors as $theme_color ) {
        // SETTINGS
        $wp_customize->add_setting( $theme_color['settings'], array(
            'default' => hslToHex($hue, $theme_color['saturation'] * $saturation, $theme_color['lightness']),
            'type' => 'theme_mod', 
            'capability' => 'edit_theme_options'
        ));
        $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'upnrunning_color_control_' . $theme_color['id'], array(
                                'settings'   => $theme_color['settings'], //pick the name of the setting it applies to
                                'label'      => __( $theme_color['label'], 'twentyseventeen' ), //set the label to appear in the Customizer
                                'section'    => 'colors' //select the section for it to appear under  
                ))
        );
    }
}


//OVERWRITE twentyseventeen_colors_css_wrap FROM PARENT functions.php
add_action('wp_loaded', 'remove_parent_theme_colors_css_function');
function remove_parent_theme_colors_css_function() {
    remove_action('wp_head', 'twentyseventeen_colors_css_wrap');
}
//this is the new one
add_action( 'wp_head', 'upnrunning_twentyseventeen_colors_css_wrap' );
function upnrunning_twentyseventeen_colors_css_wrap() {
	if ( 'custom' !== get_theme_mod( 'colorscheme' ) && ! is_customize_preview() ) {
		return;
	}

	require_once get_parent_theme_file_path( '/inc/color-patterns.php' );
	$hue = absint( get_theme_mod( 'colorscheme_hue', 250 ) );

	$customize_preview_data_hue = '';
	if ( is_customize_preview() ) {
		$customize_preview_data_hue = 'data-hue="' . $hue . '"';
	}
	?>
	<style type="text/css" id="custom-theme-colors" <?php echo $customize_preview_data_hue; ?>>
		<?php echo upnrunning_twentyseventeen_custom_colors_css(); ?>
	</style>
	<?php
}

function get_upnrunning_theme_color( $settings_name, $hue, $saturation, $overriding_defaults ) {
    global $upnrunning_theme_colors;
    $default_colour = hslToHex($hue, $upnrunning_theme_colors[$settings_name]['saturation'] * $saturation, $upnrunning_theme_colors[$settings_name]['lightness']);
    if( $overriding_defaults )
    {
        return get_theme_mod( $settings_name, $default_colour );
    }
    else
    {
        return $default_colour;
    }
}


/**
 * Generate the OVERRIDDEN CSS for the current custom color scheme. - this is normally generated in inc/color-patterns.php but we're overriding to this one instead
 */
function upnrunning_twentyseventeen_custom_colors_css() {
    
    $hue = absint( get_theme_mod( 'colorscheme_hue', 250 ) );
    $saturation         = absint( apply_filters( 'twentyseventeen_custom_colors_saturation', 50 ) );
    $reduced_saturation = ( .8 * $saturation );
    $overriding_defaults = get_option( 'upnrunning_theme_override_colors', 'No' ) == 'Yes' || get_option( 'upnrunning_theme_override_colors', 'No' ) == 'value1';

    $css                = '
/**
 * Twenty Seventeen: Color Patterns
 *
 * Colors are ordered from dark to light.
 */

.colors-custom a:hover,
.colors-custom a:active,
.colors-custom .entry-content a:focus,
.colors-custom .entry-content a:hover,
.colors-custom .entry-summary a:focus,
.colors-custom .entry-summary a:hover,
.colors-custom .comment-content a:focus,
.colors-custom .comment-content a:hover,
.colors-custom .widget a:focus,
.colors-custom .widget a:hover,
.colors-custom .site-footer .widget-area a:focus,
.colors-custom .site-footer .widget-area a:hover,
.colors-custom .posts-navigation a:focus,
.colors-custom .posts-navigation a:hover,
.colors-custom .comment-metadata a:focus,
.colors-custom .comment-metadata a:hover,
.colors-custom .comment-metadata a.comment-edit-link:focus,
.colors-custom .comment-metadata a.comment-edit-link:hover,
.colors-custom .comment-reply-link:focus,
.colors-custom .comment-reply-link:hover,
.colors-custom .widget_authors a:focus strong,
.colors-custom .widget_authors a:hover strong,
.colors-custom .entry-title a:focus,
.colors-custom .entry-title a:hover,
.colors-custom .entry-meta a:focus,
.colors-custom .entry-meta a:hover,
.colors-custom.blog .entry-meta a.post-edit-link:focus,
.colors-custom.blog .entry-meta a.post-edit-link:hover,
.colors-custom.archive .entry-meta a.post-edit-link:focus,
.colors-custom.archive .entry-meta a.post-edit-link:hover,
.colors-custom.search .entry-meta a.post-edit-link:focus,
.colors-custom.search .entry-meta a.post-edit-link:hover,
.colors-custom .page-links a:focus .page-number,
.colors-custom .page-links a:hover .page-number,
.colors-custom .entry-footer a:focus,
.colors-custom .entry-footer a:hover,
.colors-custom .entry-footer .cat-links a:focus,
.colors-custom .entry-footer .cat-links a:hover,
.colors-custom .entry-footer .tags-links a:focus,
.colors-custom .entry-footer .tags-links a:hover,
.colors-custom .post-navigation a:focus,
.colors-custom .post-navigation a:hover,
.colors-custom .pagination a:not(.prev):not(.next):focus,
.colors-custom .pagination a:not(.prev):not(.next):hover,
.colors-custom .comments-pagination a:not(.prev):not(.next):focus,
.colors-custom .comments-pagination a:not(.prev):not(.next):hover,
.colors-custom .logged-in-as a:focus,
.colors-custom .logged-in-as a:hover,
.colors-custom a:focus .nav-title,
.colors-custom a:hover .nav-title,
.colors-custom .edit-link a:focus,
.colors-custom .edit-link a:hover,
.colors-custom .site-info a:focus,
.colors-custom .site-info a:hover,
.colors-custom .widget .widget-title a:focus,
.colors-custom .widget .widget-title a:hover,
.colors-custom .widget ul li a:focus,
.colors-custom .widget ul li a:hover,
.colors-custom .site-content .accordion-container .accordion .title a:hover,
.colors-custom .site-content .accordion-container .accordion .title a:hover::after,
.colors-custom .site-content .accordion-container .accordion .title a.active::after,
.colors-custom .site-content .accordion-container .accordion .title a.active
{
	color: ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . '; /* base: #000; */
}
.site-content .accordion-container .accordion .title a:hover,
.site-content .accordion-container .accordion .title a:hover::after,
.site-content .accordion-container .accordion .title a.active,
.site-content .accordion-container .accordion .title a.active::after{
  border-color: ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';
}


.colors-custom .entry-content a,
.colors-custom .entry-summary a,
.colors-custom .comment-content a,
.colors-custom .widget a,
.colors-custom .site-footer .widget-area a,
.colors-custom .posts-navigation a,
.colors-custom .widget_authors a strong {
	-webkit-box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.1); /* ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_color', $hue, $saturation, $overriding_defaults) . '; */
	box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.1); /* ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_color', $hue, $saturation, $overriding_defaults) . '; */
}

.colors-custom input[type="text"]:focus,
.colors-custom input[type="email"]:focus,
.colors-custom input[type="url"]:focus,
.colors-custom input[type="password"]:focus,
.colors-custom input[type="search"]:focus,
.colors-custom input[type="number"]:focus,
.colors-custom input[type="tel"]:focus,
.colors-custom input[type="range"]:focus,
.colors-custom input[type="date"]:focus,
.colors-custom input[type="month"]:focus,
.colors-custom input[type="week"]:focus,
.colors-custom input[type="time"]:focus,
.colors-custom input[type="datetime"]:focus,
.colors-custom .colors-custom input[type="datetime-local"]:focus,
.colors-custom input[type="color"]:focus,
.colors-custom textarea:focus,
.colors-custom button.secondary,
.colors-custom input[type="reset"],
.colors-custom input[type="button"].secondary,
.colors-custom input[type="reset"].secondary,
.colors-custom input[type="submit"].secondary,
.colors-custom label,
.colors-custom strong,
.colors-custom .site-title,
.colors-custom .site-title a,
.colors-custom .page .panel-content .entry-title,
.colors-custom .page-title,
.colors-custom.page:not(.twentyseventeen-front-page) .entry-title,
.colors-custom .page-links a .page-number,
.colors-custom .comment-metadata a.comment-edit-link,
.colors-custom .comment-reply-link .icon,
.colors-custom h2,
.colors-custom h1.entry-title,
.colors-custom h2.widget-title,
.colors-custom h3,
.colors-custom h4,
.colors-custom h5,
.colors-custom th,
.colors-custom mark,
.colors-custom .post-navigation a:focus .icon,
.colors-custom .post-navigation a:hover .icon,
.colors-custom span.page-numbers.current,
.colors-custom .site-content .site-content-light,
.colors-custom .twentyseventeen-panel .recent-posts .entry-header .edit-link {
	color: ' . get_upnrunning_theme_color('upnrunning_theme_page_headings_color', $hue, $saturation, $overriding_defaults) . '; /* base: #222; */
}

/* BEGIN NAV MENU */
.colors-custom .navigation-top,
.colors-custom .navigation-top a,
.colors-custom .dropdown-toggle,
.colors-custom .menu-toggle,
.colors-custom .main-navigation li {
	color: ' . get_upnrunning_theme_color('upnrunning_theme_navmenu_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #222; */
        background-color: ' . get_upnrunning_theme_color('upnrunning_theme_navmenu_bg_color', $hue, $saturation, $overriding_defaults) . ';    
}

/* current page and hovered over section */
.colors-custom .navigation-top .current-menu-item > a,
.colors-custom .navigation-top .current_page_item > a,
.colors-custom .main-navigation a:hover {
    color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_hover_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #767676; */
    background-color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_hover_bg_color', $hue, $saturation, $overriding_defaults) . ';
}

/* Main Nav Menu Top Border */
.colors-custom .navigation-top,
.colors-custom .main-navigation > div > ul,
.colors-custom .pagination,
.colors-custom .comments-pagination,
.colors-custom .entry-footer,
.colors-custom .site-footer {
	border-top-color: ' . get_upnrunning_theme_color('upnrunning_theme_main_menu_border_color', $hue, $saturation, $overriding_defaults) . '; /* base: #eee; */
}

/* Main Nav Menu Bottom Border */
.colors-custom .navigation-top .wrap,
.colors-custom .main-navigation li,
.colors-custom .entry-footer,
.colors-custom .single-featured-image-header,
.colors-custom .site-content .wp-playlist-light .wp-playlist-item,
.colors-custom tr {
	border-bottom-color: ' . get_upnrunning_theme_color('upnrunning_theme_main_menu_border_color', $hue, $saturation, $overriding_defaults) . '; /* base: #eee; */
}
/* END NAV MENU */

/* GENERAL TEXT COLOR */
.colors-custom h5,
.colors-custom .entry-meta,
.colors-custom .entry-meta a,
.colors-custom.blog .entry-meta a.post-edit-link,
.colors-custom.archive .entry-meta a.post-edit-link,
.colors-custom.search .entry-meta a.post-edit-link,
.colors-custom .nav-subtitle,
.colors-custom .comment-metadata,
.colors-custom .comment-metadata a,
.colors-custom .no-comments,
.colors-custom .comment-awaiting-moderation,
.colors-custom .page-links .page-number,
.colors-custom .site-content .wp-playlist-light .wp-playlist-current-item .wp-playlist-item-artist {
	color: ' . get_upnrunning_theme_color('upnrunning_theme_blog_default_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #767676; */
}

/*General Site Wide Text Color */
body.colors-custom,
.colors-custom input,
.colors-custom select,
.colors-custom textarea,
.colors-custom h6,
.colors-custom.twentyseventeen-front-page .panel-content .recent-posts article,
.colors-custom .entry-footer .cat-links a,
.colors-custom .entry-footer .tags-links a,
.colors-custom .format-quote blockquote,
.colors-custom .nav-title,
.colors-custom .comment-body,
.colors-custom .wp-playlist-light .wp-playlist-current-item .wp-playlist-item-album {
	color: ' . get_upnrunning_theme_color('upnrunning_theme_general_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #333; */
}

/* Hyperlink Colour */
.colors-custom a {
    color: ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_color', $hue, $saturation, $overriding_defaults) . ';
}

.colors-custom a:hover,
.colors-custom a:focus {
    color: ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';
}

.colors-custom .entry-content h2 a,
.colors-custom .entry-title a,
.site-content .accordion-container .accordion .title a,
.site-content .accordion-container .accordion .title a::after {
  color: ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_color', $hue, $saturation, $overriding_defaults) . ';
	-webkit-box-shadow: inset 0 0 0 ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_color', $hue, $saturation, $overriding_defaults) . ', 0 1px 0 ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';
	box-shadow: inset 0 0 0 ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_color', $hue, $saturation, $overriding_defaults) . ' 0 1px 0 ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';

}

.colors-custom .entry-content h2 a:hover,
.colors-custom .entry-content h2 a:focus,
.colors-custom .entry-title a:hover,
.colors-custom .entry-title a:focus,
.site-content .accordion-container .accordion .title a:hover,
.site-content .accordion-container .accordion .title a:focus,
.site-content .accordion-container .accordion .title a:hover::after,
.site-content .accordion-container .accordion .title a:focus::after {
  color: ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';    
	-webkit-box-shadow: inset 0 0 0 ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ', 0 2px 0 ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';
	box-shadow: inset 0 0 0 ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ' 0 2px 0 ' . get_upnrunning_theme_color('upnrunning_theme_heading_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';

}

.colors-custom .entry-content a:focus,
.colors-custom .entry-content a:hover,
.colors-custom .entry-summary a:focus,
.colors-custom .entry-summary a:hover,
.colors-custom .comment-content a:focus,
.colors-custom .comment-content a:hover,
.colors-custom .widget a:focus,
.colors-custom .widget a:hover,
.colors-custom .site-footer .widget-area a:focus,
.colors-custom .site-footer .widget-area a:hover,
.colors-custom .posts-navigation a:focus,
.colors-custom .posts-navigation a:hover,
.colors-custom .comment-metadata a:focus,
.colors-custom .comment-metadata a:hover,
.colors-custom .comment-metadata a.comment-edit-link:focus,
.colors-custom .comment-metadata a.comment-edit-link:hover,
.colors-custom .comment-reply-link:focus,
.colors-custom .comment-reply-link:hover,
.colors-custom .widget_authors a:focus strong,
.colors-custom .widget_authors a:hover strong,
.colors-custom .entry-meta a:focus,
.colors-custom .entry-meta a:hover,
.colors-custom.blog .entry-meta a.post-edit-link:focus,
.colors-custom.blog .entry-meta a.post-edit-link:hover,
.colors-custom.archive .entry-meta a.post-edit-link:focus,
.colors-custom.archive .entry-meta a.post-edit-link:hover,
.colors-custom.search .entry-meta a.post-edit-link:focus,
.colors-custom.search .entry-meta a.post-edit-link:hover,
.colors-custom .page-links a:focus .page-number,
.colors-custom .page-links a:hover .page-number,
.colors-custom .entry-footer .cat-links a:focus,
.colors-custom .entry-footer .cat-links a:hover,
.colors-custom .entry-footer .tags-links a:focus,
.colors-custom .entry-footer .tags-links a:hover,
.colors-custom .post-navigation a:focus,
.colors-custom .post-navigation a:hover,
.colors-custom .pagination a:not(.prev):not(.next):focus,
.colors-custom .pagination a:not(.prev):not(.next):hover,
.colors-custom .comments-pagination a:not(.prev):not(.next):focus,
.colors-custom .comments-pagination a:not(.prev):not(.next):hover,
.colors-custom .logged-in-as a:focus,
.colors-custom .logged-in-as a:hover,
.colors-custom a:focus .nav-title,
.colors-custom a:hover .nav-title,
.colors-custom .edit-link a:focus,
.colors-custom .edit-link a:hover,
.colors-custom .site-info a:focus,
.colors-custom .site-info a:hover,
.colors-custom .widget .widget-title a:focus,
.colors-custom .widget .widget-title a:hover,
.colors-custom .widget ul li a:focus,
.colors-custom .widget ul li a:hover {
	-webkit-box-shadow: inset 0 0 0 ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ', 0 1px 0 ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';
	box-shadow: inset 0 0 0 ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ' 0 1px 0 ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_hover_color', $hue, $saturation, $overriding_defaults) . ';
}

.colors-custom .social-navigation a{
    color: ' . get_upnrunning_theme_color('upnrunning_theme_social_nav_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #767676; */
    background: ' . get_upnrunning_theme_color('upnrunning_theme_social_nav_bg_color', $hue, $saturation, $overriding_defaults) . '; /* base: #767676; */
}

.colors-custom .social-navigation a:hover,
.colors-custom .social-navigation a:focus{
    color: ' . get_upnrunning_theme_color('upnrunning_theme_social_nav_text_hover_color', $hue, $saturation, $overriding_defaults) . '; /* base: #767676; */
    background: ' . get_upnrunning_theme_color('upnrunning_theme_social_nav_hover_color', $hue, $saturation, $overriding_defaults) . '; /* base: #333; */
}

.colors-custom input[type="text"]:focus,
.colors-custom input[type="email"]:focus,
.colors-custom input[type="url"]:focus,
.colors-custom input[type="password"]:focus,
.colors-custom input[type="search"]:focus,
.colors-custom input[type="number"]:focus,
.colors-custom input[type="tel"]:focus,
.colors-custom input[type="range"]:focus,
.colors-custom input[type="date"]:focus,
.colors-custom input[type="month"]:focus,
.colors-custom input[type="week"]:focus,
.colors-custom input[type="time"]:focus,
.colors-custom input[type="datetime"]:focus,
.colors-custom input[type="datetime-local"]:focus,
.colors-custom input[type="color"]:focus,
.colors-custom textarea:focus,
.bypostauthor > .comment-body > .comment-meta > .comment-author .avatar {
	border-color: ' . get_upnrunning_theme_color('upnrunning_theme_input_focus_border_color', $hue, $saturation, $overriding_defaults) . '; /* base: #333; */
}

.colors-custom blockquote,
.colors-custom input[type="text"],
.colors-custom input[type="email"],
.colors-custom input[type="url"],
.colors-custom input[type="password"],
.colors-custom input[type="search"],
.colors-custom input[type="number"],
.colors-custom input[type="tel"],
.colors-custom input[type="range"],
.colors-custom input[type="date"],
.colors-custom input[type="month"],
.colors-custom input[type="week"],
.colors-custom input[type="time"],
.colors-custom input[type="datetime"],
.colors-custom input[type="datetime-local"],
.colors-custom input[type="color"],
.colors-custom textarea,
.colors-custom .site-description,
.colors-custom .entry-content blockquote.alignleft,
.colors-custom .entry-content blockquote.alignright,
.colors-custom .colors-custom .taxonomy-description,
.colors-custom .site-info a,
.colors-custom .wp-caption,
.colors-custom .gallery-caption {
	color: ' . get_upnrunning_theme_color('upnrunning_theme_input_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #666; */
}

.colors-custom abbr,
.colors-custom acronym {
	border-bottom-color: hsl( ' . $hue . ', ' . $saturation . ', 40% ); /* base: #666; */
}

.colors-custom button,
.colors-custom .prev.page-numbers,
.colors-custom .next.page-numbers,
.colors-custom input[type="button"],
.colors-custom input[type="submit"],
.colors-custom .entry-footer .edit-link a.post-edit-link {
	background-color: ' . get_upnrunning_theme_color('upnrunning_theme_button_background_color', $hue, $saturation, $overriding_defaults) . '; /* base: #222; */
        color: ' . get_upnrunning_theme_color('upnrunning_theme_button_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #fff; */
}

.colors-custom :not( .mejs-button ) > button:hover,
.colors-custom :not( .mejs-button ) > button:focus,
.colors-custom input[type="button"]:hover,
.colors-custom input[type="button"]:focus,
.colors-custom input[type="submit"]:hover,
.colors-custom input[type="submit"]:focus,
.colors-custom .entry-footer .edit-link a.post-edit-link:hover,
.colors-custom .entry-footer .edit-link a.post-edit-link:focus,
.colors-custom .prev.page-numbers:focus,
.colors-custom .prev.page-numbers:hover,
.colors-custom .next.page-numbers:focus,
.colors-custom .next.page-numbers:hover,
.colors-custom a.next.page-numbers:focus,
.colors-custom a.next.page-numbers:hover,
.colors-custom .entry-content a.next.page-numbers:focus, 
.colors-custom .entry-content a.next.page-numbers:hover,
.colors-custom .site-content .wp-playlist-light .wp-playlist-item:hover,
.colors-custom .site-content .wp-playlist-light .wp-playlist-item:focus {
	background: ' . get_upnrunning_theme_color('upnrunning_theme_button_hover_background_color', $hue, $saturation, $overriding_defaults) . '; /* base: #767676; */
    color: ' . get_upnrunning_theme_color('upnrunning_theme_button_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #fff; */
}

.colors-custom button.secondary,
.colors-custom input[type="reset"],
.colors-custom input[type="button"].secondary,
.colors-custom input[type="reset"].secondary,
.colors-custom input[type="submit"].secondary {
	background-color: ' . get_upnrunning_theme_color('upnrunning_theme_button_background_color', $hue, $reduced_saturation, $overriding_defaults) . '; /* base: #ddd; */
}

.colors-custom button.secondary:hover,
.colors-custom button.secondary:focus,
.colors-custom input[type="reset"]:hover,
.colors-custom input[type="reset"]:focus,
.colors-custom input[type="button"].secondary:hover,
.colors-custom input[type="button"].secondary:focus,
.colors-custom input[type="reset"].secondary:hover,
.colors-custom input[type="reset"].secondary:focus,
.colors-custom input[type="submit"].secondary:hover,
.colors-custom input[type="submit"].secondary:focus {
	background: ' . get_upnrunning_theme_color('upnrunning_theme_button_hover_background_color', $hue, $reduced_saturation, $overriding_defaults) . '; /* base: #bbb; */
}

.colors-custom input[type="text"],
.colors-custom input[type="email"],
.colors-custom input[type="url"],
.colors-custom input[type="password"],
.colors-custom input[type="search"],
.colors-custom input[type="number"],
.colors-custom input[type="tel"],
.colors-custom input[type="range"],
.colors-custom input[type="date"],
.colors-custom input[type="month"],
.colors-custom input[type="week"],
.colors-custom input[type="time"],
.colors-custom input[type="datetime"],
.colors-custom input[type="datetime-local"],
.colors-custom input[type="color"],
.colors-custom textarea,
.colors-custom select,
.colors-custom fieldset,
.colors-custom .widget .tagcloud a:hover,
.colors-custom .widget .tagcloud a:focus,
.colors-custom .widget.widget_tag_cloud a:hover,
.colors-custom .widget.widget_tag_cloud a:focus,
.colors-custom .wp_widget_tag_cloud a:hover,
.colors-custom .wp_widget_tag_cloud a:focus {
	border-color: ' . get_upnrunning_theme_color('upnrunning_theme_input_border_color', $hue, $reduced_saturation, $overriding_defaults) . '; /* base: #bbb; */
}

.colors-custom thead th {
	border-bottom-color: ' . get_upnrunning_theme_color('upnrunning_theme_table_header_underline_color', $hue, $reduced_saturation, $overriding_defaults) . '; /* base: #bbb; */
}

/* blog ul list showing caregories and tags has this colour for the folder icon bulletpoints */
.colors-custom .entry-footer .cat-links .icon,
.colors-custom .entry-footer .tags-links .icon {
	color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_hover_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #bbb; */
}

.colors-custom .widget .tagcloud a,
.colors-custom .widget.widget_tag_cloud a,
.colors-custom .wp_widget_tag_cloud a {
	border-color: yellow; /* hsl( ' . $hue . ', ' . $saturation . ', 87% ); /* base: #ddd; */
}

.colors-custom.twentyseventeen-front-page article:not(.has-post-thumbnail):not(:first-child),
.colors-custom .widget ul li,
.colors-custom hr {
	border-top-color: ' . get_upnrunning_theme_color('upnrunning_theme_page_seperator_line_color', $hue, $saturation, $overriding_defaults) . '; /* base: #ddd; */
}

/* marcup text */
.colors-custom pre,
.colors-custom mark,
.colors-custom ins {
	background: hsl( ' . $hue . ', ' . $saturation . ', 93% ); /* base: #eee; */
}

/* Audio Playlist Section Border */
.colors-custom .site-content .wp-playlist-light {
	border-color: hsl( ' . $hue . ', ' . $saturation . ', 93% ); /* base: #eee; */
}

/* the background color behind the header image or video */
.colors-custom .site-header,
.colors-custom .single-featured-image-header {
	background-color: hsl( ' . $hue . ', ' . $saturation . ', 98% ); /* base: #fafafa; */
}

/* audio playlist links and pagination - current page color */
.colors-custom .entry-footer .edit-link a.post-edit-link,
.colors-custom .site-content .wp-playlist-light a.wp-playlist-caption:hover,
.colors-custom .site-content .wp-playlist-light .wp-playlist-item:hover a,
.colors-custom .site-content .wp-playlist-light .wp-playlist-item:focus a,
.colors-custom .site-content .wp-playlist-light .wp-playlist-item:hover,
.colors-custom .site-content .wp-playlist-light .wp-playlist-item:focus {
	background-color: hsl( ' . $hue . ', ' . $saturation . ', 100% ); /* base: #fff; */
}

.colors-custom.has-header-image .site-title,
.colors-custom.has-header-video .site-title,
.colors-custom.has-header-image .site-title a,
.colors-custom.has-header-video .site-title a,
.colors-custom.has-header-image .site-description,
.colors-custom.has-header-video .site-description {
	color: ' . get_upnrunning_theme_color('upnrunning_theme_header_site_title_color', $hue, $saturation, $overriding_defaults) . '; /*  /* base: #fff; */
}

.colors-custom .widget ul li a,
.colors-custom .site-footer .widget-area ul li a {
	-webkit-box-shadow: inset 0 -1px 0 /* ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_color', $hue, $saturation, $overriding_defaults) . '; */ base: rgba(255, 255, 255, 0);
	box-shadow: inset 0 -1px 0 /* ' . get_upnrunning_theme_color('upnrunning_theme_hyperlink_color', $hue, $saturation, $overriding_defaults) . '); */ base: rgba(255, 255, 255, 0);
}

.colors-custom .menu-toggle,
.colors-custom .menu-toggle:hover,
.colors-custom .menu-toggle:focus,
.colors-custom .menu .dropdown-toggle,
.colors-custom .menu-scroll-down,
.colors-custom .menu-scroll-down:hover,
.colors-custom .menu-scroll-down:focus {
	background-color: transparent;
}

.colors-custom .widget .tagcloud a,
.colors-custom .widget .tagcloud a:focus,
.colors-custom .widget .tagcloud a:hover,
.colors-custom .widget.widget_tag_cloud a,
.colors-custom .widget.widget_tag_cloud a:focus,
.colors-custom .widget.widget_tag_cloud a:hover,
.colors-custom .wp_widget_tag_cloud a,
.colors-custom .wp_widget_tag_cloud a:focus,
.colors-custom .wp_widget_tag_cloud a:hover,
.colors-custom .entry-footer .edit-link a.post-edit-link:focus,
.colors-custom .entry-footer .edit-link a.post-edit-link:hover {
	-webkit-box-shadow: none !important;
	box-shadow: none !important;
}

/* Reset non-customizable hover styling for links */
/* TO DO: CAN WE GET RID OF THIS?
.colors-custom .entry-content a:hover,
.colors-custom .entry-content a:focus,
.colors-custom .entry-summary a:hover,
.colors-custom .entry-summary a:focus,
.colors-custom .comment-content a:focus,
.colors-custom .comment-content a:hover,
.colors-custom .widget a:hover,
.colors-custom .widget a:focus,
.colors-custom .site-footer .widget-area a:hover,
.colors-custom .site-footer .widget-area a:focus,
.colors-custom .posts-navigation a:hover,
.colors-custom .posts-navigation a:focus,
.colors-custom .widget_authors a:hover strong,
.colors-custom .widget_authors a:focus strong {
	-webkit-box-shadow: inset 0 0 0 rgba(255, 255, 255, 0), 0 1px 0 ' . get_upnrunning_theme_color('upnrunning_theme_navmenu_scrolldown_color', $hue, $saturation, $overriding_defaults) . ';
	box-shadow: inset 0 0 0 rgba(255, 255, 255, 0), 0 1px 0 ' . get_upnrunning_theme_color('upnrunning_theme_navmenu_scrolldown_color', $hue, $saturation, $overriding_defaults) . ';
}
*/

.colors-custom .gallery-item a,
.colors-custom .gallery-item a:hover,
.colors-custom .gallery-item a:focus {
	-webkit-box-shadow: none;
	box-shadow: none;
}

/* widescreen only menu settings */
@media screen and (min-width: 48em) {

	.colors-custom .nav-links .nav-previous .nav-title .icon,
	.colors-custom .nav-links .nav-next .nav-title .icon {
		color: #222;
	}

	.colors-custom .main-navigation li li:hover,
	.colors-custom .main-navigation li li.focus {
		background: hsl( ' . $hue . ', ' . $saturation . ', 46% );
	}

        /* Scrolldown Arrow on right hand side */
	.colors-custom .navigation-top .menu-scroll-down {
            color: ' . get_upnrunning_theme_color('upnrunning_theme_navmenu_scrolldown_color', $hue, $saturation, $overriding_defaults) . '; /* base: #222; */
	}

	.colors-custom abbr[title] {
		border-bottom-color: hsl( ' . $hue . ', ' . $saturation . ', 46% ); /* base: #767676; */;
	}

	.colors-custom .main-navigation ul ul {
		border-color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_popup_border_color', $hue, $saturation, $overriding_defaults) . '; /* base: #bbb; */
		background: ' . get_upnrunning_theme_color('upnrunning_theme_navmenu_bg_color', $hue, $saturation, $overriding_defaults) . '; /* base: #fff; */
        }
        
	.colors-custom .main-navigation li li > a,
	.colors-custom .main-navigation li li a {
        color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_popup_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #fff; */
        background-color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_popup_bg_color', $hue, $saturation, $overriding_defaults) . '; 
    }


	.colors-custom .main-navigation ul li.menu-item-has-children:before,
	.colors-custom .main-navigation ul li.page_item_has_children:before {
		border-bottom-color: hsl( ' . $hue . ', ' . $saturation . ', 73% ); /* base: #bbb; */
	}

        /* Little speach bubble up triangle thing */
	.colors-custom .main-navigation ul li.menu-item-has-children:after,
	.colors-custom .main-navigation ul li.page_item_has_children:after {
		border-bottom-color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_popup_border_color', $hue, $saturation, $overriding_defaults) . '; /* base: #fff; */
	}

	.colors-custom .main-navigation li li.focus > a,
	.colors-custom .main-navigation li li:focus > a,
	.colors-custom .main-navigation li li:hover > a,
	.colors-custom .main-navigation li li a:hover,
	.colors-custom .main-navigation li li a:focus,
	.colors-custom .main-navigation li li.current_page_item a:hover,
	.colors-custom .main-navigation li li.current-menu-item a:hover,
	.colors-custom .main-navigation li li.current_page_item a:focus,
	.colors-custom .main-navigation li li.current-menu-item a:focus,
    .colors-custom .sub-menu .current-menu-item > a {
            color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_popup_hover_text_color', $hue, $saturation, $overriding_defaults) . '; /* base: #fff; */
            background-color: ' . get_upnrunning_theme_color('upnrunning_theme_nav_popup_hover_bg_color', $hue, $saturation, $overriding_defaults) . ';   
	}
';
    if( get_option( 'upnrunning_theme_override_home_page_parralax_scroll', 'No' ) == 'Yes' || get_option( 'upnrunning_theme_override_home_page_parralax_scroll', 'No' ) == 'value1' ) {
$css = $css . '
.background-fixed .panel-image {
  background-attachment: scroll;
}';
    }

$css = $css . '}
';

	/**
	 * Filters Twenty Seventeen custom colors CSS.
	 *
	 * @since Twenty Seventeen 1.0
	 *
	 * @param string $css        Base theme colors CSS.
	 * @param int    $hue        The user's selected color hue.
	 * @param string $saturation Filtered theme color saturation level.
	 */
	return apply_filters( 'twentyseventeen_custom_colors_css', $css, $hue, $saturation );
}


function hslToHex($h, $s, $l)
{
    $h = $h/255.0;
    $s = $s/100.0;
    $l = $l/100.0;
    
    if ($s == 0) {
        $r = $g = $b = 1;
    } else {
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $r = hue2rgb($p, $q, $h + 1/3);
        $g = hue2rgb($p, $q, $h);
        $b = hue2rgb($p, $q, $h - 1/3);
    }

    return '#' . rgb2hex($r) . rgb2hex($g) . rgb2hex($b);
}

function hue2rgb($p, $q, $t) {
    if ($t < 0) $t += 1;
    if ($t > 1) $t -= 1;
    if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
    if ($t < 1/2) return $q;
    if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;

    return $p;
}

function rgb2hex($rgb) {
    return str_pad(dechex($rgb * 255), 2, '0', STR_PAD_LEFT);
}



/* 
 * Add 'Sign up and book' (or just book if logged in) header HTML to top of auto generated booking form
 */
add_action( 'em_booking_form_before_user_details', 'unr_template_booking_form_header_html' );
function unr_template_booking_form_header_html( $EM_Event ) {
    if( !is_user_logged_in() )
    {
?>
    <div class="form-header-box">
        <h3>Sign up & Book</h3><br />
        <p class="intro"><strong>Don't have an account?</strong><br />Book below and we'll create one:</p>
    </div>
<?php
    }
    else
    {
?>
    <div class="form-header-box">
        <h3>Book this event</h3><br />
        <p class="intro">Complete the form below to book your place</p>
    </div>
<?php
    }
}



/*
 * Add extra checkbox fields to edit user screen in WP-ADMIN
 */
function unr_extra_profile_fields( $user ) {
    $unr_accept_terms_checkbox_insert = ' disabled="disabled" readonly="readonly"';
    $unr_accepted_terms_meta = get_user_meta( $user->ID, 'em_data_privacy_consent', true );
    if( isset( $unr_accepted_terms_meta ) && $unr_accepted_terms_meta !=="" && $unr_accepted_terms_meta !== null ) {
        $unr_accept_terms_checkbox_insert .= ' checked="checked"';
    }
    
    $unr_accept_yoga_waiver_checkbox_insert = ' disabled="disabled" readonly="readonly"';
    $unr_accepted_yoga_waiver_meta = get_user_meta( $user->ID, 'accepted_yoga_waiver', true );
    if( isset( $unr_accepted_yoga_waiver_meta ) && $unr_accepted_yoga_waiver_meta !=="" && $unr_accepted_yoga_waiver_meta !== null ) {
        $unr_accept_yoga_waiver_checkbox_insert .= ' checked="checked"';
    }
    
    $unr_newsletter_signup_checkbox_insert = '';
    $unr_newsletter_signedup_meta = get_user_meta( $user->ID, 'newsletter_signedup', true );
    if( isset( $unr_newsletter_signedup_meta ) && ( $unr_newsletter_signedup_meta ==="1" || $unr_newsletter_signedup_meta === 1  ) ) {
        $unr_newsletter_signup_checkbox_insert .= ' checked="checked"';
    }
    
    ?>
    <h3><?php _e('Extra Strive And Fly User Details'); ?></h3>
    <table class="form-table">
        <?php 
          if( T17UNR_CHECKBOX_PRIVACY )
          { 
        ?>
        <tr>
            <th><label for="accept_terms">Privacy Consent</label></th>
            <td>
                <input name="accept_terms" type="checkbox" value="1"<?php echo $unr_accept_terms_checkbox_insert; ?> /><?php echo $unr_accepted_terms_meta; ?><br />
                <span class="description">Yes, you can store my data as per the <a href="<?php echo get_privacy_policy_url() ?>" target="_blank">Privacy Policy</a></span>
            </td>
        </tr>
        <?php 
          }
          if( T17UNR_CHECKBOX_WAIVER )
          { 
        ?>
        <tr>
            <th><label for="accept_yoga_waiver">Participant Waiver</label></th>
            <td>
                <input name="accept_yoga_waiver" type="checkbox" value="1"<?php echo $unr_accept_yoga_waiver_checkbox_insert; ?> /><?php echo $unr_accepted_yoga_waiver_meta; ?><br />
                <span class="description">Yes, I understand and agree to the <a href="<?php echo get_site_url(null, "/events/participant-waiver-agreement" ) ?>" target="_blank">Participant Waiver Agreement</a></span>
            </td>
        </tr>
        <?php 
          }
          if( T17UNR_CHECKBOX_NEWSLETTER )
          { 
        ?>
        <tr>
            <th><label for="newsletter_signup">Newsletter Signup</label></th>
            <td>
                <input name="newsletter_signup" type="checkbox" value="1"<?php echo $unr_newsletter_signup_checkbox_insert; ?> /><br />
                <span class="description">Keep me posted occasionally about upcoming classes and news (optional)</span>
            </td>
        </tr>
        <?php 
          }
        ?>
    </table>
<?php
}
// Then we hook the function to "show_user_profile" and "edit_user_profile"
if( T17UNR_CHECKBOX_PRIVACY || T17UNR_CHECKBOX_WAIVER || T17UNR_CHECKBOX_NEWSLETTER )
{
    add_action( 'show_user_profile', 'unr_extra_profile_fields', 10 );
    add_action( 'edit_user_profile', 'unr_extra_profile_fields', 10 );
}

if( T17UNR_CHECKBOX_NEWSLETTER )
{
    add_action( 'personal_options_update', 'unr_save_extra_profile_fields' );
    add_action( 'edit_user_profile_update', 'unr_save_extra_profile_fields' );
}
function unr_save_extra_profile_fields( $user_id ) {

    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    /* ts and cs and yoga waiver are readonly! just save newsletter */
    update_usermeta( $user_id, 'newsletter_signedup', (isset( $_POST['newsletter_signup'] ) && $_POST['newsletter_signup'] === "1" ? 1 : 0 ) );
}

/*
 * Add extra checkboxes to the registration form footer for 'yoga waiver' and
 * sign up to newsletter
 */


/*
 * Add extra checkbox fields to front-end booking form on events details page
 */
function unr_extra_booking_form_footer_checkboxes( $EM_Object = false ){
	if( !empty($EM_Object) && (!empty($EM_Object->booking_id) || !empty($EM_Object->post_id)) ) return; //already saved so consent was given at one point
    $yoga_waiver_consent_given_already = false;
    $newsletter_already_signedup = false;
	if( is_user_logged_in() ){
	    //check if consent was previously given and check box if true
        $yoga_waiver_user_meta = get_user_meta( get_current_user_id(), 'accepted_yoga_waiver', true );
        $newsletter_user_meta = get_user_meta( get_current_user_id(), 'newsletter_signedup', true );
        $yoga_waiver_consent_given_already = isset( $yoga_waiver_user_meta ) && $yoga_waiver_user_meta !=="" && $yoga_waiver_user_meta !== null;
        $newsletter_already_signedup = isset( $newsletter_user_meta ) && $newsletter_user_meta !=="" && $newsletter_user_meta !== null && $newsletter_user_meta !=="0" && $newsletter_user_meta !==0;
    }
    
    if( T17UNR_CHECKBOX_WAIVER && !$yoga_waiver_consent_given_already ) {
        ?>
        <p class="input-group input-checkbox">
            <label>
                <input type="hidden" name="require_yoga_waiver_consent" value="1" />
                <input type="checkbox" name="accept_yoga_waiver" value="1"<?php if( !empty($_REQUEST['accept_yoga_waiver']) ) echo ' checked="checked"'; ?> />
                Yes, I understand and agree to the <a href="<?php echo get_site_url(null, "/events/participant-waiver-agreement" ) ?>" target="_blank">Participant Waiver</a>
            </label>
            <br style="clear:both;">
        </p>
        <?php
    }
    if( T17UNR_CHECKBOX_NEWSLETTER && !$newsletter_already_signedup ) {
        ?>
        <p class="input-group input-checkbox">
            <label>
                <input type="checkbox" name="newsletter_signup" value="1"<?php if( !empty($_REQUEST['newsletter_signup']) ) echo ' checked="checked"'; ?> />
                Keep me posted occasionally about upcoming classes and news (optional)
            </label>
            <br style="clear:both;">
        </p>
        <?php
    }
}
add_action('em_booking_form_footer', 'unr_extra_booking_form_footer_checkboxes', 10, 0);

/* Called in em-actions.php before any of the below stuff is called
 * we're using this to validate all the theme specific checkboxes on the reg form
 */
add_filter('em_booking_validate','unr_validate_extra_booking_form_checkboxes_before_saving', 2, 2);
function unr_validate_extra_booking_form_checkboxes_before_saving($result, $EM_Booking){
    if ( T17UNR_CHECKBOX_WAIVER && !empty( $_REQUEST['require_yoga_waiver_consent'] ) && $_REQUEST['require_yoga_waiver_consent'] == '1') {
        if( empty($_REQUEST['accept_yoga_waiver']) )
        {
            $EM_Booking->add_error('You must accept the Participant Waiver Agreement before you can attend a course');
            $result = false;
        }
    }
    return $result;
}


/*
 * After the booking have saved in the master plugin these 2 checkboxes will still need 
 * to be saved to database so this is called after master save
 */
add_filter('em_booking_save', 'unr_extra_booking_form_checkboxes_booking_save', 9, 2);
function unr_extra_booking_form_checkboxes_booking_save( $result, $EM_Booking ){
    if( $result ){
        if( $EM_Booking->person_id != 0 ){
            if ( !empty($_REQUEST['accept_yoga_waiver']) ) {
                $timestamptosave = current_time('mysql');
                update_user_meta( $EM_Booking->person_id, 'accepted_yoga_waiver', $timestamptosave );
            }
            if ( !empty($_REQUEST['newsletter_signup']) ) {
                update_user_meta( $EM_Booking->person_id, 'newsletter_signedup', 1 );
            }
        }
        
        //there is a bug in the main event manager code. the next hook to run with
        //priority 10 is em_data_privacy_consent_booking_save (priority 10 )
        //in em-data-privacy.php - but this overrides the em_data_privacy_consent user timestamp
        //pdate_user_meta( $EM_Booking->person_id, 'em_data_privacy_consent', current_time('mysql') );
        //even if the checkbox was hidden :( so here we make sure it doesnt update it
        //if it's already set and the setting to remove the checkbox is set
        if( get_option('dbem_data_privacy_consent_remember') == 1 && !empty( get_user_meta( $EM_Booking->person_id, 'em_data_privacy_consent', true ) ) ) {
            remove_filter( 'em_booking_save', 'em_data_privacy_consent_booking_save', 10 );
        }
        
    }
    return $result;
}

/*
 * Add recaptcha to events page booking form AND standalone registration form footer AFTER checkboxes
 */
function booking_and_reg_form_footer_recaptcha( $EM_Object = false ){
	if( !empty($EM_Object) && (!empty($EM_Object->booking_id) || !empty($EM_Object->post_id)) ) {
        return; //already saved so consent was given at one point
    }
    
    if( !is_user_logged_in() && class_exists('UnrRecaptchaPlugin') && UnrRecaptchaPlugin::site_is_using_recaptcha() ) {
        //echo UnrRecaptchaPlugin::render_frontend_div_html_use_settings_version('sign-up-and-book', 'em-booking-submit', false, 'sign-up-and-book', 'booking-form');
        echo UnrRecaptchaPlugin::render_frontend_div_html_use_settings_version('sign-up-and-book', null, true, 'signUpAndBook', /*'booking-form'*/null);
        echo UnrRecaptchaPlugin::get_recaptcha_version()===UNR_RECAPTURE_VERSION_V2 ? "<p></p>" : "";
    }
}
add_action('em_booking_form_footer', 'booking_and_reg_form_footer_recaptcha', 11, 0);
add_action( 'register_form', 'booking_and_reg_form_footer_recaptcha' );

/*
 * Add recaptcha to events page login footer
 */
function login_form_footer_recaptcha() {
    if( class_exists('UnrRecaptchaPlugin') && UnrRecaptchaPlugin::site_is_using_recaptcha() ) {
        if( ueme_is_frontend_event_details_page() )
        {
            //echo UnrRecaptchaPlugin::render_frontend_div_html_use_settings_version('event_page_login', 'em_wp-submit', true, 'login_page_login' ,'em-booking-login-form');
            echo UnrRecaptchaPlugin::render_frontend_div_html_use_settings_version('login', null, false, 'eventPageLogin' ,/*'em-booking-login-form'*/null);
            echo UnrRecaptchaPlugin::get_recaptcha_version()===UNR_RECAPTURE_VERSION_V2 ? '<div style="height:17px"></div>' : '';    
        }
        else
        {
            echo UnrRecaptchaPlugin::render_frontend_div_html_use_settings_version('login', null, true, 'loginPageLogin', /*'login'*/null);
        }
    }
}
add_action('login_form', 'login_form_footer_recaptcha', 11, 0);

/* Called in em-actions.php before em_booking_validate is called
 * we're using this to validate all the fields on the reg form
 * if they exist!
 */
//add_filter('em_booking_validate','validate_booking_recaptcha_response', 1, 2);
function validate_booking_recaptcha_response($result, $EM_Booking) {

    //only validate if logged out, ajax and recaptcha plugin installed, and plugin says use recaptcha
    if ( !is_user_logged_in() && class_exists('UnrRecaptchaPlugin') && UnrRecaptchaPlugin::site_is_using_recaptcha() ) {
        $errorStringArray = array();
        if( !UnrRecaptchaPlugin::validate_recaptcha_response_use_settings_version( "sign-up-and-book", false, $errorStringArray ) ){
            	$EM_Booking->add_error( $errorStringArray ); //add_error accepts either a string or a string array!
            $result = false;
        }
    }
    return $result;
}






/* Login Recaptcha */
//add script to head tag on login page
function login_recaptcha_script() {
	do_action('unr_recaptcha_enqueue_scripts');
}
add_action("login_enqueue_scripts", "login_recaptcha_script");

//display recaptcha to login page login form
//commented out because already done above, events page and login page use same hook: login_form
/*
function login_page_display_login_captcha(){
    if( class_exists('UnrRecaptchaPlugin') && UnrRecaptchaPlugin::site_is_using_recaptcha() ) {
        echo UnrRecaptchaPlugin::render_frontend_div_html_use_settings_version("event_lage_login", 'em_wp-submit','em-booking-login-form');
    }
}
add_action('login_form', 'login_page_display_login_captcha', 11, 0);
*/

function verify_login_captcha($user, $password) {
    
    //this global stores the user_data in a temp global var set
    //by master plugin in em_register_new_user in em-functions.php
    global $em_temp_user_data;
    
    //only validate if we're logging in from login form and NOT as part of a registration on the events page AND recaptcha plugin installed, and plugin says use recaptcha
    if ( !isset($em_temp_user_data) && class_exists('UnrRecaptchaPlugin') && UnrRecaptchaPlugin::site_is_using_recaptcha() ) {
        $errorStringArray = array();
        if( !UnrRecaptchaPlugin::validate_recaptcha_response_use_settings_version( "login", true, $errorStringArray ) ){
            return new WP_Error( 'invalid_recpatcha',  sprintf( __( implode( "<br /><br />", $errorStringArray ) ) ) );
        }
    }
    return $user;
}
add_filter("wp_authenticate_user", "verify_login_captcha", 10, 2);


/* 
 * Validate recaptcha on standalone registration form
 */
function oley_validate_recaptcha_field( $errors, $sanitized_user_login, $user_email ) {

    //only validate if logged out, ajax and recaptcha plugin installed, and plugin says use recaptcha
    if ( !is_user_logged_in() && class_exists('UnrRecaptchaPlugin') && UnrRecaptchaPlugin::site_is_using_recaptcha() ) {
        $errorStringArray = array();
        if( !UnrRecaptchaPlugin::validate_recaptcha_response_use_settings_version( "sign-up-and-book", false, $errorStringArray ) ){
            foreach ($errorStringArray as $errorMessageString) {
                $errors->add( 'recaptcha_error', $errorMessageString ); //add_error accepts either a string or a string array!
            }
        }
    }
    return $errors;    
}
add_filter( 'registration_errors', 'oley_validate_recaptcha_field', 10, 3 );