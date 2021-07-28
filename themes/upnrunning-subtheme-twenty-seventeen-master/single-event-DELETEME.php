<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since Twenty Seventeen 1.0
 * @version 1.0
 */

get_header(); ?>

<div class="wrap">
    	<header class="page-header">
		<h1 class="page-title"><?php _e( 'Class Details', 'twentyseventeen' ); ?></h1>
	</header>
	<div id="primary" class="content-area" style="width: 100% !important">
            <main id="main" class="site-main" role="main"  style="width: 100% !important">
HELLO!
			<?php
			while ( have_posts() ) :
				the_post();

				get_template_part( 'template-parts/page/content', 'page' );

				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;

			endwhile; // End the loop.
			?>

          </main><!-- #main -->
	</div><!-- #primary -->
	<?php /* get_sidebar(); KILL SIDEBAR ON THIS PAGE */ ?>
</div><!-- .wrap -->

<?php
get_footer();