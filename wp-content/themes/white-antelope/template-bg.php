<?php 
/*
	* Template Name: White Background
*/
get_header(); ?>
		<section id="page">
			<div class="filter-by">Filter by: <a href="<?php bloginfo('url'); ?>/about/edie">Edie</a> <a href="<?php bloginfo('url'); ?>/about/white-antelope-studio">White Antelope Studio</a></div>
			<article id="content">
			<?php get_template_part( 'loop', 'page-bg' ); ?>
			</article>
		</section><!-- #page -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
