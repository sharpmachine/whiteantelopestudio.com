<?php if(shopp('category','hasproducts','load=coverimages')): ?>
	<div class="category">

		<?php while(shopp('category','products')): ?>
				
			<a href="<?php shopp('product','url'); ?>">
					<div class="small-box photo-box float-left gallery-box">
						<div class="image">
							<?php shopp('product','coverimage','width=239&height=239&fit=crop&quality=100&class=product-image'); ?>
						</div>
						<div class="content">
							<div class="inner">
								<div class="gallery-icons">
									<img src="<?php bloginfo('template_directory'); ?>/images/view.png" width="42" height="42" alt="View" class="shopp-img">
									<img src="<?php bloginfo('template_directory'); ?>/images/cart.png" width="42" height="42" alt="Cart">
								</div>
								<h2>View Work</h2>
							</div>
						</div>
					</div>
				</a>
		<?php endwhile; ?>

	</div>
	<div class="alignright"><?php shopp('category','pagination','show=10'); ?></div>


<?php else: ?>
	<?php if (!shopp('catalog','is-landing')): ?>
	<?php shopp('catalog','breadcrumb'); ?>
	<h3><?php shopp('category','name'); ?></h3>
	<p>No products were found.</p>
	<?php endif; ?>
<?php endif; ?>
