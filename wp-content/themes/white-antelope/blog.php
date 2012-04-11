<?php 
/*
	Template Name: Blog
*/
get_header(); ?>

		<section id="page" class="span-19  blog-landing">
		<?php get_template_part( 'loop', 'blog' ); ?>
		<?php rewind_posts(); ?>
			
		<?php
			$temp = $wp_query;
			$wp_query= null;
			$wp_query = new WP_Query();
			$wp_query->query('&paged='.$paged);
			while ($wp_query->have_posts()) : $wp_query->the_post();
		?>
			
				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<div class="span-3 append-24 prepend-24 post-img">
						<a href="<?php the_permalink(); ?>"><?php if ( has_post_thumbnail() ) {
							the_post_thumbnail( array (80, 80) );
							} else { ?>
							<img src="<?php bloginfo('template_directory'); ?>/images/default-post-thumb.jpg" alt="<?php the_title(); ?>" class="post-thumb" />
							<?php } ?>
						</a>
					</div>
					
					<div class="span-12 last">
						<h2 class="entry-title">
							<a href="<?php the_permalink(); ?>"><?php the_title();  ?></a>
						</h2>
						<div class="post-details"><span class="date"><?php the_time('m.d.y'); ?></span> <span class="author"><?php the_author_posts_link(); ?></span> <a href="<?php comments_link(); ?>" class="comment-count"><?php comments_number( ); ?></a></div>
						
						<div class="excerpt"><?php the_excerpt(); ?></div>
						
						<div class="entry-utility">
						<?php if ( count( get_the_category() ) ) : ?>
							<span class="cat-links">
								<?php printf( __( '<span class="%1$s">Posted in</span> %2$s', 'twentyten' ), 'entry-utility-prep entry-utility-prep-cat-links', get_the_category_list( ', ' ) ); ?>
							</span>
						<?php endif; ?>
						<?php
							$tags_list = get_the_tag_list( '', ', ' );
							if ( $tags_list ):
						?>
						<span class="meta-sep">|</span>
							<span class="tag-links">
								<?php printf( __( '<span class="%1$s">Tagged</span> %2$s', 'twentyten' ), 'entry-utility-prep entry-utility-prep-tag-links', $tags_list ); ?>
							</span>
						<?php endif; ?>
						<?php edit_post_link( __( 'Edit', 'twentyten' ), '<span class="meta-sep">|</span> <span class="edit-link">', '</span>' ); ?>
						</div><!-- .entry-utility -->
					</div>
					<div class="clear"></div>
				</div>

			<?php endwhile; ?>
	
			<?php if (  $wp_query->max_num_pages > 1 ) : ?>
							<?php if(function_exists('wp_paginate')) {
			    wp_paginate();
			} ?>
			
			<?php endif; ?>

			<?php $wp_query = null; $wp_query = $temp;?>
			
		</section><!-- #page -->
<?php get_sidebar(); ?> 
<?php get_footer(); ?>
