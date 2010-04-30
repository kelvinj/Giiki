<?php
/**
 * The main goal of this class is to standardize as much duplicated DB functionality as possible
 * without compromising code comprehension & maintainability
 */
abstract class Fu_DB {
	protected
	$_dbh,
	$_debug = false,
	$_fields = array(), // 1 time store of the table fields
	$_flags = array(), // store the field names that are considered flags
	$_orig = array(), // stores the original value on load
	$_class = 'Fu_DB', // stores the child class name
	$_validation,
	$_association,
	$_event
	;


	public
		$_debug_info;

	public function __construct ($options=array()) {
		$opts = array_merge(array(
			'dbh' => null
		), $options);

		$this->_dbh = ($opts['dbh']) ? $opts['dbh'] : Fu_Reg::get('dbh');

		$this->_class = get_class($this);

		$fields = get_class_vars($this->_class);

		foreach($fields as $k => $v) {
			if (substr($k, 0, 1)=='_') continue;

			$this->_fields[$k] = $k;
			if (substr($k, 0, 3)=='is_' || substr($k, 0, 4)=='has_' || substr($k, 0, 4)=='can_') {
				$this->_flags[$k] = $k;
			}
		}

		$this->_event = new Fu_Event;

		$this->_validation = Fu_DB_Validation::singleton($this->_class);

		$this->_association = Fu_DB_Association::singleton($this->_class);

		if (method_exists($this, 'init')) {
			$this->init();
		}
	}

	/**
	 * Will log debug info when if debugging is enabled and parameters are passed in
	 * Otherwise will output all previous SQL statements.
	 *
	 * @param string SQL
	 * @param array parameters
	 * @return void
	 */
	protected function debug ($s=null, $p=array()) {
		if ($this->_debug) {
			return Fu_DB_Debug::debug($s, $p);
		}
	}


    /**
     * Map certain calls to a single method, not usually on this object, e.g. the validation, which
     * reside in Fu_DB_Validation
     *
     * @return mixed
     */
    public function __call ($m, $a)
    {
		switch (true) {
			case substr($m, 0, 10) == 'validates_':
				$validation = substr($m, 10);
				return $this->_validation->add($validation, $a);
			break;

			case $m == 'bind':
				if (is_string($a[1]) && method_exists($this, $a[1])) {
					$a[1] = array($this, $a[1]);
				}
				return call_user_method_array('bind', $this->_event, $a);
			break;

			// associations
			case $m == 'fetch':
				return $this->_association->fetch($this, $a[0], (array)$a[1]);
			break;

			case $m == 'belongs_to':
				$options = array(
					'class' => $a[0],
					'association_name' => $a[1],
					'foreign_key_id' => $a[2] ? $a[2] : $a[1]
				);

				if (is_array($a[3])) {
					$options = array_merge($options, $a[3]);
				}

				return $this->_association->add($m, $options);
			break;

			case $m == 'has_one':
			case $m == 'has_many':
				$options = array(
					'class' => $a[0],
					'association_name' => $a[1],
					'association_id' => $a[2]
				);

				if (is_array($a[3])) {
					$options = array_merge($options, $a[3]);
				}

				return $this->_association->add($m, $options);
			break;

			/**
			 * has_and_belongs_to_many & habtm are aliases of many_to_many
			 */
			case $m == 'has_and_belongs_to_many':
			case $m == 'habtm':
			case $m == 'many_to_many':
				$m = 'many_to_many';
				$options = array(
					'class' => $a[0],
					'association_name' => $a[1]
				);

				if (is_array($a[2])) {
					$options = array_merge($options, $a[2]);
				}

				return $this->_association->add($m, $options);
			break;

			// pass any unknown methods calls to the DB handler
			default:
				throw new Exception("Method not found: $m");
			break;
		}
    }

	/**
	 * Run the validations that have been set
	 *
	 * @param string action defines if it's on an INSERT or an UPDATE
	 */
	protected function _run_validations ($action) {
		$this->_validation->validate_all($this, $action);
	}

	/*------------------------------------------------------------------------------------
		Finding records
	------------------------------------------------------------------------------------*/

	/**
	 * Set all properties on the object if the fields exists
	 */
	public function set_all($row) {
		$this->_orig = $row;

		foreach($row as $k => $v) {
			if ($this->field_exists($k)) {
				$this->$k = $v;
			}
		}

		return true;
	}

