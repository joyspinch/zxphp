<?php

namespace Zxadmin;

use Ctrl\Controller;

/**
 * Class Plugin
 *
 * @module Admin
 * @name Plugin
 * @rank   99
 */
// hook zxadmin_plugin_use.php

Class Plugin extends Controller
{
	// hook zxadmin_plugin_start.php

	/**
	 * @title  Index_put
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:54
	 */
	public function action_Index_put()
	{
		return $this->Template();
	}

	/**
	 * @title  Index_get
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:54
	 */
	public function action_Index_get()
	{

		plugin_init();
		$name = $this->request->param('name', '');
		$install = $this->request->param('install', 0);
		$disable = $this->request->param('disable', 0);
		$_plugins = $_ENV['plugins'];
		$install -= 1;
		$disable -= 1;
		$plugins = [];
		$i = 0;
		foreach ($_plugins as $plugin) {
			$i++;
			if ($name) {
				if (stripos($plugin['name'], $name) !== FALSE || stripos($plugin['brief'], $name) !== FALSE) {
					$plugins[$i] = $plugin;
				}
			} else {
				$plugins[$i] = $plugin;
			}
			if ($install >= 0) {
				if ($plugin['installed'] != $install) {
					unset($plugins[$i]);
				}
			}
			if ($disable >= 0) {
				if ($plugin['enable'] != $disable) {
					unset($plugins[$i]);
				}
			}
		}
		$this->response('0000', ['data' => array_values($plugins)]);
	}

	/**
	 * @title  Install
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:54
	 */
	public function action_Install()
	{
		$_plugins = $_ENV['plugins'];
		$dir = $this->request->param('dir');
		//$name = $_plugins[$dir]['name'];
		// 插件依赖检查 / check plugin dependency
		plugin_check_dependency($dir, 'install');
		// 安装插件 / install plugin
		plugin_install($dir);

		$this->Menu->update(['plugin' => $dir], ['is_delete' => 0]);
		$installfile = __PLUDIR__ . $dir . "/install.php";
		if (is_file($installfile)) {
			include _include($installfile);
		}

		$this->response('0000', ['data' => ['install' => 1]]);
	}

	/**
	 * @title  Disable
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Disable()
	{
		$dir = $this->request->param('dir');
		plugin_disable($dir);
		$this->Menu->update(['plugin' => $dir], ['is_delete' => 2]);
		$this->Config->update(['plugin' => $dir], ['is_delete' => 2]);
		Xremove(__PLUDIR__ . $dir . '/Static/', __WEBDIR__ . $dir . '/');
		$this->response('0000', ['data' => ['enable' => 0]]);
	}

	/**
	 * @title  Enable
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Enable()
	{
		$dir = $this->request->param('dir');
		plugin_enable($dir);
		$this->Menu->update(['plugin' => $dir], ['is_delete' => 0]);
		$this->Config->update(['plugin' => $dir], ['is_delete' => 0]);
		Xcopy(__PLUDIR__ . $dir . '/Static/', __WEBDIR__ . $dir . '/');
		$this->response('0000', ['data' => ['enable' => 1]]);
	}

	/**
	 * @title  Icon
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * @return string
	 * 2020/3/12 23:55
	 */

	public function action_Icon()
	{
		$dir = $this->request->param('dir', '');
		return file_read(__PLUDIR__ . $dir . '/icon.png');
	}

	// hook zxadmin_plugin_end.php
}

?>