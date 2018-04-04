<?php

require_once "Schedule".DS."ScheduleGetLastEditReservationUser.php";
require_once "Schedule".DS."ScheduleLog.php";

require_once "TimeoutInvestigation".DS."TimeoutInvestigation.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationKeys.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationExec.php";
App::import('Utility', 'Sanitize');

App::uses("K9PriceController","Controller");
App::uses("K9RestPriceController","Controller");
App::uses("K9BasePricesController","Controller");
App::uses("K9ScheduleBaseController","Controller");
class AdK9SiteController extends K9ScheduleBaseController{

		var $name = 'K9Site';
		var $uses = [

			"K9MasterRoomType",
			"K9MasterRoomSituation",
			"K9MasterCheckinColor",
			"K9MasterRoom",
			"K9MasterEmployee",
			"K9MasterColor",
			"K9DataSchedulePlan",
			"K9DataSchedule",
			"K9DataReststaySchedule",
			"K9DataCheckoutPayment",
			"K9DataReservation",
			"K9DataPriceRoomType",
			"K9DataPriceParRoom",
			"K9DataUnavailableRoom",
			"K9DataGuest",
			"K9DataDipositSchedule",
			"K9DataDipositReststaySchedule",
		];

		public function beforeFilter() {

			parent::beforeFilter();
		}

		function index($base_start_time=""){

			if(!isEnableDateByYmd($base_start_time)) $base_start_time=date("Ymd");

			$base_start_time=strtotime($base_start_time);
			$base_start_ms=$base_start_time*1000;
			$base_start_date=date("Ymd",$base_start_time);
			$start=date("Y-m-d",strtotime("-20 day",$base_start_time));
			$end  =date("Y-m-d",strtotime("+20 day",$base_start_time));

			$effect_room_types=$this->__getEffectRoomTypes();
			$stay_schedule_block_num=$this->K9MasterRoom->getScheduleBlockNum();
			$effect_rooms=$this->__getEffectRooms(date("Ymd"));
			$rooms=$effect_rooms["rooms"];

			$res_informaton=$this->__getInformations($start,$end,$stay_schedule_block_num);
			$color_list =$res_informaton["data"]["color_list"];
			$reserve_ids=(isset($res_informaton["data"]["reserve_ids"])?$res_informaton["data"]["reserve_ids"]:array());
			$informations=$res_informaton["informations"];

			$unavailable=$this->__getUnavailableSchedules($start,$end);

			$instance       =ScheduleLog::getInstance($this);
			$last_edit_time =$instance->getLastEditTime();
			$last_start_user=$instance->getLastStartUser();
			$last_edit_user_id=$instance->getLastEditUser();
			$edit_time_expired_ms=$instance->getLastEditTimeExpireMs();

			//■user edited last imformation.
			$last_modified_user=array();
			if(!empty($last_edit_user_id)){

				$last_modified_user=$this->__getLastModifiedInformations($last_edit_user_id);
				$last_modified_user["last_edit_ms"]=$last_edit_time;
				$last_modified_user["edit_time_expired"]=$edit_time_expired_ms;
			}

			$user_id=$this->Auth->user("employee_id");
			$is_authority=$this->__checkAuthorityToEdit($user_id)?1:0;

			$edit_informations=array();
			if(!empty($reserve_ids)) $edit_informations=$this->__getReserveEditInfomrations($reserve_ids);

			$holiday_base_year=date("Y",$base_start_time);
			$holidays=$this->__getCambodiaHolidaysForYear($holiday_base_year);

			$etc_blocks=$this->__etcBlocks($stay_schedule_block_num);
			$rooms+=$etc_blocks;
			$schedule_block_num=count($rooms);

			$situations          =mstepJsonEncode($this->__getReservationSituations());
			$effect_rooms		 =mstepJsonEncode($effect_rooms["data"]);
			$rooms		         =mstepJsonEncode($rooms);
			$effect_room_types 	 =mstepJsonEncode($effect_room_types);
			$edit_informations 	 =mstepJsonEncode($edit_informations);
			$informations     	 =mstepJsonEncode($informations);
			$unavailable         =mstepJsonEncode($unavailable);
			$last_modified_user  =mstepJsonEncode($last_modified_user);
			$color_list          =mstepJsonEncode($color_list);
			$client_data_sessions=mstepJsonEncode(CLIENT_DATA_SESSION);
			$holidays            =mstepJsonEncode($holidays);

			$this->set(compact("informations",
							   "unavailable",
							   "schedule_block_num",
							   "situations",
							   "rooms",
							   "worker_id",
							   "effect_rooms",
							   "holidays",
							   "effect_room_types",
							   "color_list",
							   "last_edit_time",
							   "base_start_date",
							   "edit_time_expired_ms",
							   "last_start_user",
							   "last_modified_user",
							   "is_authority",
							   "base_start_ms",
							   "edit_informations",
							   "client_data_sessions",
							   "server_time_ms"));
		}

