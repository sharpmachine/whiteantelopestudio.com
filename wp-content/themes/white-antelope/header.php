<!DOCTYPE html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7 ]>
<html class="no-js ie6" <?php language_attributes(); ?>> 
<![endif]-->
<!--[if IE 7 ]>    
<html class="no-js ie7" <?php language_attributes(); ?>> 
<![endif]-->
<!--[if IE 8 ]>    
<html class="no-js ie8" <?php language_attributes(); ?>>
 <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> 
<html class="no-js" <?php language_attributes(); ?>> 
<!--<![endif]-->
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title><?php
	/*
	 * Print the <title> tag based on what is being viewed.
	 */
	global $page, $paged;

	wp_title( '|', true, 'right' );

	// Add the blog name.
	bloginfo( 'name' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		echo " | $site_description";

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . sprintf( __( 'Page %s', 'smm' ), max( $paged, $page ) );

	?></title>
<meta name="author" content="Jesse Kade of Sharp Machine Media">
	
<link rel="shortcut icon" href="<?php bloginfo('template_directory'); ?>/images/icons/favicon.ico">
<link rel="apple-touch-icon" href="<?php bloginfo('template_directory'); ?>/images/icons/apple-touch-icon.png">
	
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/css/screen.css" type="text/css" media="screen, projection">
<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/css/print.css" type="text/css" media="print">
<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/css/style.css">
<!--[if lt IE 8]><link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/css/ie.css" type="text/css" media="screen, projection"><![endif]-->
	
<!-- Hashgrid - remove before moving to productions -->
<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/css/hashgrid.css">
	
<!-- Uncomment for mobile browsers
<link rel="stylesheet" type="text/css" media="only screen and (max-width: 480px), only screen and (max-device-width: 480px)" href="<?php bloginfo('template_directory'); ?>/css/handheld.css" />
-->
	
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<link rel="self" type="application/rss+xml" title="Revival Cry &raquo; Events Feed" href="<?php bloginfo('url'); ?>/events/rss" />
	
<script src="<?php bloginfo('template_directory'); ?>/js/modernizr-1.7.min.js"></script>
<!--[if lte IE 8]><script src="<?php bloginfo('template_directory'); ?>/js/selectivizr-min.js"></script><![endif]--> 
<?php

	if ( is_singular() && get_option( 'thread_comments' ) )
		wp_enqueue_script( 'comment-reply' );
		
	wp_head();
?>
</head>

<body <?php body_class(); ?>>
	<div id="top-bar-wrapper">
		<div id="top-bar">
			<div class="span-4 colborder">
				<a href="<?php bloginfo('url'); ?>/cart"><img src="<?php bloginfo('template_directory'); ?>/images/shopping-cart.png" width="19" height="14" alt="Shopping Cart">Shopping Cart</a>
			</div>
			<div class="span-5 colborder">
				<a href="#"><img src="<?php bloginfo('template_directory'); ?>/images/newsletter.png" width="18" height="14" alt="Newsletter">Signup for Newsletter</a>
			</div>
			<div class="span-6 colborder rss-feeds">
				<img src="<?php bloginfo('template_directory'); ?>/images/rss.png" width="15" height="15" alt="Rss">
				<a href="#">Gallery</a>
				<a href="<?php bloginfo('url'); ?>/events/rss" type="application/rss+xml">Events</a>
				<a href="<?php bloginfo("rss_url"); ?>">Blog</a>
			</div>
			<div class="span-6 social-media last">
				<a href="#"><img src="<?php bloginfo('template_directory'); ?>/images/pinterest.png" width="58" height="15" alt="Pinterest" class="Pinterest" style="position:relative; top:4px;"></a>
				<a href="#"><img src="<?php bloginfo('template_directory'); ?>/images/twitter.png" width="61" height="12" alt="Twitter" class="Twitter" style="position:relative; top:5px;"></a>
				<a href="#"><img src="<?php bloginfo('template_directory'); ?>/images/facebook.png" width="72" height="16" alt="Facebook" class="Facebook" style="padding:0"></a>
			</div>
			
		</div>
	</div>
	<div id="wrapper">
		<?php get_sidebar(); ?>
		<div class="container"> <!-- some layouts will require this to moved down just above the #page tag -->
			<header role="banner">
				<hgroup>
					<h1 id="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><img src="<?php bloginfo('template_directory'); ?>/images/logo.png" width="404" height="199" alt="Logo"></a></h1>
					<h2 id="site-description"><?php bloginfo( 'description' ); ?></h2>
				</hgroup>
			</header>
			