<?php
class Fu_Pager {
	protected
		$options = array(),
		$pager;
		

	function __construct ($o) {
		$this->options = array(
			'mode'       => 'Jumping', // Sliding
			'perPage'    => $o->paging_options['per_page'],
			//'delta'      => 2,
			'urlVar' => 'page',
			'append' => true,
			//'path' => dirname($_dynamic_path),
			//'fileName' => '/'.basename($_dynamic_path).'?pageID=%d&'.http_build_query(array('params' => $_params)),
			'totalItems' => count_all($o),
		);
		
		$this->pager = Pager::factory($this->options);
	}
	
	/**
	 * Return PEAR Pager object
	 */
	public function get_pager () {
		return $this->pager;
	}
	
	/**
	 * Return an array like:
	 * array(from, to)
	 */
	public function get_from_to () {
		return $this->pager->getOffsetByPageId();
	}
	
	/**
	 * Return an array with different options for links
	 */
	public function get_links () {
		return $this->pager->getLinks();
	}
	
	/**
	 * Return HTML to be echoe'd straight out by the script
	 */
	public function to_string () {
		$links = $this->get_links();
		return $links['all'];
	}
}