<?php
/**
 * This class allows us to easily access an indexed row on a db resultset and
 * to iterate over the resultset using foreach.
 *
 * NB: The internal PHP iterator starts at 0, but MySQL counts from 1.
 */
class Fu_DB_Result implements Iterator, Countable {
	public
		$stmt,
		$position = 0, // position of cursor, so to speak
		$current_row = array(),
		$num_rows = 0, // the number of rows in this rowset
		$total_rows = 0,
		$paging_options = array(),
		$klass // stroes the name of the class to use to reconstruct the Object
		; // if a limit was used, the number of total rows that would have been returned

	public function __construct ($stmt) {
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$this->stmt = $stmt;
		$this->num_rows = (int) $stmt->rowCount();
	}
	
	public function __call ($func, $params) { 	
		return call_user_func_array(array($this->stmt, $func), $params);
	}
	
	public function fetch () {
		$row = $this->stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($this->klass) {
			$o = new $this->klass;
			$o->set_all($row);
			return $o;
		}
		else {
			return $row;
		}
	}
	
	public function set_total_rows ($n) {
		$this->total_rows = (int) $n;
	}
	
	public function set_paging_params ($options) {
		$this->paging_options = $options;
	}
	
	public function to_xml ($options=array()) {
		$default_options = array(
			'include' => array(), // only include these columns
			'exclude' => array() // exclude all of these
		);
		$opts = array_merge($default_options, $options);
		
		$xml = new Fu_XmlWriter();
		
		$xml->push('results');
		foreach ($this as $row) {
			$xml->push('record');
			foreach ($row as $col => $val) {
				if (!empty($opts['include']) && !in_array($col, $opts['include'])) {
					continue;
				}
				if (!empty($opts['exclude']) && in_array($col, $opts['exclude'])) {
					continue;
				}
				
				$xml->element($col, $val);
			}
			$xml->pop();
		}
		$xml->pop();
		
		return $xml->getXml();
	}
	
	/**
	 * Sets the class to be used when returning the current row
	 *
	 * @param string class name
	 */
	public function set_class ($klass) {
		$this->klass = $klass;
	}
	
	/**
	 * Allows count() function to be called on this object
	 */
	public function count () {
		return $this->num_rows;
	}

	/**
	 * Iterator functionality
	 */
	public function current () {
		$this->current_row = $this->fetch();
		return $this->current_row;
	}
	
	public function key () {
		return $this->position;
	}
	
	public function next () {
		$this->position++;
		return $this->current_row;
	}
	
	public function rewind () {
		$this->position = ($this->limit_offset) ? $this->limit_offset : 0;
		return true;
	}
	
	public function valid () {
		switch (true) {
			case $this->position >= $this->num_rows:
				$return = false;
			break;
			
			default:
				$return = true;
			break;
			
		}
		
		return $return;
	}
}