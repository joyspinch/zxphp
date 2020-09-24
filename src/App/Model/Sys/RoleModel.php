<?php

namespace Model;
// hook model_role_use.php
use App\Model;

class RoleModel extends Model
{
	// hook model_role_public_start.php
	public $table = 'zx_role';
	public $index = 'role_id';
	public $role_kv = [];
	public $role = [];
	public $is_delete = 'is_delete';

	// hook model_role_public_end.php

	public function __construct($app)
	{
		parent::__construct($app);
		$this->reload_role();
	}

	// hook model_role_start.php

	public function reload_role($reload = 0)
	{
		$cache = $this->CacheGet($this->table);
		if (!$cache || $reload == 1) {
			$cache = $this->select(['is_delete' => 0]);
			$this->CacheSet($this->table, $cache);
		}
		$this->role_kv = arrlist_key_values($cache, 'role_id', 'role_name');
		$this->role = $cache;
		return $cache;
	}

	/**
	 * @param $role_id
	 * @param $status
	 */
	public function update_status_by_role_id($role_id, $status)
	{
		// hook model_role_update_status_by_role_id_start.php
		$this->update(['role_id' => $role_id], ['status' => $status]);
		$this->RoleAuth->update(['role_id' => $role_id], ['status' => $status]);
		// hook model_role_update_status_by_role_id_end.php
	}

	/**
	 * @param $role_id
	 */
	public function delete_by_role_id($role_id)
	{
		// hook model_role_delete_by_role_id_start.php
		$this->delete(['role_id' => $role_id]);
		$this->RoleAuth->delete(['role_id' => $role_id]);
		// hook model_role_delete_by_role_id_end.php
	}
	// hook model_role_end.php
}

?>