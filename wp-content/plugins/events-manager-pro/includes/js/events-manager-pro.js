jQuery(document).ready(function($){
	$(document).delegate('.em-bookings-approve-offline', 'click', function(e){
		if( !confirm(EM.offline_confirm) ){
			e.stopPropagation();
			e.stopImmediatePropagation();
			e.preventDefault();
			return false;
		}
	});
	//Approve/Reject Links
	$(document).delegate('.em-transaction-delete', 'click', function(){
		var el = $(this); 
		if( !confirm(EM.transaction_delete) ){ return false; }
		var url = em_ajaxify( el.attr('href'));		
		var td = el.parents('td').first();
		td.html(EM.txt_loading);
		td.load( url );
		return false;
	});
});