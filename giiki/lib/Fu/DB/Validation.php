<?php
/**
 * Handles all the DB validations
 *
 * NB: As a rule of thumb with validations, if a field is empty, the only validation applicable
 * is the validates_uniqueness_of.
 */
class Fu_DB_Validation {
	private
		$validations = array(),
		$failures = array(),
		$dbo
		;

	public
		$frozen = false // when the object is frozen, no new validations can be added.
		;

	/**
	 * Singleton should be used to save memory.
	 * It keeps 1 global object that is used by BS for each type of DB object
	 *
	 * @param string class name
	 * @return Fu_DB_Validation object instance
	 */
	public function singleton ($class) {
		$instance = Fu_Reg::get("validation_$class");

		if (is_null($instance)) {
			$instance = Fu_Reg::set("validation_$class", new Fu_DB_Validation($dbo));
		}
		else { // as we are restoring this object from the global register, we freeze it
			$instance->frozen = true;
		}

		return $instance;
	}

	/**
	 * @param Fu_DB_Base based object
	 */
	public function __construct () {}

	public function add ($type, $opts=array()) {
		if ($this->frozen) {
			return true;
		}

		$field = $opts[0];
		$opts = (array) $opts[1];

		if (!$field) {
			throw new Exception('no field name for validation');
		}

		if (method_exists($this, $type)) {
			$this->validations[] = array(
				$type,
				$field,
				$opts
			);
			return true;
		}
		else {
			throw new Exception('invalid validation');
		}
	}

	/**
	 * Validates DB object aginst saved validations
	 *
	 * If any validations fail, a Fu_DB_Validation_Exception will be raised
	 *
	 * @param DB object
	 * @param string action defines if it's on an INSERT or an UPDATE
	 * @return true
	 */
	public function validate_all ($dbo, $action) {
		$this->dbo = $dbo;

		$this->failures = array();

		foreach ($this->validations as $v) {
			list($type, $field, $opts) = $v;

			if ($opts['on'] && $opts['on']!=$action) {
				continue;
			}

			if (is_array($field)) {
				foreach($field as $f) {
					$this->$type($f, $opts);
				}
			}
			else {
				$this->$type($field, $opts);
			}
		}

		if ($this->failures) {
			throw new Fu_DB_Validation_Exception($this->failures);
		}

		return true;
	}

	/**
	 * Validates the presence of a field
	 *
	 * @param string field name
	 * @param array options
	 * @return bool
	 */
	private function presence_of ($field, $user_opts = array()) {
		$opts = array(
			'message' => '%s is a required field'
		);
		$opts = array_merge($opts, $user_opts);

		$value = trim((string)$this->dbo->$field);

		if ($value == '') {
			$this->failures[] = array(
				'type' => __FUNCTION__,
				'field' => $field,
				'message' => sprintf($opts['message'], Fu_Inflector::humanize($field))
			);
			return;
		}
	}

	/**
	 * Validates the uniqueness of a field
	 *
	 * @param string field name
	 * @param array options
	 * @return bool
	 */
	private function uniqueness_of ($field, $user_opts = array()) {
		$opts = array(
			'message' => '%s has already been taken',
			'conditions' => null
		);

		$opts = array_merge($opts, $user_opts);

		$value = trim((string)$this->dbo->$field);

		if (trim((string) $value) == '') return; // only verify non-empty fields

		if ($this->dbo->id) { // update
			$conditions = array("`$field` = ? AND id != ?", array($value, $this->dbo->id) );
		}
		else {
			$conditions = array("`$field` = ?", $value);
		}

		// we have been given a where condition, append to auto-generated condition
		if ($opts['conditions']) {
			$conditions[0] .= sprintf(' AND (%s)', $opts['conditions'][0]);

			if (array_key_exists(1, $opts['conditions'])) { // params given
				$conditions[1] = array_merge((array) $conditions[1], (array) $opts['conditions'][1]);
			}
		}

		$qoptions = array(
			'conditions' => $conditions
		);

		$result = $this->dbo->find_all($qoptions);

		if (count($result) > 0) {
			$this->failures[] = array(
				'type' => __FUNCTION__,
				'field' => $field,
				'message' => sprintf($opts['message'], Fu_Inflector::humanize($field))
			);
			return;
		}
	}

	/**
	 * Validates that this field *is not* present in an array of options.
	 *
	 * @param string field name
	 * @param array options
	 * @return bool
	 */
	private function exclusion_of ($field, $user_opts = array()) {
		$opts = array(
			'message' => '%s is reserved',
			'in' => array() // this should be overwritten by the rule in the DB class
		);
		$opts = array_merge($opts, $user_opts);

		$lc = (function_exists('mb_strtolower')) ? 'mb_strtolower' : 'strtolower';
		$value = trim((string)$this->dbo->$field);

		// exclusion list not an array or an empty array
		if (!is_array($opts['in']) || (is_array($opts['in']) && count($opts['in'])==0) || $value == '') {
			return;
		}

		$opts['in'] = array_map($lc, (array)$opts['in']);

		if (in_array($lc($value), $opts['in'])) {
			$this->failures[] = array(
				'type' => __FUNCTION__,
				'field' => $field,
				'message' => sprintf($opts['message'], trim((string) $value), Fu_Inflector::humanize($field))
			);
			return;
		}
	}

