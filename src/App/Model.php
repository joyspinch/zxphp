<?php

namespace App;
// hook model_use.php

Class Model extends \Server\Libs\Model
{
    public $table;
	// hook model_start.php
	public function __construct($server)
	{
		parent::__construct($server);


	}


	// hook model_end.php

    public function checkUnique($checkFileds=[]){
        if(empty($checkFileds)){
            return null;
        }
        if(!empty($this->table)){
            $result=$this->find_one($checkFileds);
           if(!empty($result)){
               return $result;
           }else{
               return null;
           }
        }
        return null;
    }
}

?>