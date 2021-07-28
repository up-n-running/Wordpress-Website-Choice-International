<?php
/* 
 * This file generates the default login form within the booking form (if enabled in options).
 */

//get category image url to display in background
$count_cats = count($EM_Event->get_categories()->categories) > 0;
$category_image_url = '';
if( count($EM_Event->get_categories()->categories) > 0 )
{
    foreach($EM_Event->get_categories() as $EM_Category) {
        if( $EM_Category->get_image_url() != '' ) {
            $category_image_url = $EM_Category->get_image_url();
        }
    }
}
//this loginform top anchor is used when the user clicks a link at the top
//of the page to login - when it scrolls down to the anchor it scrolls down
//too far cos the page doesnt realise that the top inch or so of the page
//is covered by the nav menu - so anchor links have to be raised.
?>
<div class="em-booking-login">
    <span class="or-overlay"><img width="49" height="49" src="<?php echo get_stylesheet_directory_uri() ?>/assets/img/or.png"></span>
    <div class="form-header-box">
        <a style="position: relative; top: -105px;" name="loginformtop"></a>
        <h3>Log in</h3><br />
        <p class="intro"><strong>Already have an account?</strong><br />Login below before booking:</p>
    </div>
    <div class="login-background" style="background-image: url('<?php echo esc_url( $category_image_url ) ?>');">
        <form class="em-booking-login-form" 
              onsubmit="return unrExecuteRecaptchaIfManual(event, 'login');" 
              onsubmitOLD="console.log('FORM TAG ONSUBMIT (em-booking-form), this = %o', this);console.log('FORM TAG ONSUBMIT em-booking-form, event = %o', event);"
              action="<?php echo site_url('wp-login.php', 'login_post'); ?>" method="post">
        <p>
            <label><?php esc_html_e( 'Email','events-manager') ?></label>
            <input type="text" name="log" class="input" value="" autocomplete="on" />
        </p>
        <p style="margin-bottom: 45px !important;">
            <label><?php esc_html_e( 'Password','events-manager') ?></label>
            <input type="password" name="pwd" class="input" value="" autocomplete="on"/>
        </p>
        <?php do_action('login_form'); ?>
        <input type="submit" name="wp-submit" id="em_wp-submit" value="<?php esc_html_e('Log In', 'events-manager'); ?>" tabindex="100" />
        <label for="em_rememberme" style="padding-top: 10px; padding-bottom: 5px"><input name="rememberme" type="checkbox" id="em_rememberme" value="forever" /><span style="padding-right: 8px"><?php esc_html_e( 'Remember Me','events-manager') ?></span></label>
        <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_site_url(false, $_SERVER['REQUEST_URI']) ); ?>#em-booking" />
        <div style="height:8px"></div>
        <?php        
        //Signup Links
        if ( get_option('users_can_register') ) {
            if ( function_exists('bp_get_signup_page') ) { //Buddypress
                $register_link = bp_get_signup_page();
            }elseif ( file_exists( ABSPATH."/wp-signup.php" ) ) { //MU + WP3
                $register_link = site_url('wp-signup.php', 'login');
            } else {
                $register_link = site_url('wp-login.php?action=register', 'login');
            }
            ?>
            <a href="<?php echo $register_link ?>"><?php esc_html_e('Sign Up','events-manager') ?></a>&nbsp;&nbsp;|&nbsp;&nbsp; 
            <?php
        }
        ?>	                    
        <a href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>" title="<?php esc_html_e('Password Lost and Found', 'events-manager') ?>"><?php esc_html_e('Lost your password?', 'events-manager') ?></a><br />
        <br />
        <br />
        <br />
        <br />
        </form>
    </div>
</div>