	/**
	 * Validates that this field *is* present in an array of options
	 *
	 * @param string field name
	 * @param array options
	 * @return bool
	 */
	private function inclusion_of ($field, $user_opts = array()) {
		$opts = array(
			'message' => '%s is not included in the list',
			'in' => array() // this should be overwritten by the rule in the DB class
		);
		$opts = array_merge($opts, $user_opts);

		$lc = (function_exists('mb_strtolower')) ? 'mb_strtolower' : 'strtolower';
		$value = trim((string)$this->dbo->$field);

		// inclusion list not an array or an empty array
		if (!is_array($opts['in']) || (is_array($opts['in']) && count($opts['in'])==0) || $value == '') {
			return;
		}

		$opts['in'] = array_map($lc, (array)$opts['in']);

		if (!in_array($lc($value), $opts['in'])) {
			$this->failures[] = array(
				'type' => __FUNCTION__,
				'field' => $field,
				'message' => sprintf($opts['message'], trim((string) $value), Fu_Inflector::humanize($field))
			);
			return;
		}
	}

	/**
	 * Validates the length of a field against a particular rule:
	 *
	 * 	min: minimum length of a field
	 * 	max: maximum length of a field
	 * 	in: defined as array(min, max), field is >= min && <= max
	 *
	 * @param string field name
	 * @param array options
	 * @return bool
	 */
	private function length_of ($field, $user_opts = array()) {
		$opts = array(
			'too_short' => '%s is too short (minimimum is %d characters)',
			'too_long' => '%s is too long (maximimum is %d characters)',
			'wrong_length' => '%s is the wrong length (should be between %d and %d characters)'
		);
		$opts = array_merge($opts, $user_opts);

		$value = trim((string)$this->dbo->$field);

		// inclusion list not an array or an empty array
		if ($value == '') {
			return;
		}

		$len = (function_exists('mb_strlen')) ? mb_strlen($value) : strlen($value);

		$error_message = '';
		$error_args = array();

		switch (true) {
			case isset($opts['min']):
				if ((int) $opts['min'] > $len) {
				   $error_message = $opts['too_short'];
				   $error_args[] = $opts['min'];
				}
			break;

			case isset($opts['max']):
				if ((int) $opts['max'] < $len) {
				   $error_message = $opts['too_long'];
				   $error_args[] = $opts['max'];
				}
			break;

			case isset($opts['in']) && is_array($opts['in']):
				list($min, $max) = $opts['in'];
				if ((int) $min > $len || (int) $max < $len) {
				   $error_message = $opts['wrong_length'];
				   $error_args[] = $min;
				   $error_args[] = $max;
				}
			break;
		}

		// if message is defined, it overwrites all messages
		if ($opts['message']) {
			$error_message = $opts['message'];
		}

		if ($error_message) {
			$this->failures[] = array(
				'type' => __FUNCTION__,
				'field' => $field,
				'message' => sprintf($error_message, Fu_Inflector::humanize($field), $error_args[0], $error_args[1])
			);
			return;
		}
	}

	/**
	 * Validates that the field is numeric
	 *
	 * @param string field name
	 * @param array options
	 * @return bool
	 */
	private function numericality_of ($field, $user_opts = array()) {
		$opts = array(
			'message' => '%s is not a number'
		);
		$opts = array_merge($opts, $user_opts);

		$value = trim((string)$this->dbo->$field);

		// inclusion list not an array or an empty array
		if ($value == '') {
			return;
		}

		if (!is_numeric($value)) {
			$this->failures[] = array(
				'type' => __FUNCTION__,
				'field' => $field,
				'message' => sprintf($opts['message'], Fu_Inflector::humanize($field))
			);
			return;
		}
	}

	/**
	 * Validates the value of a field against a REGEX. Regex should be passed as 'with' in options.
	 *
	 * @param string field name
	 * @param array options
	 * @return bool
	 */
	private function format_of ($field, $user_opts = array()) {
		$opts = array(
			'message' => '%s is invalid',
			'with' => '/.*/'
		);
		$opts = array_merge($opts, $user_opts);

		$value = trim((string)$this->dbo->$field);

		// inclusion list not an array or an empty array
		if (!$opts['with'] || $value == '') {
			return;
		}

		if (!preg_match($opts['with'], $value)) {
			$this->failures[] = array(
				'type' => __FUNCTION__,
				'field' => $field,
				'message' => sprintf($opts['message'], Fu_Inflector::humanize($field))
			);
			return;
		}
	}
}

class Fu_DB_Validation_Exception extends Exception {
	private $errors;

	public function __construct ($errors, $code=null) {
		parent::__construct('Validations failed', $code);
		$this->errors = $errors;
	}

	public function getErrors() {
		return $this->errors;
	}
}