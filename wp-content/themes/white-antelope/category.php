<?php get_header(); ?>

		<section id="page" class="span-20">

			<h1 class="page-title"><?php
				printf( __( 'Category Archives: %s', 'smm' ), '<span>' . single_cat_title( '', false ) . '</span>' );
			?></h1>
			<?php
				$category_description = category_description();
				if ( ! empty( $category_description ) )
					echo '<div class="archive-meta">' . $category_description . '</div>';

			get_template_part( 'loop', 'category' );
			?>

		</section><!-- #page -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
