<?php exit;

/**
 * @title  action_getChatLog
 * @auth   true
 * @login  true
 * @menu   false
 * @button false
 * @rank   99
 * 2020/9/1 10:14
 */
public function action_getChatLog()
{
	$this->is_ajax=1;
	empty($this->token['uid']) && $this->response('0401',[],'未登录');
	$uid = $this->token['uid'];
	$friends = $this->ChatFriends->select(['uid1'=>$uid]);
	$uid_arr = arrlist_values($friends,'uid2');
	$userlist = $uid_arr ? $this->User->select_fmt(['uid'=>$uid_arr],[],'uid,nickname,avatar'):[];
	$list = $this->ChatLog->select(['to_id' => $uid,'read_flag'=>1], ['from_id' => 1],'*', 1, 1000);
	$list = arrlist_change_key($list,'from_id');
	$log_id = arrlist_values($list,'log_id');
	$loglist = $log_id ? $this->ChatLogInfo->select(['log_id'=>$log_id]):[];
	$loglist= arrlist_key_values($loglist,'log_id','message');
	foreach ($userlist as &$_row){
		$arr = [];
		$_row['rank']=0;
		if(!empty($list[$_row['uid']])){
			$arr=$list[$_row['uid']];
			$arr['id']=$arr['log_id'];
			$arr['type']='get';
			$arr['msg']=$loglist[$arr['log_id']];
			$arr['time']=date('Y-m-d H:i:s',$arr['create_at']);
			$arr['from_id']=xn_encrypt($arr['from_id']);
			$arr['to_id']=xn_encrypt($arr['to_id']);
			$_row['rank']=$arr['create_at'];
		}
		$_row['uid']=xn_encrypt($_row['uid']);
		$_row['chat_log']=$arr;
	}
	$userlist= arrlist_multisort($userlist,'rank',false);
	$this->response('0000', ['data' => $userlist]);
}
