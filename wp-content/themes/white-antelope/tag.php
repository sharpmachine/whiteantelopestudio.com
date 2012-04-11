<?php get_header(); ?>

		<section id="page" class="span-19">

			<h1 class="page-title"><?php
				printf( __( 'Tag: %s', 'smm' ), '<span>' . single_tag_title( '', false ) . '</span>' );
			?></h1>

<?php get_template_part( 'loop', 'blog' ); ?>
		</section><!-- #page -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
