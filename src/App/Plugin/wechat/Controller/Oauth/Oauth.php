<?php

namespace Index;

use Ctrl\Controller;

Class Oauth extends Controller
{
	public $typeid=0;
	public $open=false;
	public $config=[];

//	public function __construct($server, $route, \Request $request, $response)
//	{
//		parent::__construct($server, $route, $request, $response);
//	}

	// hook oauth_index_start.php


	public function check(){
		$url = '../../';
		$this->response('0000','','',$url,302);
	}

	public function GetToken(){


	}

	public function GetInfoByOpenID($openid){
		$open = $this->UserOpen->read(['openid'=>$openid,'typeid'=>$this->typeid]);
		return $open ?$open :[];
	}

	public function BindUser($openid,$unionid,$uid){
		empty($openid) && empty($unionid) && $this->response('0001','','OpenID|UnionID均为空');
		empty($this->typeid) && $this->response('0001','','TypeID为空');
		empty($uid) && $this->response('0001','','UserID为空');
		$cond = ['openid'=>$openid,'typeid'=>$this->typeid];
		$user = $this->UserOpen->read($cond);
		!empty($user['uid']) && $this->response('0001','','已绑定用户');
		!empty($unionid) && $this->UserOpen->update(['unionid'=>$unionid],['uid'=>$uid]);
		return $this->UserOpen->update($cond,['uid'=>$uid]);
	}

	// hook oauth_index_end.php
}

?>