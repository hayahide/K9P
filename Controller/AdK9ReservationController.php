<?php

require_once "Schedule".DS."ScheduleLog.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigation.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationKeys.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationExec.php";

App::uses('K9ScheduleBaseController','Controller');
App::uses('K9BasePricesController','Controller');
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
class AdK9ReservationController extends K9ScheduleBaseController{

	var $name = "K9Reservation";
	var $uses = [

		"K9MasterCard",
		"K9DataGuestEmail",
		"K9DataSchedule",
		"K9DataCheckoutPayment",
		"K9DataReststaySchedule",
		"K9MasterRoomType",
		"K9DataSchedulePlan",
		"K9DataGuest",
		"K9MasterReservationSalesource",
	];

	function beforeFilter(){
	
		$this->loadModel("K9MasterCard");
		$this->loadModel("K9MasterRoom");
		$this->loadModel("K9DataReservation");
		$this->loadModel("K9DataCompany");
		parent::beforeFilter();

		if(!SETTING_EDIT) $this->redirect("/K9Site/");
	}

	function __setCheckInOutStatus($reservation_info=array())
	{
		$is_checkin=false;
		$is_checkout=false;
		if(empty($reservation_info)){
		
			$this->set(compact("is_checkin","is_checkout"));
			return;
		}

		$is_checkin=strtotime($reservation_info["checkin_time"])>-1;
		$is_checkout=strtotime($reservation_info["checkout_time"])>-1;
		$this->set(compact("is_checkin","is_checkout"));
	}

	function __setGuestInfo($info){

		$modified=date("YmdHis",strtotime($info["modified"]));
		$parson_title=$info["title"];
		$parson_passport=decData($info["passport"]);
		if(empty($passport))$passport="";

		$parson_incidential=$info["incidential"];
		$parson_language=$info["language_num"];
		$parson_firstname=$info["first_name"];
		$parson_lastname=$info["last_name"];
		$contact_address=$info["contact_address"];
		$contact_state=$info["contact_state"];
		$contact_city=$info["contact_city"];
		$contact_zip=$info["contact_zip_code"];
		$contact_country=$info["contact_country"];
		$contact_nationality=$info["contact_nationality"];

		$contact_tel=decData($info["contact_tel"]);
		if(empty($contact_tel))$contact_tel="";
		$contact_email=decData($info["contact_email"]);
		if(empty($contact_email)) $contact_email="";

		$remark_guest_note=$info["remarks"];
		$guest_hash=$info["hash"];

		$this->set(compact(
		
			"guest_hash",
			"parson_title",
			"parson_passport",
			"parson_incidential",
			"parson_language",
			"parson_firstname",
			"parson_lastname",
			"contact_address",
			"contact_state",
			"contact_city",
			"contact_zip",
			"contact_country",
			"contact_nationality",
			"contact_tel",
			"contact_email",
			"remark_guest_note"
		));
	}

	public function __setPaymenttypes($paymenttype)
	{
		$this->set(compact("paymenttype"));
	}

	public function __setPurchase($purchase_flg)
	{
		$this->set(compact("purchase_flg"));
	}

	public function __setStaytype($staytype)
	{
		
		$this->set(compact("staytype"));
	}

	function __setReservationInfo($info){

		$administration_salesource=$info["salesource_id"];
		$rate_adults=$info["adults_num"];
		$rate_child=$info["child_num"];
		$remark_reserve_note=$info["remarks"];
		$bookin_id=$info["booking_id"];

		$rate_weekday=$info["weekday_price"];
		$rate_weekend=$info["weekend_price"];
		$is_rate_overwrite=($rate_weekday>0 || $rate_weekend>0);
		$agency_id=$info["company_id"];

		$this->set(compact(

			"administration_salesource",
			"agency_id",
			"bookin_id",
			"is_rate_overwrite",
			"rate_adults",
			"rate_child",
			"rate_weekend",
			"rate_weekday",
			"remark_reserve_note"
		));
	}