	/**
	 * find the row by id
	 *
	 * @param mixed $id id of record
	 * @return Child object
	 */
	public function find ($id, $options=array()) {
		$sql = 'SELECT * FROM `'.$this->_table.'` WHERE id=?';
		$params = array($id);

		$conditions = $options['conditions'];
		if ($conditions) {
			list($clean_sql, $clean_params) = $this->_clean_conditions($conditions);
			$sql.= " AND $clean_sql";
			$params = array_merge($params, $clean_params);
		}

		$result = $this->query(array(
			'sql' => $sql,
			'params' => $params
		));

		Fu_DB_Debug::debug($sql, array($id));

		// setup object
		$row = $result->fetch();;

		if (!$row) {
			throw new Fu_DB_Exception('Record Not Found');
		}

		$o = new $this->_class;
		$o->set_all($row);

		return $o;
	}

	/**
	 * find the row by defined field and store against this record
	 *
	 * @param string column name
	 * @param mixed value of column to find
	 * @param array of where clauses to add
	 * @return Child Object
	 */
	public function find_by ($field, $value, $options=array()) {
		if (!$this->field_exists($field)) {
			throw new Fu_DB_Exception('Invalid field name:  '.$field);
		}

		$condition = '`'.$field.'`=?';
		$cparam = array($value);

		if ($options['conditions']) {
			$options['conditions'][0] = $condition." AND ".$options['conditions'][0];
			if (!is_array($options['conditions'][1])) {
				$options['conditions'][1] = array($options['conditions'][1]);
			}

			$options['conditions'][1] = array_merge($cparam, $options['conditions'][1]);
		}
		else {
			$options['conditions'] = array($condition, $cparam);
		}

		$options['limit'] = '1';

		$result = $this->find_all($options);

		if (count($result) == 0) {
			throw new Fu_DB_Exception('Record Not Found');
		}
		else {
			return $result->current();
		}
	}

	/**
	 * find the rows corresponding to this criteria
	 *
	 * @param array of where clauses to add
	 * @return Fu_DB_Result result set
	 */
	public function find_all ($options=array()) {
		/* get options */
		$conditions = $options['conditions'];
		$order = $options['order'];
		$limit = $options['limit'];

		$sql = 'SELECT * FROM `'.$this->_table.'` WHERE 1';
		if ($conditions) {
			list($clean_sql, $clean_params) = $this->_clean_conditions($conditions);
			$sql.= " AND $clean_sql";
			$params = $clean_params;
		}

		if ($this->field_exists('is_deleted')) {
			$sql.= ' AND is_deleted=0';
		}

		$result = $this->query(array(
								'sql' => $sql,
								'params' => $params,
								'order' => $order,
								'limit' => $limit,
								'per_page' => $options['per_page'],
								'current_page' => $options['current_page']
								));

		$result->set_class($this->_class);
		return $result;
	}

	/**
	 * Parse through an SQL statement and any bound parameters
	 * A Fu_DB_Result object will be returned.
	 *
	 * If a LIMIT clause is used, a second QUERY will automatically be generated to calculate
	 * the total number of rows that would have been returned.
	 */
	public function query ($options=array()) {
		$sql = $options['sql'];
		$params = $options['params'];
		$order = $options['order'];
		$limit = $options['limit'];
		$per_page = $options['per_page'];
		$current_page = $options['current_page'];

		if ($per_page > 0) {
			$current_page = ($current_page) ? $current_page : 1;
			$limit = (($current_page-1) * $per_page) . ",$per_page";
		}

		if ($order) {
			$sql.= " ORDER BY $order";
		}
		else if ($this->field_exists('name')) {
			$sql.= " ORDER BY name";
		}

		if ($limit) {
			// need to generate secondary
			$count_sql = preg_replace('/^SELECT\s+(.*)\s+FROM/i', 'SELECT COUNT(*) FROM', $sql, 1);
			$sql.= " LIMIT $limit";
		}

		$st = $this->_dbh->prepare($sql);
		$st->execute($params);
		$result = new Fu_DB_Result($st);
		Fu_DB_Debug::debug($sql, $params);

		if ($limit) {
			$count_st = $this->_dbh->prepare($count_sql);
			$count_st->execute($params);
			$result->set_total_rows($count_st->fetchColumn());
			Fu_DB_Debug::debug($count_sql, $params);
		}

		if ($per_page) {
			$result->set_paging_params(array(
				'per_page' => $per_page,
				'current_page' => $current_page
			));
		}

		return $result;
	}

	/*------------------------------------------------------------------------------------
		Record saving
	------------------------------------------------------------------------------------*/

