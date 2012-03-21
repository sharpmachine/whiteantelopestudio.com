		
			<footer role="contentinfo">
				<article class="span-5 colborder">
					<h2>Invite Edie</h2>
					<p>Sed in arcu felis, vel dictum odio. Nunc mollis nulla eget magna lobortis quis egestas risus feugiat.</p>
					<a href="<?php bloginfo('url'); ?>/contact">Contact Edie Now</a>
				</article>
				<article class="span-5 colborder">
					<h2>Upcoming Events</h2>
					<ul class="footer-events">
						<li><a href="#">+ Freedom to Worship</a></li>
						<li><a href="#">+ Another Cool Event</a></li>
						<li><a href="#">+ The Coolest Event</a></li>
					</ul>
					<a href="<?php bloginfo('url'); ?>/events">See All Events</a>
				</article>
				<article class="span-5 colborder">
					<h2>Last Blog Entry</h2>
					<?php query_posts('posts_per_page=1'); ?>
					<?php if (have_posts()) : ?>
									
						<?php while (have_posts()) : the_post(); ?>
										
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<?php the_excerpt(); ?>
										
						<?php endwhile; ?>
										
						<?php else : ?>
										
							<?php // No Posts Found ?>
									
					<?php endif; ?>
					
				
				</article>
				<article class="span-6 last">
					<p>White Antelope exists to model, promote, teach and inspire art, including prophetic art, with the goal of helping others understand and harness the inherent property of healing that art carries, both through the production and appreciation of artistic works.</p>
					<a href="#">Sign Up For Newsletter</a>
				</article>
				<div id="site-info">
					&copy;<?php echo date ('Y'); ?> <?php bloginfo( 'name' ); ?> | <a href="<?php bloginfo('url'); ?>/terms-conditions">Terms &amp; Conditions</a> | <a href="<?php bloginfo('url'); ?>/privacy-policy">Privacy Policy</a> | <a href="#">Shipping &amp; Returns Policy</a>
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
