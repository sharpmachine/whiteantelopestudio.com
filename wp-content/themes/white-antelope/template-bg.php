<?php 
/*
	* Template Name: White Background
*/
get_header(); ?>
		<section id="page">
			<article id="content">
			<?php get_template_part( 'loop', 'page-bg' ); ?>
			</article>
		</section><!-- #page -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
