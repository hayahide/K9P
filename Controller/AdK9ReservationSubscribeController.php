<?php

require_once "Schedule".DS."ScheduleLog.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigation.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationKeys.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationExec.php";

App::uses('K9BaseReservationSubscribeController','Controller');
class AdK9ReservationSubscribeController extends K9BaseReservationSubscribeController{

	var $name = 'K9ReservationSubscribe';
	var $scheduleModel;

	public function beforeFilter() {

		parent::beforeFilter();
		$this->scheduleModel=$this->K9DataSchedule;
	}

	//stayは清掃状況は確認しません
	function checkRoomAvailable(){

		//if(!$this->isPostRequest()) exit;
		$post=$this->__getTestPostData();
		
		//$post=$this->data;
		$result=$this->__checkRoom($post);
		if(empty($result["status"])) Output::__outputNo(array("message"=>$result["message"]));

		$res["message"]=$this->__getRoomDetailMessage($result["avaliable_room_ids"]);
		$res["data"]["is_available"]=(!empty($result["avaliable_room_ids"])?true:false);
		Output::__outputYes($res);
	}

	function checkRoomTypeAvailable(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		
		$post=$this->data;
		$result=$this->__checkRoom($post);
		if(empty($result["status"])) Output::__outputNo(array("message"=>$result["message"]));

		$res["message"]=$this->__getRoomDetailMessage($result["avaliable_room_ids"]);
		$res["data"]["is_available"]=(!empty($result["avaliable_room_ids"])?true:false);
		Output::__outputYes($res);
	}

	private function __subscribe()
	{
		//$post=$this->__getTestPostData();
		$post=$this->data;
		$user_id=$post["master"]["user_id"];

		$local_time_key    =isset($post["local_time_key"])     ?$post["local_time_key"]:false;
		$last_edit_time    =isset($post["last_edit_time"])     ?$post["last_edit_time"]:false;
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$reservation_hash=isset($post["reservation"]["hash"])?$post["reservation"]["hash"]:false;
		$guest_hash=isset($post["guest"]["hash"])?$post["guest"]["hash"]:false;
		$is_new=empty($reservation_hash);

		/*==================================================================*/
		$pre_info=$this->__getPreInformations($reservation_hash,$guest_hash);
		/*==================================================================*/

	    $datasource=$this->K9DataReservation->getDataSource();
	    $datasource->begin();

		/*==================================================================*/
		$__guest=$this->__guestSubscribe($pre_info["guest_id"],$post["parson"],array(
		
			"employee_id"=>$this->Auth->user("employee_id"),
			"hash"       =>$this->Auth->user()["K9MasterEmployee"]["hash"]
		));
		/*==================================================================*/

		/*==================================================================*/
		$guest_id=!empty($pre_info["guest_id"])?$pre_info["guest_id"]:$__guest["id"];
		$is_checkout=(!empty($pre_info["current_reservation"]) AND strtotime($pre_info["current_reservation"]["checkout_time"])>-1);

		$__reservation=$this->__reservationStaySubscribe($pre_info["reserve_id"],$guest_id,$post["reservation"],$post["room"]);
		/*==================================================================*/

		$reserve_id=!empty($pre_info["reserve_id"])?$pre_info["reserve_id"]:$__reservation["id"];

		/*==================================================================*/
		$this->__reservationCheckoutpaymentInitital($reserve_id);
		/*==================================================================*/

		/*==================================================================*/

		$cash_type_id=$post["reservation"]["administration-paymenttype"];
		$purchase_flg=$post["reservation"]["administration-purchase"];
		$res=$this->__savePaymentWay(array(

			"reserve_id"  =>$reserve_id,
			"cash_type_id"=>$cash_type_id,
			"purchase_flg"=>$purchase_flg,
			"employee_id" =>$this->Auth->user("employee_id")
		));

		if(empty($res)) throw new Exception(__("正常に処理が終了しませんでした"));

		/*==================================================================*/

		if(!empty($is_checkout)){

			$datasource->commit();
			$res["data"]["room"]["id"]=$post["room"]["rate-roomnum"];
			$res["data"]["reservation"]["hash"]=$__reservation["hash"];
			$res["data"]["guest"]["hash"]=$__guest["hash"];
			return $res;
		}

		/*==================================================================*/
		$target_dates=$this->__makeScheduleRange($post["reservation"]["administration-range"],array("is_new"=>$is_new));
		/*==================================================================*/

		/*==================================================================*/
		//更新時は確認しない (no need to check if this record is as renewal)
		if($is_new) $this->__checkRoomSituation($post["room"],$target_dates);
		/*==================================================================*/

		/*==================================================================*/
		$room_id=$this->__reservationPlanSubscribe($reserve_id,$post["room"],$target_dates);
		/*==================================================================*/

		/*==================================================================*/
		$this->__scheduleSubscribe($reserve_id,$target_dates,array("is_new"=>$is_new));
		/*==================================================================*/

		$datasource->commit();

		$last_edit_time=$this->__closeDandoriHandlings(false,$user_id);

		$res["last_edit_time"]=$last_edit_time;
		$res["data"]["room"]["id"]=$room_id;
		$res["data"]["reservation"]["hash"]=$__reservation["hash"];
		$res["data"]["guest"]["hash"]=$__guest["hash"];
		return $res;
	}