	function __setDateRange($info,$date){

		if(empty($info)){

			$today=date("Ymd");

			$arrival_stime=max(strtotime($today),strtotime($date));
			$arrival_ymd  =localDateNormalUtime($arrival_stime);
			$departure_ymd=localDateNormalUtime(strtotime("+1 day",$arrival_stime));

			$this->set(compact(
			
				"arrival_ymd",
				"departure_ymd"
			));
			return;
		}

		$arrival=$info[0];
		$departure=$info[count($info)-1];

		// add 1 day is important.
		$arrival_ymd=localDate($arrival["start_month_prefix"].sprintf("%02d",$arrival["start_day"]));
		$departure_stime=strtotime("+1 day",strtotime($departure["start_month_prefix"].sprintf("%02d",$departure["start_day"])));
		$departure_ymd=localDateNormalUtime($departure_stime);
		$nights=count(makeDatePeriod($arrival_ymd,$departure_ymd))-1;

		$this->set(compact(
		
			"nights",
			"arrival_ymd",
			"departure_ymd"
		));
	}

	function __setRoomInfo($info)
	{
		
		$last_plan=$info[count($info)-1];
		$room_id=$last_plan["room_id"];
		$this->set(compact("room_id"));
	}

	function __judgeIsReststayByRoomId($room_id)
	{
		return is_numeric(strpos($room_id,"_"));
	}

	function __getStaytypeParamByRoomId($room_id)
	{
		return $this->__judgeIsReststayByRoomId($room_id)?$this->K9DataReststaySchedule->stayType:$this->K9DataSchedule->stayType;
	}

	function index($selected_room_id,$date,$reservation_hash=""){

		if(!empty($reservation_hash)){

			if(!$current=$this->__getReservationByHash($reservation_hash)){
			
				$this->redirect("/K9Site/");
				return;
			}

			$staytype=$current["K9DataReservation"]["staytype"];
			$schedule_model=$this->__stayTypeModel($staytype);

			$reservation_info=$current["K9DataReservation"];
			$guest_info=$current["K9DataGuest"];
			$plan_info =$current["K9DataSchedulePlan"];

			$cash_type_id=$current["K9DataCheckoutPayment"]["cash_type_id"];
			$purchase    =$current["K9DataCheckoutPayment"]["purchase_flg"]?1:0;

			$schedule_info=$current[$schedule_model->name];
			$this->__setGuestInfo($guest_info);
			$this->__setReservationInfo($reservation_info);
			$this->__setDateRange($schedule_info,$date);
			$this->__setRoomInfo($plan_info);
			$this->__setStaytype($staytype);
			$this->__setCheckInOutStatus($reservation_info);
			$this->__setPaymenttypes($cash_type_id);
			$this->__setPurchase($purchase);

		}else{

			$staytype=$this->__getStaytypeParamByRoomId($selected_room_id);
			$this->__setDateRange(array(),$date);
			$this->__setStaytype($staytype);
			$this->__setCheckInOutStatus(false);
			$this->__setPaymenttypes(1);
			$this->__setPurchase(0);
		}

		//休憩の初期(room選択されてない)
		if(empty($reservation_hash) AND $staytype==$this->K9DataReststaySchedule->stayType) $selected_room_id=$this->__getFirstMasterRoomId();

		$today=date("Ymd");
		if(empty($date)) $date=$today;
		$purchase     =mstepJsonEncode(getTSVPurchase());
		$cardtypes    =mstepJsonEncode($this->__getCardTypes());
		$countrys     =mstepJsonEncode(getTSVCountryList());
		$working_ids  =mstepJsonEncode(getTSVWorkingIdsList());
		$nationality  =mstepJsonEncode(getTSVNationalityList());
		$classfication=mstepJsonEncode(getTSVClassfication());
		$language     =mstepJsonEncode(getTSVLanguage());
		$salesource   =mstepJsonEncode($this->K9MasterReservationSalesource->getSaleSource());
		$nametitle    =mstepJsonEncode(getTSVNametitle());
		$reservationtype=mstepJsonEncode(getTSVReservationType());
		$roomtypes    =mstepJsonEncode($this->__getRoomTypes());
		$roomnums     =mstepJsonEncode($this->__getRoomNums());
		$agencies     =mstepJsonEncode($this->__getAgency());

		$instance       =ScheduleLog::getInstance($this);
		$last_edit_time =$instance->getLastEditTime();
		$last_start_user=$instance->getLastStartUser();
		$last_edit_user_id=$instance->getLastEditUser();
		$edit_time_expired_ms=$instance->getLastEditTimeExpireMs();

		$this->set(compact(

			"date",
			"last_edit_time",
			"last_start_user",
			"edit_time_expired_ms",
			"last_edit_user_id",
			"selected_room_id",
			"reservation_hash",
			"purchase",
			"roomtypes",
			"cardtypes",
			"roomnums",
			"nametitle",
			"countrys",
			"working_ids",
			"nationality",
			"classfication",
			"reservationtype",
			"language",
			"agencies",
			"salesource"));
	}

