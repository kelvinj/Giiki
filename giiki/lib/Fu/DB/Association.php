<?php
/**
 * Handles all types of associations that can be defined by a class:
 *
 * + belongs_to
 * + has_one
 * + has_many
 * + has_and_belongs_to_many (habtm)
 *
 */
class Fu_DB_Association {
	private
		$associations = array(),
		$failures = array(),
		$dbo
		;

	public
		$frozen = false // when the object is frozen, no new associations can be added.
		;

	/**
	 * Singleton should be used to save memory.
	 * It keeps 1 global object that is used by BS for each type of DB object
	 *
	 * @param Fu_DB_Base based object
	 * @return Fu_DB_Association object instance
	 */
	public function singleton ($class) {
		$instance = Fu_Reg::get("association_$class");

		if (is_null($instance)) {
			$instance = Fu_Reg::set("association_$class", new Fu_DB_Association($dbo));
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

	/**
	 * Adds an association to this object
	 *
	 * @param string type of association
	 * @param string association class name
	 * @param string association name
	 * @param array options
	 * @return void
	 */
	public function add ($type, $opts=array()) {
		if ($this->frozen) {
			return true;
		}

		if (!$opts['association_name']) {
			throw new Fu_DB_Association_Exception('no name for association');
		}

		if (!$opts['class']) {
			throw new Fu_DB_Association_Exception('no class for association');
		}

		if (method_exists($this, $type)) {
			$opts['type'] = $type;
			$this->associations[$opts['association_name']] = $opts;
			return true;
		}
		else {
			throw new Fu_DB_Association_Exception('invalid association');
		}
	}

	/**
	 * Fetches and returns an association. Depending on the type of association, this will either
	 * be a DB_Base based object, or a DB_Result resultset
	 *
	 * @param Object Fu_DB_Based derived object
	 * @param string association name
	 * @param array options
	 * @return true
	 */
	public function fetch ($dbo, $assoc_name, $options = array()) {
		if ($this->associations[$assoc_name]) {
			$func = $this->associations[$assoc_name]['type'];

			$opts = array_merge($this->associations[$assoc_name], $options);

			return $this->$func($dbo, $opts);
		}
		else {
			throw new Fu_DB_Association_Exception('Unknown association');
		}
	}

	/**
	 * Return a single associated object for which this object holds an id.
	 *
	 * @param DB object
	 * @param array association
	 * @return DB object or false on failure
	 */
	private function belongs_to ($dbo, $options) {
		$opts = array(
			'association_id' => 'id'
		);
		$opts = array_merge($opts, $options);

		$fk = $opts['foreign_key_id'];
		$fk_id = $dbo->$fk;

		if (!$fk_id) {
			return;
		}

		$fko = new $opts['class'];
		return $fko->find_by($opts['association_id'], $fk_id);
	}

	/**
	 * Return a result set of associated objects where the id is on the target table
	 *
	 * @param DB object
	 * @param array association
	 * @return Fu_DB_Result object or false on failure
	 */
	private function has_many ($dbo, $options) {
		$opts = array(
			'association_id' => 'id',
			'order'	=> 'id ASC'
		);
		$opts = array_merge($opts, $options);

		if (!$dbo->id) {
			return;
		}

		$fko = new $opts['class'];
		$conditions = array('`'.$opts['association_id'].'` = ?', $dbo->id);
		$query_options = array(
			'conditions' => $conditions,
			'order' => $opts['order'],
			'limit' => $opts['limit'],
			'per_page' => $opts['per_page'],
			'current_page' => $opts['current_page']
		);

		return $fko->find_all($query_options);
	}

	/**
	 * Return a DBO of 1 associated object where the id is on the target table
	 *
	 * @param DB object
	 * @param array association
	 * @return DB object or false on failure
	 */
	private function has_one ($dbo, $options) {
		$result = $this->has_many($dbo, $options);
		if ($result instanceof Fu_DB_Result && count($result) > 0) {
			return $result->current();
		}
		else {
			return false;
		}
	}

	/**
	 * A slightly renamed has_and_belongs_to_many association, coz we're lazy init!
	 *
	 * Return a Fu_DB_Result result set of all matching results on target table
	 *
	 * @param DB object
	 * @param array association
	 * @return DB object or false on failure
	 */
	private function many_to_many ($dbo, $options) {
		$opts = array(
			'table' => null,
			'foreign_key' => null,
			'association_foreign_key' => null
		);
		$opts = array_merge($opts, $options);

		if (!$opts['table'] || !$opts['foreign_key'] || !$opts['association_foreign_key']) {
			throw new Fu_DB_Association_Exception('invalid association: invalid options (rtfm)');
		}

		$target_o = new $opts['class'];
		$target_table = $target_o->get_table();

		$intersection = $opts['table'];
		$intersection_fk = $opts['foreign_key'];
		$intersection_afk = $opts['association_foreign_key'];

		$intersect_query = sprintf('SELECT `%s`.`%s` AS id FROM `%1$s` WHERE `%1$s`.`%s`=?', $intersection, $intersection_afk, $intersection_fk);

		$query = 'SELECT `%s`.* FROM `%1$s` INNER JOIN (%s) AS intersection ON `%1$s`.id=intersection.id';
		if ($target_o->field_exists('is_deleted')) {
			$query.= ' WHERE is_deleted=0';
		}

		$query.= ' GROUP BY `%1$s`.id';

		$sql = sprintf($query, $target_table, $intersect_query);

		$params = array(
			$dbo->id
		);

		$result = $dbo->query(array(
								'sql' => $sql,
								'params' => $params,
								'order' => $opts['order'],
								'limit' => $opts['limit'],
								'per_page' => $opts['per_page'],
								'current_page' => $opts['current_page']
								));

		$result->set_class($opts['class']);
		return $result;
	}
}

class Fu_DB_Association_Exception extends Exception {}
