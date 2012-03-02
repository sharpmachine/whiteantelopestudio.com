		<section id="sidebar" class="span-4 last">
					
					<nav role="navigation">
					  <?php /*  Allow screen readers / text browsers to skip the navigation menu and get right to the good stuff */ ?>
						<div class="skip-link screen-reader-text"><a href="#content" title="<?php esc_attr_e( 'Skip to content', 'smm' ); ?>"><?php _e( 'Skip to content', 'smm' ); ?></a></div>
						<?php /* Our navigation menu.  If one isn't filled out, wp_nav_menu falls back to wp_page_menu.  The menu assiged to the primary position is the one used.  If none is assigned, the menu with the lowest ID is used.  */ ?>
						<?php wp_nav_menu( array( 'container_class' => 'menu-header', 'theme_location' => 'primary' ) ); ?>
					</nav><!-- nav -->
	

		</section><!-- #sidebar -->