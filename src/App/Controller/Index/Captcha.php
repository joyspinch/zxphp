<?php

namespace Index;

use Ctrl\Controller;

/**
 * Class Captcha
 *
 * @module Index
 * @name Captcha
 * @rank   99
 */

// hook index_captcha_use.php

Class Captcha extends Controller
{
	// hook index_captcha_start.php

	/**
	 * @title  Index_GET
	 * @auth   false
	 * @login  false
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:58
	 */
	public function action_Index_GET()
	{

		$name = $this->request->param('name');
		$num = max(4,$this->request->param('num',4));
		// hook index_captcha_index_get_start.php
		$_vc = new \ValidateCode($this->request,$this->_response);  //实例化一个对象
		$img = $_vc->doimg($num);
		$_vc->getCode($name);
		// hook index_captcha_index_get_end.php
		$this->response('0000', ['data' => ['type' => 'image/png', 'data' => $img]], '', '', 2001, 1);
	}

	// hook index_captcha_end.php

}
?>