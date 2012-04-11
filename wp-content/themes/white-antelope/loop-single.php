<?php
/**
 * The loop that displays a single post.
 *
 * The loop displays the posts and the post content.  See
 * http://codex.wordpress.org/The_Loop to understand it and
 * http://codex.wordpress.org/Template_Tags to understand
 * the tags used in it.
 *
 * This can be overridden in child themes with loop-single.php.
 *
 */
?>

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<div id="single-post-img">
						<?php if ( has_post_thumbnail() ) {
							the_post_thumbnail( array (80, 80) );
							} else { ?>
							<img src="<?php bloginfo('template_directory'); ?>/images/default-post-thumb.jpg" alt="<?php the_title(); ?>" class="post-thumb" />
					<?php } ?>
					</div>
					<div class="single-post-details">
						<h1 class="entry-title"><?php the_title(); ?></h1>
	
						<div class="post-details">
							<span class="date"><?php the_time('m.d.y'); ?></span> 
							<span class="author"><?php the_author_posts_link(); ?></span> 
							<a href="<?php comments_link(); ?>" class="comment-count"><?php comments_number( ); ?></a>
						</div>
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
						<div class="clear"></div>
					</div>
					<div class="clear"></div>
					
					<div class="entry-content">
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'smm' ), 'after' => '</div>' ) ); ?>
					</div><!-- .entry-content -->

<?php if ( get_the_author_meta( 'description' ) ) : // If a user has filled out their description, show a bio on their entries  ?>
					<div id="entry-author-info">
						<div id="author-avatar">
							<?php echo get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'smm_author_bio_avatar_size', 115 ) ); ?>
						</div><!-- #author-avatar -->
						<div id="author-description">
							<h2><?php printf( esc_attr__( 'About %s', 'smm' ), get_the_author() ); ?></h2>
							<?php the_author_meta( 'description' ); ?>
							<div id="author-link">
								<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>" rel="author">
									<?php printf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'smm' ), get_the_author() ); ?>
								</a>
							</div><!-- #author-link	-->
						</div><!-- #author-description -->
					</div><!-- #entry-author-info -->
<?php endif; ?>

				</div><!-- #post-## -->

				<?php comments_template( '', true ); ?>

<?php endwhile; // end of the loop. ?>