<?php

class AdScheduleLog{

	protected static $uniqueInstance;
	public $controller;
	public $useModels=array("K9DataScheduleLog");
	public $findValue=array();
	public $models;

	public static function getInstance(Controller &$controller){

        if(!isset(static::$uniqueInstance[$controller->name])) static::$uniqueInstance[$controller->name]=new ScheduleLog($controller);
        return static::$uniqueInstance[$controller->name];
    }

	public function clear(){
	
		$this->findValue=null;
	}

	function __construct(Controller &$controller){

		$this->models=new SetModel($this,$controller);
		$this->controller=$controller;
	}

	public function updateEditCurrentTime(){

		$id=$this->__getLogID();
		return $this->models->getSettedModels()["K9DataScheduleLog"]->updateEditCurrentTime($id);
	}

	public function getLastEditTimeExpireMs(){

		if(!empty($this->findValue)) return $this->findValue["K9DataScheduleLog"]["edit_time_expired_ms"];
		$this->findValue=$this->models->getSettedModels()["K9DataScheduleLog"]->findOne();
		if(!$this->findValue) return false;
		$edit_time_expired_ms=$this->findValue["K9DataScheduleLog"]["edit_time_expired_ms"];
		return $edit_time_expired_ms;
	}

	public function getLastEditUser(){

		if(!empty($this->findValue)) return $this->findValue["K9DataScheduleLog"]["edit_user_id"];
		$this->findValue=$this->models->getSettedModels()["K9DataScheduleLog"]->findOne();
		if(!$this->findValue) return false;
		$edit_user_id=$this->findValue["K9DataScheduleLog"]["edit_user_id"];
		return $edit_user_id;
	}

	public function getLastStartUser(){

		if(!empty($this->findValue)) return $this->findValue["K9DataScheduleLog"]["start_user_id"];
		$this->findValue=$this->models->getSettedModels()["K9DataScheduleLog"]->findOne();
		if(!$this->findValue) return false;
		$start_user_id=$this->findValue["K9DataScheduleLog"]["start_user_id"];
		return $start_user_id;
	}

	public function getBinKey(){

		if(!empty($this->findValue)) return $this->findValue["K9DataScheduleLog"]["bin_key"];
		$this->findValue=$this->models->getSettedModels()["K9DataScheduleLog"]->findOne();
		if(!$this->findValue) return false;
		$bin_key=$this->findValue["K9DataScheduleLog"]["bin_key"];
		return $bin_key;
	}

	public function getLastEditTime(){

		if(!empty($this->findValue)){ 

			$last_edit_time=strtotime($this->findValue["K9DataScheduleLog"]["edit_time"]);
			return (max($last_edit_time,0)*1000);
		}

		$this->findValue=$this->models->getSettedModels()["K9DataScheduleLog"]->findOne();
		if(!$this->findValue) return 0;
		$edit_time=max(strtotime($this->findValue["K9DataScheduleLog"]["edit_time"])*1000,0);
		return $edit_time;
	}

	private function __getLogID(){

		$id=empty($this->findValue)?1:$this->findValue["K9DataScheduleLog"]["id"];
		return $id;
	}

	public function timeInitialize($user_id,$last_edit_time=""){

		$id=$this->__getLogID();

		$edit_time="";
		if(!empty($last_edit_time)) $edit_time=date("Y/m/d H:i:s",$last_edit_time);
		return $this->models->getSettedModels()["K9DataScheduleLog"]->timeInitialize(array(
		
			"id"       =>$id,
			"user_id"  =>$user_id,
			"edit_time"=>$edit_time
		));
	}

	public function editTime($user_id,$current_time_ms,$time_key=""){

		if(empty($time_key)) $time_key=time();
		$bin_key=TimeoutInvestigationKeys::makeBinKey($user_id,$time_key);
		if(!$save=$this->models->getSettedModels()["K9DataScheduleLog"]->editTime(array(
		
			"bin_key"             =>$bin_key,
			"user_id"             =>$user_id,
			"edit_time_expired_ms"=>$current_time_ms, //今の時間
			"id"                  =>1
		))){

			return false;
		};

		return $save;
	}
}

?>
