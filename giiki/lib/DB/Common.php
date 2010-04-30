<?php
/**
 * This classes contains common functionality that may be called upon by all the Database classes
 */
class DB_Common extends Fu_DB {
	/**
	 * Wraps around the Fu_DB::save method and adds error to Fu_Feedback
	 */
	function save () {
		try{
			parent::save();
			return $this->id;
		}
		catch (Fu_DB_Validation_Exception $e) {
			$errors = $e->getErrors();
			foreach ($errors as $error) {
				Fu_Feedback::add_error($error['message']);
			}
			return false;
		}
	}

	/**
	 * Wraps around the Fu_DB::save method and adds error to Fu_Feedback
	 */
	function find_all ($options=array()) {
		try{
			if (!$options['limit']) {
				$options['limit'] = '9999999';
			}
			$results = parent::find_all($options);
			$results->num_rows = $results->total_rows;
			return $results;
		}
		catch (Fu_DB_Validation_Exception $e) {
			$errors = $e->getErrors();
			foreach ($errors as $error) {
				Fu_Feedback::add_error($error['message']);
			}
			throw new Fu_DB_Exception('Validations failed');
		}
	}
}