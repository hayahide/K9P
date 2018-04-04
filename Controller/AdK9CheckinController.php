<?php

App::uses('K9TotalCostsController','Controller');
App::uses('K9SettingTaxController','Controller');

class AdK9CheckinController extends AppController {

	var $name = 'K9Checkin';
	var $uses = [ "K9DataReservation","K9DataSchedule","K9DataReststaySchedule","K9MasterRoom"];

	public function beforeFilter() {

		parent::beforeFilter();
		$this->loadModel("K9MasterRoomSituation");
		$this->loadModel("K9DataHistoryPriceCard");
	}

	function checkin(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$reservation_hash=$post["hash"];
		$reserve_id=$post["id"];

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		try{

			$reservation=$this->__getReservation($reservation_hash);

		}catch(Exception $e){
		
			Output::__outputNo(array("message"=>$e->getMessage()));
		}

		//どちらか一方にデータが記録されている
		$staytype=$reservation["K9DataReservation"]["staytype"];
		$schedule_model=$this->__stayTypeModel($staytype);

		$first_schedule=$reservation[$schedule_model->name][0];
		$first_date=$first_schedule["start_month_prefix"].sprintf("%02d",$first_schedule["start_day"]);
		if($first_date>date("Ymd")) Output::__outputNo(array("message"=>__("まだこちらの宿泊は開始していません")));

		if(strtotime($reservation["K9DataReservation"]["checkin_time"])>0) Output::__outputNo(array("message"=>__("既にチェックイン済みです")));
		if($reserve_id!=$reservation["K9DataReservation"]["id"])           Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		if(!$time=$this->K9DataReservation->checkin($reserve_id))          Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		
		//situation update for dirty.
		$room_id=$reservation["K9DataSchedulePlan"][0]["K9MasterRoom"]["id"];
		$this->K9MasterRoom->updateRoomSituation($room_id,K9MasterRoomSituation::$SITUATION_DIRTY);

		$res["data"]=array();
		$res["data"]["time"]=localDatetime($time);
		Output::__outputYes($res);
	}

	function __getReservation($reservation_hash){
	
		if(!$reservation=$this->__getReservationByHash($reservation_hash)) throw new Exception(__("正常に処理が終了しませんでした"));
		return $reservation;
	}

	function __getReservationByHash($hash){

		$association=$this->K9DataReservation->association;

		$schedule=$association["hasMany"]["K9DataSchedule"];
		$schedule["conditions"]=array("K9DataSchedule.del_flg"=>'0');
		$schedule["order"]=array("K9DataSchedule.start_month_prefix ASC","K9DataSchedule.start_day ASC");

		$schedule_rest=$association["hasMany"]["K9DataReststaySchedule"];
		$schedule_rest["conditions"]=array("K9DataReststaySchedule.del_flg"=>'0');
		$schedule_rest["order"]=array("K9DataReststaySchedule.start_month_prefix ASC","K9DataReststaySchedule.start_day ASC");

		//最新の1件
		$scheduleplan=$association["hasMany"]["K9DataSchedulePlan"];
		$scheduleplan["order"]=array("K9DataSchedulePlan.start DESC");
		$scheduleplan["conditions"]=array("K9DataSchedulePlan.del_flg"=>'0');
		$scheduleplan["limit"]=1;

		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedule"=>$schedule,"K9DataReststaySchedule"=>$schedule_rest,"K9DataSchedulePlan"=>$scheduleplan)));
		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));
		$data=$this->K9DataReservation->getReservationByHash($hash,0,array(
		
			"recursive"=>2
		));
		return $data;
	}
}
