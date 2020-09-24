<?php

namespace Model;
// hook model_chat_log_use.php

use App\Model;

class ChatLogModel extends Model
{
	// hook model_chat_log_public_start.php
	public $table = 'zx_chat_log';
	public $index = 'log_id';
	//public $is_delete='is_delete';

	// hook model_chat_log_public_end.php

	// hook model_chat_log_start.php


	// hook model_chat_log_end.php
}

?>