<?php get_header(); ?>
		

		<div class="blog-filter shopp-filter">Filter by:
			<a href="#" class="all">All</a>
			<?php wp_nav_menu( array('menu' => 'Gallery Filter By', 'container' => 'false' )); ?>
		</div>

		<section id="page">
			<?php get_template_part( 'loop', 'shopp' ); ?>
		</section><!-- #page -->
		
<?php get_footer(); ?>
