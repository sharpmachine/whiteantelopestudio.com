// 1.6.3
jQuery(document).ready(function(){
	// TABS
	jQuery('#tabs').tabs();
	jQuery('#tabs ul li:last').hide();
	jQuery('#icon-options-ie6w').click(function() {
		jQuery('#tabs ul li:last').show();
	});
	// Browsers
	jQuery('input[name^="ie6w_"][name$="u"]').each(function() {
		if ( jQuery(this).val().search('^(http://)|(https://)|(ftp://)') != 0 ) { jQuery(this).css({ 'background-color': '#FF9999' }).after("Invalid URL!"); }
	})
	if ( jQuery('select[name="ie6w_firefox"]').val() == 'false' ) { jQuery('input[name="ie6w_firefoxu"]').css({ 'background-color': '#CCCCCC' }); }	
	if ( jQuery('select[name="ie6w_opera"]').val() == 'false' ) { jQuery('input[name="ie6w_operau"]').css({ 'background-color': '#CCCCCC' }); }	
	if ( jQuery('select[name="ie6w_chrome"]').val() == 'false' ) { jQuery('input[name="ie6w_chromeu"]').css({ 'background-color': '#CCCCCC' }); }	
	if ( jQuery('select[name="ie6w_safari"]').val() == 'false' ) { jQuery('input[name="ie6w_safariu"]').css({ 'background-color': '#CCCCCC' }); }	
	if ( jQuery('select[name="ie6w_ie"]').val() == 'false' ) { jQuery('input[name="ie6w_ieu"]').css({ 'background-color': '#CCCCCC' }); }	

	// IE6 Crash Methods
	if ( jQuery('select[name="ie6w_crashmode"]').val() == '1' ) {
		jQuery('#ie6w_crashmode_txt').text('<style>*{position:relative}</style><table><input></table>');
	} else if ( jQuery('select[name="ie6w_crashmode"]').val() == '2' ) {
		jQuery('#ie6w_crashmode_txt').text('<STYLE>@;/*');
	}
	jQuery('select[name="ie6w_crashmode"]').change(function() {
		if ( jQuery('select[name="ie6w_crashmode"]').val() == '1' ) {
			jQuery('#ie6w_crashmode_txt').text('<style>*{position:relative}</style><table><input></table>');
		} else if ( jQuery('select[name="ie6w_crashmode"]').val() == '2' ) {
			jQuery('#ie6w_crashmode_txt').text('<STYLE>@;/*');
		}
	});
	// Test Mode & JS Test
	if ( jQuery('select[name="ie6w_test"]').val() == 'true' ) { jQuery('select[name="ie6w_test"]').css({ 'background-color': '#FF3333' }); }
	if ( jQuery('select[name="ie6w_jstest"]').val() == 'true' ) { jQuery('select[name="ie6w_jstest"]').css({ 'background-color': '#FF3333' }); }
});