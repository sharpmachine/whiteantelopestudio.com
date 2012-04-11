<?php get_header(); ?>
		<?php if(is_category('tutorials')): ?>
			<div class="blog-filter">Filter by:
				<?php wp_nav_menu( array('menu' => 'Blog Filter By', 'container' => 'false' )); ?>
			</div>
		<?php endif; ?>
		<section id="page" class="span-19">
			<h1 class="page-title"><?php
				printf( __( 'Category: %s', 'smm' ), '<span>' . single_cat_title( '', false ) . '</span>' );
			?></h1>
			<?php
				$category_description = category_description();
				if ( ! empty( $category_description ) )
					echo '<div class="archive-meta">' . $category_description . '</div>';

			get_template_part( 'loop', 'blog' );
			?>

		</section><!-- #page -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
