<?php

namespace Ctrl;

// hook event_use.php

class Event extends \Server\Libs\Event
{

	// hook event_public_start.php

	// hook event_public_end.php
	public function sendMessage($data, $frame)
	{
		$uid = $this->getUidByClientId($frame->fd);
		$data['to_id']=xn_decrypt($data['to_id']);
		if (empty($uid)) {
			$arr = ['resp_code' => 400, 'cmd' => 'Error', 'msg' => '授权登陆后才能聊天'];
			$this->sendToClient($frame->fd, xn_json_encode($arr));
		} else if (empty($data['to_id'])) {
			$arr = ['resp_code' => 400, 'cmd' => 'Error', 'msg' => '缺少接接收者'];
			$this->sendToClient($frame->fd, xn_json_encode($arr));
		} else {
			if(!$this->ChatFriends->read_uid1_uid2($data['to_id'],$uid)){
				$arr = ['resp_code' => 400, 'cmd' => 'Error', 'msg' => '对方拒绝了您的消息'];
				$this->sendToClient($frame->fd, xn_json_encode($arr));
				return;
			}

			$logid = $this->ChatLog->insert(['from_id'=>$uid,'to_id'=>$data['to_id']]);

			if($logid){
				$this->ChatLogInfo->insert(['log_id'=>$logid,'message'=>$data['content']]);
				$arr = ['resp_code' => "0000", 'cmd' => 'chatMessage', 'data' => [
					'from_id' => xn_encrypt($uid),
					'content' => htmlspecialchars($data['content']),
					'timestamp' => time(),
					'msgid' => $logid,
				]];
				$this->sendToUid($data['to_id'], xn_json_encode($arr));
				$arr = ['resp_code' => "0000", 'cmd' => 'SendOK', 'data' => ['msgid' => $logid]];
				$this->sendToUid($uid, xn_json_encode($arr));
			}else{
				$arr = ['resp_code' => "0000", 'cmd' => 'SendError', 'data' => ['msgid' => $logid]];
				$this->sendToUid($uid, xn_json_encode($arr));
			}

		}
	}

	/**
	 * @title  初始化成功
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 *
	 * @param $data
	 * @param $frame
	 * 2020/7/7 09:47
	 */
	public function userInit($data, $frame)
	{
		$fuid = $this->getUidByClientId($frame->fd);
		$fuid && $fuid != $data['customer_id'] && $this->unbindUid($frame->fd, $fuid);
		$this->bindUid($frame->fd, $data['uid']);
		$uid = $data['uid'];
		$arr = ['resp_code' => 400, 'cmd' => 'userInit', 'msg' => '请重新尝试分配客服'];
		$this->sendToUid($uid, xn_json_encode($arr));

		$arr = ['resp_code' => "0000", 'cmd' => 'hello', 'data' => [
			'avatar' => "/common/images/kefu.png", 'content' => "您好！欢迎使用在线客服", 'time' => date('Y-m-d H:i:s'),
		]];
		$this->sendToUid($uid, xn_json_encode($arr));

		$quest = $this->Question->select_all();
		$str = '[p]猜您想问：[/p]';
		foreach ($quest as $k => $row) {
			$str .= '[p style=cursor:pointer;color:#1E9FFF; onclick=autoAnswer(this) data-id=' . $row['question_id'] . ']' . str_pad($k + 1, 2, '0', STR_PAD_LEFT) . '. ' . $row['question'] . '[/p]';
		}
		if (isset($quest[0]['question_id'])) {
			$arr = ['resp_code' => "0000", 'cmd' => 'comQuestion', 'data' => [
				'avatar' => "/common/images/robot.jpg", 'content' => $str, 'time' => date('Y-m-d H:i:s'),
			]];
			$this->sendToUid($uid, xn_json_encode($arr));
		}

	}

