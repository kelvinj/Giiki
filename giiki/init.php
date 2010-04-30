<?php
/**
 * Stores internals for giiki application/
 */
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING);
ini_set('include_path', ini_get('include_path').':'.ROOT.'/giiki/lib');
require_once 'Fu/Autoloader.php';

// reset same _SERVER vars to show correct path, mod-rewrtie screws them up
$_SERVER['SCRIPT_NAME'] = ($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

include 'giiki/options.php';
include 'giiki/functions.php';

$giiki = $g = new Giiki($giiki_options);
$g->authenticate();

switch (true) { // which action

	case isset($_REQUEST['script']):
		$page_name = preg_replace('/[^a-z_\-\.]/i', '', $_REQUEST['p']);
		if ($page_name == 'logout.php') {
			$g->logout();
		}


		$file = ROOT.'/giiki/theme/'.$page_name;
		if (file_exists($file)) {
			include $file;
		}
		else {
			die('No such page');
		}
	break;

	case isset($_REQUEST['edit']):
		include 'giiki/theme/edit.php';
	break;

	case isset($_REQUEST['view']):
	default:
		if (http_is_post()) {
			if ($_POST['form_action'] == 'newpage') {
				$page_name = preg_replace('/[^a-z\s0-9\/_\-\.]/i', '', $_POST['pagename']);
				if (!$page_name) {
					http_redirect(http_request_uri());
				}
				http_redirect('/'.$page_name.'.html?edit');
			}
		}
		if (!$g->page_exists()) {
			http_redirect('/'.$g->get_page().'?edit');
		}
		$qs = str_replace(http_path().'?', '', http_request_uri());
		if (strlen($qs) == 40 && preg_match('/^[a-z0-9]+$/i', $qs)) {
			define('COMMIT', $qs);
		}
		include 'giiki/theme/view.php';
	break;
}