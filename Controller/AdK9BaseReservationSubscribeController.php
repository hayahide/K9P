<?php

App::import('Utility', 'Sanitize');
App::uses('K9ScheduleBaseController','Controller');
App::uses('K9CheckoutPaymentController','Controller');
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
class AdK9BaseReservationSubscribeController extends K9ScheduleBaseController{

	var $uses = [

		"K9DataReservation",
		"K9DataSchedule",
		"K9DataReststaySchedule",
		"K9DataCheckoutPayment",
		"K9DataSchedulePlan",
		"K9DataUnavailableRoom",
		"K9DataGuest"
	];

	function beforeFilter() {

		parent::beforeFilter();

		$this->loadModel("K9MasterRoomSituation");
		$this->loadModel("K9MasterRoom");
	}
	
	protected function __makeScheduleRange($ranges,$params=array()){

		$today=strtotime(date("Ymd"));
		$min_max_date=$this->__getMinMaxDate($ranges);
		$min_date=strtotime($min_max_date["min"]);
		$max_date=strtotime($min_max_date["max"]);

		//本日以前
		if($params["is_new"] AND $today>$min_date) throw new Exception(__("宿泊日の情報が不正です"));

		$target_dates=array();
		foreach($ranges as $k=>$v){

			//両方空は有りえない
			if(empty($v["start"]) AND empty($v["end"])) throw new Exception(__("宿泊日の情報が不正です"));
			$start=!empty($v["start"])?$v["start"]:$v["end"];
			$end  =!empty($v["end"])  ?$v["end"]  :$v["start"];
			$dates=array_keys(makeDatePeriod($start,$end));

			//最大日、最小日に対して
			//$__min=date("Y-m-d",strtotime($dates[0]));
			//$__max=date("Y-m-d",strtotime($dates[count($dates)-1]));
			$__min=strtotime($dates[0]);
			$__max=strtotime($dates[count($dates)-1]);
			if($min_date>$__min) throw new Exception(__("宿泊日の情報が不正です"));
			if($__max>$max_date) throw new Exception(__("宿泊日の情報が不正です"));

			//important -1 day. because 15-16 is 1 night.
			$dates[count($dates)-1]=(Int)date("Ymd",strtotime("-1 day",strtotime($dates[1])));
			$target_dates=array_merge($dates,$target_dates);
		}

		//重複日確認
		//if(count(array_unique($target_dates))!=count($target_dates)) throw new Exception(__("宿泊日の情報が不正です"));

		$target_dates=array_unique($target_dates);
		//v($target_dates);
		sort($target_dates);
		return $target_dates;
	}

	protected function __getPreInformations($reservation_hash,$guest_hash){

		$guest_id="";
		$reserve_id="";
		$current_reservation="";
		$current_guest="";
		//v($guest_hash);
		//v($reservation_hash);
		if(!empty($reservation_hash)) $current_reservation=$this->__getReservationByHash($reservation_hash);

		switch(true){
		case(!empty($guest_hash) AND $current_guest=$this->__getGuestByHash($guest_hash)):
			$guest_id=$current_guest["id"];
			break;
		case(empty($guest_hash) AND !empty($current_reservation)):
			$guest_id=$current_reservation["guest_id"];
			break;
		}

		if(!empty($current_reservation)) $reserve_id=$current_reservation["id"];

		if(!empty($reservation_hash) AND empty($current_reservation)) throw new Exception(__("宿泊者情報の取得が正常に行えませんでした"));
		if(!empty($guest_hash) AND empty($current_guest)) throw new Exception(__("宿泊者情報の取得が正常に行えませんでした"));

		$res["guest_id"]=$guest_id;
		$res["reserve_id"]=$reserve_id;
		$res["current_reservation"]=$current_reservation;
		$res["current_guest"]=$current_guest;
		return $res;
	}

	protected function __getRoomDetailMessage($room_ids=array()){

		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
	
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation")));
		$rooms=$this->K9MasterRoom->findAllById($room_ids);

		$list=array();
		foreach($rooms as $k=>$v) $list[]=__("部屋番号").":{$v["K9MasterRoom"]["room_num"]}({$v["K9MasterRoomType"][$roomtype_name]})".__("は空室です");
		return implode("<br />",$list);
	}

	protected function __getReservationByHash($hash){

		$this->K9DataReservation->unbindFully();
		if(!$data=$this->K9DataReservation->getReservationByHash($hash,0)) return false;
		return $data["K9DataReservation"];
	}

