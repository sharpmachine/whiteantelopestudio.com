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
			<article class="single-blog-post">
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
							<span class="date"><?php echo do_shortcode('[event post_id="'. get_the_ID() . '"]#m.#d.#y - #@_{m.d.y}[/event]'); ?></span>
						
							<span class="city-state"><?php echo do_shortcode('[event post_id="'. get_the_ID() . '"]#_LOCATIONTOWN, #_LOCATIONSTATE[/event]'); ?></span> 
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
					<div class="clear"></div>
				</div><!-- #post-## -->
			</article>
			<?php echo do_shortcode(
					'[event post_id="'. get_the_ID() . '"]
					{has_bookings}
			<article class="single-blog-post registration-form">
				<div class="entry-content">
					<blockquote>
						Please fill in your details below to register for this event.
					</blockquote>
					#_BOOKINGFORM
					<img class="cc-logos" title="Credit Cards" src="'. get_bloginfo('url') . '/wp-content/uploads/2011/06/cards.png" alt="Credit Cards" width="144" height="21" />All payments are processed by<img class="paypal-logo" title="Paypal Logo" src="'. get_bloginfo('url') . '/wp-content/uploads/2011/06/paypal.png" alt="Paypal Logo" width="74" height="21" />
				</div>
			</article>
					{/has_bookings}
					[/event]'); 
			?>
				
<?php endwhile; // end of the loop. ?>