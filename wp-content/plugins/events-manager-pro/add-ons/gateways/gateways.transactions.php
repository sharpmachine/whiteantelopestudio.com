<?php
if(!class_exists('EM_Gateways_Transactions')) {
class EM_Gateways_Transactions{
	var $limit = 20;
	var $total_transactions = 0;
	
	function __construct(){
		$this->order = ( !empty($_REQUEST ['order']) ) ? $_REQUEST ['order']:'ASC';
		$this->orderby = ( !empty($_REQUEST ['order']) ) ? $_REQUEST ['order']:'booking_name';
		$this->limit = ( !empty($_REQUEST['limit']) ) ? $_REQUEST['limit'] : 20;//Default limit
		$this->page = ( !empty($_REQUEST['pno']) ) ? $_REQUEST['pno']:1;
		$this->gateway = !empty($_REQUEST['gateway']) ? $_REQUEST['gateway']:false;
		//Add options and tables to EM admin pages
		if( current_user_can('manage_others_bookings') ){
			add_action('em_bookings_dashboard', array(&$this, 'output'),10,1);
			add_action('em_bookings_ticket_footer', array(&$this, 'output'),10,1);
			add_action('em_bookings_single_footer', array(&$this, 'output'),10,1);
			add_action('em_bookings_person_footer', array(&$this, 'output'),10,1);
			add_action('em_bookings_event_footer', array(&$this, 'output'),10,1);
		}
		add_action('wp_ajax_em_transactions_table', array(&$this, 'ajax'),10,1);
	}
	
	function ajax(){
		if( wp_verify_nonce($_REQUEST['_wpnonce'],'em_transactions_table') ){
			//Get the context
			global $EM_Event, $EM_Booking, $EM_Ticket, $EM_Person;
			em_load_event();
			$context = false;
			if( !empty($_REQUEST['booking_id']) && is_object($EM_Booking) && $EM_Booking->can_manage('manage_bookings','manage_others_bookings') ){
				$context = $EM_Booking;
			}elseif( !empty($_REQUEST['event_id']) && is_object($EM_Event) && $EM_Event->can_manage('manage_bookings','manage_others_bookings') ){
				$context = $EM_Event;
			}elseif( !empty($_REQUEST['person_id']) && is_object($EM_Person) && current_user_can('manage_bookings') ){
				$context = $EM_Person;
			}elseif( !empty($_REQUEST['ticket_id']) && is_object($EM_Ticket) && $EM_Ticket->can_manage('manage_bookings','manage_others_bookings') ){
				$context = $EM_Ticket;
			}			
			echo $this->mytransactions($context);
			exit;
		}
	}
	
	function output( $context = false ) {
		global $page, $action, $wp_query;
		?>
		<div class="wrap">
		<div class="icon32" id="icon-bookings"><br></div>
		<h2><?php echo __('Transactions','dbem'); ?></h2>
		<?php $this->mytransactions($context); ?>
		<script type="text/javascript">
			jQuery(document).ready( function($){
				//Pagination link clicks
				$('#em-transactions-table .tablenav-pages a').live('click', function(){
					var el = $(this);
					var form = el.parents('#em-transactions-table form.transactions-filter');
					//get page no from url, change page, submit form
					var match = el.attr('href').match(/#[0-9]+/);
					if( match != null && match.length > 0){
						var pno = match[0].replace('#','');
						form.find('input[name=pno]').val(pno);
					}else{
						form.find('input[name=pno]').val(1);
					}
					form.trigger('submit');
					return false;
				});
				//Widgets and filter submissions
				$('#em-transactions-table form.transactions-filter').live('submit', function(e){
					var el = $(this);			
					el.parents('#em-transactions-table').find('.table-wrap').first().append('<div id="em-loading" />');
					$.get( EM.ajaxurl, el.serializeArray(), function(data){
						el.parents('#em-transactions-table').first().replaceWith(data);
					});
					return false;
				});
			});
		</script>
		</div>
		<?php
	}

	function mytransactions($context=false) {
		global $EM_Person;
		$transactions = $this->get_transactions($context);
		$total = $this->total_transactions;

		$columns = array();

		$columns['event'] = __('Event','em-pro');
		$columns['user'] = __('User','em-pro');
		$columns['date'] = __('Date','em-pro');
		$columns['amount'] = __('Amount','em-pro');
		$columns['transid'] = __('Transaction id','em-pro');
		$columns['gateway'] = __('Gateway','em-pro');
		$columns['status'] = __('Status','em-pro');
		$columns['note'] = __('Notes','em-pro');

		$trans_navigation = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'total' => ceil($total / 20),
			'current' => $this->page
		));
		?>
		<div id="em-transactions-table" class="em_obj">
		<form id="em-transactions-table-form" class="transactions-filter" action="" method="post">
			<?php if( is_object($context) && get_class($context)=="EM_Event" ): ?>
			<input type="hidden" name="event_id" value='<?php echo $context->event_id ?>' />
			<?php elseif( is_object($context) && get_class($context)=="EM_Person" ): ?>
			<input type="hidden" name="person_id" value='<?php echo $context->person_id ?>' />
			<?php endif; ?>
			<input type="hidden" name="pno" value='<?php echo $this->page ?>' />
			<input type="hidden" name="order" value='<?php echo $this->order ?>' />
			<input type="hidden" name="orderby" value='<?php echo $this->orderby ?>' />
			<input type="hidden" name="_wpnonce" value="<?php echo ( !empty($_REQUEST['_wpnonce']) ) ? $_REQUEST['_wpnonce']:wp_create_nonce('em_transactions_table'); ?>" />
			<input type="hidden" name="action" value="em_transactions_table" />
			
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="limit">
						<option value="<?php echo $this->limit ?>"><?php echo sprintf(__('%s Rows','dbem'),$this->limit); ?></option>
						<option value="5">5</option>
						<option value="10">10</option>
						<option value="25">25</option>
						<option value="50">50</option>
						<option value="100">100</option>
					</select>
					<select name="gateway">
						<option value="">All</option>
						<?php
						global $EM_Gateways;
						foreach ( $EM_Gateways as $EM_Gateway ) {
							?><option value='<?php echo $EM_Gateway->gateway ?>' <?php if($EM_Gateway->gateway == $this->gateway) echo "selected='selected'"; ?>><?php echo $EM_Gateway->title ?></option><?php
						}
						?>
					</select>
					<input id="post-query-submit" class="button-secondary" type="submit" value="<?php _e ( 'Filter' )?>" />
					<?php if( is_object($context) && get_class($context)=="EM_Event" ): ?>
					<?php _e('Displaying Event','dbem'); ?> : <?php echo $context->event_name; ?>
					<?php elseif( is_object($context) && get_class($context)=="EM_Person" ): ?>
					<?php _e('Displaying User','dbem'); echo ' : '.$context->get_name(); ?>
					<?php endif; ?>
				</div>
				<?php 
				if ( $this->total_transactions >= $this->limit ) {
					echo em_admin_paginate( $this->total_transactions, $this->limit, $this->page, array(),'#%#%','#');
				}
				?>
			</div>

			<div class="table-wrap">
			<table cellspacing="0" class="widefat">
				<thead>
				<tr>
				<?php
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</thead>

				<tfoot>
				<tr>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
				</tr>
				</tfoot>

				<tbody>
					<?php
						echo $this->print_transactions($transactions);
					?>

				</tbody>
			</table>
			</div>
		</form>
		</div>
		<?php
	}
	
	function print_transactions($transactions, $columns=7){
		ob_start();
		if($transactions) {
			foreach($transactions as $key => $transaction) {
				?>
				<tr valign="middle" class="alternate">
					<td>
						<?php
							$EM_Booking = new EM_Booking($transaction->booking_id);
							echo '<a href="'.EM_ADMIN_URL.'&amp;page=events-manager-bookings&amp;event_id='.$EM_Booking->get_event()->event_id.'">'.$EM_Booking->get_event()->event_name.'</a>';
						?>
					</td>
					<td>
						<?php
							echo '<a href="'.EM_ADMIN_URL.'&amp;page=events-manager-bookings&amp;person_id='.$EM_Booking->get_person()->ID.'">'.$EM_Booking->get_person()->get_name().'</a>';
						?>
					</td>
					<td class="column-date">
						<?php
							echo mysql2date("d-m-Y", $transaction->transaction_timestamp);
						?>
					</td>
					<td class="column-amount">
						<?php
							$amount = $transaction->transaction_total_amount;
							echo $transaction->transaction_currency;
							echo "&nbsp;" . number_format($amount, 2, '.', ',');
						?>
					</td>
					<td class="column-transid">
						<?php
							if(!empty($transaction->transaction_gateway_id)) {
								echo $transaction->transaction_gateway_id;
							} else {
								echo __('None yet','em-pro');
							}
						?>
					</td>
					<td class="column-transid">
						<?php
							if(!empty($transaction->transaction_gateway)) {
								echo $transaction->transaction_gateway;
							} else {
								echo __('None yet','em-pro');
							}
						?>
					</td>
					<td class="column-transid">
						<?php
							if(!empty($transaction->transaction_status)) {
								echo $transaction->transaction_status;
							} else {
								echo __('None yet','em-pro');
							}
						?>
					</td>
					<td class="column-transid">
						<?php
							if(!empty($transaction->transaction_note)) {
								echo esc_html($transaction->transaction_note);
							} else {
								echo __('None','em-pro');
							}
						?>
					</td>
			    </tr>
				<?php
			}
		} else {
			$columncount = count($columns);
			?>
			<tr valign="middle" class="alternate" >
				<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Transactions','em-pro'); ?></td>
		    </tr>
			<?php
		}
		return ob_get_clean();
	}
	
	/**
	 * @param mixed $context
	 * @return stdClass|false
	 */
	function get_transactions($context=false) {
		global $wpdb;
		$join = '';
		$conditions = array();
		$table = EM_BOOKINGS_TABLE;
		//we can determine what to search for, based on if certain variables are set.
		if( is_object($context) && get_class($context)=="EM_Booking" && $context->can_manage('manage_bookings','manage_others_bookings') ){
			$conditions[] = "booking_id = ".$context->booking_id;
		}elseif( is_object($context) && get_class($context)=="EM_Event" && $context->can_manage('manage_bookings','manage_others_bookings') ){
			$join = "tx JOIN $table ON $table.booking_id=tx.booking_id";	
			$conditions[] = "event_id = ".$context->event_id;		
		}elseif( is_object($context) && get_class($context)=="EM_Person" ){
			//FIXME peole could potentially view other's txns like this
			$join = "tx JOIN $table ON $table.booking_id=tx.booking_id";
			$conditions[] = "person_id = ".$context->ID;			
		}elseif( is_object($context) && get_class($context)=="EM_Ticket" && $context->can_manage('manage_bookings','manage_others_bookings') ){
			$booking_ids = array();
			foreach($context->get_bookings()->bookings as $EM_Booking){
				$booking_ids[] = $EM_Booking->booking_id;
			}
			if( count($booking_ids) > 0 ){
				$conditions[] = "booking_id IN (".implode(',', $booking_ids).")";
			}else{
				return new stdClass();
			}			
		}
		if( is_multisite() && !is_main_blog() ){ //if not main blog, we show only blog specific booking info
			global $blog_id;
			$join = "tx JOIN $table ON $table.booking_id=tx.booking_id";
			$conditions[] = "booking_id IN (SELECT booking_id FROM $table, ".EM_EVENTS_TABLE." e WHERE e.blog_id=".$blog_id.")";
		}
		//filter by gateway
		if( !empty($this->gateway) ){
			$conditions[] = $wpdb->prepare('transaction_gateway = %s',$this->gateway);
		}
		//build conditions string
		$condition = (!empty($conditions)) ? "WHERE ".implode(' AND ', $conditions):'';
		$offset = ( $this->page > 1 ) ? ($this->page-1)*$this->limit : 0;		
		$sql = $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM ".EM_TRANSACTIONS_TABLE." $join $condition ORDER BY transaction_id DESC  LIMIT %d, %d", $offset, $this->limit );
		$return = $wpdb->get_results( $sql );
		$this->total_transactions = $wpdb->get_var( "SELECT FOUND_ROWS();" );
		return $return;
	}	
}
global $EM_Gateways_Transactions;
$EM_Gateways_Transactions = new EM_Gateways_Transactions();
}