	public function __getFirstMasterRoomId()
	{

		$this->K9MasterRoom->unbindFully();
		$master_room=$this->K9MasterRoom->find("first",array(
		
			"conditions"=>array("K9MasterRoom.del_flg"=>0)
		));
		$selected_room_id=$master_room["K9MasterRoom"]["id"];
		return $selected_room_id;
	}

	function __getReservationByHash($hash){

		$association=$this->K9DataReservation->association;
		$schedule_plan=$association["hasMany"]["K9DataSchedulePlan"];
		$schedule_plan["order"]=array("K9DataSchedulePlan.start ASC");
		$schedule_plan["conditions"]["and"]["K9DataSchedulePlan.del_flg"]=0;

		$schedule=$association["hasMany"]["K9DataSchedule"];
		$schedule["conditions"]=array("K9DataSchedule.del_flg"=>'0');
		$schedule["order"]=array("K9DataSchedule.start_month_prefix ASC","K9DataSchedule.start_day ASC");

		$schedule_rest=$association["hasMany"]["K9DataReststaySchedule"];
		$schedule_rest["conditions"]=array("K9DataReststaySchedule.del_flg"=>'0');
		$schedule_rest["order"]=array("K9DataReststaySchedule.start_month_prefix ASC","K9DataReststaySchedule.start_day ASC");

		$payment=$association["hasOne"]["K9DataCheckoutPayment"];
		$payment["conditions"]["and"]["K9DataCheckoutPayment.del_flg"]=0;

		$this->K9DataCheckoutPayment->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataReservation->bindModel(array("hasOne"=>array("K9DataCheckoutPayment"=>$payment)));
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedulePlan"=>$schedule_plan,"K9DataSchedule"=>$schedule,"K9DataReststaySchedule"=>$schedule_rest)));

		$this->K9DataSchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataReststaySchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));

		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation")));
		$this->K9DataSchedulePlan->unbindModel(array("belongsTo"=>array("K9MasterRoom")));
		$data=$this->K9DataReservation->getReservationByHash($hash,0,array( "recursive"=>2 ));
		return $data;
	}

	public function __getAgency()
	{
		$this->K9DataCompany->unbindModel(array("belongsTo"=>array("K9MasterAgencyType","K9MasterEmployee")));
		$data=$this->K9DataCompany->getAgentsByIds(null,array(K9DataCompany::$STATUS_PUBLIC,K9DataCompany::$STATUS_FIX),0);
		if(empty($data)) return array();

		$names=Set::combine($data,"{n}.K9DataCompany.id","{n}.K9DataCompany.name");
		$salesources=Set::combine($data,"{n}.K9DataCompany.id","{n}.K9DataCompany.salesource_id");

		$res=array();
		$res["names"]=$names;
		$res["salesources"]=$salesources;
		return $res;
	}

	function __getRoomNums(){

		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation","K9MasterCategory")));

		$conditions=array();
		$conditions["and"]["K9MasterRoom.del_flg"]=0;
		$roomnums=$this->K9MasterRoom->find("all",array( "conditions"=>$conditions ));

		$list=array();
		foreach($roomnums as $k=>$v){

			$type=$v["K9MasterRoom"]["type"];
			$list[$type][$v["K9MasterRoom"]["id"]]="{$v["K9MasterRoom"]["room_num"]}({$v["K9MasterRoomType"][$roomtype_name]})";
		} 
		return $list;
	}

	function __getRoomTypes(){

		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";

		$this->K9MasterRoomType->unbindFully();

		$conditions=array();
		$conditions["and"]["K9MasterRoomType.del_flg"]=0;
		$order=array("K9MasterRoomType.room_type ASC");
		$room_types=$this->K9MasterRoomType->find("all",array( "conditions"=>$conditions,"order"=>$order ));

		$list=array();
		foreach($room_types as $k=>$v){

			$type=$v["K9MasterRoomType"]["room_type"];
			$list[$type][$v["K9MasterRoomType"]["id"]]=$v["K9MasterRoomType"][$roomtype_name];
		}

		return $list;
	}

	function __getCardTypes(){

		$cards=$this->K9MasterCard->getCards(0);
		return Set::combine($cards,"{n}.K9MasterCard.id","{n}.K9MasterCard.card_type");
	}

}
