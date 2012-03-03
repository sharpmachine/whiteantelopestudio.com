		
			<footer role="contentinfo">
				<div id="site-info">
					&copy;<?php echo date ('Y'); ?> <a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
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
