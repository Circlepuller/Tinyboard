<?php

/*
 *  Copyright (c) 2010-2020 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

require_once 'vendor/autoload.php';

$twig = false;

function load_twig() {
	global $twig, $config;
	
	$loader = new Twig\Loader\FilesystemLoader($config['dir']['template']);
	$loader->setPaths($config['dir']['template']);
	$twig = new Twig\Environment($loader, array(
		'autoescape' => false,
		'cache' => is_writable('templates') || (is_dir('templates/cache') && is_writable('templates/cache')) ?
			new Twig_Cache_TinyboardFilesystem("{$config['dir']['template']}/cache") : false,
		'debug' => $config['debug']
	));
	$twig->addExtension(new Twig_Extensions_Extension_Tinyboard());
	$twig->addExtension(new Twig_Extensions_Extension_I18n());
}

function Element($templateFile, array $options) {
	global $config, $debug, $twig, $build_pages;
	
	if (!$twig)
		load_twig();
	
	if (function_exists('create_pm_header') && ((isset($options['mod']) && $options['mod']) || isset($options['__mod'])) && !preg_match('!^mod/!', $templateFile)) {
		$options['pm'] = create_pm_header();
	}
	
	if (isset($options['body']) && $config['debug']) {
		$_debug = $debug;
		
		if (isset($debug['start'])) {
			$_debug['time']['total'] = '~' . round((microtime(true) - $_debug['start']) * 1000, 2) . 'ms';
			$_debug['time']['init'] = '~' . round(($_debug['start_debug'] - $_debug['start']) * 1000, 2) . 'ms';
			unset($_debug['start']);
			unset($_debug['start_debug']);
		}
		if ($config['try_smarter'] && isset($build_pages) && !empty($build_pages))
			$_debug['build_pages'] = $build_pages;
		$_debug['included'] = get_included_files();
		$_debug['memory'] = round(memory_get_usage(true) / (1024 * 1024), 2) . ' MiB';
		$_debug['time']['db_queries'] = '~' . round($_debug['time']['db_queries'] * 1000, 2) . 'ms';
		$_debug['time']['exec'] = '~' . round($_debug['time']['exec'] * 1000, 2) . 'ms';
		$options['body'] .=
			'<h3>Debug</h3><pre style="white-space: pre-wrap;font-size: 10px;">' .
				str_replace("\n", '<br/>', utf8tohtml(print_r($_debug, true))) .
			'</pre>';
	}
	
	// Read the template file
	if (@file_get_contents("{$config['dir']['template']}/${templateFile}")) {
		$body = $twig->render($templateFile, $options);
		
		if ($config['minify_html'] && preg_match('/\.html$/', $templateFile)) {
			$body = trim(preg_replace("/[\t\r\n]/", '', $body));
		}
		
		return $body;
	} else {
		throw new Exception("Template file '${templateFile}' does not exist or is empty in '{$config['dir']['template']}'!");
	}
}

/**
 * {@inheritdoc}
 */
class Twig_Cache_TinyboardFilesystem extends Twig\Cache\FilesystemCache
{
	private $directory;
	private $options;

	/**
	 * {@inheritdoc}
	 */
	public function __construct($directory, $options = 0)
	{
		parent::__construct($directory, $options);

		$this->directory = $directory;
	}

	/**
	 * This function was removed in Twig 2.x due to developer views on the Twig library. Who says we can't keep it for ourselves though?
	 */
	public function clear()
	{
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
			if ($file->isFile()) {
				@unlink($file->getPathname());
			}
		}
	}
}