	function reservationSubscribe(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		
		try{

			$res=$this->__subscribe();

		}catch(Exception $e){

			Output::__outputNo(array( "message"=>$e->getMessage() ));
		}

		Output::__outputYes($res);
	}

	private function __checkRoom($data)
	{
		$res=array("status"=>false);
		$reservation_hash=$data["reservation"]["hash"];
		$arrival_date    =$data["arrival_date"];
		$departure_date  =$data["departure_date"];

		switch(true){
		
		case(isset($data["room_type_id"]) AND is_numeric($data["room_type_id"])):

			$room_ids=$this->__getRoomIdByType($data["room_type_id"],null);
			break;

		default:

			$room_ids=is_array($data["room_id"])?$data["room_id"]:array($data["room_id"]);
			break;
		}

		try{

			$avaliable_room_ids=$this->__checkRoomAvailable($room_ids,$reservation_hash,array(
			
				"arrival_date"  =>$arrival_date,
				"departure_date"=>$departure_date
			));

		}catch(Exception $e){

			$res["message"]=$e->getMessage();
			return $res;
		}

		if(empty($avaliable_room_ids)){

			$res["message"]=__("空室が有りません");
			return $res;
		}

		$res["status"]=true;
		$res["avaliable_room_ids"]=$avaliable_room_ids;
		return $res;
		   
		$res["message"]=$this->__getRoomDetailMessage($avaliable_room_ids);
		$res["data"]["is_available"]=(!empty($avaliable_room_ids)?true:false);
		Output::__outputYes($res);
	}

	function __checkRoomAvailable($room_ids=array(),$reservation_hash,$params=array())
	{
		$current_reservation="";
		if(!empty($reservation_hash) AND !$current_reservation=$this->__getReservationByHash($reservation_hash)){
		
			throw new Exception(__("正常に処理が終了しませんでした"));
		}

		// over today is important.
		// -1 day is important.
		$today=date("Ymd");
		$arrival_date  =$params["arrival_date"];
		$departure_date=$params["departure_date"];
		//$arrival_date=date("Ymd",max(strtotime($arrival_date),strtotime($today)));
		//$departure_date=date("Ymd",strtotime("-1 day",strtotime($departure_date)));
		
		$arrival_date=date("Ymd",max(strtotime($arrival_date),strtotime($today)));
		$departure_date=date("Ymd",max(strtotime($departure_date),strtotime($today)));

		$range[0]["start"]=$arrival_date;
		$range[0]["end"]  =$departure_date;
		$reserve_id=!empty($current_reservation)?$current_reservation["id"]:null;
		$is_new=empty($reserve_id);

		$target_dates=$this->__makeScheduleRange($range,array("is_new"=>$is_new));
		$avaliable_room_ids=$this->__getAvailableRoomIDsByRoomIDs($this->K9DataSchedule,$reserve_id,$room_ids,$target_dates);
		$avaliable_rest_room_ids=$this->__getAvailableRoomIDsByRoomIDs($this->K9DataReststaySchedule,$reserve_id,$room_ids,$target_dates);

		$avaliable_room_ids=array_intersect($avaliable_room_ids,$avaliable_rest_room_ids);
		return $avaliable_room_ids;
	}

	function __checkPricePlans($plans,$dates,$res=array()){

		if(empty($dates)) return $res;
		$date=array_shift($dates);

		foreach($plans as $k=>$v){
		
			$start=$v[0]["start"];
			if(!isset($plans[$k+1])){

				$res[$date]["room_id"]=$v["K9DataSchedulePlan"]["room_id"];
				$res[$date]["plan_id"]=$v["K9DataSchedulePlan"]["id"];
				$res[$date]["start"]  =$v[0]["start"];
				break;
			}

			$next=$plans[$k+1][0]["start"];
			if($date>=$start && $next>$date){

				$res[$date]["room_id"]=$v["K9DataSchedulePlan"]["room_id"];
				$res[$date]["plan_id"]=$v["K9DataSchedulePlan"]["id"];
				$res[$date]["start"]  =$v[0]["start"];
				break;
			}
		}

		return $this->__checkPricePlans($plans,$dates,$res);
	}

	private function __getAvailableRoomIdsByBothTypesByType($room,$reserve_id,$target_dates){
	
		//通常側
		$params=array("room_type_id"=>$room["rate-roomtype"],"room_id"=>$room["rate-roomnum"]);
		$avaliable_room_ids     =$this->__getAvailableRoomIdsByBothTypes($this->K9DataSchedule,$params,$reserve_id,$target_dates);
		$avaliable_rest_room_ids=$this->__getAvailableRoomIdsByBothTypes($this->K9DataReststaySchedule,$params,$reserve_id,$target_dates);

		$avaliable_room_ids=array_intersect($avaliable_room_ids,$avaliable_rest_room_ids);
		return $avaliable_room_ids;
	}

