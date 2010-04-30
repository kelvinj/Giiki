<?php
class DB_User extends DB_Common {
	protected
		$_table = 'users';

	public
		$id,
		$email,
		$password,
		$is_admin,
		$name,
		$settings,
		$token,
		$is_deleted,
		$created_at,
		$updated_at;

	/**
	 * Called by Fu_DB __constuct
	 */
	function init () {
		$this->validates_presence_of(array(
			'name',
			'email',
			'password'
		));

		// make sure this email is unique in this PC only
		$this->validates_uniqueness_of('email');

		$this->bind('before_save', 'encode_settings');
		$this->bind('before_insert', 'rand_token');
	}

	function rand_token () {
		$this->token = md5(rand());
	}

	function encode_settings () {
		if (is_array($this->settings)) {
			$this->settings = json_encode($this->settings);
		}
	}

	function settings () {
		$settings = json_decode($this->settings);
		if (!$settings->editor) {
			$settings->editor = 'tinymce';
		}

		return $settings;
	}
}