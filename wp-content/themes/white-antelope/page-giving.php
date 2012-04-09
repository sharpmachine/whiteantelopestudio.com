<?php get_header(); ?>
		<section id="page">
			<article id="content">
			<?php get_template_part( 'loop', 'page-bg' ); ?>
				<article id="payment-buttons">
					Make a 
					<a class="button paypal" title="One-Time Donation" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=<?php the_field('one_time_donation_id'); ?>">One-Time Donation</a>
					or become a Monthly Partner
					<a class="button paypal" title="<?php the_field('mp_1_denomination'); ?>" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=3004825"><?php the_field('mp_1_denomination'); ?></a>
				</article>
			</article>
		</section><!-- #page -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
