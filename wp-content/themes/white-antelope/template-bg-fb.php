<?php 
/*
	* Template Name: White Background w/ Fitler By
*/
get_header(); ?>
		<section id="page">
			<div class="filter-by">Filter by:
				<ul>
				<?php
							if (is_page( )) 
							{
								$page = $post->ID;
								if ($post->post_parent) {
								$page = $post->post_parent;
							}
						$children=wp_list_pages( 'echo=0&child_of=' . $page . '&title_li=' );
							if ($children) 
							{
								$output = wp_list_pages ('echo=0&child_of=' . $page . '&title_li=');
							}
						}
						echo $output;
						?>
						</ul>
			</div>
			<article id="content">
			<?php get_template_part( 'loop', 'page-bg' ); ?>
			</article>
		</section><!-- #page -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