		function getSubDateApi(){

			if(!$this->isPostRequest()) exit;

			//■基準日,何ヶ月分
			$post=$this->data;
			$date=isset($post["date"]) ? $post["date"] : date("Ymd");
			$day =isset($post["day"])  ? $post["day"]  : "14";

			$end  =date("Y-m-d",strtotime($date));
			$start=date("Y-m-d",strtotime("- {$day} day",strtotime($date)));
			$stay_schedule_block_num=$this->K9MasterRoom->getScheduleBlockNum();

			$data=$this->__getInformations($start,$end,$stay_schedule_block_num);
			$reserve_ids=(isset($res_informaton["data"]["reserve_ids"])?$res_informaton["data"]["reserve_ids"]:array());
			$edit_informations=array();
			if(!empty($reserve_ids)) $edit_informations=$this->__getReserveEditInfomrations($reserve_ids);

			$unavailable=$this->__getUnavailableSchedules($start,$end);

			$informations=$data["informations"];
			$res["data"]["edit_informations"]=$edit_informations;
			$res["data"]["informations"]=$informations;
			$res["data"]["unavailable"] =$unavailable;
			Output::__outputYes($res);
		}

		function getAddDateApi(){

			if(!$this->isPostRequest()) exit;

			//■基準日,何ヶ月分
			$post  = $this->data;
			$date  = isset($post["date"]) ? $post["date"] : date("Ymd");
			$day   = isset($post["day"])  ? $post["day"]  : "14";
			$start = date("Y-m-d",strtotime($date));
			$end   = date("Y-m-d",strtotime("+ {$day} day",strtotime($date)));

			$stay_schedule_block_num=$this->K9MasterRoom->getScheduleBlockNum();

			$data=$this->__getInformations($start,$end,$stay_schedule_block_num);
			$reserve_ids=(isset($res_informaton["data"]["reserve_ids"])?$res_informaton["data"]["reserve_ids"]:array());
			$edit_informations=array();
			if(!empty($reserve_ids)) $edit_informations=$this->__getReserveEditInfomrations($reserve_ids);

			$unavailable=$this->__getUnavailableSchedules($start,$end);

			$informations=$data["informations"];
			$res["data"]["edit_informations"]=$edit_informations;
			$res["data"]["informations"]=$informations;
			$res["data"]["unavailable"] =$unavailable;
			Output::__outputYes($res);
		}

		function getDateRange(){

			if(!$this->isPostRequest()) exit;

			$post=$this->data;
			$start=(!isset($post["start"]))?date("Ymd"):$post["start"];
			$end  =(!isset($post["end"]))?date("Ymd",strtotime("+14 day",strtotime($start))):$post["end"];
			$informations=$this->__getDateRange($start,$end);
			$res["data"]["informations"]=$informations;
			Output::__outputYes($res);
		}

