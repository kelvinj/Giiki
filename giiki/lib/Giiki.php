<?php
class Giiki {
	private
		$git,
		$options,
		$data_dir,
		$page,
		$page_filepath,
		$page_exists,
		$dsn,
		$user
		;

	function __construct ($options) {
		$this->options = $options;
		$this->data_dir = ROOT.'/data';
		$this->page = $_GET['p'] ? $_GET['p'] : 'index.html';
		$this->page_filepath = $this->data_dir.'/'.$this->page;
		$this->page_exists = file_exists($this->page_filepath);

		$this->git = new Git($this->data_dir, $options['git_bin']);
		$this->dsn = 'sqlite:'.$this->data_dir.'/.sqlite.db';

		$this->_check_installation();

		$dbh = Fu_Reg::get('dbh');
		if (!$dbh) {
			Fu_Reg::set('dbh', new PDO($this->dsn));
		}
	}

	function __destruct () {
		exec('chmod -R 0777 '.$this->data_dir);
	}

	function get_option ($k) {
		return $this->options[$k];
	}

	function get_site_name () {
		return $this->options['name'];
	}

	function get_page () {
		return $this->page;
	}

	function get_page_name ($show_commit_info=false) {
		$page = str_replace('.html', '', $this->page);
		$name = $page == 'index' ? 'home' : $page;

		if ($show_commit_info && defined('COMMIT')) {
			$name.= " @".substr(COMMIT, 0, 7);
		}

		return $name;
	}

	function get_history () {
		return $this->git->get_history($this->page);
	}

	function page_exists() {
		return $this->page_exists;
	}

