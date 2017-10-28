<?php

require 'info.php';

function sharp_build($action, $settings) {
	$b = new Sharp();
	$b->build($action, $settings);
}

class Sharp {
	private $excluded;
	
	public function build($action, $settings) {
		global $config;
		
		if ($action == 'all') copy('templates/themes/sharp/site.css', $config['dir']['home'] . $settings['css']);
		
		$this->excluded = explode(' ', $settings['exclude']);
		
		if ($action == 'all' || $action == 'boards' || $action == 'post') file_write($config['dir']['home'] . $settings['file_main'], $this->homepage($settings));
		if ($action == 'all' || $action == 'news') file_write($config['dir']['home'] . $settings['file_news'], Sharp::news($settings));
	}
	
	public function homepage($settings) {
		global $config, $board;
		
		$categories = $config['categories'];
		$query = '';
		$recent_images = array();
		$recent_posts = array();
		$stats = array();
		
		foreach ($categories as &$_boards) {
			foreach ($_boards as &$_board) {
				$title = boardTitle($_board);
				
				if (!$title) $title = $_board;
				
				$_board = array('title' => $title, 'uri' => sprintf($config['board_path'], $_board));
			}
		}
		
		$boards = listBoards();
		
		foreach ($boards as &$_board) {
			if (in_array($_board['uri'], $this->excluded)) continue;
			
			$query .= sprintf("SELECT *, '%s' AS `board` FROM `posts_%s` WHERE `file` IS NOT NULL AND `file` != 'deleted' AND `thumb` != 'spoiler' UNION ALL ", $_board['uri'], $_board['uri']);
		}
		
		$query = preg_replace('/UNION ALL $/', 'ORDER BY `time` DESC LIMIT ' . (int)$settings['limit_images'], $query);
		$query = query($query) or error(db_error());
		
		while ($post = $query->fetch()) {
			openBoard($post['board']);
			
			$post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], ($post['thread'] ? $post['thread'] : $post['id'])) . '#' . $post['id'];
			$post['src'] = $config['uri_thumb'] . $post['thumb'];
			
			$recent_images[] = $post;
		}
		
		$query = '';
		
		foreach ($boards as &$_board) {
			if (in_array($_board['uri'], $this->excluded)) continue;
			
			$query .= sprintf("SELECT *, '%s' AS `board` FROM `posts_%s` WHERE `body` <> '' UNION ALL ", $_board['uri'], $_board['uri']);
		}
		
		$query = preg_replace('/UNION ALL $/', 'ORDER BY `time` DESC LIMIT ' . (int)$settings['limit_posts'], $query);
		$query = query($query) or error(db_error());
		
		while ($post = $query->fetch()) {
			openBoard($post['board']);
			
			$post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], ($post['thread'] ? $post['thread'] : $post['id'])) . '#' . $post['id'];
			$post['snippet'] = pm_snippet($post['body'], 30);
			$post['board_name'] = $board['name'];
			
			$recent_posts[] = $post;
		}
		
		$query = 'SELECT SUM(`top`) AS `count` FROM (';
		
		foreach ($boards as &$_board) {
			if (in_array($_board['uri'], $this->excluded)) continue;
			
			$query .= sprintf("SELECT MAX(`id`) AS `top` FROM `posts_%s` UNION ALL ", $_board['uri']);
		}
		
		$query = preg_replace('/UNION ALL $/', ') AS `posts_all`', $query);
		$query = query($query) or error(db_error());
		$res = $query->fetch();
		$stats['total_posts'] = number_format($res['count']);
		$query = 'SELECT COUNT(DISTINCT(`ip`)) AS `count` FROM (';
		
		foreach ($boards as &$_board) {
			if (in_array($_board['uri'], $this->excluded)) continue;
			
			$query .= sprintf("SELECT `ip` FROM `posts_%s` UNION ALL ", $_board['uri']);
		}
		
		$query = preg_replace('/UNION ALL $/', ') AS `posts_all`', $query);
		$query = query($query) or error(db_error());
		$res = $query->fetch();
		$stats['unique_posters'] = number_format($res['count']);
		$query = 'SELECT SUM(`filesize`) AS `count` FROM (';
		
		foreach($boards as &$_board) {
			if (in_array($_board['uri'], $this->excluded)) continue;
			
			$query .= sprintf("SELECT `filesize` FROM `posts_%s` UNION ALL ", $_board['uri']);
		}
		
		$query = preg_replace('/UNION ALL $/', ') AS `posts_all`', $query);
		$query = query($query) or error(db_error());
		$res = $query->fetch();
		$stats['active_content'] = $res['count'];
				
		return Element('themes/sharp/index.html', array(
			'settings' => $settings,
			'config' => $config,
			'categories' => $categories,
			'recent_images' => $recent_images,
			'recent_posts' => $recent_posts,
			'stats' => $stats
		));
	}
	
	public static function news($settings) {
		global $config;
		
		$query = query("SELECT * FROM `news` ORDER BY `time` DESC") or error(db_error());
		$news = $query->fetchAll(PDO::FETCH_ASSOC);
		
		return Element('themes/sharp/news.html', array(
			'settings' => $settings,
			'config' => $config,
			'news' => $news
		));
	}
}

?>