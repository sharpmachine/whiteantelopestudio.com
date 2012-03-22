		
			<footer role="contentinfo">
				<article class="span-5 colborder">
					<?php if ( is_active_sidebar( 'first-footer-widget-area' ) ) : ?>
						<?php dynamic_sidebar( 'first-footer-widget-area' ); ?>
					<?php endif; ?>
				</article>
				<article class="span-5 colborder">
					<?php if ( is_active_sidebar( 'second-footer-widget-area' ) ) : ?>
						<?php dynamic_sidebar( 'second-footer-widget-area' ); ?>
					<?php endif; ?>
				</article>
				<article class="span-5 colborder">
					<?php if ( is_active_sidebar( 'third-footer-widget-area' ) ) : ?>
						<?php dynamic_sidebar( 'third-footer-widget-area' ); ?>
						
						<?php else: ?>
							
					
						<h2>Last Blog Entry</h2>
						<?php query_posts('posts_per_page=1'); ?>
						<?php if (have_posts()) : ?>
							<?php while (have_posts()) : the_post(); ?>	
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<p><?php echo get_footer_excerpt(); ?></p>
							<?php endwhile; else : ?>
								<p>Bummer, no posts yet!</p>
						<?php endif; ?>
					<?php endif; ?>
				</article>
				<article class="span-6 last">
					<?php if ( is_active_sidebar( 'fourth-footer-widget-area' ) ) : ?>
						<?php dynamic_sidebar( 'fourth-footer-widget-area' ); ?>
					<?php endif; ?>
				</article>
				<div id="site-info">
					&copy;<?php echo date ('Y'); ?> <?php bloginfo( 'name' ); ?> | <a href="<?php bloginfo('url'); ?>/terms-conditions">Terms &amp; Conditions</a> | <a href="<?php bloginfo('url'); ?>/privacy-policy">Privacy Policy</a> | <a href="<?php bloginfo('url'); ?>/shipping-returns-policy">Shipping &amp; Returns Policy</a>
				</div><!-- #site-info -->
			</footer>
		</div><!-- .container -->
	</div><!-- #wrapper -->
<!-- scripts concatenated and minified via ant build script-->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>	
<script src="<?php bloginfo ('template_directory'); ?>/js/plugins.js"></script>
<script src="<?php bloginfo ('template_directory'); ?>/js/script.js"></script>

<!-- Remove these before deploying to production -->
<script src="<?php bloginfo ('template_directory'); ?>/js/hashgrid.js" type="text/javascript"></script>

<?php wp_footer(); ?>
</body>
</html>
