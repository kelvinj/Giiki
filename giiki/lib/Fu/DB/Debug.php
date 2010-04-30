<?php
/**
 * This class database debugging
 */

Fu_Reg::set('fu_db_debug', new Fu_DB_Debug);

class Fu_DB_Debug {
    private
		$info = array(),
		$debug=false
		;


	/**
	 * Handles certain housekeeping tasks to keep the flash messages in order.
	 */
	function __construct () {
		if (defined('FU_DB_DEBUG') && FU_DB_DEBUG===true) {
			$this->debug = true;
		}
	}

	public function debug ($s=null, $p=array()) {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $instance = Fu_Reg::get("fu_db_debug");
            return $instance->debug($s, $p);
        }
		if ($this->debug) {
			if ($s) {
				$this->info[] = array(
					'sql' => $s,
					'params' => $p
				);
			}
			else {
				return $this->info;
			}
		}
	}
}