	private function __getGuestByHash($hash){

		$this->K9DataGuest->unbindFully();
		if(!$data=$this->K9DataGuest->findByHash($hash,0)) return false;
		return $data["K9DataGuest"];
	}

	function __guestSubscribe($guest_id,$parson,$params=array()){

		//v($parson);
		$time=date("YmdHis");
		//$base=$this->Auth->user("employee_id")."_".$this->Auth->user()["K9MasterEmployee"]["hash"]."_".$time;
		$base=$params["employee_id"]."_".$params["hash"]."_".$time;

		$passwordHasher=new SimplePasswordHasher(array('hashType'=>'sha256'));
		$new_guest_hash=$passwordHasher->hash($base);

		switch(!empty($guest_id)){
		
		case(true):

			$save["id"]=$guest_id;
			$save["final_employee_entered"]=$params["employee_id"];
			break;

		case(false):

			$save["first_employee_entered"]=$params["employee_id"];
			$save["final_employee_entered"]=$save["first_employee_entered"];
			$save["created"]=$time;
			break;
		}

		$save["title"]=$parson["title"];
		$save["hash"]=$new_guest_hash;
		$save["first_name"]=$parson["parson-firstname"];
		$save["last_name"]=$parson["parson-lastname"];
		$save["passport"]=encData($parson["parson-passport"]);
		$save["incidential"]=$parson["parson-incidential"];
		$save["language_num"]=$parson["parson-language"];
		$save["contact_address"]=$parson["contact-address"];
		$save["contact_city"]=$parson["contact-city"];
		$save["contact_state"]=$parson["contact-state"];
		$save["contact_nationality"]=$parson["contact-nationality"];
		$save["contact_country"]=$parson["contact-country"];
		$save["contact_tel"]=encData($parson["contact-tel"]);
		$save["contact_email"]=encData($parson["contact-email"]);
		$save["contact_zip_code"]=$parson["contact-zip-code"];
		$save["remarks"]=$parson["remark-guest-note"];
		$save["modified"]=$time;
		if(!$res=$this->K9DataGuest->save($save)) throw new Exception(__("宿泊者情報の登録が正常に終了しませんでした"));
		return $res["K9DataGuest"];
	}

	protected function __reservationSubscribe(Model $schedule_model,$reserve_id,$guest_id,$reservation,$room){

		$time=date("YmdHis");
		$base=$this->Auth->user("employee_id")."_".$this->Auth->user()["K9MasterEmployee"]["hash"]."_".$time;
		$passwordHasher=new SimplePasswordHasher(array('hashType'=>'sha256'));
		$new_reservation_hash=$passwordHasher->hash($base);

		if(!empty($reserve_id)) $save["id"]=$reserve_id;

		$save["guest_id"]      =$guest_id;
		$save["hash"]          =$new_reservation_hash;
		if(empty($reserve_id)) $save["first_employee_entered"]=$this->Auth->user("employee_id");
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		$save["color_id"]      =0;
		$save["edit_user_id"]  =$save["final_employee_entered"];
		$save["salesource_id"] =$reservation["administration-salesource"];
		$save["company_id"]    =$reservation["administration-agency"];
		$save["remarks"]       =$reservation["remark-reserve-note"];
		$save["adults_num"]    =max(0,$room["rate-adults"]);
		$save["child_num"]     =max(0,$room["rate-child"]);
		$save["booking_id"]    =empty($reservation["administration-bookingid"])?CLIENT."_".date("YmdHis"):$reservation["administration-bookingid"];
		if(isset($room["rate-weekday"])) $save["weekday_price"]=max(0,$room["rate-weekday"]);
		if(isset($room["rate-weekend"])) $save["weekend_price"]=max(0,$room["rate-weekend"]);
		$save["staytype"]=$schedule_model->stayType;
		if(!$res=$this->K9DataReservation->save($save)) throw new Exception(__("正常に処理が終了しませんでした"));
		return $res["K9DataReservation"];
	}

	protected function __reservationCheckoutpaymentInitital($reserve_id){
	
		$save["reserve_id"]=$reserve_id;
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		//$this->K9DataCheckoutPayment->id=null;
		if(!$res=$this->K9DataCheckoutPayment->save($save)) throw new Exception(__("正常に処理が終了しませんでした"));
		$last_id=$this->K9DataCheckoutPayment->getLastInsertID();

		$res=$res["K9DataCheckoutPayment"];
		$res["id"]=$last_id;
		return $res;
	}

