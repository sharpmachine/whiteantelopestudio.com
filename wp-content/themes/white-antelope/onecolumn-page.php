<?php
/**
 * Template Name: One column, no sidebar
 *
 * A custom page template without sidebar.
 *
 */

get_header(); ?>

		<section id="page">

			<?php get_template_part( 'loop', 'page' ); ?>
			
		</section><!-- #page -->

<?php get_footer(); ?>
