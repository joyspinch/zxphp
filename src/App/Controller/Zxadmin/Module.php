<?php

namespace Zxadmin;

use Ctrl\AdminController;

/**
 * Class Module
 *
 * @module Admin
 * @name Module
 * @rank   99
 */
// hook zxadmin_module_use.php

Class Module extends AdminController
{
	// hook zxadmin_module_start.php

	/**
	 * @title  Index_Put
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:56
	 */
	public function action_Index_Put()
	{
		// hook zxadmin_module_index_put_use.php
		return $this->Template();
	}

	/**
	 * @title  Index_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:56
	 */
	public function action_Index_GET()
	{
		// hook zxadmin_module_index_get_start.php
		$module = $this->Menu->select(['is_delete' => 0], [], 'module');
		$arr = $_ENV['Module'];
		$module = array_filter(array_unique(arrlist_values($module, 'module')));
		foreach ($module as $v) {
			!in_array($v, $arr) AND $data[] = ['k' => $v, 'name' => $v];
		}
		// hook zxadmin_module_index_get_end.php
		$this->response('0000', ['data' => $data]);
	}

	// hook zxadmin_module_end.php
}

?>