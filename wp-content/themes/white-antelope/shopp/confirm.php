<article id="content">
<?php shopp('checkout','cart-summary'); ?>

	<form action="<?php shopp('checkout','url'); ?>" method="post" class="shopp" id="checkout">
		<?php shopp('checkout','function','value=confirmed'); ?>
	<img class="cc-logos" title="Credit Cards" src="<?php bloginfo('url'); ?>/wp-content/uploads/2011/06/cards.png" alt="Credit Cards" width="144" height="21" />All payments are processed by<img class="paypal-logo" title="Paypal Logo" src="<?php bloginfo('url'); ?>/wp-content/uploads/2011/06/paypal.png" alt="Paypal Logo" width="74" height="21" />
		<p class="submit">
		<span class="payoption-button payoption-0">
			<input type="submit" name="process" id="checkout-button"  value="Confirm Order" class="confirm-button" />
		</span>
	</p>
	</form>
</article>
