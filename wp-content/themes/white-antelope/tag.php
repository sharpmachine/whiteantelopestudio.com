<?php get_header(); ?>

		<section id="page" class="span-20">

			<h1 class="page-title"><?php
				printf( __( 'Tag Archives: %s', 'smm' ), '<span>' . single_tag_title( '', false ) . '</span>' );
			?></h1>

<?php get_template_part( 'loop', 'tag' ); ?>
		</section><!-- #page -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