		function __getInformations($start,$end,$schedule_block_num){

			$reststay_latest_positon_num=$schedule_block_num;
			$informations=makeDatePeriod($start,$end);

			//■color list
			$color_list=$this->__getReservationSituations();

			//■initialize response data.
			$res=array();
			$res["informations"]=array();
			$res["last_modified_ms"]="";

			$target_range_dates =array_keys($informations);
			$start_date         =$target_range_dates[0];
			$end_date           =$target_range_dates[count($target_range_dates)-1];
			$work_normal_schedules=$this->__getSiteSchedules($this->K9DataSchedule,$start_date,$end_date);
			$work_rest_schedules  =$this->__getSiteSchedules($this->K9DataReststaySchedule,$start_date,$end_date);
			$work_schedules=array_merge($work_normal_schedules,$work_rest_schedules);

			if(empty($work_schedules)){

				$res["data"]["reserve_ids"]=array();
				$res["data"]["color_list"] =$color_list;
				$res["informations"]=$informations;
				return $res;
			}

			$reserve_data=Set::combine($work_schedules,"{n}.K9DataReservation.id","{n}.K9DataReservation");
			$reserve_ids=array_keys($reserve_data);
			$schedule_plans=$this->__getSchedulePlans($reserve_ids);

			$price_info=$this->__getPrice($reserve_ids,array(
			
				"start_date"=>$start_date,
				"end_date"  =>$end_date
			));

			$price_reststay_info=$this->__getReststayPrice(array(
			
				"start_date"=>$start_date,
				"end_date"  =>$end_date
			));

			$today=date("Ymd");
			$reststay_position_counter=array();
			$total_reservation_price=array();
			foreach($work_schedules as $k=>$v){

				$staytype=$v["K9DataReservation"]["staytype"];
				$schedule_model=$this->__stayTypeModel($staytype);
				$schedule_diposit_model=$this->__stayTypeDipositModel($staytype);
				$k9_schedule_diposit=$v[$schedule_diposit_model->name];

				$k9_schedule=$v[$schedule_model->name];
				$k9_employee=$v["K9DataReservation"]["K9MasterEmployee"];
				$k9_checkoutpayment=!empty($v["K9DataReservation"]["K9DataCheckoutPayment"])?$v["K9DataReservation"]["K9DataCheckoutPayment"]:false;
				$k9_payment        =!empty($k9_checkoutpayment)?$k9_checkoutpayment["K9MasterCard"]:false;

				$k9_reservation=$v["K9DataReservation"];
				$k9_guests=$v["K9DataReservation"]["K9DataGuest"];
				$reserve_id=$k9_reservation["id"];
				$ymd=$k9_schedule["start_month_prefix"].sprintf("%02d",$k9_schedule["start_day"]);
				if(!isset($reststay_position_counter[$ymd])) $reststay_position_counter[$ymd]=$schedule_block_num;

				$count=count($informations[$ymd]);
				$schedule_id=$k9_schedule["id"];

				if($staytype==$this->K9DataReststaySchedule->stayType) $schedule_id="_{$schedule_id}";

				//reservation.
				/*==================================================================*/
				$is_checkin =strtotime($k9_reservation["checkin_time"])>=0?true:false;
				$is_checkout=strtotime($k9_reservation["checkout_time"])>=0?true:false;

				$informations[$ymd][$schedule_id]["reservation"]["id"]          =$k9_reservation["id"];
				$informations[$ymd][$schedule_id]["reservation"]["booking_id"]  =$k9_reservation["booking_id"];
				$informations[$ymd][$schedule_id]["reservation"]["status"]      =$k9_reservation["status"];
				$informations[$ymd][$schedule_id]["reservation"]["remarks"]     =escapeJsonString($k9_reservation["remarks"]);
				$informations[$ymd][$schedule_id]["reservation"]["edit_user_id"]=$k9_reservation["edit_user_id"];
				$informations[$ymd][$schedule_id]["reservation"]["hash"]        =$k9_reservation["hash"];
				$informations[$ymd][$schedule_id]["reservation"]["is_checkin"]  =$is_checkin;
				$informations[$ymd][$schedule_id]["reservation"]["is_checkout"] =$is_checkout;
				$informations[$ymd][$schedule_id]["reservation"]["staytype"]    =$staytype;
				$informations[$ymd][$schedule_id]["reservation"]["staff"]       =escapeJsonString($k9_employee["first_name"]);
				$informations[$ymd][$schedule_id]["reservation"]["payment"]     =$k9_payment?"{$k9_payment["type"]}({$k9_payment["card_type"]})":"none";

				$checkin_time=($is_checkin)?localDatetime($k9_reservation["checkin_time"]):0;
				$informations[$ymd][$schedule_id]["reservation"]["checkin_time"] =$checkin_time;
				$checkout_time=($is_checkout)?localDatetime($k9_reservation["checkout_time"]):0;
				$informations[$ymd][$schedule_id]["reservation"]["checkout_time"]=$checkout_time;

				//about schedule color.
				$schedule_color_status=$this->__getCheckinStatus($is_checkin,$is_checkout);
				$schedule_color=$color_list[$schedule_color_status];

				//if diposit is paid in advance.(after the day)
				//colors of checkin and checkout is high priority.
				$is_diposit=(count($k9_schedule_diposit)>0);
				if(!empty($is_diposit)) $schedule_color=$color_list[K9MasterCheckinColor::$DIPOSIT];

				/*==================================================================*/

				//room.
				/*==================================================================*/
				$k9_plans=$this->__getPainByYmd($schedule_plans[$reserve_id],$ymd);

				$room_id     =$k9_plans["room_id"];
				$room_type_id=$k9_plans["room_type_id"];
				$room_type   =$k9_plans["room_type"];
				$room_floor  =$k9_plans["room_floor"];

				switch($staytype){
				
				case($this->K9DataSchedule->stayType):

					$pricedetails=$this->__getPriceParYmd($ymd,$price_info,array("room_id"=>$room_id,"room_type_id"=>$room_type_id));
					$price  =$pricedetails["price"];
					$status =$pricedetails["status"];
					$data_id=$pricedetails["data_id"];
					break;

				case($this->K9DataReststaySchedule->stayType):

					$pricedetails=$this->__getRestPriceParYmd($ymd,$price_reststay_info);
					$price  =$pricedetails["price"];
					$status =K9RestPriceController::$PRICE_RESTSTAY;
					$data_id=$pricedetails["data_id"];
					break;
				}

				$this->__updateWithWeekdayOrWeekend($price,$status,$k9_reservation,$ymd);

				$informations[$ymd][$schedule_id]["room"]["room_price"]        =(Double)$price;
				$informations[$ymd][$schedule_id]["room"]["room_price_status"] =$status;
				$informations[$ymd][$schedule_id]["room"]["room_price_data_id"]=$data_id;
	
				$informations[$ymd][$schedule_id]["room"]["id"]        =$room_id;
				$informations[$ymd][$schedule_id]["room"]["room_type"] =$k9_plans["room_type"];
				$informations[$ymd][$schedule_id]["room"]["room_floor"]=$k9_plans["room_floor"];
				$informations[$ymd][$schedule_id]["room"]["room_num"]  =$k9_plans["room_num"];

				$informations[$ymd][$schedule_id]["room"]["room_type_id"]      =$k9_plans["room_type_id"];
				$informations[$ymd][$schedule_id]["room"]["room_situation_id"] =$k9_plans["room_situation_id"];
				$informations[$ymd][$schedule_id]["room"]["room_situation"]    =$k9_plans["room_situation"];
				$informations[$ymd][$schedule_id]["room"]["type"]              =$k9_plans["type"];
				/*==================================================================*/

				//schedule.
				/*==================================================================*/
				$informations[$ymd][$schedule_id]["schedule"]["id"]                =$schedule_id;
				$informations[$ymd][$schedule_id]["schedule"]["remarks"]           =escapeJsonString($k9_schedule["remarks"]);
				$informations[$ymd][$schedule_id]["schedule"]["start_month_prefix"]=$k9_schedule["start_month_prefix"];
				$informations[$ymd][$schedule_id]["schedule"]["start_day"]         =$k9_schedule["start_day"];
				$informations[$ymd][$schedule_id]["schedule"]["start_date_ms"]     =strtotime($ymd);
				$informations[$ymd][$schedule_id]["schedule"]["color"]             =$schedule_color["bgcolor"];
				$informations[$ymd][$schedule_id]["schedule"]["fontcolor"]         =$schedule_color["fontcolor"];

				//休憩の場合は、位置指定せず上から積み重ねる
				$informations[$ymd][$schedule_id]["schedule"]["position_num"]      =($staytype=="stay")?$k9_plans["room_position"]:$reststay_position_counter[$ymd]++;
				/*==================================================================*/

				//guests.
				/*==================================================================*/
				$informations[$ymd][$schedule_id]["guest"]["first_name"] =escapeJsonString($k9_guests["first_name"]);
				$informations[$ymd][$schedule_id]["guest"]["middle_name"]=escapeJsonString($k9_guests["middle_name"]);
				$informations[$ymd][$schedule_id]["guest"]["last_name"]  =escapeJsonString($k9_guests["last_name"]);
				$informations[$ymd][$schedule_id]["guest"]["remarks"]    =escapeJsonString($k9_guests["remarks"]);
				$informations[$ymd][$schedule_id]["guest"]["id"]         =$k9_guests["id"];
				$informations[$ymd][$schedule_id]["guest"]["hash"]       =$k9_guests["hash"];
				/*==================================================================*/

				//guests.
				/*==================================================================*/
				$informations[$ymd][$schedule_id]["payment"]["is_purchase"]=($k9_checkoutpayment["purchase_flg"]?1:0);
				/*==================================================================*/
			}

			$res=array();
			$res["data"]["reserve_ids"]=$reserve_ids;
			$res["data"]["color_list"] =$color_list;
			$res["informations"]=$informations;
			return $res;
		}

