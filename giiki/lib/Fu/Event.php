<?php
/**
 * An event handling mechanism.
 *
 * With theis class, you can bind method calls / functions to an event, which can then be triggered
 * at varipous other points.
 */
class Fu_Event {
	private
		$events = array()
		;
	
	public $eh;

	public function __construct () {}

	/**
	 * bind an event to a function
	 *
	 * @param string event to bind to
	 * @param mixed string for the function to call, array(Object, 'method') for a method.
	 * @return void
	 */
	public function bind ($event, $callback) {
		if (!is_callable($callback)) {
			throw new Fu_Event_Exception('Invalid callback on bind');
		}
		
		if (!array_key_exists($event, $this->events)) {
			$this->events[$event] = array();
		}
		
		$this->events[$event][] = $callback;
	}
	
	/**
	 * Trigger all callbacks for this event
	 *
	 * @param string event
	 * @param array of arguments to parse onto callback
	 * @return void
	 */
	public function trigger ($event, $arguments=array()) {
		if (!is_array($this->events[$event])) {
			return;
		}

		foreach ($this->events[$event] as $callback) {
			if (!is_callable($callback)) {
				throw new Fu_Event_Exception('Invalid callback on trigger');
			}
			
			call_user_func_array($callback, $arguments);
		}
	}
	
	/**
	 * At some point in the future, maybe we should add ability to cancel an event
	 *
	 * @param string returned by self::bind()
	 * @return bool
	 */
	public function cancel ($error) {}
}

class Fu_Event_Exception extends Exception {}