<?php

// Installation/upgrade file	
define('VERSION', 'v0.10.1');
require 'inc/functions.php';
loadConfig();

// Salt generators
class SaltGen {
	public $salt_length = 128;

	// Best function of the lot. Works with any PHP version as long as OpenSSL extension is on
	private function generate_install_salt_openssl() {
		$ret = openssl_random_pseudo_bytes($this->salt_length, $strong);
		if (!$strong)
			error(_("Misconfigured system: OpenSSL returning weak salts. Cannot continue."));
		return base64_encode($ret);
	}

	private function generate_install_salt_php7() {
		return base64_encode(random_bytes($this->salt_length));
	}

	public function generate() {
		if (extension_loaded('openssl'))
			return "OSSL." . $this->generate_install_salt_openssl();
		else
			return "PHP7." . $this->generate_install_salt_php7();
	}
}

$step = isset($_GET['step']) ? round($_GET['step']) : 0;
$page = [
	'config' => $config,
	'title' => 'Install',
	'body' => '',
	'nojavascript' => true
];

// this breaks the display of licenses if enabled
$config['minify_html'] = false;

if (file_exists($config['has_installed'])) {
	
	// Check the version number
	$version = trim(file_get_contents($config['has_installed']));
	if (empty($version))
		$version = '4.9.90';
	
	function __query($sql) {
		sql_open();

		return query($sql);
	}
	
	$boards = listBoards();
	
	switch ($version) {
		case '4.9.90':
		case '4.9.91':
		case '4.9.92':
            foreach ($boards as &$board)
                query(sprintf('ALTER TABLE ``posts_%s`` ADD `slug` VARCHAR(255) DEFAULT NULL AFTER `embed`;', $board['uri'])) or error(db_error());
        case '4.9.93':
            query('ALTER TABLE ``mods`` CHANGE `password` `password` VARCHAR(255) NOT NULL;') or error(db_error());
            query('ALTER TABLE ``mods`` CHANGE `salt` `salt` VARCHAR(64) NOT NULL;') or error(db_error());
		case '5.0.0':
			query('ALTER TABLE ``mods`` CHANGE `salt` `version` VARCHAR(64) NOT NULL;') or error(db_error());
		case '5.0.1':
		case '5.1.0':
			query('CREATE TABLE IF NOT EXISTS ``pages`` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `board` varchar(255) DEFAULT NULL,
			  `name` varchar(255) NOT NULL,
			  `title` varchar(255) DEFAULT NULL,
			  `type` varchar(255) DEFAULT NULL,
			  `content` text,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `u_pages` (`name`,`board`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;') or error(db_error());
		case '5.1.1':
            foreach ($boards as &$board)
                query(sprintf("ALTER TABLE ``posts_%s`` ADD `cycle` int(1) NOT NULL AFTER `locked`", $board['uri'])) or error(db_error());
		case '5.1.2':
		case '5.1.3':
		case '5.1.4':
			query('DROP TABLE IF EXISTS ``nntp_references``;') or error(db_error());
			query('DROP TABLE IF EXISTS ``captchas``;') or error(db_error());
		case '5.2.0-dev-1':
			// Back to Tinyboard versioning at this point.
			// PHP 7.0 and MySQL/MariaDB 5.5.3 or newer are now requirements.
		case 'v0.10.0-dev-1':
		case 'v0.10.0-dev-2':
		case 'v0.10.0-dev-3':
			// Replaced longtable with tablesorter, updated copyright years, PHP 7.3 fixes implemented
			// Next update will feature some nice surprises!
		case 'v0.10.0':
			// Require PHP 7.2 or newer
		case false:
			query("CREATE TABLE IF NOT EXISTS ``search_queries`` (  `ip` varchar(39) NOT NULL,  `time` int(11) NOT NULL,  `query` text NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8;") or error(db_error());

			// Update version number
			file_write($config['has_installed'], VERSION);
			
			$page['title'] = 'Upgraded';
			$page['body'] = '<p style="text-align:center">Successfully upgraded from ' . $version . ' to <strong>' . VERSION . '</strong>.</p>';
			break;
		default:
			$page['title'] = 'Unknown version';
			$page['body'] = '<p style="text-align:center">Tinyboard was unable to determine what version is currently installed.</p>';
			break;
		case VERSION:
			$page['title'] = 'Already installed';
			$page['body'] = '<p style="text-align:center">It appears that Tinyboard is already installed (' . $version . ') and there is nothing to upgrade! Delete <strong>' . $config['has_installed'] . '</strong> to reinstall.</p>';
			break;
	}			
	
	die(Element('page.html', $page));
}

function create_config_from_array(&$instance_config, &$array, $prefix = '') {
	foreach ($array as $name => $value) {
		if (is_array($value)) {
			$instance_config .= "\n";
			create_config_from_array($instance_config, $value, $prefix . '[\'' . addslashes($name) . '\']');
			$instance_config .= "\n";
		} else {
			$instance_config .= '	$config' . $prefix . '[\'' . addslashes($name) . '\'] = ';

			if (is_numeric($value))
				$instance_config .= $value;
			else
				$instance_config .= "'" . addslashes($value) . "'";

			$instance_config .= ";\n";
		}
	}
}

session_start();

if ($step == 0) {
	// Agreeement
	$page['body'] = '
	<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black" disabled>' . htmlentities(file_get_contents('LICENSE.md')) . '</textarea>
	<p style="text-align:center">
		<a href="?step=1">I have read and understood the agreement. Proceed to installation.</a>
	</p>';
	
	echo Element('page.html', $page);
} elseif ($step == 1) {
	$page['title'] = 'Pre-installation test';
	
	$can_exec = true;
	if (!function_exists('shell_exec'))
		$can_exec = false;
	elseif (in_array('shell_exec', array_map('trim', explode(', ', ini_get('disable_functions')))))
		$can_exec = false;
	elseif (ini_get('safe_mode'))
		$can_exec = false;
	elseif (trim(shell_exec('echo "TEST"')) !== 'TEST')
		$can_exec = false;
	
	if (!defined('PHP_VERSION_ID')) {
		$version = explode('.', PHP_VERSION);
		define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
	}
	
	// Required extensions
	$extensions = [
		'PDO' => [
			'installed' => extension_loaded('pdo'),
			'required' => true
		],
		'GD' => [
			'installed' => extension_loaded('gd'),
			'required' => true
		],
		'Imagick' => [
			'installed' => extension_loaded('imagick'),
			'required' => false
		],
		'OpenSSL' => [
			'installed' => extension_loaded('openssl'),
			'required' => false
		]
	];

	$tests = [
		[
			'category' => 'PHP',
			'name' => 'PHP &ge; 7.2',
			'result' => PHP_VERSION_ID >= 70209,
			'required' => true,
			'message' => 'Tinyboard requires PHP 7.2.9 or better.',
		],
		[
			'category' => 'PHP',
			'name' => 'PHP &ge; 7.3',
			'result' => PHP_VERSION_ID >= 70300,
			'required' => false,
			'message' => 'Tinyboard works best on PHP 7.3 or better.',
		],
		[
			'category' => 'PHP',
			'name' => 'mbstring extension installed',
			'result' => extension_loaded('mbstring'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/mbstring.installation.php">mbstring</a> extension.',
		],
		[
			'category' => 'PHP',
			'name' => 'OpenSSL extension installed',
			'result' => extension_loaded('openssl'),
			'required' => false,
			'message' => 'It is highly recommended that you install the PHP <a href="http://www.php.net/manual/en/openssl.installation.php">OpenSSL</a> extension. Installing the OpenSSL extension allows Tinyboard to generate a secure salt automatically for you.',
		],
		[
			'category' => 'Database',
			'name' => 'PDO extension installed',
			'result' => extension_loaded('pdo'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/intro.pdo.php">PDO</a> extension.',
		],
		[
			'category' => 'Database',
			'name' => 'MySQL PDO driver installed',
			'result' => extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers()),
			'required' => true,
			'message' => 'The required <a href="http://www.php.net/manual/en/ref.pdo-mysql.php">PDO MySQL driver</a> is not installed.',
		],
		[
			'category' => 'Image processing',
			'name' => 'GD extension installed',
			'result' => extension_loaded('gd'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/intro.image.php">GD</a> extension. GD is a requirement even if you have chosen another image processor for thumbnailing.',
		],
		[
		 	'category' => 'Image processing',
		 	'name' => 'GD: JPEG',
			'result' => function_exists('imagecreatefromjpeg'),
			'required' => true,
			'message' => 'imagecreatefromjpeg() does not exist. This is a problem.',
		],
		[
			'category' => 'Image processing',
			'name' => 'GD: PNG',
			'result' => function_exists('imagecreatefrompng'),
			'required' => true,
			'message' => 'imagecreatefrompng() does not exist. This is a problem.',
		],
		[
			'category' => 'Image processing',
			'name' => 'GD: GIF',
			'result' => function_exists('imagecreatefromgif'),
			'required' => true,
			'message' => 'imagecreatefromgif() does not exist. This is a problem.',
		],
		[
			'category' => 'Image processing',
			'name' => '`convert` (command-line ImageMagick)',
			'result' => $can_exec && shell_exec('which convert'),
			'required' => false,
			'message' => '(Optional) `convert` was not found or executable; command-line ImageMagick image processing cannot be enabled.',
			'effect' => function (&$config) { $config['thumb_method'] = 'convert'; },
		],
		[
			'category' => 'Image processing',
			'name' => '`identify` (command-line ImageMagick)',
			'result' => $can_exec && shell_exec('which identify'),
			'required' => false,
			'message' => '(Optional) `identify` was not found or executable; command-line ImageMagick image processing cannot be enabled.',
		],
		[
			'category' => 'Image processing',
			'name' => '`gm` (command-line GraphicsMagick)',
			'result' => $can_exec && shell_exec('which gm'),
			'required' => false,
			'message' => '(Optional) `gm` was not found or executable; command-line GraphicsMagick (faster than ImageMagick) cannot be enabled.',
			'effect' => function (&$config) { $config['thumb_method'] = 'gm'; },
		],
		[
			'category' => 'Image processing',
			'name' => '`gifsicle` (command-line animted GIF thumbnailing)',
			'result' => $can_exec && shell_exec('which gifsicle'),
			'required' => false,
			'message' => '(Optional) `gifsicle` was not found or executable; you may not use `convert+gifsicle` for better animated GIF thumbnailing.',
			'effect' => function (&$config) { if ($config['thumb_method'] == 'gm')      $config['thumb_method'] = 'gm+gifsicle';
							  if ($config['thumb_method'] == 'convert') $config['thumb_method'] = 'convert+gifsicle'; },
		],
		[
			'category' => 'Image processing',
			'name' => '`md5sum` (quick file hashing on GNU/Linux)',
			'prereq' => '',
			'result' => $can_exec && shell_exec('echo "tinyboard" | md5sum') == "24844695af37aa69aaae43f853446cb8  -\n",
			'required' => false,
			'message' => '(Optional) `md5sum` was not found or executable; file hashing for multiple images will be slower. Ignore if not using Linux.',
			'effect' => function (&$config) { $config['gnu_md5'] = true; },
		],
		[
			'category' => 'Image processing',
			'name' => '`/sbin/md5` (quick file hashing on BSDs)',
			'result' => $can_exec && shell_exec('echo "tinyboard" | /sbin/md5 -r') == "24844695af37aa69aaae43f853446cb8\n",
			'required' => false,
			'message' => '(Optional) `/sbin/md5` was not found or executable; file hashing for multiple images will be slower. Ignore if not using BSD.',
			'effect' => function (&$config) { $config['bsd_md5'] = true; },
		],
		[
			'category' => 'File permissions',
			'name' => getcwd(),
			'result' => is_writable('.'),
			'required' => true,
			'message' => 'Tinyboard does not have permission to create directories (boards) here. You will need to <code>chmod</code> (or operating system equivalent) appropriately.'
		],
		[
			'category' => 'File permissions',
			'name' => getcwd() . '/templates/cache',
			'result' => is_writable('templates') || (is_dir('templates/cache') && is_writable('templates/cache')),
			'required' => true,
			'message' => 'You must give Tinyboard permission to create (and write to) the <code>templates/cache</code> directory or performance will be drastically reduced.'
		],
		[
			'category' => 'File permissions',
			'name' => getcwd() . '/tmp/cache',
			'result' => is_dir('tmp/cache') && is_writable('tmp/cache'),
			'required' => true,
			'message' => 'You must give Tinyboard permission to write to the <code>tmp/cache</code> directory.'
		],
		[
			'category' => 'File permissions',
			'name' => getcwd() . '/inc/instance-config.php',
			'result' => is_writable('inc/instance-config.php'),
			'required' => false,
			'message' => 'Tinyboard does not have permission to make changes to <code>inc/instance-config.php</code>. To complete the installation, you will be asked to manually copy and paste code into the file instead.'
		],
		[
			'category' => 'Misc',
			'name' => 'Caching available (APC, XCache, or Redis)',
			'result' => extension_loaded('apc') || extension_loaded('xcache')
				|| extension_loaded('redis'),
			'required' => false,
			'message' => 'You will not be able to enable the additional caching system, designed to minimize SQL queries and significantly improve performance. <a href="http://php.net/manual/en/book.apc.php">APC</a> is the recommended method of caching, but <a href="http://xcache.lighttpd.net/">XCache</a>, and <a href="http://pecl.php.net/package/redis">Redis</a> are also supported.'
		],
		[
			'category' => 'Misc',
			'name' => 'Tinyboard installed using git',
			'result' => is_dir('.git'),
			'required' => false,
			'message' => 'Tinyboard is still beta software and it\'s not going to come out of beta any time soon. As there are often many months between releases yet changes and bug fixes are very frequent, it\'s recommended to use the git repository to maintain your Tinyboard installation. Using git makes upgrading much easier.'
		]
	];

	$config['font_awesome'] = true;
	
	$additional_config = [];
	foreach ($tests as $test)
		if ($test['result'] && isset($test['effect']))
			$test['effect']($additional_config);

	$more = '';
	create_config_from_array($more, $additional_config);
	$_SESSION['more'] = $more;

	echo Element('page.html', [
		'body' => Element('installer/check-requirements.html', [
			'extensions' => $extensions,
			'tests' => $tests,
			'config' => $config,
		]),
		'title' => 'Checking environment',
		'config' => $config,
	]);
} elseif ($step == 2) {

	// Basic config
	$page['title'] = 'Configuration';

	$sg = new SaltGen();
	$config['cookies']['salt'] = $sg->generate();
	$config['secure_trip_salt'] = $sg->generate();
	
	echo Element('page.html', [
		'body' => Element('installer/config.html', [
			'config' => $config,
			'more' => $_SESSION['more'],
		]),
		'title' => 'Configuration',
		'config' => $config
	]);
} elseif ($step == 3) {
	$more = $_POST['more'];
	unset($_POST['more']);

	$instance_config = 
'<'.'?php

/*
*  Instance Configuration
*  ----------------------
*  Edit this file and not config.php for imageboard configuration.
*
*  You can copy values from config.php (defaults) and paste them here.
*/

';
	
	create_config_from_array($instance_config, $_POST);
	
	$instance_config .= "\n";
	$instance_config .= $more;
	$instance_config .= "\n";
	
	if (@file_put_contents('inc/instance-config.php', $instance_config)) {
		opcache_invalidate('inc/instance-config.php');
		header('Location: ?step=4', true, $config['redirect_http']);
	} else {
		$page['title'] = 'Manual installation required';
		$page['body'] = '
			<p>I couldn\'t write to <strong>inc/instance-config.php</strong> with the new configuration, probably due to a permissions error.</p>
			<p>Please complete the installation manually by copying and pasting the following code into the contents of <strong>inc/instance-config.php</strong>:</p>
			<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black">' . htmlentities($instance_config) . '</textarea>
			<p style="text-align:center">
				<a href="?step=4">Once complete, click here to complete installation.</a>
			</p>
		';
		echo Element('page.html', $page);
	}
} elseif ($step == 4) {
	// SQL installation
	
	buildJavascript();
	
	$sql = @file_get_contents('install.sql') or error("Couldn't load install.sql.");
	
	sql_open();
	$mysql_version = mysql_version();
	
	// This code is probably horrible, but what I'm trying
	// to do is find all of the SQL queires and put them
	// in an array.
	preg_match_all("/(^|\n)((SET|CREATE|INSERT).+)\n\n/msU", $sql, $queries);
	$queries = $queries[2];
	
	$queries[] = Element('posts.sql', ['board' => 'b']);
	
	$sql_errors = '';
	foreach ($queries as $query) {
		$query = preg_replace('/^([\w\s]*)`([0-9a-zA-Z$_\x{0080}-\x{FFFF}]+)`/u', '$1``$2``', $query);
		if (!query($query))
			$sql_errors .= '<li>' . db_error() . '</li>';
	}
	
	$page['title'] = 'Installation complete';
	$page['body'] = '<p style="text-align:center">Thank you for using Tinyboard. Please remember to report any bugs you discover. <a href="https://web.archive.org/web/20121003095922/http://tinyboard.org/docs/?p=Config">How do I edit the config files?</a></p>';
	
	if (!empty($sql_errors)) {
		$page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to install the database. This may be the result of using a database which is already occupied with a Tinyboard installation; if so, you can probably ignore this.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul><p><a href="?step=5">Ignore errors and complete installation.</a></p></div>';
	} else {
		$boards = listBoards();
		foreach ($boards as &$_board) {
			setupBoard($_board);
			buildIndex();
		}
		
		file_write($config['has_installed'], VERSION);
		/*if (!file_unlink(__FILE__)) {
			$page['body'] .= '<div class="ban"><h2>Delete install.php!</h2><p>I couldn\'t remove <strong>install.php</strong>. You will have to remove it manually.</p></div>';
		}*/
	}
	
	echo Element('page.html', $page);
} elseif ($step == 5) {
	$page['title'] = 'Installation complete';
	$page['body'] = '<p style="text-align:center">Thank you for using Tinyboard. Please remember to report any bugs you discover.</p>';
	
	$boards = listBoards();
	foreach ($boards as &$_board) {
		setupBoard($_board);
		buildIndex();
	}
	
	file_write($config['has_installed'], VERSION);
	if (!file_unlink(__FILE__))
		$page['body'] .= '<div class="ban"><h2>Delete install.php!</h2><p>I couldn\'t remove <strong>install.php</strong>. You will have to remove it manually.</p></div>';
	
	echo Element('page.html', $page);
}
