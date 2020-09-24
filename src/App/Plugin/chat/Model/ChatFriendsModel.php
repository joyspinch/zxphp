<?php

namespace Model;
// hook model_chat_friends_use.php

use App\Model;

class ChatFriendsModel extends Model
{
	// hook model_chat_friends_public_start.php
	public $table = 'zx_chat_friends';
	//public $index = 'id';
	//public $is_delete='is_delete';

	// hook model_chat_friends_public_end.php

	// hook model_chat_friends_start.php

	public function read_uid1_uid2($uid1,$uid2)
	{
		$Friends = $this->read(['uid1'=>$uid1,'uid2'=>$uid2]);
		if(empty($Friends['uid1'])){
			$this->insert(['uid1'=>$uid1,'uid2'=>$uid2]);
			$this->insert(['uid1'=>$uid2,'uid2'=>$uid1]);
			return true;
		}elseif($Friends['status']==2){
			return false;
		}else{
			return true;
		}
	}

	// hook model_chat_friends_end.php
}

?>