<?php get_header(); ?>

	<section id="page">

		<div id="post-0" class="post error404 not-found">
			<h1 class="entry-title"><?php _e( 'Not Found', 'smm' ); ?></h1>
			<div class="entry-content">
				<p><?php _e( 'Apologies, but the page you requested could not be found. Perhaps searching will help.', 'smm' ); ?></p>
				<?php get_search_form(); ?>
				<h3>Try these links too!</h3>
				<?php wp_list_pages(); ?>
				<?php wp_list_categories(); ?>
			</div><!-- .entry-content -->
		</div><!-- #post-0 -->

	</section><!-- #page -->
	
	<script type="text/javascript">
		// focus on search field after it has loaded
		document.getElementById('s') && document.getElementById('s').focus();
	</script>

<?php get_footer(); ?>