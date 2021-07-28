<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen Child Theme by up-n-running
 * @since Twenty Seventeen 1.0
 * @version 1.12
 */

get_header(); ?>
<div class="wrap">
	<?php if ( is_home() && ! is_front_page() ) : ?>
		<header class="page-header">
			<h1 class="page-title"><?php single_post_title(); ?></h1>
		</header>
	<?php else : ?>
	<header class="page-header">
		<h1 class="page-title"><?php _e( 'Frequently Asked Questions', 'twentyseventeen' ); ?></h1>
	</header>
	<?php endif; ?>
	<div id="primary" class="content-area" style="width: 100% !important">
            <main id="main" class="site-main" role="main"  style="width: 100% !important">
             <?php
                if ( have_posts() ) :
                        ?>
                <div class="accordion-container">                    
                    <div class="accordion">
<?php

				// Start the Loop.
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/post/content', 'faqs' );
				endwhile;

				the_posts_pagination(
					array(
						'prev_text'          => twentyseventeen_get_svg( array( 'icon' => 'arrow-left' ) ) . '<span class="screen-reader-text">' . __( 'Previous page', 'twentyseventeen' ) . '</span>',
						'next_text'          => '<span class="screen-reader-text">' . __( 'Next page', 'twentyseventeen' ) . '</span>' . twentyseventeen_get_svg( array( 'icon' => 'arrow-right' ) ),
						'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentyseventeen' ) . ' </span>',
					)
				);
                        ?>
                    </div>
                </div>  
                <?php
                else :
                        get_template_part( 'template-parts/post/content', 'none' );
                endif;
		?>
            </main><!-- #main -->
	</div><!-- #primary -->
	<?php /* get_sidebar(); KILL SIDEBAR ON THIS PAGE */ ?>
</div><!-- .wrap -->

<script type="text/javascript">
    //<![CDATA[
    // Code By Webdevtrick ( https://webdevtrick.com )
    //makes accordian work on mouse click
    const accordian_items = document.querySelectorAll(".accordion-container .accordion .title a");

    function toggleAccordion()
    { 
      this.classList.add('clicked');  
      accordian_items.forEach(
        function(item) {	
          if( item.classList.contains('active') && !item.classList.contains('clicked') ) {
            item.classList.remove('active');
            item.parentElement.nextElementSibling.classList.remove('active');
          }		
        }
      ); 
      this.classList.remove('clicked');  
        
      this.classList.toggle('active');
      this.parentElement.nextElementSibling.classList.toggle('active');
    }

    accordian_items.forEach(item => item.addEventListener('click', toggleAccordion));
    //]]>
</script>

<?php
get_footer();