<?php
/**
 * Register and retrive global variables in a simple namespaced fashion.
 *
 * Useful for registering internal variables in Fu, or using variables that span multiple
 * objects, e.g. a DB Handler.
 */

class Fu_Reg {
	/**
	 * Register a global variable called __fu_$var & also $var if requested.
	 *
	 * We use this so that we have vars we can rely upon (__fu_*) and optionally short
	 * versions that the coder can use.
	 *
	 * Variables that have already been declared cannot be overwritten.
	 *
	 * @param string name of variable
	 * @param mixed value of variable
	 * @param bool if true will also set a global variable by the same name
	 * @return mixed value of variable
	 */
	function &set ($k, $v, $reg_global=false) {
		$var_name = "__fu_$k";

		$GLOBALS[$var_name] =& $v;
		if ($reg_global) {
			$GLOBALS[$k] =& $GLOBALS[$var_name];
		}

		return Fu_Reg::get($k);
	}

	/**
	 * Gets the registeres variable
	 *
	 * @param string name of variable, must be the same that was used to set
	 * @return mixed value of variable
	 */
	function &get ($k) {
		$var_name = "__fu_$k";
		return $GLOBALS[$var_name];
	}
}