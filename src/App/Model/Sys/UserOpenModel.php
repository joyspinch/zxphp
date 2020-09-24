<?php

namespace Model;
// hook model_ms_user_use.php

use App\Model;

class UserOpenModel extends Model
{
	// hook model_ms_user_public_start.php
	public $table = 'zx_user_open';
	public $index = 'id';

	//public $is_delete='is_delete';
	public function __construct($server)
	{
		parent::__construct($server);
		//$this->User->add_with($this->table,'uid','uid');
	}

	// hook model_ms_user_public_end.php

	// hook model_ms_user_start.php

	// hook model_ms_user_end.php
}

?>