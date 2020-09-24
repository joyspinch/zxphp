<?php
namespace Oauth;

use Index\Oauth;

class Wechat extends Oauth
{
	public $typeid=11;

	public function check()
	{
		$this->UserInfo('','../../oauth/wechat/loginlink',1);
		parent::check();
	}

	public function action_checkSignature()
	{
		$signature = $this->request->param('signature');
		$timestamp = $this->request->param("timestamp");
		$echostr = $this->request->param("echostr");
		$nonce = $this->request->param("nonce");
		$token = _CONF('wxgzh_token');
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $tmpStr == $signature ){
			return $echostr;
		}else{
			return false;
		}
	}

	public function action_LoginLink ($type = 1)
	{
		if ( empty($type) ) {
			//授权登录 可获取头像 用户名等
			$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' ._CONF('wxgzh_appid'). '&redirect_uri=' . urlencode( $this->request->_S('HTTP_HOST') . '/oauth/wechat/gettoken') . '&response_type=code&scope=snsapi_userinfo&state=snsapi_userinfo#wechat_redirect';
		} else {
			//不授权只取 openid
			$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' ._CONF('wxgzh_appid'). '&redirect_uri=' . urlencode($this->request->_S('HTTP_HOST') . '/oauth/wechat/gettoken') . '&response_type=code&scope=snsapi_base&state=snsapi_base#wechat_redirect';
		}
		$this->response('0000',[],'Oauth',$url,302);
	}

	public function GetToken()
	{
		$code=$this->request->param('code');
		$state=$this->request->param('state');
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . _CONF('wxgzh_appid') . '&secret=' . _CONF('wxgzh_appsecret') . '&code=' . $code . '&grant_type=authorization_code';
		$data = https_get($url);
		$data = xn_json_decode($data);
		$openid = '';
		if ( !empty( $data['openid'] ) ) {
			$openid = $data['openid'];
			$xn_openid = xn_encrypt($openid);
			$this->setcookie('openid',$xn_openid,8640000);
			$user = $this->GetInfoByOpenID($openid );
			(!empty($user['id']) && empty($user['unionid']) && !empty($data['unionid'])) && $this->UserOpen->update(['id'=>$user['id']],['unionid'=>$data['unionid']]);
			if ( !empty($user['uid']) ) {

				$this->response('0000',[],'登录成功', $this->request->server['HTTP_HOST'],302);
			}elseif ( !empty($user['openid']) ) {
				$this->response('0000',[],'登录成功','../../oauth/oauth/binding',302);
			} else {
				$state=='snsapi_base' AND $this->action_LoginLink(0);
				$url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $data['access_token'] . '&openid=' . $openid . '&lang=zh_CN';
				$data = https_get($url);
				$data = xn_json_decode($data);
				$this->UserOpen->insert(['openid'=>$openid,'unionid'=>$data['unionid'],'nickname'=>$data['nickname'],'typeid'=>$this->typeid,'create_at'=>time(),'uid'=>0]);
				if ( !empty( $user['uid'] ) ) {
					$this->response('0000',[],'登录成功', $this->request->server['HTTP_HOST'],302);
				}else{
					$this->response('0001', [],'登录失败');
				}
			}
		} else {
			$this->response('0001', [],'参数失效,请重试');
		}
		parent::GetToken();
	}


}