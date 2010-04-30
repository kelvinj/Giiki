<?php
class DB_Page extends DB_Common {
	protected
		$_table = 'pages';

	public
		$id,
		$name,
		$dir,
		$searchfield,
		$is_deleted,
		$created_at,
		$updated_at;

	/**
	 * Called by Fu_DB __constuct
	 */
	function init () {
		$this->validates_presence_of(array(
			'name'
		));
		$this->validates_uniqueness_of('name');
		$this->bind('before_insert', 'save_dir');
	}

	function save_dir () {
		$dir_page_name = "/".$this->name;
		$this->dir = substr(dirname($dir_page_name), 1);
	}

	function get_name () {
		if ($this->name == 'index.html') {
			return 'home';
		}
		return str_replace('.html', '', $this->name);
	}
}