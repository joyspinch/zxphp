<?php

namespace Zxadmin;

use Ctrl\AdminController;

/**
 * Class Config
 *
 * @module Admin
 * @name Config
 * @rank   99
 */

// hook zxadmin_config_use.php

Class Config extends AdminController
{
	// hook zxadmin_config_start.php

	/**
	 * @title  系统设置
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:51
	 */
	public function action_Index_PUT()
	{
		// hook zxadmin_config_index_put_start.php
		$conf = $this->Config->select();
		foreach ($conf as $k => $v) {
			if ($v['is_json'] == 1) {
				$conf[$k]['value'] = xn_json_decode($conf[$k]['value']);
			}
		}
		$conf = arrlist_multisort($conf, 'rank', false);
		$conf = arrlist_group($conf, 'tabs');
		// hook zxadmin_config_index_put_end.php
		return $this->Template('', ['_data' => $conf]);
	}

	/**
	 * @title  系统设置保存
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button 1
	 * @rank   99
	 * 2020/3/12 23:51
	 */
	public function action_Index_POST()
	{
		$conf = $this->request->param();
		// hook zxadmin_config_index_post_start.php
		$config = $this->Config->select([]);
        $config=arrlist_key_values($config,"name");
		foreach ($config as $k => $v) {
			if (isset($conf[$k])) {
				if ($v['is_json'] == 1) {
					$conf[$k] = xn_json_encode($conf[$k]);
				}
				$config[$k] = $conf[$k];
				$this->Config->SetValue($k, $conf[$k]);
			}
		}
		// hook zxadmin_config_index_post_end.php
		$this->response('0000', ['data' => $config]);
	}

	// hook zxadmin_config_end.php
}

?>