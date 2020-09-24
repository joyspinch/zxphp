<?php

namespace Oauth;

use Index\Oauth;

class WechatXcx extends Oauth
{
	public $typeid = 11;

	public static $OK = 0;
	public static $IllegalAesKey = -41001;
	public static $IllegalIv = -41002;
	public static $IllegalBuffer = -41003;
	public static $DecodeBase64Error = -41004;

	public function check()
	{
		$this->UserInfo('', '../../oauth/wechat/loginlink', 1);
		parent::check();
	}

	public function action_BindUser()
	{
		$this->is_ajax = 1;
		$openid = $this->request->param('openid');
		$iv = $this->request->param('iv');
		$encryptedData = $this->request->param('encryptedData');
		$nickname = $this->request->param('nickname');
		$avatar = $this->request->param('avatar');
		$this->CheckEmpty([$openid, $iv, $encryptedData], ['用户未登录', '缺少IV', '缺少encryptedData']);
		$useropen = $this->UserOpen->read(['openid' => $openid, 'typeid' => $this->typeid]);
		empty($useropen['id']) && $this->response('0001', [], '登陆失败');

		if (empty($useropen['uid'])) {
			$data = $this->decryptData($encryptedData, $iv, $useropen['session_key']);
			$user = $this->User->read(['mobile' => $data->phoneNumber]);
			if (empty($user)) {
				$password = mt_rand(10000000, 99999999);
				$salt = mt_rand(100000, 999999);
				$newpwd = md5(md5($password) . $salt);
				$user = [
					'username' => $data->phoneNumber,
					'nickname' => $nickname,
					'mobile' => $data->phoneNumber,
					'password' => $newpwd,
					'salt' => $salt,
					'create_ip' => $this->request->get_client_ip(1),
					'login_at' => time(),
					'login_ip' => $this->request->get_client_ip(1),
				];
				$user['uid'] = $this->User->insert($user);
			}
			parent::BindUser($useropen['openid'], $useropen['unionid'], $user['uid']);
		} else {
			$user = $this->User->read_by_uid($useropen['uid']);
		}
		$update = [];
		$nickname && $update['nickname'] = $nickname;
		if ($avatar) {
			$update['avatar'] = time();
			$filename = $user['uid'] . '_' . getut() . '.png';
			file_put_contents(__UPFDIR__ . $filename, https_get($avatar));
			$file = ['name' => $filename, 'tmp_name' => __UPFDIR__ . $filename];
			\Upload::upload_by_id('avatar', $file, $user['uid']);
			unlink(__UPFDIR__ . $filename);
		}
		$this->User->update(['uid' => $user['uid']], $update);

		$token = $this->User->make_token($this->request, $user);
		$user = $this->User->user_safe_info($user);
		$user['uid'] = xn_encrypt($user['uid']);
		$this->response('0000', ['data' => [
			'user' => $user,
			'token' => $token,
			'openid' => $openid,
		]], '绑定成功');
	}

	public function action_GetToken()
	{
		$this->is_ajax = 1;
		$code = $this->request->param('code');

		$data = [
			'create_at' => time(),
			'typeid' => $this->typeid,
		];
		$url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . _CONF('wxxcx_appid') . "&secret=" . _CONF('wxxcx_appsecret') . "&js_code=" . $code . "&grant_type=authorization_code";
		//获取用户的openid
		$res = https_get($url);
		$oauthInfo = xn_json_decode($res);
		if (isset($oauthInfo['errcode']) && isset($oauthInfo['errmsg'])) {
			$this->response('0003', [], 'code:' . $oauthInfo['errcode'] . ', msg:' . $oauthInfo['errmsg']);
		}
		if ($oauthInfo['unionid']) {
			$userRow = $this->UserOpen->read(['unionid' => $oauthInfo['unionid'], 'typeid' => $this->typeid]);
		} else {
			$userRow = $this->UserOpen->read(['openid' => $oauthInfo['openid'], 'typeid' => $this->typeid]);
		}

		if (!isset($userRow['unionid'])) {
			$data['unionid'] = $oauthInfo['unionid'];
			$oauthInfo['unionid'] && $this->UserOpen->update(['id' => $userRow['id']], ['unionid' => $oauthInfo['unionid']]);
		}

		if ($userRow['uid']) {
			$this->UserOpen->update(['id' => $userRow['id']], ['session_key' => $oauthInfo['session_key']]);
		} else if ($userRow['id']) {
			$this->UserOpen->update(['id' => $userRow['id']], ['session_key' => $oauthInfo['session_key']]);
		} else {
			$data['openid'] = $oauthInfo['openid'];
			$data['session_key'] = $oauthInfo['session_key'];
			$this->UserOpen->insert($data);
		}

		if ($userRow['uid']) {
			$user = $this->User->read_by_uid($userRow['uid']);
			$token = $this->User->make_token($this->request, $user);
			$user = $this->User->user_safe_info($user);
			$user['uid'] = xn_encrypt($user['uid']);
			$this->response('0000', ['data' => [
				'user' => $user,
				'token' => $token,
				'openid' => $oauthInfo['openid'],
			]], '登陆成功');
		} else {
			$this->response('0000', ['data' => [
				'user' => [],
				'token' => '',
				'openid' => $oauthInfo['openid'],
			]], '未登录');
		}
	}


	public function decryptData($encryptedData, $iv, $sessionKey)
	{
		if (strlen($sessionKey) != 24) {
			$this->response(self::$IllegalAesKey);
		}
		$aesKey = base64_decode($sessionKey);
		if (strlen($iv) != 24) {
			$this->response(self::$IllegalIv);
		}
		$aesIV = base64_decode($iv);
		$aesCipher = base64_decode($encryptedData);
		$result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
		$data = json_decode($result);
		if ($data == NULL) {
			$this->response(self::$IllegalBuffer);
		}
		if ($data->watermark->appid != _CONF('wxxcx_appid')) {
			$this->response(self::$IllegalBuffer);
		}
		return $data;
	}

}