<?php

namespace Index;

use Ctrl\Controller;

// hook index_index_use.php

/**
 * Class Index
 *
 * @module Index
 * @name 首页
 * @rank   99
 */
Class Index extends Controller
{

	// hook index_index_start.php

	/**
	 * @title  Index
	 * @auth   false
	 * @login  false
	 * @menu   false
	 * @button false
	 * @rank   99
	 * @return string
	 * 2020/3/12 23:58
	 */
	public function action_Index()
	{
		// hook index_index_index_start.php
		$this->table_check('index', '首页操作加锁100Ms演示');

//		return $this->redirect('http://www.baidu.com');
//		for($i=1;$i<=1000000;$i++){
//			$a = mt_rand(11202597366,11602597366)/100000000;
//			$b = mt_rand(2002597366,2454605355)/100000000;
//			$this->User->CacheGeoAdd($a,$b,$i);
//		}
//		$id=[];
//		$id[] = mt_rand(1,1000000);
//		$id[] = mt_rand(1,1000000);
//		$id[] = mt_rand(10000000,20000000);
//		$id[] = mt_rand(1,1000000);
//		$id[] = mt_rand(1,1000000);
//		$id[] = mt_rand(1,1000000);
//
//		$json = $this->User->CacheGeoPos('geo::user',...$id);
//		$json  =  $this->User->CacheGeoPos(mt_rand(1,1000000));
//		var_dump($json);
//		$ip=$this->request->get_client_ip();
//		$address =  $_ENV['Ip2Region']->memorySearch($ip);
//		$address['region'] = implode(' ',  array_filter(array_unique( explode('|',$address['region']))));
		//$this->MsStore->GetListRpi([],['store_id'=>-1,'sid'=>1]);


		// hook index_index_index_end.php
		$this->table_unlock('index', 100);
		return $this->View(get_defined_vars());
	}


	/**
	 * @title  action_xcx
	 * @auth   false
	 * @login  false
	 * @menu   false
	 * @button false
	 * @rank   99
	 * @return string
	 * 2020/8/28 21:33
	 */
	public function action_xcx()
	{
		return $this->View();
	}


	// hook index_index_end.php
}

?>