	/**
	 * @title  访客进入
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 *
	 * @param $data
	 * @param $frame
	 * 2020/7/7 09:47
	 */
	public function customerIn($data, $frame)
	{
		$fuid = $this->getUidByClientId($frame->fd);
		$fuid && $fuid != $data['customer_id'] && $this->unbindUid($frame->fd, $fuid);
		$this->bindUid($frame->fd, $data['customer_id']);
		$uid = $data['customer_id'];
		$arr = ['resp_code' => "0000", 'cmd' => 'customerIn', 'msg' => 'login success'];
		$this->sendToUid($uid, xn_json_encode($arr));
	}

	/**
	 * @title  聊天消息
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 *
	 * @param $data
	 * @param $frame
	 * 2020/7/7 09:48
	 */
	public function chatMessage($data, $frame)
	{
		$uid = $this->getUidByClientId($frame->fd);
		//发送消息之后
		$arr = ['resp_code' => "0000", 'cmd' => 'afterSend', 'data' => time(), 'msg' => htmlspecialchars($data['content'])];
		$this->sendToUid($uid, xn_json_encode($arr));
		$user = $this->User->read_by_uid($uid);
		$arr = ['resp_code' => "0000", 'cmd' => 'chatMessage', 'data' => [
			'username' => $user['username'],
			'avatar' => '/common/images/customer.png',
			'id' => $uid,
			'fromid' => $uid,
			'type' => 'friend',
			'content' => htmlspecialchars($data['content']),
			'timestamp' => time(),
			'mine' => false,
			'cid' => time(),
		]];
		$this->sendToUid($data['to_id'], xn_json_encode($arr));
	}

	public function readMessage($data, $frame)
	{

	}

	/**
	 * @title  常见问题
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 *
	 * @param $data
	 * @param $frame
	 * 2020/7/7 09:48
	 */
	public function comQuestion($data, $frame)
	{
		$question_id = $data['question_id'];
		$uid = $this->getUidByClientId($frame->fd);
		$question = arrlist_key_values($this->Question->select_all(), 'question_id', 'answer');
		if (isset($question[$question_id])) {
			$arr = ['resp_code' => "0000", 'cmd' => 'comQuestion', 'data' => [
				'avatar' => '/common/images/robot.jpg',
				'time' => date("Y-m-d H:i:s"),
				'content' => htmlspecialchars($question[$question_id]),
				'mine' => false,
				'read_flag' => 2,
			]];
			$this->sendToUid($uid, xn_json_encode($arr));
		} else {
			$arr = ['resp_code' => "0000", 'cmd' => 'answerComQuestion', 'data' => [
				'avatar' => '/common/images/robot.jpg',
				'time' => date("Y-m-d H:i:s"),
				'content' => 'Good Job',
				'mine' => false,
				'read_flag' => 2,
			]];
			$this->sendToUid($uid, xn_json_encode($arr));
		}
	}


	/**
	 * @title  客服消息
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 *
	 * @param $data
	 * @param $frame
	 * 2020/7/7 09:46
	 */
	public function customerMessage($data, $frame)
	{
		$arr = ['resp_code' => "0000", 'cmd' => 'chatMessage', 'data' => [
			'name' => $data['mine']['name'], 'id' => $data['mine']['id'], 'chat_log_id' => time(),
			'avatar' => "/common/images/kefu.png", 'content' => htmlspecialchars($data['mine']['content']), 'time' => date('Y-m-d H:i:s'),
		]];
		$ret = $this->sendToUid($data['to']['id'], xn_json_encode($arr));
		if ($ret) {
			$uid = $this->getUidByClientId($frame->fd);
			$arr = ['resp_code' => "0000", 'cmd' => 'sendok', 'id' => time()];
			$this->sendToUid($uid, xn_json_encode($arr));
		} else {
			$uid = $this->getUidByClientId($frame->fd);
			$arr = ['resp_code' => "400", 'cmd' => 'sendok', 'id' => time()];
			$this->sendToUid($uid, xn_json_encode($arr));
		}
	}
	// hook event_end.php
}