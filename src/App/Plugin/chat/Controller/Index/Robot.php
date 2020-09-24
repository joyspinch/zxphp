<?php

namespace Index;

use Ctrl\Controller;
use Lizhichao\Word\VicWord;

// hook index_robot_use.php

/**
 * Class Index
 *
 * @module Index
 * @name 首页
 * @rank   99
 */
Class Robot extends Controller
{

	// hook index_robot_start.php

	/**
	 * @title  Service
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * @return string
	 * 2020/3/12 23:58
	 */
	public function action_Service()
	{
		// hook index_robot_service_start.php
		$q = $this->request->param('q','');
		$fc = new VicWord('igb');
		$ar = $fc->getAutoWord($q);
		$word = arrlist_values($ar,0);
		$quest = $this->Word->find_like($word,'word_id,question');
		$str='[p]猜您想问：[/p]';
		foreach ($quest as $k => $row){

			$str.='[p style=cursor:pointer;color:#1E9FFF; onclick=robotAutoAnswer(this) data-id='.$row['word_id'].']'.str_pad($k+1,2,'0',STR_PAD_LEFT).'. '.$row['question'].'[/p]';
		}
		$msg='';
		if(isset($quest[0]['word_id'])) {
			$msg =  $str ;
		}
		// hook index_robot_service_end.php
		$this->response($msg?'0000':'0101',[],$msg);
	}

	public function action_autoAnswer(){
		$id = $this->request->param('id',0);
		empty($id) && $this->response('0100',[],'缺少必要参数');
		$ask = $this->Word->read(['word_id'=>$id]);
		$msg = isset($ask['answer']) ?'[p style=color:#01AAED]'.$ask['question'].'：[/p][p style=color:#2F4056;]'.htmlspecialchars($ask['answer']).'[/p]':'';
		$this->response($msg?'0000':'0101',[],$msg);
	}
	// hook index_robot_end.php
}

?>