	function __reservationPlanSubscribe($reserve_id,$room,$target_dates=array()){

		// check if target room is available. ※ both of stay and rest.
		$avaliable_room_ids=$this->__getAvailableRoomIdsByBothTypesByType($room,$reserve_id,$target_dates);
		$use_room_id=array_shift($avaliable_room_ids);

		$plan_date_list=array();
		$current_date_plans=array();
		if($current_plans=$this->__getSchedulePlanByReserveId($reserve_id)){

			$plan_date_list=Set::extract($current_plans,"{}.0.start");
			$current_date_plans=$this->__checkPricePlans($current_plans,$target_dates);
		}

		$today=date("Ymd");
		$plan_dates=array_keys($current_date_plans);
		$save=array();

		//v($plan_dates);
		switch(true){

		//this is the first schedule.
		case(empty($current_date_plans)):

			//insert
			$save["reserve_id"]=$reserve_id;
			$save["room_id"]   =$use_room_id;
			$save["start"]     =$target_dates[0];
			break;

		//room is diffrent but start day also is diffrent.
		case(isset($current_date_plans[$today]) AND $current_date_plans[$today]["room_id"]!=$use_room_id AND $current_date_plans[$today]["start"]!=$today):

			//insert
			$save["reserve_id"]=$reserve_id;
			$save["room_id"]   =$use_room_id;
			$save["start"]     =$today;
			break;

		//room is diffrent but start day is same.
		case(isset($current_date_plans[$today]) AND $current_date_plans[$today]["room_id"]!=$use_room_id AND $current_date_plans[$today]["start"]==$today):

			//update
			$save["id"]     =$current_date_plans[$today]["plan_id"];
			$save["room_id"]=$use_room_id;
			$save["del_flg"]=0;
			break;

		// in this case of reservation is not started.
		case(!isset($current_date_plans[$today]) AND $plan_dates[0]>$today AND $current_date_plans[$plan_dates[0]]["room_id"]!=$use_room_id):

			//update
			$save["id"]     =$current_date_plans[$plan_dates[0]]["plan_id"];
			$save["room_id"]=$use_room_id;
			$save["start"]=$target_dates[0];
			$save["del_flg"]=0;
			break;

		// if start date is extended for past.
		// from 13 - 15 to 12 - 15.
		case($plan_date_list[0]>$target_dates[0]):

			$save["id"]=$current_date_plans[$plan_date_list[0]]["plan_id"];
			$save["room_id"]=$use_room_id;
			$save["start"]=$target_dates[0];
			$save["del_flg"]=0;
			break;

		// if start date is extented for a future.
		// from 12 -15 to 13 - 15.
		case($target_dates[0]>$plan_date_list[0]):

			//v($current_date_plans);
			//v($target_dates);
			$save["id"]=$current_date_plans[$target_dates[0]]["plan_id"];
			$save["room_id"]=$use_room_id;
			$save["start"]=$target_dates[0];
			$save["del_flg"]=0;
			break;
		}

		if(empty($save)) return $use_room_id;

		if(!$this->K9DataSchedulePlan->save($save)){

			throw new Exception(__("正常に処理が終了しませんでした"));
		}

		return $use_room_id;
	}

	function __reservationStaySubscribe($reserve_id,$guest_id,$reservation,$room){
	
		return parent::__reservationSubscribe($this->K9DataSchedule,$reserve_id,$guest_id,$reservation,$room);
	}

	function __hasReservatedRooms(Model $model,$schedules){

		switch($model->name){
		
		case($this->K9DataSchedule->name);

			$non_available_rooms=parent::__hasReservatedRooms($model,$schedules);
			return $non_available_rooms;
			break;

		case($this->K9DataReststaySchedule->name);

			$today=date("Ymd");
			$non_available_rooms=parent::__hasReservatedRooms($model,$schedules);

			$todays_data=array();
			if(isset($non_available_rooms[$today])){
			
				$todays_data=$non_available_rooms[$today];
				unset($non_available_rooms[$today]);
			}

			//本日のデータのみチェック
			//チェックアウト状況確認
			//仕様として、当日の場合はチェックアウト状況を確認
			//別の日の場合は、部屋が予約されているかを確認
			//チェックアウト前に同部屋を予約する場合は、既存のを消してもらう
			if(!empty($todays_data)){

				$todays_checkouts_reservations=array();
				$todays_reserve_ids=Set::extract($todays_data,"{}.reserve_id");
				if($checkout_reservations=$this->K9DataReservation->checkOutReservations($todays_reserve_ids)){
				
					$todays_checkouts_reservations=Set::extract($checkout_reservations,"{}.K9DataReservation.id");
				}

				foreach($todays_data as $k=>$v){
				
					//チェックアウトされている場合は使用を許可(使えない部屋はunsetしない)
					if(!in_array($v["reserve_id"],$todays_checkouts_reservations)) continue;
					unset($todays_data[$k]);
				}

				if(!empty($todays_data)) $non_available_rooms[$today]=$todays_data;
				ksort($non_available_rooms);
			}

			return $non_available_rooms;
			break;
		}
	}
}