	/**
	 * saves a row to the database, inserting or updating where needed
	 *
	 * Fu_Event triggers: before_save, before_insert, before_update, after_save, after_insert, after_update
	 *
	 * @return Fu_DB_Result result set
	 */
	public function save () {
		// use old id in case developer wants to manually set the id
		$is_insert = ((bool) $this->old('id')) ? false : true;
		$action = ($is_insert) ? 'insert' : 'update';

		$e = $this->_event;

		// run the data validations
		$e->trigger('before_validation');
		$e->trigger("before_validation_on_$action");

		$this->_run_validations($action);

		$e->trigger("after_validation_on_$action");
		$e->trigger('after_validation');

		$e->trigger("before_$action");
		$e->trigger('before_save');

		$fields = array();
		foreach($this->_fields as $v) {
			if (in_array($v, array('id', 'created_at', 'created_on', 'updated_at', 'updated_on'))) {
				continue;
			}

			$fields[] = $v;
		}

		$sql = ($is_insert) ? 'INSERT INTO' : 'UPDATE';
		$sql.= ' `'.$this->_table.'`';

		$params = array();
		foreach ($fields as $k) {
			$v = $this->$k; // get field value

			if ($this->is_flag($k)) {
				$v = ($v) ? 1 : 0;
			}
			$params[$k] = $v;
		}

		if ($is_insert) {
			$columns_sql = '(';
			$values_sql = 'VALUES(';
			$sql_params = array();

			foreach ($params as $k => $v) {
				if (!empty($sql_params)) {
					$columns_sql.= " ,";
					$values_sql.= " ,";
				}

				$columns_sql.= "`$k`";
				$values_sql.= "?";
				$sql_params[] = $v;
			}

			if ($this->field_exists('created_at')) {
				$columns_sql.= ',created_at';
				$values_sql.=",datetime('now')";
			}
			else if ($this->field_exists('created_on')) {
				$columns_sql.= ',created_on';
				$values_sql.=",date('now')";
			}

			if ($this->id) { // manually setting id
				$columns_sql.= ',`id`';
				$values_sql.= ',?';
				$sql_params[] = $this->id;
			}

			$columns_sql.= ')';
			$values_sql.= ')';


			$sql.= " $columns_sql $values_sql";
		}
		else { // update
			$sql.= ' SET ';

			foreach ($params as $k => $v) {
				if (!empty($sql_params)) {
					$sql.= " ,";
				}

				$sql.= "`$k`=?";
				if ($this->is_flag($k)) {
					$v = ($v) ? 1 : 0;
				}
				$sql_params[] = $v;
			}

			if ($this->field_exists('updated_at')) {
				$sql.= ' ,updated_at=datetime(\'now\')';
			}
			else if ($this->field_exists('updated_on')) {
				$sql.= ' ,updated_on=date(\'now\')';
			}

			$sql.= ' WHERE id=?';
			$sql_params[] = $this->id;
		}

		$e->trigger("before_$action");
		$st = $this->_dbh->prepare($sql);
		$result = $st->execute($sql_params);

		Fu_DB_Debug::debug($sql, $sql_params);

		if ($is_insert && $result==true && !$this->id) {
			$this->id = $this->_dbh->lastInsertId();
			$this->_orig['id'] = $this->id;
		}

		$e->trigger("after_$action");

		$this->_event->trigger('after_save');

		return $result;
	}

	/*------------------------------------------------------------------------------------
		Record deletion
	------------------------------------------------------------------------------------*/

	/**
	 * deletes a row from the database, unless there's an is_deleted column, in which case the flag is set to 1
	 *
	 * Fu_Event triggers: before_delete, after_delete
	 *
	 * @param array options
	 * @return bool false on failure, true on success
	 */
	public function delete ($options=array()) {
		$conditions = $options['conditions'];
		$id = ($options['id']) ? $options['id'] : $this->id;

		if (!$id && !$conditions) {
			throw new Fu_DB_Exception('Delete failed: nothing to delete');
		}

		if ($this->field_exists('is_deleted')) {
			$sql = 'UPDATE `'.$this->_table.'` SET is_deleted=1';
			if ($this->field_exists('deleted_at')) {
				$sql.= ', deleted_at=NOW()';
			}
			else if ($this->field_exists('deleted_on')) {
				$sql.= ', deleted_on=NOW()';
			}
			$sql.= ' WHERE 1';
		}
		else {
			$sql = 'DELETE FROM `'.$this->_table.'` WHERE 1';
		}

		if (is_numeric($id)) {
			$sql.= ' AND id=?';
			$sql_params = array($id);
		}

		if ($conditions) {
			list($clean_sql, $clean_params) = $this->_clean_conditions($conditions);
			$sql.= " AND $clean_sql";
			$sql_params = array_merge((array) $sql_params, $clean_params);
		}

		$this->_event->trigger('before_delete');

		$st = $this->_dbh->prepare($sql);
		$result = $st->execute($sql_params);

		Fu_DB_Debug::debug($sql, $sql_params);

		$this->_event->trigger('after_delete');

		return $result;
	}


