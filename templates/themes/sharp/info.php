<?php

$theme = array();
$theme['name'] = 'Sharp';
$theme['description'] = 'A special theme for AwsumChan';
$theme['version'] = 'v0.1';
$theme['config'] = array();
$theme['config'][] = array(
	'title' => 'Title',
	'name' => 'title',
	'type' => 'text',
	'default' => 'AwsumChan'
);

$theme['config'][] = Array(
	'title' => 'Slogan',
	'name' => 'subtitle',
	'type' => 'text',
	'comment' => '(optional)'
);

$theme['config'][] = array(
	'title' => 'Excluded boards',
	'name' => 'exclude',
	'type' => 'text',
	'comment' => '(space seperated)'
);

$theme['config'][] = array(
	'title' => '# of recent images',
	'name' => 'limit_images',
	'type' => 'text',
	'default' => '3',
	'comment' => '(maximum images to display)'
);

$theme['config'][] = array(
	'title' => '# of recent posts',
	'name' => 'limit_posts',
	'type' => 'text',
	'default' => '30',
	'comment' => '(maximum posts to display)'
);

$theme['config'][] = array(
	'title' => 'Main HTML file',
	'name' => 'file_main',
	'type' => 'text',
	'default' => $config['file_index'],
	'comment' => '(eg. "index.html")'
);

$theme['config'][] = array(
	'title' => 'News file',
	'name' => 'file_news',
	'type' => 'text',
	'default' => 'news.html',
	'comment' => '(eg. "news.html")'
);

$theme['config'][] = array(
	'title' => 'CSS file',
	'name' => 'css',
	'type' => 'text',
	'default' => 'site.css',
	'comment' => '(eg. "site.css")'
);

$theme['build_function'] = 'sharp_build';
$theme['install_callback'] = 'sharp_install';

if (!function_exists('sharp_install')) {
	function sharp_install($settings) {
		global $config;
		
		if (!isset($config['categories'])) {
			return array(false, '<h2>Prerequisites not met!</h2>' . 
				'This theme requires $config[\'boards\'] and $config[\'categories\'] to be set.');
		}
		
		if (!is_numeric($settings['limit_images']) || $settings['limit_images'] < 0)
			return array(false, '<strong>' . utf8tohtml($settings['limit_images']) . '</strong> is not a non-negative integer.');
		if (!is_numeric($settings['limit_posts']) || $settings['limit_posts'] < 0)
			return array(false, '<strong>' . utf8tohtml($settings['limit_posts']) . '</strong> is not a non-negative integer.');
	}
}