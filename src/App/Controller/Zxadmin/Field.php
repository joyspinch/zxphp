<?php

namespace Zxadmin;

use Ctrl\AdminController;

/**
 * Class Field
 *
 * @module Admin
 * @name Field
 * @rank   99
 */

// hook zxadmin_field_use.php

Class Field extends AdminController
{
	// hook zxadmin_field_start.php

	/**
	 * @title  Index_PUT
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:57
	 */
	public function action_Index_PUT()
	{
		// hook zxadmin_field_index_put_use.php
		return $this->Template();
	}

	/**
	 * @title  Index_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:57
	 */
	public function action_Index_GET()
	{

		// hook zxadmin_field_index_get_start.php
		$dblink = $this->request->param('dblink', 'db');
		empty($dblink) AND $dblink = 'db';
		empty($_ENV['db_class'][$dblink]) && $this->response('0001', [], '数据库不允许访问');
		$this->User->db = $_ENV['db_class'][$dblink];
		$data = $this->User->show_tables($_ENV['db_class'][$dblink]->conf['database_name']);
		$this->User->db = $_ENV['db_class'][$this->User->link];
		foreach ($_ENV['_models'] as $name => $model) {
			$model->table && $models[$model->table] = $name;
		}

		foreach ($data as &$row) {
			//echo $row['Name'],' ',$models[$row['Name']],"\r\n";
			$row['ismodel'] = isset($models[$row['Name']]) ? 1 : 0;
			$row['Data_length'] = humansize($row['Data_length']);
			$row['Index_length'] = humansize($row['Index_length']);
		}
		// hook zxadmin_field_index_get_end.php
		$this->response('0000', ['data' => $data]);
	}


	/**
	 * @title  表字段详情
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:57
	 */
	public function action_Detail_GET()
	{
		// hook zxadmin_field_detail_get_start.php
		$dblink = $this->request->param('dblink', 'db');
		empty($dblink) AND $dblink = 'db';
		empty($_ENV['db_class'][$dblink]) && $this->response('0001', [], '数据库不允许访问');
		$table = $this->request->param('table', '');
		$this->User->db = $_ENV['db_class'][$dblink];
		$data = $this->User->show_columns($table);
		$this->User->db = $_ENV['db_class'][$this->User->link];
		// hook zxadmin_field_detail_get_end.php
		$this->response('0000', ['data' => $data]);
	}


	// hook zxadmin_field_end.php
}

?>