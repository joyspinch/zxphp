<?php

namespace Model;
// hook model_chat_word_use.php

use App\Model;

class ChatWordModel extends Model
{
	// hook model_chat_word_public_start.php
	public $table = 'zx_chat_word';
	public $index = 'word_id';
	//public $is_delete='is_delete';

	// hook model_chat_word_public_end.php

	// hook model_chat_word_start.php

	public function find_like(array $word,$feld='*')
	{
		$sql = 'SELECT '.$feld.' FROM '.$this->table.' WHERE MATCH (question) AGAINST (\''.implode(' ',$word).'\' IN BOOLEAN MODE);';
		return $this->query($sql);
	}

	// hook model_chat_word_end.php
}

?>