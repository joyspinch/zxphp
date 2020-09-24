<?php

namespace Zxadmin;

use Ctrl\AdminController;

// hook zxadmin_user_use.php

/**
 * Class User
 *
 * @module Zxadmin
 * @name User
 * @rank   99
 */

Class User extends AdminController
{
	// hook zxadmin_user_start.php

	/**
	 * @title  Index_PUT
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:53
	 */
	public function action_Index_PUT()
	{
		// hook zxadmin_user_index_put_start.php
		return $this->Template();
	}

	/**
	 * @title  Index_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:53
	 */
	public function action_Index_GET()
	{
		$cond = [];
		$page = $this->request->param('page', 1);
		$size = $this->request->param('limit', 10);
		$field = $this->request->param('field', 'uid');
		$order = $this->request->param('order', '');
		$username = $this->request->param('username', '');
		$mobile = $this->request->param('mobile', '');
		$gid = $this->request->param('gid', 0);
		// hook zxadmin_user_index_get_start.php

		empty($field) AND $field = 'uid';
		!empty($username) AND $cond['username']['LIKE'] = $username;
		!empty($mobile) AND $cond['mobile']['LIKE'] = $mobile;
		!empty($gid) AND $cond['gid'] = $gid;
		$orderby = [$field => $order == 'asc' ? 1 : -1];
		$data = $this->User->GetListFmt($cond, $orderby, $page, $size);

		// hook zxadmin_user_index_get_end.php
		$this->response('0000', $data);
	}

	/**
	 * @title  Index_POST
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:53
	 */
	public function action_Index_POST()
	{
		$userid = $this->request->param('uid', '');
		$password = $this->request->param('password', '');
		$gid = $this->request->param('gid', 0);
		$qq = $this->request->param('qq', '');
		$this->CheckEmpty([$userid, $gid], ['登录账户', '用户组']);
		// hook zxadmin_user_index_post_start.php
		$this->token['gid'] > $gid AND $this->response('0003', [], '权限等级不足');
		mt_srand();
		$data = [
			'gid' => $gid,
			'qq' => $qq,
		];

		if ($password) {
			$data['salt'] = mt_rand(100000, 999999);
			$data['password'] = md5(md5($password) . $data['salt']);
		}
		// hook zxadmin_user_index_post_end.php
		$this->User->update(['uid' => $userid], $data);
		$this->response('0000');
	}


	/**
	 * @title  Index_PATCH
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:53
	 */
	public function action_Index_PATCH()
	{
		$username = $this->request->param('username', '');
		$password = $this->request->param('password', '');
		$this->CheckEmpty([$username, $password], ['登录账户', '登录密码']);
		// hook zxadmin_user_index_patch_start.php
		mt_srand();
		$salt = mt_rand(100000, 999999);
		$password = md5(md5($password) . $salt);

		$data = [
			'username' => $username,
			'password' => $password,
			'group_id' => 10002,
			'salt' => $salt,
		];
		// hook zxadmin_user_index_patch_end.php
		$this->User->insert($data);
		$this->response('0000');
	}


	/**
	 * @title  Field_POST
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:52
	 */
	public function action_Field_POST()
	{
		$id = $this->request->param('id', 0);
		$field = $this->request->param('field', '');
		$value = $this->request->param('value', '');
		$this->CheckEmpty([$id, $field], ['用户ID', '修改字段']);
		$arr = ['status', 'realname', 'mobile', 'qq', 'gid'];
		// hook zxadmin_user_field_post_start.php
		!in_array($field, $arr) AND $this->response('0003', [], '字段不可修改');
		$user = $this->User->read_by_uid($id);
		empty($user['uid']) AND $this->response('0003', [], '账户不存在');
		switch ($field) {
			case 'status':
			case 'gid':
				($this->token['gid'] > $user['gid'] && $user['gid'] != 0) AND $this->response('0003', [], '权限等级不足');
				$this->token['uid'] == $user['uid'] AND $this->response('0003', [], '此字段自己不可修改');
				break;
		}
		// hook zxadmin_user_field_post_start.php
		$this->User->update(['uid' => $id], [$field => $value]);
		$this->response('0000');
	}

	/**
	 * @title  Password_POST
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:52
	 */
	public function action_Password_POST()
	{
		$id = $this->request->param('id');
		$password = $this->request->param('value');
		$this->CheckEmpty([$id, $password], ['用户ID', '新密码']);
		// hook zxadmin_user_password_post_start.php
		mt_srand();
		$salt = mt_rand(100000, 999999);
		$password = md5(md5($password) . $salt);
		// hook zxadmin_user_password_post_end.php
		$this->User->update(['uid' => $id], ['password' => $password, 'salt' => $salt]);
		$this->response('0000');
	}

	/**
	 * @title  Option_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:52
	 */
	public function action_Option_GET()
	{
		// hook zxadmin_user_option_get_start.php
		$data = $this->User->select([], [], 'uid, username');
		$data = arrlist_multisort($data, 'uid', false);
		// hook zxadmin_user_option_get_end.php
		$this->response('0000', ['data' => $data]);
	}

	// hook zxadmin_user_after.php
}

?>