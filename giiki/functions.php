<?php
function h ($str, $return=false) {
	$str = htmlentities(stripslashes($str), ENT_QUOTES, 'UTF-8');
    if ($return) {
		return $str;
	}

	echo $str;
}

function app_output ($content) {
	global $g;

	if ($g->get_option('autolink_urls')) {
		$content = preg_replace('/(<a(.*)<\/a>)/i', '||tagdelim||$1||tagdelim||', $content);
		$parts = explode('||tagdelim||', $content);

		$new_content = "";
		foreach ($parts as $part) {
			if (strtolower(substr($part, 0, 3)) != '<a ') {
				$part = preg_replace('/(https?:[^\s<\n\r\t\h\v\b\(\)]+)/i', '<a href="$1">$1</a>', $part);
			}
			$new_content.=$part;
		}
		echo $new_content;
	}
	else {
		echo $content;
	}
}

function http_redirect ($url) {
    if (stristr($_SERVER['HTTP_ACCEPT'], 'javascript')) { // part of XHR
        header("HTTP/1.0 401 Unauthorized");
    }
    else {
		header("Location: http://".$_SERVER['SERVER_NAME'].$url);
    }
    exit;
}

function http_request_uri () {
	return $_SERVER['REQUEST_URI']  ?  $_SERVER['REQUEST_URI']  :  $_SERVER['REDIRECT_URL'];
}

/**
 * Fetch the path to this script, excluding any arguments sent through,
 *
 * a REQUEST of /users?id=109 will return a path of /users
 *
 * @return string path
 */
function http_path () {
	$p = rtrim($_SERVER["SCRIPT_NAME"], "/");
	return $p ? $p : '/';
}

function http_channel () {
	static $channel, $channel_retrieved = false;

	if ($channel_retrieved) return $channel;

	$path = http_path();
	$paths_array = explode('/',ltrim($path, '/'));

	$channel = (count($paths_array) > 0) ? $paths_array[0] : '';
	$channel_retrieved = true;

	return $channel;
}



function http_is_post () {
	return $_SERVER['REQUEST_METHOD'] == 'POST';
}

function http_is_get () {
	return $_SERVER['REQUEST_METHOD'] == 'GET';
}

/**
 * Shows all possible feedback in one fell swoop
 */
function app_show_feedback() {
	echo '<div class="flash">';
	app_show_success();
	app_show_info();
	app_show_errors();
	echo '</div>';
}

/**
 * Display an error box on a form
 */
function app_show_success () {
	if ($msg = Fu_Feedback::get_flash('message')) {
		printf('<div class="message success"><p>%s</p></div>', $msg);
	}
}

/**
 * Display an error box on a form
 */
function app_show_info () {
	if ($msg = Fu_Feedback::get_flash('info')) {
		printf('<div class="message notice"><p>%s</p></div>', $msg);
	}
}

/**
 * Display an error box on a form
 */
function app_show_errors () {
	if (Fu_Feedback::has_errors()) {
		echo '<div class="message error"><p>Sorry - a problem occurred:</p><ul>';
		foreach (Fu_Feedback::errors() as $e) {
			printf("<li>%s</li>\n", $e);
		}
		echo "</ul></div>";
	}
	else if ($msg = Fu_Feedback::get_flash('error')) {
		printf('<div class="message error"><p>Sorry - a problem occurred:</p><ul><li>%s</li></ul></div>', $msg);
	}
}

function app_debug () {
	$dbg = Fu_DB_Debug::debug();
	if ($dbg) {
		var_dump($dbg);
	}
}
