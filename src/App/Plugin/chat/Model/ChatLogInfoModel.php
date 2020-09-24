<?php

namespace Model;
// hook model_chat_log_info_use.php

use App\Model;

class ChatLogInfoModel extends Model
{
	// hook model_chat_log_info_public_start.php
	public $table = 'zx_chat_log_info';
	public $index = 'log_id';
	//public $is_delete='is_delete';

	// hook model_chat_log_info_public_end.php

	// hook model_chat_log_info_start.php


	// hook model_chat_log_info_end.php
}

?>