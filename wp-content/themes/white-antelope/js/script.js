/* Author: 

*/

// Allows you to use the $ shortcut.  Put all your code  inside this wrapper
jQuery(document).ready(function($) {
// Side nav that follows you like a lost puppy
// Doug Neiner - http://dougneiner.com/
	$(function() {
	    var $sidebar   = $("nav"),
	        $window    = $(window),
	        offset     = $sidebar.offset(),
	        topPadding = 100;
	
	    $window.scroll(function() {
	        if ($window.scrollTop() > offset.top) {
	            $sidebar.stop().animate({
	                marginTop: $window.scrollTop() - offset.top + topPadding
	            });
	        } else {
	            $sidebar.stop().animate({
	                marginTop: 0
	            });
	        }
	    });
	});
	
// Registration Form
	$(function(){
		$('.registration-form').hide();
		$('a.registration-button').click(function(){
			$('.registration-form').slideDown();
		});
	});
});





















