jQuery(document).ready(function($){
	$(document).delegate('.em-bookings-approve-offline', 'click', function(e){
		if( !confirm(EM.offline_confirm) ){
			e.stopPropagation();
			e.stopImmediatePropagation();
			e.preventDefault();
			return false;
		}
	});
});