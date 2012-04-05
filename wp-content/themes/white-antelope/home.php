<?php get_header(); ?>

		<section id="page">
			<div class="col-240">
				<div class="small-box">
				</div>
				<a href="<?php bloginfo('url'); ?>/blog">
					<div class="small-box menu-box">
						<span>
							<h2>Blog</h2>
							<p>Articles, tutorials, inspiration and more...</p>
						</span>
					</div>
				</a>
				<a href="<?php the_field('piece_url_bottom_left', 'options'); ?>">
					<div class="small-box photo-box">
						<div class="image">
							<img src="<?php the_field('image_bottom_left', 'options'); ?>" width="240" height="240" alt="<?php the_field('title_bottom_left', 'options'); ?>">
						</div>
						<div class="content">
							<div class="inner">
								<h2><?php the_field('title_bottom_left', 'options'); ?></h2>
								<p><?php the_field('description_bottom_left', 'options'); ?></p>
							</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-240">
				<a href="<?php bloginfo('url'); ?>/events">
					<div class="small-box menu-box">
						<span>
							<h2>Upcoming Events</h2>
							<?php echo do_shortcode('[events_list limit="1"]
								<h3>#_EVENTNAME</h3>
								<p>#l, #F #j, #Y at #g:#i#a until #@_{l, F j, Y} in #_LOCATIONTOWN, #_LOCATIONSTATE</p>
							[/events_list]'); ?>
						</span>
					</div>
				</a>
				<div class="small-box video-box">
					<a href="<?php the_field('video_url', 'options'); ?>" rel="lightbox[video]" class="video-gallery">
						<img src="<?php the_field('background_image', 'options'); ?>" width="240" height="240" alt="Painting">
						<div class="video-box-bg">
							<div class="play-button">
								<img src="<?php bloginfo('template_directory'); ?>/images/play-button.gif" width="54" height="55" alt="Video">
							</div>
						</div>
					</div>
				</a>
				<a href="<?php the_field('piece_url_bottom_center', 'options'); ?>">
					<div class="small-box photo-box">
						<div class="image">
							<img src="<?php the_field('image_bottom_center', 'options'); ?>" width="240" height="240" alt="<?php the_field('title_bottom_center', 'options'); ?>">
						</div>
						<div class="content">
							<div class="inner">
								<h2><?php the_field('title_bottom_center', 'options'); ?></h2>
								<p><?php the_field('description_bottom_center', 'options'); ?></p>
							</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-480">
				<a href="<?php the_field('piece_url_top_right', 'options'); ?>">
					<div class="large-box photo-box">
						<div class="image">
							<img src="<?php the_field('image_top_right', 'options'); ?>" width="480" height="480" alt="<?php the_field('title_top_right', 'options'); ?>">
						</div>
						<div class="content">
							<div class="inner">
								<h2><?php the_field('title_top_right', 'options'); ?></h2>
								<p><?php the_field('description_top_right', 'options'); ?></p>
							</div>
						</div>
					</div>
				</a>
				<a href="<?php bloginfo('url'); ?>/gallery">
					<div class="small-box menu-box float-left">
						<span>
							<h2>Browse the Gallery</h2>
							<p>View our art and buy your favorite piece today.</p>
						</span>
					</div>
				</a>
				<a href="<?php the_field('about_url', 'options'); ?>">
					<div class="small-box photo-box about-box float-left">
						<div class="image">
							<img src="<?php the_field('headshot', 'options'); ?>" width="240" height="240" alt="<?php the_field('name', 'options'); ?>">
						</div>
						<div class="content">
							<div class="inner">
								<h2><?php the_field('name', 'options'); ?></h2>
								<p><?php the_field('title_position', 'options'); ?></p>
							</div>
						</div>
					</div>
				</a>
			</div>
		<div class="clear"></div>
		</section><!-- #page -->
<?php get_footer(); ?>