	protected function __getSchedulePlanByReserveId($reserve_id){

		$this->K9DataSchedulePlan->unbindFully();
		$o=array("K9DataSchedulePlan.start ASC");
		$f=array("id","DATE_FORMAT(start,'%Y%m%d') as start","room_id");
		$current_plans=$this->K9DataSchedulePlan->findAllByReserveId($reserve_id,$f,$o);
		return $current_plans;
	}

	protected function __getAvailableRoomIDsByRoomIDs(Model $schedule_model,$reserve_id,$room_ids=array(),$target_dates=array())
	{
		$dates=separateRangeDays($target_dates);

		//check for unavailable room setting with any reason.
		$unavailable_room_ids=$this->__getUnavailableRoomsWitnAnyreason($dates[0]["start"],$dates[0]["end"],$room_ids);
		$allow_room_ids=array_diff($room_ids,$unavailable_room_ids);
		if(empty($allow_room_ids)) throw new Exception(__("使用可能な部屋が有りません"));

		//check for if other schedules isn't set on the same period.
		$available_room_ids=$this->__getAvailableRoomIds($schedule_model,$allow_room_ids,$reserve_id,$dates);
		if(empty($available_room_ids)) throw new Exception(__("空室が有りません"));

		return $available_room_ids;
	}

	protected function __getAvailableRoomIdsByBothTypes(Model $schedule_model,$params=array(),$reserve_id,$target_dates)
	{
		switch(true){
		
			case(!empty($params["room_type_id"])):

				$room_ids=$this->__getCleanRoomByType($params["room_type_id"]);
				if(empty($room_ids)) throw new Exception(__("清掃済みの部屋が有りません"));
				$available_room_ids=$this->__getAvailableRoomIDsByRoomIDs($schedule_model,$reserve_id,$room_ids,$target_dates);
				break;

			case(!empty($params["room_id"])):

				$room_ids=array($params["room_id"]);
				$available_room_ids=$this->__getAvailableRoomIDsByRoomIDs($schedule_model,$reserve_id,$room_ids,$target_dates);
				break;

			default:

				throw new Exception(__("宿泊日の情報が不正です"));
				break;
		}

		return $available_room_ids;
	}

	protected function __getCleanRoomByType($room_type_id)
	{
		$room_ids=$this->__getClearnRoomIds($this->K9MasterRoomType,array( "room_type_id"=>$room_type_id ));
		return $room_ids;
	}

	private function __getClearnRoomIds(Model $master_model,$params=array())
	{
		switch($master_model->name){
		
		case($this->K9MasterRoom->name):

			$room_ids=$this->__getClearnRoomFromByRoomid($params["room_id"],array( "del_flg"=>0 ));
			return $room_ids;
			break;

		case($this->K9MasterRoomType->name):

			$room_ids=$this->__getClearnRoomFromByRoomtype($params["room_type_id"]);
			return $room_ids;
			break;
		}
	}

	private function __getClearnRoomFromByRoomtype($room_type_id)
	{
		$conditions=array();
		$conditions["and"]["K9MasterRoomType.id"]=$room_type_id;

		$this->K9MasterRoomType->hasMany["K9MasterRoom"]["conditions"]["and"]["K9MasterRoom.del_flg"]=0;
		$this->K9MasterRoom->belongsTo["K9MasterRoomSituation"]["conditions"]["and"]["K9MasterRoomSituation.id"]=K9MasterRoomSituation::$SITUATION_CLEAN;
		$this->K9MasterRoomType->unbindModel(array("belongsTo"=>array("K9MasterCategory")));
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomType","K9MasterCategory")));
		$data=$this->K9MasterRoomType->find("all",array("conditions"=>$conditions,"recursive"=>2));
		if(empty($data)) return array();

		$room_ids=array();
		foreach($data as $k=>$v){

			if(empty($v["K9MasterRoom"])) continue;
			foreach($v["K9MasterRoom"] as $_k=>$_v){
			
				if(empty($_v["K9MasterRoomSituation"])) continue;
				$room_ids[]=$_v["id"];
			}
		}

		return $room_ids;
	}

	private function __getClearnRoomFromByRoomid($room_id,$params=array())
	{

		$conditions=array();
		$conditions["and"]["K9MasterRoom.id"]=$room_id;
		if(is_numeric($params["del_flg"])) $conditions["and"]["K9MasterRoom.del_flg"]=$params["del_flg"];
		$conditions["and"]["K9MasterRoomSituation.id"]=K9MasterRoomSituation::$SITUATION_CLEAN;
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomType")));
		$data=$this->K9MasterRoom->find("all",array( "conditions"=>$conditions ));
		if(empty($data)) return array();
		return Set::extract($data,"{}.K9MasterRoom.id");
	}

