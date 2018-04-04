<?php

class AdTimeoutInvestigationExec{

	public $controller;
	public $useModels=array("K9DataScheduleLog");
	public $findValue=array();
	public $models;
	public $session;

	function __construct(Controller &$controller,Component &$session){

		$this->models=new SetModel($this,$controller);
		$this->controller=&$controller;
		$this->session=&$session;
	}

	public function checkLastEditTimeSesKey($unique_key,$user_id) {

		$ses_key =TimeoutInvestigationKeys::makeTimeSesKey($unique_key);
		$time_key=$this->session->read($ses_key);
		if(empty($time_key)) return false;
		$res=$this->checkLastEditTime($user_id,$time_key);
		return $res;
	}

	static function checkSessionKey($user_id,$time_key,$bin_key){

		if(empty($time_key)) return false;

		if(!$dec=TimeoutInvestigationKeys::decBinKey($bin_key,$time_key)) return false;
		if($dec["user_id"]!=$user_id)  return false;
		if($dec["time_key"]!=$time_key) return false;
		return true;
	}

	public function checkLastEditTime($user_id,$time_key){

		if(!$log=$this->models->getSettedModels()["K9DataScheduleLog"]->getData()) return false;
		if($log["K9DataScheduleLog"]["start_user_id"]!=$user_id) return false;

		$bin_key=TimeoutInvestigationKeys::makeBinKey($user_id,$time_key);
		if(!self::checkSessionKey($user_id,$time_key,$bin_key)) return false;
		return true;	
	}

	private function __getValue(){

		if(!empty($this->findValue)) return $this->findValue;
		$this->findValue=$this->models->getSettedModels()["K9DataScheduleLog"]->getData();
		return $this->findValue;
	}

	public function checkEffectiveTime(){

		//今の時間がdeadlineを超えているか
		$current_time_ms=TimeoutInvestigation::currentMsTime();
		$last_modified=$this->__getValue();
		$deadline=(Int)$last_modified["K9DataScheduleLog"]["edit_time_expired_ms"]+TimeoutInvestigation::effectiveTime();
		$res=($current_time_ms>$deadline);
		return $res;
	}
}

?>
