<?php

class AdScheduleGetLastEditReservationUser{

	protected static $uniqueInstance;
	public $controller;
	public $useModels=array("K9MasterEmployee");
	public $findValues=array();
	public $models;

	function __construct(Controller &$controller){

		$this->models=new SetModel($this,$controller);
		$this->controller=$controller;
	}

	public static function getInstance(Controller &$controller){

        if(!isset(static::$uniqueInstance[$controller->name])) static::$uniqueInstance[$controller->name]=new ScheduleGetLastEditReservationUser($controller);
        return static::$uniqueInstance[$controller->name];
    }

	public function setReservationInformations($values=array()){

		$this->findValues=$values;
	}

	private function getEditUserIDs(){
	
		if(empty($this->findValues)) return array();
		$edit_user_ids=array_unique(array_values(Set::extract($this->findValues,"{}.edit_user_id")));
		return $edit_user_ids;
	}

	public function getEditUsers(){

		if(empty($this->findValues)) return array();
		$edit_user_ids=$this->getEditUserIDs();
		$edit_users=$this->models->getSettedModels()["K9MasterEmployee"]->findAllByIdAndDelFlg($edit_user_ids,0);
		return $edit_users;
	}

	public function getEditUsersInformations(){

		$edit_users=$this->getEditUsers();
		if(empty($edit_users)) return array();

		$edit_user_names=Set::combine($edit_users,"{n}.K9MasterEmployee.id","{n}.K9MasterEmployee.first_name");

		$edit_informations=array();
		foreach($this->findValues as $site_id=>$v){

			if(!isset($edit_informations[$site_id])) $edit_informations[$site_id]=array();
			$edit_informations[$site_id]["first_name"]=isset($edit_user_names[$v["edit_user_id"]])?$edit_user_names[$v["edit_user_id"]]:"";
			$edit_informations[$site_id]["last_modified_ms"]=strtotime($v["modified"]) * 1000;
		}

		return $edit_informations;
	}
}

?>
