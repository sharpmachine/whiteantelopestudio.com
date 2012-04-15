<?php get_header(); ?>

		<section id="page">
			<article id="friends">
			<?php query_posts('post_type=friends'); ?>
			<?php if (have_posts()) : ?>
			
	<?php while (have_posts()) : the_post(); ?>
			
		
		
		<a href="<?php the_field('friends_url'); ?>">
					<div class="small-box photo-box float-left friends-box">
						<div class="image">
							<img src="<?php the_field('friends_logo'); ?>" width="240" height="240" alt="<?php the_field('title_bottom_left', 'options'); ?>">
						</div>
						<div class="content">
							<div class="inner">
								<h2><?php the_title(); ?></h2>
								<p><?php the_field('friends_url'); ?></p>
							</div>
						</div>
					</div>
				</a>
			
	<?php endwhile; ?>
			
		<?php // Navigation ?>
			
	<?php else : ?>
			
		<?php // No Posts Found ?>
			
<?php endif; ?>
				<div class="clear"></div>
			</article>
		</section><!-- #page -->

<?php get_footer(); ?>
