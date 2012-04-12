<?php if (shopp('product','found')): ?>

	<div class="shopp-gallery-images">
		<?php shopp('product','gallery','p_setting=gallery-previews&thumbsetting=gallery-thumbnails'); ?>
	</div>
	
	<div class="gallery-functions">
		<h3><?php shopp('product','name'); ?></h3>
		<?php if (shopp('product','onsale')): ?>
		<span class="original price"><?php shopp('product','price'); ?></h3>
		<h3 class="sale price"><?php shopp('product','saleprice'); ?></h3>
		<?php if (shopp('product','has-savings')): ?>
			<p class="savings">You save <?php shopp('product','savings'); ?> (<?php shopp('product','savings','show=%'); ?>)!</p>
		<?php endif; ?>
	<?php else: ?>
		<span class="price"><?php shopp('product','price'); ?></span>
	<?php endif; ?>
	
	
	</div>

	<form action="<?php shopp('cart','url'); ?>" method="post" class="shopp product validate validation-alerts">
		<?php if(shopp('product','has-variations')): ?>
		<ul class="variations">
			<?php shopp('product','variations','mode=multiple&label=true&defaults=Select an option&before_menu=<li>&after_menu=</li>'); ?>
		</ul>
		<?php endif; ?>
		<?php if(shopp('product','has-addons')): ?>
			<ul class="addons">
				<?php shopp('product','addons','mode=menu&label=true&defaults=Select an add-on&before_menu=<li>&after_menu=</li>'); ?>
			</ul>
		<?php endif; ?>

		<p>Quantity<?php shopp('product','quantity','class=selectall&input=menu'); ?>
		<?php shopp('product','addtocart'); ?><a href="http://pinterest.com/pin/create/button/?url=<?php bloginfo('url'); ?>/&media=<?php shopp('product','coverimage','width=500&height=500&fit=crop&quality=100&class=product-image&property=url'); ?>&description=<?php shopp('product','name'); ?>" class="pin-it-button" count-layout="horizontal"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a></p>
	
		
	
	</form>

	<?php if (shopp('product','freeshipping')): ?>
	<p class="freeshipping">Free Shipping!</p>
	<?php endif; ?>

	<div class="product-info">
	
		<?php shopp('product','description'); ?>
	
		<?php if(shopp('product','has-specs')): ?>
		<dl class="details">
			<?php while(shopp('product','specs')): ?>
			<dt><?php shopp('product','spec','name'); ?>:</dt><dd><?php shopp('product','spec','content'); ?></dd>
			<?php endwhile; ?>
		</dl>
		<?php endif; ?>
	
	<?php else: ?>
	<h3>Product Not Found</h3>
	<p>Sorry! The product you requested is not found in our catalog!</p>
	<?php endif; ?>
	</div>