class Twig_Extensions_Extension_Tinyboard extends Twig\Extension\AbstractExtension
{
	/**
	* Returns a list of filters to add to the existing list.
	*
	* @return array An array of filters
	*/
	public function getFilters()
	{
		return [
			new Twig\TwigFilter('filesize', 'format_bytes'),
			new Twig\TwigFilter('truncate', 'twig_truncate_filter'),
			new Twig\TwigFilter('truncate_body', 'truncate'),
			new Twig\TwigFilter('truncate_filename', 'twig_filename_truncate_filter'),
			new Twig\TwigFilter('extension', 'twig_extension_filter'),
			new Twig\TwigFilter('capcode', 'capcode'),
			new Twig\TwigFilter('remove_modifiers', 'remove_modifiers'),
			new Twig\TwigFilter('hasPermission', 'twig_hasPermission_filter'),
			new Twig\TwigFilter('strftime', 'twig_strftime_filter'),
			new Twig\TwigFilter('poster_id', 'poster_id'),
			new Twig\TwigFilter('ago', 'ago'),
			new Twig\TwigFilter('until', 'until'),
			new Twig\TwigFilter('push', 'twig_push_filter'),
			new Twig\TwigFilter('bidi_cleanup', 'bidi_cleanup'),
			new Twig\TwigFilter('addslashes', 'addslashes'),
		];
	}
	
	/**
	* Returns a list of functions to add to the existing list.
	*
	* @return array An array of filters
	*/
	public function getFunctions()
	{
		return [
			new Twig\TwigFunction('time', 'time'),
			new Twig\TwigFunction('hiddenInputs', 'hiddenInputs'),
			new Twig\TwigFunction('hiddenInputsHash', 'hiddenInputsHash'),
			new Twig\TwigFunction('ratio', 'twig_ratio_function'),
			new Twig\TwigFunction('secure_link_confirm', 'twig_secure_link_confirm'),
			new Twig\TwigFunction('secure_link', 'twig_secure_link'),
			new Twig\TwigFunction('link_for', 'link_for')
		];
	}
	
	/**
	* Returns the name of the extension.
	*
	* @return string The extension name
	*/
	public function getName()
	{
		return 'tinyboard';
	}
}

function twig_push_filter($array, $value) {
	array_push($array, $value);
	return $array;
}

function twig_strftime_filter($date, $format) {
	return gmstrftime($format, $date);
}

function twig_hasPermission_filter($mod, $permission, $board = null) {
	return hasPermission($permission, $board, $mod);
}

function twig_extension_filter($value, $case_insensitive = true) {
	$ext = mb_substr($value, mb_strrpos($value, '.') + 1);
	if($case_insensitive)
		$ext = mb_strtolower($ext);		
	return $ext;
}

function twig_truncate_filter($value, $length = 30, $preserve = false, $separator = '…') {
	if (mb_strlen($value) > $length) {
		if ($preserve) {
			if (false !== ($breakpoint = mb_strpos($value, ' ', $length))) {
				$length = $breakpoint;
			}
		}
		return mb_substr($value, 0, $length) . $separator;
	}
	return $value;
}

function twig_filename_truncate_filter($value, $length = 30, $separator = '…') {
	if (mb_strlen($value) > $length) {
		$value = strrev($value);
		$array = array_reverse(explode(".", $value, 2));
		$array = array_map("strrev", $array);
		
		$filename = &$array[0];
		$extension = isset($array[1]) ? $array[1] : false;

		$filename = mb_substr($filename, 0, $length - ($extension ? mb_strlen($extension) + 1 : 0)) . $separator;

		return implode(".", $array);
	}
	return $value;
}

function twig_ratio_function($w, $h) {
	return fraction($w, $h, ':');
}
function twig_secure_link_confirm($text, $title, $confirm_message, $href) {
	global $config;

	return '<a onclick="if (event.which==2) return true;if (confirm(\'' . htmlentities(addslashes($confirm_message)) . '\')) document.location=\'?/' . htmlspecialchars(addslashes($href . '/' . make_secure_link_token($href))) . '\';return false;" title="' . htmlentities($title) . '" href="?/' . $href . '">' . $text . '</a>';
}
function twig_secure_link($href) {
	return $href . '/' . make_secure_link_token($href);
}