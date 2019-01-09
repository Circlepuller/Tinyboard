<?php
	$theme = [];
	
	// Theme name
	$theme['name'] = 'AwsumChan';
	// Description (you can use Tinyboard markup here)
	$theme['description'] = 'A combination of the RecentPosts and Basic themes, made specially for AwsumChan.';
	$theme['version'] = 'v1.2';
	
	// Theme configuration	
	$theme['config'] = [];
	
	$theme['config'][] = [
		'title' => 'Site Title',
		'name' => 'title',
		'type' => 'text'
	];
	
	$theme['config'][] = [
		'title' => 'Slogan',
		'name' => 'subtitle',
		'type' => 'text',
		'comment' => '(optional)'
	];
	
	$theme['config'][] = [
		'title' => '# of recent entries',
		'name' => 'no_recent',
		'type' => 'text',
		'default' => 0,
		'size' => 3,
		'comment' => '(number of recent news entries to display; "0" is infinite)'
	];
	
	$theme['config'][] = [
		'title' => 'Excluded boards',
		'name' => 'exclude',
		'type' => 'text',
		'comment' => '(space seperated)'
	];
	
	$theme['config'][] = [
		'title' => '# of recent images',
		'name' => 'limit_images',
		'type' => 'text',
		'default' => '3',
		'comment' => '(maximum images to display)'
	];
	
	$theme['config'][] = [
		'title' => '# of recent posts',
		'name' => 'limit_posts',
		'type' => 'text',
		'default' => '30',
		'comment' => '(maximum posts to display)'
	];
	
	$theme['config'][] = [
		'title' => 'Index file',
		'name' => 'file_index',
		'type' => 'text',
		'default' => 'index.html',
		'comment' => '(eg. "index.html")'
	];
	
	$theme['config'][] = [
		'title' => 'CSS file',
		'name' => 'file_css',
		'type' => 'text',
		'default' => 'recent.css',
		'comment' => '(eg. "recent.css")'
	];

	$theme['config'][] = [
		'title' => 'Logo file',
		'name' => 'file_logo',
		'type' => 'text',
		'default' => 'logo.png',
		'comment' => '(optional, leave blank to disable)'
	];

	$theme['config'][] = [
		'title' => 'Favicon file',
		'name' => 'file_favicon',
		'type' => 'text',
		'default' => 'favicon.ico',
		'comment' => '(optional, leave blank to disable)'
	];

	$theme['config'][] = [
		'title' => 'Frames file',
		'name' => 'file_frames',
		'type' => 'text',
		'default' => 'frames.html',
		'comment' => '(optional, required if using frames - leave blank to disable)'
	];
	 
	$theme['config'][] = [
		'title' => 'Sidebar file',
		'name' => 'file_sidebar',
		'type' => 'text',
		'default' => 'sidebar.html',
		'comment' => '(optional, required if using frames - leave blank to disable)'
	];

	// This one's a bit of an odd one
	$theme['config'][] = [
		'title' => 'YouTube video playlist',
		'name' => 'playlist',
		'type' => 'text',
		'default' => '',
		'comment' => '(optional, play a YouTube playlist in the sidebar - leave blank to disable)'
	];

	$theme['config'][] = [
		'title' => 'CSS stylesheet name',
		'name' => 'basecss',
		'type' => 'text',
		'default' => 'recent.css',
		'comment' => '(eg. "recent.css" - see templates/themes/awsumchan for details)'
	      ];
	
	// Unique function name for building everything
	$theme['build_function'] = 'awsumchan_build';
	$theme['install_callback'] = 'awsumchan_install';

	if (!function_exists('awsumchan_install')) {
		function awsumchan_install($settings) {
			if (!is_numeric($settings['limit_images']) || $settings['limit_images'] < 0)
			  return [false, '<strong>' . utf8tohtml($settings['limit_images']) . '</strong> is not a non-negative integer.'];
			if (!is_numeric($settings['limit_posts']) || $settings['limit_posts'] < 0)
				return [false, '<strong>' . utf8tohtml($settings['limit_posts']) . '</strong> is not a non-negative integer.'];
		}
	}
	