	/*------------------------------------------------------------------------------------
		Transactions
	------------------------------------------------------------------------------------*/

	public static function begin () {
		$depth = (int) Fu_Reg::get('Fu_DB_Transaction_Depth');
		if ($depth) {
			Fu_Reg::set('Fu_DB_Transaction_Depth', $depth+1);
			return;
		}
		else { // no open transaction
			Fu_Reg::set('Fu_DB_Transaction_Depth', $depth+1);
			$dbh = Fu_Reg::get('dbh');
			$dbh->beginTransaction();
		}
	}

	public static function commit () {
		$depth = (int) Fu_Reg::get('Fu_DB_Transaction_Depth');
		Fu_Reg::set('Fu_DB_Transaction_Depth', $depth-1);
		if ($depth > 1) {
			return;
		}
		else { // at the root transaction
			$dbh = Fu_Reg::get('dbh');
			$dbh->commit();
		}
	}

	public static function rollback () {
		$depth = (int) Fu_Reg::get('Fu_DB_Transaction_Depth');
		Fu_Reg::set('Fu_DB_Transaction_Depth', $depth-1);
		if ($depth > 1) {
			return;
		}
		else { // at the root transaction
			$dbh = Fu_Reg::get('dbh');
			$dbh->rollback();
		}
	}


	/*------------------------------------------------------------------------------------
		Utility methods
	------------------------------------------------------------------------------------*/

	/**
	 * Does this fields exist
	 *
	 * @param string field name
	 * @return bool
	 */
	public function field_exists ($field) {
		return (bool) !empty($this->_fields[$field]);
	}

	/**
	 * Is this field a flag
	 *
	 * @param string field name
	 * @return bool
	 */
	public function is_flag ($field) {
		return (bool) !empty($this->_flags[$field]);
	}

	/**
	 * Tells us whether this object has been changed since self::set_all() was called.
	 *
	 * If $field is defined, will only tell us if a particular field has been changed.
	 *
	 * @param string column name
	 * @return bool
	 */
	public function changed ($field=null) {
		if ($field) {
			return $this->_orig['id'] && $this->$field != $this->_orig[$field];
		}
		else {
			foreach ($this->_fields as $f) {
				if ($this->changed($f)) {
					return true;
				}
			}

			// no fields changed
			return false;
		}
	}

	/**
	 * Return the old property of a value
	 *
	 * (Mainly for external access)
	 *
	 * @param string key
	 * @return mixed
	 */
	public function old ($k) {
		return $this->_orig[$k];
	}

	/**
	 * Returns an array of the original properties of this Row
	 *
	 * @return array
	 */
	public function get_original_row () {
		return $this->_orig;
	}

	/**
	 * return the maximum $column on this table
	 * optionaly qualified by some where conditions
	 *
	 * @param string name of column to find max value of
	 * @params array where conditions to add to max
	 * @return bool
	 */
	protected function max ($column='id', $where=array()) {
		$sql = 'SELECT MAX('.$column.') as `max` FROM `'.$this->_table.'` WHERE 1';

		if ($where) {
			foreach ($where as $clause) {
				$sql.= ' AND '.$clause;
			}
		}

		$st = $this->_dbh->prepare($sql);
		$st->execute();

		// setup object
		$row = $st->fetch(PDO::FETCH_ASSOC);
		return ($row['max']) ? $row['max'] : 0;
	}

	/**
	 * Return the name of the associated table
	 *
	 * @return string table
	 */
	public function get_table () {
		return $this->_table;
	}

	/**
	 * Takes the common conditions argument and return it as SQL and params
	 *
	 * @param mixed conditions argument either as (string) SQL || array((string) SQL, (mixed) argument) || array((string) SQL, (array) argument((mixed) arg, ... )
	 * @return array 0=> sql, 1=>params
	 */
	private function _clean_conditions ($conditions) {
		if (is_string($conditions)) {
			return array((string) $conditions, array());
		}
		elseif (is_array($conditions)) {
			list($sql, $params) = $conditions;

			if (!is_array($params)) {
				$params = array($params);
			}

			return array ((string) $sql, (array) $params);
		}
	}
}

class Fu_DB_Exception extends Exception {}