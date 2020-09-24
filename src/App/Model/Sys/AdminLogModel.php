<?php

namespace Model;

// hook model_adminlog_use.php
use App\Model;

class AdminLogModel extends Model
{

	// hook model_adminlog_public_start.php
	public $table = 'zx_admin_log';
	public $index = 'id';
	// hook model_adminlog_public_end.php


	// hook model_adminlog_start.php

	public function insert($data)
	{
		// hook model_adminlog_insert_start.php
		return parent::insert($data);
	}

	// hook model_adminlog_end.php
}

?>