	function delete_page () {
		$page = $this->get_db_page();
		try {
			$page->delete();
			$this->git->rm($this->page);
			$this->git->commit('Page deleted');
			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	function get_breadcrumbs () {
		$page = self::get_db_page();
		$dirs = explode('/', $page->dir);

		if ($this->page == 'index.html') {
			return array();
		}

		$crumbs = array(
			'/' => 'home'
		);

		$dbo = new DB_Page;
		$current_dir = '';
		foreach ((array) $dirs as $d) {
			try {
				$page = $dbo->find_by('name', "$current_dir$d.html");
				$crumbs['/'.$page->name] = substr($page->name, 0, -5);
			}
			catch (Exception $e) {}
			$current_dir.= "$d/";
		}

		return $crumbs;
	}

	function get_child_pages () {
		$page = self::get_db_page();
		$dbo = new DB_Page;

		if ($this->page == 'index.html') {
			$cond = '(dir IS NULL OR dir="" OR dir=" ")';
		}
		else {
			$cond = array(
				'dir=?',
				substr($page->name, 0, -5)
			);
		}

		$children = $dbo->find_all(array(
			'conditions' => $cond,
			'order' => 'name ASC'
		));

		$crumbs = array();
		foreach ($children as $page) {
			if ($page->name == 'index.html') continue;

			$crumbs['/'.$page->name] = substr($page->name, 0, -5);
		}
		return $crumbs;
	}

	function get_db_page () {
		try {
			$dbo = new DB_Page;
			return $dbo->find_by('name', $this->page);
		}
		catch (Exception $e) {
			$dbo->name = $this->page;
			$dbo->save();
			return $dbo;
		}
	}

	function get_pages () {
		$dbo = new DB_Page;
		return $dbo->find_all(array(
			'order' => 'name ASC'
		));
	}

	function search ($term) {
		$dbo = new DB_Page;
		return $dbo->find_all(array(
			'conditions' => array(
				'searchfield LIKE ? OR name LIKE ?',
				array(
					  "%$term%",
					  "%$term%"
				)
			),
			'order' => 'name ASC'
		));
	}

	function get_page_contents () {
		if (!$this->page_exists) {
			$u = $this->get_logged_in_user();
			$s = $u->settings();
			if ($s->editor == 'normal') {
				return '<p>Type here to begin</p>';
			}
			else {
				return '<h1>Title</h1><table style="width: 100%;" border="0" cellpadding="10"><tr valign="top"><td style="width: 50%;">Column 1</td><td style="width: 50%;">Column 2</td></tr></table>';
			}

		}
		else {
			if (defined('COMMIT')) {
				$contents = $this->git->show(COMMIT.':'.$this->page);
			}

			if (!$contents) {
				$contents = file_get_contents($this->page_filepath);
			}
			$parts = explode('<!--dm-->', $contents);

			return $parts[1];
		}
	}

	/**
	 * Set page contents
	 */
	function set_page_contents ($content) {
		if (!$this->page_exists) {
			$this->_create_page();
		}

		$contents = file_get_contents($this->page_filepath);
		$parts = explode('<!--dm-->', $contents);
		$parts[1] = "\n$content\n";
		$contents = implode("<!--dm-->", $parts);

		if (file_put_contents($this->page_filepath, $contents, FILE_TEXT)) {
			try {
				if ($this->options['index_page_content_in_db']) {
					$page = self::get_db_page();
					$page->searchfield = strip_tags($content);
					$page->save();
				}
			}
			catch (Exception $e) {}
			exec('chmod -R 0777 '.$this->data_dir);
			$this->git->add_all_commit($this->get_page_name().' content updated');
		}
	}

	/**
	 * Get all users
	 */
	function get_users () {
		$dbo = new DB_User;
		return $dbo->find_all();
	}

	/**
	 * Get user
	 */
	function get_user ($id) {
		$dbo = new DB_User;
		return $dbo->find($id);
	}

	/**
	 * Add a new user
	 */
	function add_user ($fields=array()) {
		try {
			$user = new DB_User;
			$user->set_all($fields);
			$user->save();
			return $user;
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Update user
	 */
	function update_user ($id, $fields=array()) {
		$user = self::get_user($id);
		$user->set_all($fields);
		return $user->save();
	}

	/**
	 * Check if user logged in.
	 *
	 * If not, redirect.
	 */
	function authenticate () {
		if (http_path() == '/login.php') {
			return;
		}

		do {
			if ($_COOKIE['cs']) { // user id and token present, check
				$cookie_store = json_decode($_COOKIE['cs']);
				if (!$cookie_store->email) {
					break;
				}

				try {
					$dbo = new DB_User;
					$user = $dbo->find_by('email', $cookie_store->email);

					if ($cookie_store->key != md5($cookie_store->email.$cookie_store->ts.$user->token)) {
						break;
					}

					$this->user = $user;
					$this->git->set_author($this->user);
					return true;
				}
				catch (Exception $e) {
					break;
				}
			}
			else {
				break; // no chance of being logged in
			}
		}
		while (0);

		http_redirect('/login.php?fwd='.rawurlencode(http_request_uri()));
	}

	/**
	 * Performs a login
	 */
	function login ($email, $password, $fwd_url=null) {
		try {
			$dbo = new DB_User;
			$user = $dbo->find_by('email', $email);
			if (!$user->password == $password) {
				throw new Exception;
			}

			// have correct details
			$cookie_store = new stdClass;
			$cookie_store->email = $user->email;
			$cookie_store->ts = time();
			$cookie_store->key = md5($cookie_store->email.$cookie_store->ts.$user->token);

			setcookie('cs', json_encode($cookie_store), 0, '/');

			$redirect_url = ($fwd_url) ? $fwd_url : '/';
			http_redirect($redirect_url);
		}
		catch (Exception $e) {
			Fu_Feedback::add_error('Could not find user');
			return false;
		}
	}

	function logout () {
		setcookie('cs', '', time()-(60*60*24*30), '/');
		http_redirect('/login.php');
	}

	function change_password ($old, $new) {
		$u = $this->get_logged_in_user();
		if ($u->password == $old) {
			$u->password = $new;
			return $u->save();
		}
		else {
			Fu_Feedback::add_error('Incorrect password');
			return false;
		}
	}

	function save_settings ($new) {
		$u = $this->get_logged_in_user();
		$u->settings = $new;
		return $u->save();
	}

	function get_logged_in_user () {
		return $this->user;
	}

	function is_admin_user () {
		return !!$this->user->is_admin;
	}

	function enforce_admin () {
		if (!$this->is_admin_user()) {
			Fu_Feedback::set_flash('Access denied', 'info');
			http_redirect('/');
		}
	}

	function _create_page () {
		ob_start();
			$g = $this;
			include ROOT.'/giiki/theme/save.php';
			$contents = ob_get_contents();
		ob_end_clean();

		$contents = str_replace('<!--content-->', '<!--dm--><!--dm-->', $contents);

		$dir = dirname($this->page_filepath);
		if (strstr($dir, $this->data_dir.'/') !== false) {
			exec('mkdir -p '.$dir);
		}

		if (file_put_contents($this->page_filepath, $contents, FILE_TEXT)) {
			try {
				$page = new DB_Page;
				$page->name = $this->page;

				if ($this->options['index_page_content_in_db']) {
					$page->searchfield = $contents;
				}

				$page->save();
			}
			catch (Exception $e) {}
			exec('chmod -R 0777 '.$this->data_dir);
			$this->git->add_all_commit($this->get_page_name().' page added');
		}
	}


	function _check_installation () {
		$errors = array();

		do {
			if (!is_writable($this->data_dir)) {
				$errors[] = "Data directory not writeable ({$this->data_dir})";
				break;
			}

			if (!is_dir($this->data_dir.'/.git')) { // no git install, try to fix
				if (!file_exists($this->data_dir.'/.gitignore')) { // pop a git ignore file in to ignore the sql db
					file_put_contents($this->data_dir.'/.gitignore', ".DS_Store\n*.db\n");
				}
				if ($this->git->init()) {
					exec('chmod -R 0777 '.$this->data_dir);
					$this->git->add_all_commit('adding existing pages');
				}
				else {
					$errors[] = "Could not initialize git repository ({$this->data_dir}/.git)";
				}
			}

			if (!file_exists($this->data_dir.'/.sqlite.db')) { // no sql db
				if (!copy(ROOT.'/giiki/virgin.db', $this->data_dir.'/.sqlite.db')) {
					$errors[] = "Could not copy SQLite database ({$this->data_dir}/.sqlite.db)";
				}
				else {
					Fu_Reg::set('dbh', new PDO($this->dsn));
					$dbo = new DB_User;
					$admin_user = $dbo->find(1);
					$admin_user->rand_token();
					$admin_user->save();
				}
			}
		} while (0);

		if ($errors) {
			echo implode('<br />', $errors);
			exit;
		}
	}
}