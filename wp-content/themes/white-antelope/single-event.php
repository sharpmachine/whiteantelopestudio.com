<?php 
/*
	* Template Name: Events
*/
get_header(); ?>

		<section id="page" class="single-post-event">

			<?php
			/* Run the loop to output the post.
			 * If you want to overload this in a child theme then include a file
			 * called loop-single.php and that will be used instead.
			 */
			get_template_part( 'loop', 'event' );
			?>
		</section><!-- #page -->
		
<?php get_footer(); ?>