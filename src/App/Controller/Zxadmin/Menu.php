<?php

namespace Zxadmin;

use Ctrl\AdminController;

// hook zxadmin_menu_use.php
Class Menu extends AdminController
{

	// hook zxadmin_menu_start.php
	/**
	 * @title  菜单页面模板
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * 2020/3/8 22:20
	 */
	public function action_Index_PUT()
	{
		// hook zxadmin_menu_index_put_start.php
		return $this->Template();
	}

	/**
	 * @title  获取组菜单
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * 2020/3/8 22:20
	 */
	public function action_Index_GET()
	{
		$module = $this->request->param('module', '');
		$cond = [];
		// hook zxadmin_menu_index_get_start.php
		!empty($module) AND $cond['module'] = $module;

		$data = $this->Menu->select($cond);

		foreach ($data as &$v) {
			$v['_node'] = $v['node'];
			$v['method'] AND $v['_node'] .= '|' . $v['method'];
		}
		$data = arrlist_multisort($data, 'rank', true);
		$data = $data ? arr2table($data, '_node', 'parent_node'):[];
		// hook zxadmin_menu_index_get_end.php
		$this->response('0000', ['data' => $data]);
	}


	/**
	 * @title  Edit_POST
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:56
	 */
	public function action_Edit_POST()
	{
		$node = $this->request->param('node', '');
		$node = explode('|', $node);
		$field = $this->request->param('field', '');
		$value = $this->request->param('value', '');
		$this->Menu->update(['node' => $node[0], 'method' => $node[1]], [$field => $value]);
		$this->response('0000');
	}

	/**
	 * @title  Read_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * 2020/3/8 22:24
	 */
	public function action_Read_GET()
	{
		$node = $this->request->param('node');
		// hook zxadmin_menu_read_get_start.php
		$data = $this->Menu->read(['node' => $node]);
		// hook zxadmin_menu_read_get_end.php
		$this->response('0000', ['data' => $data]);
	}

	/**
	 * @title  User_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:56
	 */
	public function action_User_GET()
	{
		// hook zxadmin_menu_user_get_start.php
		$menu = $this->Menu->select(['module' => 'zxadmin',  'is_menu' => 1]);
		// hook zxadmin_menu_user_get_menu_end.php
		foreach ($menu as $k => $v) {
			$menu[$k]['node']=$v['node'].($v['method']?'_'.$v['method']:'');
		}
		if ($this->token['gid'] != 1) {
			$role = $this->Role->read(['role_id' => $this->token['gid']]);
			$role['status'] != 1 AND $this->response('0401', [], '权限组已禁用');
			$role = $this->RoleAuth->select(['role_id' => $this->token['gid']]);
			$nodes = arrlist_values($role, 'node');
			foreach ($menu as $k => $v) {
				$v['node'] = str_replace('/', '_', $v['node']);
				if (!in_array($v['node'], $nodes, 1)) {
					unset($menu[$k]);
				}
			}
		}

		$menu = arrlist_multisort($menu, 'rank', false);
		$menu = arr2tree($menu, 'node', 'parent_node');

		// hook zxadmin_menu_user_get_end.php
		$this->response('0000', ['data' => $menu]);
	}



	/**
	 * @title  action_Reload_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/8/28 21:34
	 */
	public function action_Reload_GET()
	{

		$nodes = getMethodList();

		$method_arr = ['get', 'post', 'patch', 'put', 'delete', 'options'];
		foreach ($nodes as $key => $node) {
			$_key = explode('/', $key);
			$method = explode('_', $_key[count($_key) - 1]);
			$method = $method[count($method) - 1];
			$method = in_array($method, $method_arr) ? strtoupper($method) : '';
			unset($_key[count($_key) - 1]);
			$key = explode('_', $key);
			if (in_array($key[count($key) - 1], $method_arr)) {
				unset($key[count($key) - 1]);
			}
			$key = strtolower(str_replace('\\','/', implode('_', $key)));

			$_node = $this->Menu->read(['node' => $key, 'method' => $method]);

			$data = [
				'node' => $key,
				'parent_node' => implode('/', $_key),
				'method' => $method,
				'name' => $node['title'],
				'module' => $_key[0],
				'is_login' => $node['login'] == true ? 1 : 0,
				'is_auth' => $node['auth'] == true ? 1 : 0,
			];
			empty($_node['node']) AND $this->Menu->insert($data);
			$data = [
				'is_login' => $node['login'] == true ? 1 : 0,
				'is_auth' => $node['auth'] == true ? 1 : 0,
			];
			!empty($_node['node']) AND $this->Menu->update(['node' => $key, 'method' => $method], $data);
		}
		$this->response('0000');
	}
	// hook zxadmin_menu_end.php

}

?>