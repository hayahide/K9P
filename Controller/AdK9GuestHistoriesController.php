<?php

class AdK9GuestHistoriesController extends AppController {

	var $name = 'K9GuestHistories';
	var $uses = [

		"K9DataReservation",
		"K9DataSchedulePlan",
		"K9DataGuest",
		"K9MasterRoom"
	];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function getHistory(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$guest_hash=$post["guest_hash"];

		try{
			
			$guest=$this->__getGuestInformation($guest_hash);

		}catch(Exception $e){

			Output::__outputNo(array("message"=>$e->getMessage()));
		}

		$histories=$this->__getReservationHistory($guest["guest"]["id"]);

		$res["data"]["guest"]=$guest;
		$res["data"]["history"]=$histories;
		Output::__outputYes($res);
	}

	function __getReservationHistory($guest_id){

		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
	
		$association=$this->K9DataReservation->association;
		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation")));

		$plan_association=$association["hasMany"]["K9DataSchedulePlan"];
		$plan_association["order"]=array("K9DataSchedulePlan.start DESC");
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedulePlan"=>$plan_association)));

		$w=null;
		$w["and"]["K9DataReservation.guest_id"]=$guest_id;
		$data=$this->K9DataReservation->find("all",array(
		
			"conditions"=>$w,
			"recursive"=>3
		));

		$res=array();
		foreach($data as $k=>$v){
		
			$k9_reservation=$v["K9DataReservation"];
			$k9_schedule_plans=$v["K9DataSchedulePlan"];
			$reserve_id=$k9_reservation["id"];
			if(!isset($res[$reserve_id])) $res[$reserve_id]=array();

			$res[$reserve_id]["reservation"]["remarks"] =$k9_reservation["remarks"];
			$res[$reserve_id]["reservation"]["guest_id"]=$k9_reservation["guest_id"];

			$res[$reserve_id]["plan"]=array();
			foreach($k9_schedule_plans as $_k=>$_v){
			
				$res[$reserve_id]["plan"][$_k]["plan"]["id"]=$_v["id"];
				$res[$reserve_id]["plan"][$_k]["plan"]["remarks"]=escapeJsonString($_v["remarks"]);
				$res[$reserve_id]["plan"][$_k]["plan"]["start"]=date("Ymd",strtotime($_v["start"]));
				$res[$reserve_id]["plan"][$_k]["room"]["id"]=$_v["K9MasterRoom"]["id"];
				$res[$reserve_id]["plan"][$_k]["room"]["room_num"]=$_v["K9MasterRoom"]["room_num"];
				$res[$reserve_id]["plan"][$_k]["room"]["room_type_id"]=$_v["K9MasterRoom"]["room_type_id"];
				$res[$reserve_id]["plan"][$_k]["room"]["room_floor"]=$_v["K9MasterRoom"]["floor"];
				$res[$reserve_id]["plan"][$_k]["room"]["room_type"]=$_v["K9MasterRoom"]["K9MasterRoomType"][$roomtype_name];
			}
		}

		return $res;
	}

	function __getGuestInformation($guest_hash){
	
		$data=$this->K9DataGuest->getGuestInformationWithHash($guest_hash);
		if(empty($data)) throw new Exception(__("正常に処理が終了しませんでした"));

		$modified=date("YmdHis",strtotime($data["K9DataGuest"]["modified"]));
		$res["guest"]["first_name"] =$data["K9DataGuest"]["first_name"];
		$res["guest"]["middle_name"]=$data["K9DataGuest"]["middle_name"];
		$res["guest"]["last_name"]  =$data["K9DataGuest"]["last_name"];
		$res["guest"]["tel"]        =decData($data["K9DataGuest"]["contact_tel"]);
		$res["guest"]["email"]      =decData($data["K9DataGuest"]["contact_email"]);
		$res["guest"]["id"]         =$data["K9DataGuest"]["id"];
		return $res;
	}

}
