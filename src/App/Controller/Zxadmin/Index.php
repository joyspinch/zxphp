<?php

namespace Zxadmin;

use Ctrl\Controller;

/**
 * Class Index
 *
 * @module Admin
 * @name Index
 * @rank   99
 */

// hook zxadmin_index_use.php

Class Index extends Controller
{
	public $not_closed=1;
	// hook zxadmin_index_start.php

	/**
	 * @title  Index_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * @return string
	 * 2020/3/12 23:50
	 */
	public function action_Index_GET()
	{
		// hook zxadmin_index_index_get_start.php

		// hook zxadmin_index_index_get_end.php
		return $this->View();
	}

	/**
	 * @title  登陆页面
	 * @auth   false
	 * @login  false
	 * @menu   false
	 * @button false
	 * @rank   99
	 * @return string
	 * 2020/3/12 23:50
	 */
	public function action_Login_GET()
	{
		// hook zxadmin_index_login_get_start.php
		if (!empty($this->token)) {
			$this->response('0000', [], '已登录', '../../index/index', 302);
		}

		// hook zxadmin_index_login_get_end.php
		return $this->View();
	}

	/**
	 * @title  账户登陆
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:50
	 */
	public function action_Login_POST()
	{

		$code = $this->request->cookie(md5('admin_xcode'));
		$code = xn_decrypt(substr($code,34));
		$username = $this->request->param('username');
		$password = $this->request->param('password');
		$vercode = $this->request->param('vercode');
		// hook zxadmin_index_login_post_start.php

		if($vercode != $code || empty($code)){
			$this->response('0003',[],'验证码错误');
		}

		$user = $this->User->read_by_username($username);
		if (empty($user['username']) || md5($password . $user['salt']) !== $user['password']) {
			$this->response('0003',[],'账户或密码错误');
		}

		$token = $this->User->make_token($this->request,$user);
		$this->setcookie(_CONF('session_id'),$token,864000);
		$this->set_cookie(md5('admin_xcode'),'',-10000);
		$user['url'] = '../index/index';
		$user = $this->User->user_safe_info($user);
		$user['token'] = $token;
		// hook zxadmin_index_login_post_start.php
		$this->response('0000', ['data' => $user], '登陆成功');
	}

	/**
	 * @title  退出登录
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:50
	 */
	public function action_Logout()
	{
		// hook zxadmin_index_logout_start.php
		$this->setcookie(_CONF('session_id'),'',-10000);
		// hook zxadmin_index_logout_end.php
		$this->response('0401', [], '', '../index/login', 302);
	}

	// hook zxadmin_index_end.php

}
?>