	private function __getUnavailableRoomsWitnAnyreason($start,$end,$room_id)
	{
		$data=$this->K9DataUnavailableRoom->getAllDataBasedonRange($start,$end,array( "room_id"=>$room_id ));
		$unavailable_room_ids=Set::extract($data,"{}.K9DataUnavailableRoom.room_id");
		if(empty($unavailable_room_ids)) return array();
		return array_unique($unavailable_room_ids);
	}

	private function __getAvailableRoomIds(Model $schedule_model,$room_ids=array(),$reserve_id,$range=array())
	{
		$today=date("Ymd");
		$start=date("Ymd",max(strtotime($range[0]["start"]),strtotime($today)));
		$end  =$range[count($range)-1]["end"];

		//通常のスケジュール
		$schedules=$this->__scheduleByYmOtherTargetReservation($schedule_model,$reserve_id,$start,$end);
		if(empty($schedules)) return $room_ids;

		//any room used already.
		$non_available_rooms=$this->__hasReservatedRooms($schedule_model,$schedules);

		//intented staying of rooms.
		$used_room_ids=array();
		foreach($non_available_rooms as $ymd=>$v) $used_room_ids=array_merge($used_room_ids,Set::extract($v,"{}.room_id"));
		$used_room_ids=array_unique($used_room_ids);

		$available_room_ids=array_diff($room_ids,$used_room_ids);
		sort($available_room_ids);
		return $available_room_ids;
	}

	protected function __hasReservatedRooms(Model $model,$schedules){

		$list=array();
		foreach($schedules as $k=>$v){

			$ymd=$v[$model->name]["start_month_prefix"].sprintf("%02d",$v[$model->name]["start_day"]);
			if(!isset($list[$ymd])) $list[$ymd]=array();
			$plans=$v["K9DataReservation"]["K9DataSchedulePlan"];
			foreach($plans as $_k=>$plan){

				$plan_start=date("Ymd",strtotime($plan["start"]));
				if(!isset($plans[$_k+1])){

					if($plan_start>$ymd) throw new Exception("Wrong Range Data.");
					$count=count($list[$ymd]);
					$list[$ymd][$count]["room_id"]=$plan["room_id"];
					$list[$ymd][$count]["reserve_id"]=$plan["reserve_id"];
					break;
				}

				$next=$plans[$_k+1];
				$plan_next=date("Ymd",strtotime("-1 day",strtotime($next["start"])));
				if($plan_start>$ymd || $ymd>$plan_next) continue;

				$count=count($list[$ymd]);
				$list[$ymd][$count]["room_id"]=$plan["room_id"];
				$list[$ymd][$count]["reserve_id"]=$plan["reserve_id"];
				break;
			}
		}

		return $list;
	}

	protected function __scheduleByYmOtherTargetReservation(Model $schedule_model,$reserve_id,$start,$end){

		$association=$this->K9DataReservation->association["hasMany"];
		$association[$schedule_model->name]["order"]=array("K9DataSchedulePlan.start DESC");
		$association["K9DataSchedulePlan"]["conditions"]=array("K9DataSchedulePlan.del_flg"=>0);

		$this->K9DataReservation->unbindModel(array("belongsTo"=>"K9DataGuest"));
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedulePlan"=>$association["K9DataSchedulePlan"])));

		$schedules=$schedule_model->scheduleByYmOtherTargetReservation($reserve_id,$start,$end,array(
		
			"del_flg"  =>0,
			"recursive"=>2
		));

		return $schedules;
	}

	protected function __registNewReservationSchedule($dates=array(),$params,$insert_datas=array()){

		if(empty($dates)){

			return $insert_datas;
		}

		$reserve_id =$params["reserve_id"];
		$date=array_shift($dates);

		$s=strtotime($date);
		$date=date("Y/m/d 00:00:00",$s);
		$save["start_month_prefix"]=date("Ym",$s);
		$save["start_day"]=date("j",$s);
		$save["reserve_id"]=$reserve_id;
		$save["start_date"]=$date;
		$save["end_date"]  =$date;
		$this->scheduleModel->id=null;
		if(!$this->scheduleModel->save($save)) throw new Exception(__("正常に処理が終了しませんでした"));

		$schedule_id=$this->scheduleModel->getLastInsertID();
		$save["schedule_id"]=$schedule_id;
		$save["date"]=$date;
		$insert_datas[]=$save;
		return $this->__registNewReservationSchedule($dates,$params,$insert_datas);
	}

