<?php if (shopp('cart','hasitems')): ?>
<article id="content">
	
	<div class="cart-functions">
		<h1 class="entry-title">Shopping Cart</h1>
		<a href="<?php shopp('checkout','url'); ?>" class="right button"><img src="<?php bloginfo('template_directory'); ?>/images/gallery-cart.png" width="18" height="13" alt="Gallery Cart">Checkout</a>
		<a href="<?php shopp('cart','referrer'); ?>" class="right">Continue Shopping</a>
	</div>

	<form id="cart" action="<?php shopp('cart','url'); ?>" method="post">
	
	<?php shopp('cart','function'); ?>
	<table class="cart">
		<tr>
			<th scope="col" class="item">Title</th>
			<th scope="col">Qty</th>
			<th scope="col" class="money">Item Price</th>
			<th scope="col" class="money">Item Total</th>
		</tr>
	
		<?php while(shopp('cart','items')): ?>
			<tr class="gallery-cart-item">
				<td>
					<a href="<?php shopp('cartitem','url'); ?>"><?php shopp('cartitem','name'); ?></a>
					<?php shopp('cartitem','options'); ?>
					<?php shopp('cartitem','addons-list'); ?>
					<?php shopp('cartitem','inputs-list'); ?>
				</td>
				<td><?php shopp('cartitem','quantity','input=text'); ?><br \>
					<?php shopp('cartitem','remove'); ?></td>
				<td class="money"><?php shopp('cartitem','unitprice'); ?></td>
				<td class="money"><?php shopp('cartitem','total'); ?></td>
			</tr>
		<?php endwhile; ?>
			
		<?php while(shopp('cart','promos')): ?>
			<tr><td colspan="4" class="money"><?php shopp('cart','promo-name'); ?><strong><?php shopp('cart','promo-discount',array('before' => '&nbsp;&mdash;&nbsp;')); ?></strong></td></tr>
		<?php endwhile; ?>
	
		<tr class="totals first">
			<td colspan="2" rowspan="5">
				<?php if (shopp('cart','needs-shipping-estimates')): ?>
				Estimate shipping &amp; taxes for:
				<?php shopp('cart','shipping-estimates'); ?>
				<?php endif; ?>
				<?php shopp('cart','promo-code'); ?>
			</td>
			<th scope="row">Subtotal</th>
			<td class="money"><?php shopp('cart','subtotal'); ?></td>
		</tr>
		<?php if (shopp('cart','hasdiscount')): ?>
		<tr class="totals">
			<th scope="row">Discount</th>
			<td class="money">-<?php shopp('cart','discount'); ?></td>
		</tr>
		<?php endif; ?>
		<?php if (shopp('cart','needs-shipped')): ?>
		<tr class="totals">
			<th scope="row"><?php shopp('cart','shipping','label=Estimated Shipping'); ?></th>
			<td class="money"><?php shopp('cart','shipping'); ?></td>
		</tr>
		<?php endif; ?>
		<tr class="totals">
			<th scope="row"><?php shopp('cart','tax','label=Tax'); ?></th>
			<td class="money"><?php shopp('cart','tax'); ?></td>
		</tr>
		<tr class="totals total">
			<th scope="row">Total</th>
			<td class="money"><?php shopp('cart','total'); ?></td>
		</tr>
		<tr class="buttons">
			<td colspan="4"><?php shopp('cart','update-button'); ?></td>
		</tr>
	</table>	
	</form>
	<div class="cart-functions bottom">
		<a href="<?php shopp('checkout','url'); ?>" class="right button"><img src="<?php bloginfo('template_directory'); ?>/images/gallery-cart.png" width="18" height="13" alt="Gallery Cart">Checkout</a>
		<a href="<?php shopp('cart','referrer'); ?>" class="right">Continue Shopping</a>
	</div>
	<?php else: ?>
		
		
		<div class="cart-functions bottom">
			<h1 class="entry-title">Shopping Cart</h1>
			
			<a href="<?php shopp('cart','referrer'); ?>" class="right">Continue Shopping</a>
			<p class="warning">There are currently no items in your shopping cart.</p>
	</div>
	<?php endif; ?>
	
	
	
</article>