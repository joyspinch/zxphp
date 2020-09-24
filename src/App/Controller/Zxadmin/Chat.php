<?php

namespace Zxadmin;

use Ctrl\AdminController;

// hook zxadmin_chat_use.php

Class Chat extends AdminController
{
	// hook zxadmin_chat_start.php

	/**
	 * @title  Members
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:53
	 */
	public function action_Members()
	{
		$data = ['data' => [
			'mine' => ["username" => $this->token['username']
				, "id" => $this->token['id']
				, "status" => "online"
				, "sign" => "在深邃的编码世界，做一枚轻盈的纸飞机"
				, "avatar" => "../../common/images/kefu.png"],
		]];
		$group = $this->Role->role_kv;
		$list = $this->User->select(['gid'=>['<'=>10000]]);
		$list = arrlist_group($list,'gid');
		foreach ($list as $gid => $v){
			$friend = ['groupname'=>$group[$gid],'id'=>$gid];
			foreach ($v as $row){
				if($row['uid']==$this->token['uid']){
					continue;
				}
				$friend['list'][]=[
					'username'=>$row['username'],
					'id'=>$row['uid'],
					 "avatar" => "../../common/images/kefu.png"
					, "sign" => "这些都是测试数据，实际使用请严格按照该格式返回"
					, "status" => "hide",
				];
			}
			$data['data']['friend'][]=$friend;
		}
		if($this->token['chat_open']){
			$data['data']['friend'][]=[
				'groupname' => '本次接待',
				"id" => 10000,
				'list' => [],
			];
		}

		$this->response('0000', $data);
	}

	// hook zxadmin_chat_after.php
}

?>