	private function __getScheduleForReservation($target_dates){

		$this->scheduleModel->unbindFully();
		if(!$schedules=$this->scheduleModel->getSiteScheduleByDate($target_dates,false)) return array();

		$data=array();
		foreach($schedules as $k=>$v){

			$reserve_id=$v[$this->scheduleModel->name]["reserve_id"];
			if(!isset($data[$reserve_id])) $data[$reserve_id]=array();
			$count=count($data[$reserve_id]);
			$data[$reserve_id][$count]["date"]=$v[$this->scheduleModel->name]["start_month_prefix"].sprintf("%02d",$v[$this->scheduleModel->name]["start_day"]);
			$data[$reserve_id][$count]["id"]  =$v[$this->scheduleModel->name]["id"];
		}

		return $data;
	}

	protected function __scheduleSubscribe($reserve_id,$target_dates,$params=array()){

		if(empty($params["is_new"])){

			//over today.
			$today=date("Ymd");
			try{ $this->scheduleModel->changeDeleteScheduleByReservationIdOverTheDay($reserve_id,$today);
			}catch(Exception $e){
			
				throw new Exception($e->getMessage());
			}
		}

		$update_dates=array();
		$schedules=$this->__getScheduleForReservation($target_dates);
		$reserve_schedules=isset($schedules[$reserve_id])?$schedules[$reserve_id]:array();
		if(!empty($reserve_schedules)) $update_dates=Set::extract($reserve_schedules,"{}.date");
		$insert_dates=array_diff($target_dates,$update_dates);

		if(!empty($update_dates)){
		
			$res=$this->scheduleModel->changeDeleteSituationScheduleById(Set::extract($reserve_schedules,"{}.id"),0);
			if(empty($res["status"])) throw new Exception($res["message"]);
		}

		if(!empty($insert_dates)){

			try{

				$this->__registNewReservationSchedule($insert_dates,array("reserve_id"=>$reserve_id));

			}catch(Exception $e){ throw new Exception($e->getMessage()); }
		}

		return true;
	}

	protected function __savePaymentWay($data=array()){

		$controller=new K9CheckoutPaymentController();
		$res=$controller->__savePaymentWay($data);
		return $res;
	}

	protected function __checkRoomSituation($room,$target_dates)
	{
		switch(true){
	
		case(!empty($room["rate-roomtype"])):

			// if today is in the target date.		
			if(in_array((Int)date("Ymd"),$target_dates)){

				//if clean room is avaiable.
				$room_type_id=$room["rate-roomtype"];
				$available_room_ids=$this->__getCleanRoomByType($room_type_id);
				if(empty($available_room_ids)) throw new Exception(__("使用可能(清掃後)な部屋が存在しません、使用可能な部屋が存在するか確認して下さい"));
			}
			break;

		case(!empty($room["rate-roomnum"])):

			// 期間が変わればチェックして
			// if today is in the target date.		
			if(in_array((Int)date("Ymd"),$target_dates)){

				// if clean room is avaiblable.
				$room_id=$room["rate-roomnum"];
				$available_room_ids=$this->__getClearnRoomIds($this->K9MasterRoom,array( "room_id"=>$room_id ));
				if(empty($available_room_ids)) throw new Exception(__("使用可能(清掃後)な部屋が存在しません、使用可能な部屋が存在するか確認して下さい"));
			}
			break;
		}

		return true;
	}

	protected function __updateArrayValuesOnlyCleanRoom(&$room_ids){

		$this->K9MasterRoom->unbindFully();
		$conditions["and"]["K9MasterRoom.id"]=$room_ids;
		$conditions["and"]["K9MasterRoom.situation_id"]=K9MasterRoomSituation::$SITUATION_CLEAN;
		$conditions["and"]["K9MasterRoom.del_flg"]     =0;
		$mater_rooms=$this->K9MasterRoom->find("all",array( "conditions"=>$conditions ));

		if(empty($mater_rooms)){
		
			$room_ids=array();
			return;
		}

		$room_ids=Set::extract($mater_rooms,"{}.K9MasterRoom.id");
		return;
	}

	private function __getMinMaxDate($ranges=array()){

		$min_date=$ranges[0]["start"];
		$last_range=$ranges[count($ranges)-1];

		switch(true){
		case(!empty($last_range["end"])):
			$max_date=$last_range["end"];
			break;
		case(!empty($last_range["start"])):
			$max_date=$last_range["start"];
			break;
		default:

			//両方空は有りない
			throw new Exception(__("宿泊日の情報が不正です")."(1)");
			break;
		}

		$res["min"]=$min_date;
		$res["max"]=$max_date;
		return $res;
	}
}
