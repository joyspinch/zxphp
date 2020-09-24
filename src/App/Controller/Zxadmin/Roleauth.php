<?php

namespace Zxadmin;

use Ctrl\AdminController;

/**
 * Class Role
 *
 * @module Admin
 * @name Role
 * @rank   99
 */

// hook zxadmin_role_auth_use.php

Class Roleauth extends AdminController
{
	// hook zxadmin_role_auth_start.php

	/**
	 * @title  Index_GET
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:54
	 */
	public function action_Index_GET()
	{
		$role_id = $this->request->param('role_id', 0);
		// hook zxadmin_role_auth_index_get_start.php
		$role = $this->RoleAuth->select(['role_id' => $role_id]);
		$nodes = arrlist_values($role, 'node');
		$menu = $this->Menu->select([], [], 'node as id,method,module,parent_node as parent,name as title,rank');
		foreach ($menu as $k => $v) {
			$menu[$k]['id'] = str_replace('/', '_', $menu[$k]['id']);
			$menu[$k]['parent'] = str_replace('/', '_', $menu[$k]['parent']);
			$menu[$k]['id'] .= ($v['method'] ? '_' . $v['method'] : '');
			in_array($menu[$k]['id'], $nodes, 1) AND $menu[$k]['checked'] = true;
			$menu[$k]['parent'] .= (!empty($menu[$k]['parent']) ? '|' : '') . $v['module'];
			$menu[$k]['id'] .= '|' . $v['module'];
			$menu[$k]['spread']=true;
			$menu[$k]['field']='node';
			empty($menu[$k]['title']) && $menu[$k]['title']=$menu[$k]['id'];
		}

		$menu = arrlist_multisort($menu, 'rank', false);
		$menu = arrlist_tree($menu, 'id', 'parent', 'children',false);
		// hook zxadmin_role_auth_index_get_end.php
		$this->response('0000', ['data' => $menu]);
	}


	/**
	 * @title  index_POST
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:54
	 */
	public function action_index_POST()
	{
		$role_id = $this->request->param('role_id', 0);
		// hook zxadmin_role_auth_index_post_start.php
		$this->CheckEmpty([$role_id], ['权限组']);
		$menus = $this->request->param('node', []);
		$this->RoleAuth->delete(['role_id' => $role_id]);
		$data = $this->children($menus,$role_id);

		count($data) > 0 AND $this->RoleAuth->insertALL($data);
		// hook zxadmin_role_auth_index_post_end.php
		$this->response('0000', ['data' => $menus]);
	}

	public function children($menus,$role_id){
		foreach ($menus as $menu) {
			$_menu = explode("|", $menu['id']);
			$data[] = [
				'role_id' => $role_id, 'node' => str_replace('/', '_', $_menu[0]), 'module' => $_menu[1],
			];
			$menu['children'] && $data=array_merge($data,$this->children($menu['children'],$role_id));
		}
		return $data;
	}
	// hook zxadmin_role_auth_end.php
}

?>