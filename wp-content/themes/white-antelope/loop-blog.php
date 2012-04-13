		<?php if (have_posts()) : ?>
			
	<?php while (have_posts()) : the_post(); ?>
			
		
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
								<img src="<?php bloginfo('template_directory'); ?>/images/categories.png" width="15" height="20" alt="Categories"><?php printf( __( '%2$s', 'twentyten' ), 'entry-utility-prep entry-utility-prep-cat-links', get_the_category_list( ', ' ) ); ?>
							</span>
						<?php endif; ?>
						<?php
							$tags_list = get_the_tag_list( '', ', ' );
							if ( $tags_list ):
						?>
							<span class="tag-links">
								<img src="<?php bloginfo('template_directory'); ?>/images/tags.png" width="13" height="18" alt="Tags"><?php printf( __( '%2$s', 'twentyten' ), 'entry-utility-prep entry-utility-prep-tag-links', $tags_list ); ?>
							</span>
						<?php endif; ?>
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
			
	<?php else : ?>
			
		<p>No posts yet!  Check back soon.</p>
			
<?php endif; ?>