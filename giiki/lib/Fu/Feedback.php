<?php
/**
 * This class handles feedback given to the user. In the form of:
 *
 *  - errors (multiple)
 *  - flash messages
 *
 *  Errors are added to stacks. By default there is one stack (default)
 *  which is used for general errors. Errors can be added to another stack,
 *  e.g. 'debug', which can be conditionally called up fro the view. It is also
 *  possible to only allow a certain stack in a certain environment.
 *
 *  Flash messages persist over 1 page request, but are then destroyed.
 */

Fu_Reg::set('fu_feedback', new Fu_Feedback);

class Fu_Feedback {
    private
		$errors = array(),
		$flashes,
		$flash_key = '__fu_feedback_flash_messages';


	/**
	 * Handles certain housekeeping tasks to keep the flash messages in order.
	 */
	function __construct () {
		session_start();

		$key = $this->flash_key;
		if ($_SESSION[$key]) {
			$this->flashes = $_SESSION[$key];
			$_SESSION[$key] = array();
		}
	}

	/**
	 * Add an error message, and optionally set a stack for it.
	 *
	 * For example, you may have a debug stack which is only used in a dev environment
	 *
	 * @param mixed message, can be a string or an array of strings
	 * @param string stack name
	 */
    function add_error ($msg, $stack='default') {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $instance = Fu_Reg::get("fu_feedback");
            return $instance->add_error($msg, $stack);
        }

		if (!is_array($msg)) {
			$msg = array($msg);
		}

		foreach ($msg as $m) {
			$this->errors[$stack][] = $m;
		}
    }

	/**
	 * Returns all errors from a particular stack
	 *
	 * @param string stack name
	 * @return array
	 */
    function errors ($stack='default') {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $instance = Fu_Reg::get("fu_feedback");
            return $instance->errors($stack);
        }

        return (array) $this->errors[$stack];
    }

	/**
	 * Do we have any errors to show for a particular stack
	 *
	 * @param string stack name
	 * @return bool
	 */
    function has_errors ($stack='default') {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $instance = Fu_Reg::get("fu_feedback");
            return $instance->has_errors($stack);
        }

        return !!$this->errors[$stack];
    }



	/**
	 * A flash message is a message which persists from one page request to another, but is then
	 * destroyed.
	 *
	 * E.g. Fu_Feedback::set_flash('You are approaching your limit', 'warning')
	 *
	 * @param string text to send
	 * @param string type of text, defaults to message but could be set to anything, e.g. notice
	 */
    function set_flash ($text, $type='message') {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $instance = Fu_Reg::get("fu_feedback");
            return $instance->set_flash($text, $type);
        }

		$key = $this->flash_key;
		if (!$_SESSION[$key]) {
			$_SESSION[$key] = array();
		}

		$_SESSION[$key][] = array(
			'text' => $text,
			'type' => $type
		);
    }

	/**
	 * Return an array of all flash messages
	 *
	 * @return array
	 */
    function get_flashes () {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $instance = Fu_Reg::get("fu_feedback");
            return $instance->get_all_flashes();
        }

        return (array) $this->flashes;
    }

	/**
	 * Will return the text of the first flash message that matches the given type.
	 *
	 * E.g. Fu_Feedback::get_flash('warning')
	 *
	 * @param string type
	 * @return string text
	 */
    function get_flash ($type='message') {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $instance = Fu_Reg::get("fu_feedback");
            return $instance->get_flash($type);
        }

		foreach ((array)$this->flashes as $flash) {
			if ($flash['type'] == $type) {
				return $flash['text'];
			}
		}

		return '';
    }
}