<?php

namespace Zxadmin;

use Ctrl\AdminController;

/**
 * Class Admin
 *
 * @module Admin
 * @name Admin
 * @rank   99
 */


// hook zxadmin_admin_use.php
Class Admin extends AdminController
{

	// hook zxadmin_admin_start.php
	/**
	 * @title  User_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:57
	 */
	public function action_User_GET()
	{
		// hook zxadmin_admin_user_get_start.php
		$token = $this->token;
		$token = $this->User->user_safe_info($token);
		// hook zxadmin_admin_user_get_end.php
		$this->response('0000', ['data' => $token]);
	}

	/**
	 * @title  Password_POST
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:57
	 */
	public function action_Password_POST()
	{
		$password = $this->request->param('value');
		// hook zxadmin_admin_password_post_start.php
		$this->CheckEmpty([$password], ['密码']);

		$salt = mt_srand(100000, 999999);
		$password = md5(md5($password) . $salt);
		$this->User->update(['uid' => $this->token['uid']], ['password' => $password, 'salt' => $salt]);

		$this->DestroyToken();
		// hook zxadmin_admin_password_post_end.php
		$this->response('0401', [], '', 'index/login', 302);
	}

	// hook zxadmin_admin_end.php
}

?>