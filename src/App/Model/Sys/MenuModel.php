<?php

namespace Model;
// hook model_menu_use.php

use App\Model;

class MenuModel extends Model
{
	// hook model_menu_public_start.php
	public $table = 'zx_menu';
	public $index = 'node';
	//public $is_delete = 'is_delete';

	// hook model_menu_public_end.php

	// hook model_menu_start.php


	// hook model_menu_end.php
}

?>