		function __updateWithWeekdayOrWeekend(&$price,&$status,$k9_reservation,$ymd){

			switch(true){
			
			case(!empty($k9_reservation["weekend_price"]) AND in_array(date("w",strtotime($ymd)),array(0,6))):
				$price=$k9_reservation["weekend_price"];
				$status=K9PriceController::$PRICE_WEEKEND_FORCE;
				break;
			case(!empty($k9_reservation["weekday_price"]) AND in_array(date("w",strtotime($ymd)),array(1,2,3,4,5))):
				$price=$k9_reservation["weekday_price"];
				$status=K9PriceController::$PRICE_WEEKDAY_FORCE;
				break;
			}
		}

		function __getDateRange($start,$end){

			$stay_schedule_block_num=$this->K9MasterRoom->getScheduleBlockNum();

			$data=$this->__getInformations($start,$end,$stay_schedule_block_num);
			$informations=$data["informations"];
			if(empty($informations)) $informations=$this->__makeDatePeriod($start,$end);
			return $informations;
		}

		function __getReserveEditInfomrations($reserve_ids=array()){

			$this->K9DataReservation->unbindFully();
			$data=$this->K9DataReservation->findAllByIdAndDelFlg($reserve_ids,0);
			$data=Set::combine($data,"{n}.K9DataReservation.id","{}.K9DataReservation");
			$instance=ScheduleGetLastEditReservationUser::getInstance($this);
			$instance->setReservationInformations($data);
			$edit_informations=$instance->getEditUsersInformations();
			return $edit_informations;
		}

