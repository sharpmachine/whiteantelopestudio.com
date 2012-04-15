<?php
// Create the function to output the contents of our Dashboard Widget
function help_dashboard_widget_function() {
	// Display whatever it is you want to show
	echo '
		<ul style="width:40%;float:left;margin-right:55px;min-width:153px;">
			<li style="color:#666;font-size:14px;border-bottom-style:solid;border-bottom-width:1px;border-bottom-color:#DFDFDF;padding-bottom:5px;margin-bottom:10px;">WordPress 101 Videos:</li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp101-video-training-part-1-the-dashboard/" target="_blank">The Dashboard</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-2-creating-a-new-post/" target="_blank">Creating A New Post</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-3-edit-existing-post/" target="_blank">Edit Existing Post</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-4-using-categories-and-tags/" target="_blank">Using Categories and Tag</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-5-creating-and-editing-pages/" target="_blank">Creating and Editing Pages</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-6-adding-images/" target="_blank">Adding Images &amp; Photos</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-7-embedding-video/" target="_blank">How to Embed Video</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-8-media-library/" target="_blank">Using the Media Library</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-9-managing-comments/" target="_blank">Managing Comments</a></li> 
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-12-widgets/" target="_blank">Adding Widgets</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-13-custom-menus/" target="_blank">Building Custom Menus</a></li>
			<li><a href="http://wp.tutsplus.com/tutorials/wp-101-video-training-part-15-users/" target="_blank">Adding New Users</a></li>
		</ul>
		
		<ul style="width:40%;float:left;min-width:153px;">
			<li style="color:#666;font-size:14px;border-bottom-style:solid;border-bottom-width:1px;border-bottom-color:#DFDFDF;padding-bottom:5px;margin-bottom:10px;">Videos Specific To Your Site:</li>
			<li><a href="http://youtu.be/mGoV2YeWcSI" target="_blank">Site Overview</a></li>
			<li><a href="http://www.youtube.com/watch?v=IE_10_nwe0c" target="_blank">SEO Ultimate Tutorial</a></li>
			<li><a href="http://www.youtube.com/watch?v=5PKsUaxSCNk" target="_blank">Managing Your Store With Shopp</a></li>
			<li><a href="http://youtu.be/UywmOitsQZ4" target="_blank">Managing Homepage Options</a></li>
			<li><a href="http://youtu.be/K0lCp1bU1iw" target="_blank">Managing Friends</a></li>
			<li><a href="http://youtu.be/XU5vZMjcFHo" target="_blank">Managing Events</a></li>
			<li><a href="http://youtu.be/CvEpapem5RM" target="_blank">Managing the Footer</a></li>
			<li><a href="http://youtu.be/iu3qJNXpDgc" target="_blank">Managing Support Buttons</a></li>
			<li><a href="http://youtu.be/N8YIVpwanyY" target="_blank">Managing Pages with Images</a></li>
		</ul>
		
		<p style="clear:both;padding-top:5px;margin-bottom:0.5em;color:#666;font-size:14px;">Helpful Quick Links:</p>
		
		<a href="http://login.mailchimp.com" target="_blank">Mailchimp Login</a> |
		<a href="http://mailchimp.com/support/online-training" target="_blank">Mailchimp Training</a> |
		<a href="http://docs.disqus.com/kb" target="_blank">Disqus Training</a> |
		<a href="http://google.com/analytics" target="_blank">Analytics Login</a> | 	
		<a href="http://mail.'. substr(get_bloginfo('url'), 7).'" target="_blank">Mail Login</a>
		
		<p>Still stuck?  Give us a call at <strong>(530) 638-3581</strong> or email us at <a href="mailto:info@sharpmachinemedia.com?subject=Help!"><strong>info@sharpmachinemedia.com</strong></a>.
	';
} 

// Create the function use in the action hook
function help_add_dashboard_widgets() {
	add_meta_box('help_dashboard_widget', 'Need Help?', 'help_dashboard_widget_function', 'dashboard', 'side', 'low');	
} 

// Hook into the 'wp_dashboard_setup' action to register our other functions
add_action('wp_dashboard_setup', 'help_add_dashboard_widgets' );
?>