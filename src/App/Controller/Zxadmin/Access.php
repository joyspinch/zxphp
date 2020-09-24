<?php

namespace Zxadmin;

use Ctrl\AdminController;

/**
 * Class Access
 *
 * @module Admin
 * @name Access
 * @rank   99
 */

// hook zxadmin_access_use.php

Class Access extends AdminController
{
	// hook zxadmin_access_start.php

	/**
	 * @title  Index_PUT
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:50
	 */
	public function action_Index_PUT()
	{
		// hook zxadmin_access_index_put_start.php
		return $this->Template();
	}

	/**
	 * @title  Index_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:50
	 */
	public function action_Index_GET()
	{
		$cond = [];
		// hook zxadmin_access_index_get_start.php
		$this->response('0000', $cond);
	}
	// hook zxadmin_access_end.php
}

?>