		// for class.
		function __getLastModifiedInformations($last_edit_user_id){

			$master_user=$this->K9MasterEmployee->findById($last_edit_user_id);
			$last_modified_user["user"]["first_name"] =stripslashes($master_user["K9MasterEmployee"]["first_name"]);
			$last_modified_user["user"]["middle_name"]=stripslashes($master_user["K9MasterEmployee"]["middle_name"]);
			$last_modified_user["user"]["last_name"]  =stripslashes($master_user["K9MasterEmployee"]["last_name"]);
			$last_modified_user["user"]["other_name"] =stripslashes($master_user["K9MasterEmployee"]["other_name"]);
			return $last_modified_user;
		}

		function __checkAuthorityToEdit($user_id){

			$time_key=$this->Session->read(TimeoutInvestigationKeys::makeTimeSesKey(UNIQUE_KEY));
			App::uses("K9SiteManagesEditAuthoritiesController","Controller");
			$controller=new K9SiteManagesEditAuthoritiesController();
			$is_edit=$controller->__checkAuthorityToEditWithDeadline($user_id,$time_key);
			return $is_edit;
		}

		function __getEffectRoomTypes()
		{
			$lang=Configure::read('Config.language');
			$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
			if(!$data=$this->K9MasterRoomType->getEffectRoomTypes(false)) return array();
			return Set::combine($data,"{n}.K9MasterRoomType.id","{n}.K9MasterRoomType.{$roomtype_name}");
		}

		function __getRestPriceParYmd($ymd,$price_reststay_infos){

			//endが存在しない場合はそれ以上設定されてない
			$price_reststay_info=array_shift($price_reststay_infos);
			if(!isset($price_reststay_info["date"]["end"])){

				$res["data_id"]=$price_reststay_info["data"]["data_id"];
				$res["price"]  =$price_reststay_info["price"];
				return $res;
			}

			$start=$price_reststay_info["date"]["start"];
			$end  =$price_reststay_info["date"]["end"];
			if($ymd>=$start AND $end>=$ymd){

				$res["data_id"]=$price_reststay_info["data"]["data_id"];
				$res["price"]  =$price_reststay_info["price"];
				return $res;
			} 

			return $this->__getRestPriceParYmd($ymd,$price_reststay_infos);
		}

		function __getCheckinStatus($is_checkin,$is_checkout){

			App::uses("K9ColorController", "Controller");
			$controller=new K9ColorController();
			$controller->beforeFilter();
			$color=$controller->__getCheckinStatus($is_checkin,$is_checkout);
			return $color;
		}

		function __getReststayPrice($params=array()){

			$controller=new K9RestPriceController();
			$res=$controller->__getReststayPrice($params);
			return $res;
		}

		function __getPrice($reserve_ids=array(),$params=array()){

			$controller=new K9PriceController();
			$res=$controller->__getPrice($reserve_ids,$params);
			return $res;
		}

		function __getCambodiaHolidaysForYear($base_year){

			App::uses("K9GoogleHolidaysController", "Controller");
			$controller = new K9GoogleHolidaysController();
			$controller->beforeFilter();
			$res =$controller->__getCambodiaHolidaysForYear($base_year);
			$data=$controller->__convertFormatHolidays($res);
			return $data;
		}
}
