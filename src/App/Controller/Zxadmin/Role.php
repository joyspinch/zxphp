<?php

namespace Zxadmin;

use Ctrl\AdminController;

/**
 * Class Role
 *
 * @module Admin
 * @name Role
 * @rank   99
 */

// hook zxadmin_role_use.php

Class Role extends AdminController
{
	// hook zxadmin_role_start.php

	/**
	 * @title  Index_PUT
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:54
	 */
	public function action_Index_PUT()
	{
		// hook zxadmin_role_index_put_start.php
		return $this->Template();
	}

	/**
	 * @title  Index_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:54
	 */
	public function action_Index_GET()
	{
		$cond = [];
		$page = $this->request->param('page', 1);
		$size = $this->request->param('limit', 10);
		$field = $this->request->param('field', 'role_id');
		$_order = $this->request->param('order', '');
		$role_name = $this->request->param('role_name', '');
		// hook zxadmin_role_index_get_start.php

		empty($field) AND $field = 'role_id';
		!empty($role_name) AND $cond['role_name']['LIKE'] = $role_name;
		$order = [$field => $_order == 'asc' ? 1 : -1];
		$data = $this->Role->GetList($cond, $order, $page, $size);
		// hook zxadmin_role_index_get_end.php
		$this->response('0000', $data);
	}

	/**
	 * @title  Field_POST
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:54
	 */
	public function action_Field_POST()
	{
		$role_id = $this->request->param('role_id', 0);
		$field = $this->request->param('field', '');
		$value = $this->request->param('value', '');
		$this->CheckEmpty([$role_id, $field], ['权限组ID', '修改字段']);
		$arr = ['role_name', 'desc', 'status'];
		// hook zxadmin_role_field_post_start.php
		!in_array($field, $arr) AND $this->response('0003', [], '字段不可修改');
		$role = $this->Role->read(['role_id' => $role_id]);
		empty($role['role_id']) AND $this->response('0003', [], '账户不存在');
		switch ($field) {
			case 'status':
			case 'role_name':
			case 'desc':
				$this->token['gid'] > $role['role_id'] AND $this->response('0003', [], '权限等级不足');
				break;
		}
		// hook zxadmin_role_field_post_start.php
		$this->Role->update(['role_id' => $role_id], [$field => $value]);
		$this->response('0000');
	}

	/**
	 * @title  删除权限组
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:53]
	 */
	public function action_index_DELETE()
	{
		$role_id = $this->request->param('role_id', 0);
		// hook zxadmin_role_index_delete_start.php
		$this->CheckEmpty([$role_id], ['权限组']);
		$role_id == 1 AND $this->response('0003', [], '超级管理员组不能删');
		$this->token['gid'] > $role_id AND $this->response('0003', [], '权限等级不足');
		$this->Role->delete_by_role_id($role_id);
		// hook zxadmin_role_index_delete_end.php
		$this->response('0000', '', '删除组成功');
	}

	/**
	 * @title  修改权限组状态
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:53
	 */
	public function action_Status_POST()
	{
		$role_id = $this->request->param('role_id', 0);
		$status = $this->request->param('status', 0);
		// hook zxadmin_role_status_post_start.php
		$this->CheckEmpty([$role_id], ['权限组']);
		$role_id == 1 AND $this->response('0003', [], '超级管理员组不能删');
		$this->Role->update_status_by_role_id($role_id, $status);
		// hook zxadmin_role_status_post_end.php
		$this->response('0000', '', $status == 1 ? '启用成功' : '禁用成功');
	}

	/**
	 * @title  Option_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:53
	 */
	public function action_Option_GET()
	{
		// hook zxadmin_role_option_get_start.php
		$list = $this->Role->select(['status' => 1], [], 'role_id, role_name');
		// hook zxadmin_role_option_get_end.php
		$this->response('0000', ['data' => $list]);
	}



	// hook zxadmin_role_end.php
}

?>