<?php 
/*
	* Template Name: Events
*/
get_header(); ?>

		<section id="page" class="span-18 append-1">
			<article id="single-blog-post">

			<?php
			/* Run the loop to output the post.
			 * If you want to overload this in a child theme then include a file
			 * called loop-single.php and that will be used instead.
			 */
			get_template_part( 'loop', 'single' );
			?>
			</article>
		</section><!-- #page -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>