<?php

App::uses("K9PriceController","Controller");
App::uses("K9SiteController","Controller");
class AdK9CheckPriceRateController extends AppController {

		var $name = 'K9CheckPriceRate';
		var $uses = [

			"K9DataPriceParRoom",
			"K9DataPriceRoomType",
			"K9MasterRoomType",
			"K9MasterRoom",
			"K9DataSchedule",
			"K9DataReservation",
			"K9DataGuest",
			"K9DataHistoryPriceRoomType",
			"K9DataHistoryPriceRoom"
		];

		public function beforeFilter() {

			parent::beforeFilter();
		}

		function checkPriceRate()
		{
			
			if(!$this->isPostRequest()) exit;
			//$post=$this->__getTestPostData();

			$post=$this->data;
			$type=$post["type"];

			//v($post);
		//	v($type);
			switch($type){
			case("room"):

				$res=$this->__checkPriceRateRoom($post);
				////v($res);
				break;

			case("room-type"):

				$res=$this->__checkPriceRateRoomType($post);
				break;

			default:

				$res["status"]=false;
				$res["error"]=__("登録処理に失敗しました(1)");
				break;
			}

			$res["data"]=$res;
			Output::__outputYes($res);
		}

		function __checkPriceRateRoom($data)
		{
			$data_id=$data["data_id"];
			$res=$this->__getPriceRateRoom($data_id);
			$start=date("Ymd",strtotime($res["K9DataPriceParRoom"]["start"]));
			$end  =date("Ymd",strtotime($res["K9DataPriceParRoom"]["end"]));
			$room_id=$res["K9MasterRoom"]["id"];
			$schedules=$this->__getSchedulesRangeForRoom(array(
			
				"room_id"=>$room_id,
				"start"  =>$start,
				"end"    =>$end
			));

			if(empty($schedules)) return array();

			return $this->__findScheduleData($schedules,array($room_id));
		}

		function __findScheduleData($schedules,$room_ids=array()){

			$reserve_ids=Set::extract($schedules,"{}.K9DataReservation.id");
			$schedule_plans=$this->__getSchedulePlans($reserve_ids);

			$start_schedule=$schedules[0]["K9DataSchedule"];
			$last_schedule =$schedules[count($schedules)-1]["K9DataSchedule"];
			$start_date=$start_schedule["start_month_prefix"].sprintf("%02d",$start_schedule["start_day"]);
			$last_date=$last_schedule["start_month_prefix"].sprintf("%02d",$last_schedule["start_day"]);

			$price_info=array();
			$price_info=$this->__getPrice($reserve_ids,array(
			
				"start_date"=>$start_date,
				"end_date"  =>$last_date
			));

			$data=array();
			foreach($schedules as $k=>$v){
			
				if(1>count($v["K9DataReservation"]["K9DataSchedulePlan"])) continue;

				$k9_schedule=$v["K9DataSchedule"];
				$k9_reservation=$v["K9DataReservation"];
				$k9_plan=$v["K9DataReservation"]["K9DataSchedulePlan"];
				$k9_guest=$v["K9DataReservation"]["K9DataGuest"];

				$reserve_id=$k9_reservation["id"];
				$ymd=$k9_schedule["start_month_prefix"].sprintf("%02d",$k9_schedule["start_day"]);
				$schedule_id=$k9_schedule["id"];
	
				$__schedule_plans=$schedule_plans[$reserve_id];
				$k9_plans=$this->__getPainByYmd($schedule_plans[$reserve_id],$ymd);

				if(!empty($room_ids) AND !in_array($k9_plans["room_id"],$room_ids)) continue;

				$priceinfo=$this->__getPriceParYmd($ymd,$price_info,array(

					"room_id"     =>$k9_plans["room_id"],
					"room_type_id"=>$k9_plans["room_type_id"],
				));

				if(!isset($data[$reserve_id])) $data[$reserve_id]=array();
				if(!isset($data[$reserve_id]["guest"])){

					$data[$reserve_id]["guest"]["id"]=$k9_guest["id"];
					$data[$reserve_id]["guest"]["first_name"] =$k9_guest["first_name"];
					$data[$reserve_id]["guest"]["middle_name"]=$k9_guest["middle_name"];
					$data[$reserve_id]["guest"]["last_name"]  =$k9_guest["last_name"];
					$data[$reserve_id]["guest"]["remarks"]    =$k9_guest["remarks"];
				} 

				if(!isset($data[$reserve_id]["reservation"])){
				
					$data[$reserve_id]["reservation"]["id"]=$k9_reservation["id"];
					$data[$reserve_id]["reservation"]["color_id"]=$k9_reservation["color_id"];
				} 

				switch(true){
				
				case($weekend_price=weekendPrice($k9_reservation["weekend_price"],$ymd)):
					$price=$weekend_price;
					$status=K9PriceController::$PRICE_WEEKEND_FORCE;
					break;
				case($weekday_price=weekdayPrice($k9_reservation["weekday_price"],$ymd)):
					$price=$weekday_price;
					$status=K9PriceController::$PRICE_WEEKDAY_FORCE;
					break;
				default:
					$price=$priceinfo["price"];
					$status=$priceinfo["status"];
					break;
				}
	
				$ymd=$k9_schedule["start_month_prefix"].sprintf("%02d",$k9_schedule["start_day"]);
				$data[$reserve_id]["schedule"][$ymd]["schedule_id"]        =$k9_schedule["id"];
				$data[$reserve_id]["schedule"][$ymd]["price"]["price"]     =$price;
				$data[$reserve_id]["schedule"][$ymd]["price"]["data_id"]   =$priceinfo["data_id"];
				$data[$reserve_id]["schedule"][$ymd]["price"]["base_price"]=$priceinfo["base_price"];
				$data[$reserve_id]["schedule"][$ymd]["price"]["status"]    =$status;
			}

			return $data;
		}

		function __getPriceRateRoom($data_id){

			$w=null;
			$w["and"]["K9DataPriceParRoom.id"]=$data_id;
			$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation")));
			if(!$data=$this->K9DataPriceParRoom->find("first",array("recursive"=>2,"conditions"=>$w))) throw new Exception(__("正常に処理が終了しませんでした"));
			return $data;
		}

		function __getPriceRateRoomType($data_id){

			$w=null;
			$w["and"]["K9DataPriceRoomType.id"]=$data_id;
			$this->K9MasterRoomType->unbindModel(array("belongsTo"=>"K9DataHistoryPriceRoomType"));
			$this->K9MasterRoomType->bindModel(array("hasMany"=>array("K9MasterRoom"=>array("className"=>"K9MasterRoom","foreignKey"=>"room_type_id"))));
			if(!$data=$this->K9DataPriceRoomType->find("first",array("recursive"=>2,"conditions"=>$w))) throw new Exception(__("正常に処理が終了しませんでした"));
			return $data;
		}

		function __checkPriceRateRoomType($data)
		{
			$data_id=$data["data_id"];
			$res=$this->__getPriceRateRoomType($data_id);
			$start=date("Ymd",strtotime($res["K9DataPriceRoomType"]["start"]));
			$end  =date("Ymd",strtotime($res["K9DataPriceRoomType"]["end"]));
			$room_ids=Set::extract($res["K9MasterRoomType"]["K9MasterRoom"],"{}.id");
			$schedules=$this->__getSchedulesRangeForRoom(array(
			
				"room_id"=>$room_ids,
				"start"  =>$start,
				"end"    =>$end
			));

			if(empty($schedules)) return array();
			return $this->__findScheduleData($schedules,$room_ids);
		}

		function __getSchedulesRangeForRoom($params=array()){

			$this->K9DataReservation->bindModel(array("hasMany"=>array(
			
				"K9DataSchedulePlan"=>array(
					"className" =>"K9DataSchedulePlan",
					"foreignKey"=>"reserve_id",
					"conditions"=>array("K9DataSchedulePlan.room_id"=>$params["room_id"]),
					"order"=>array("K9DataSchedulePlan.start ASC")
				)
			)));

			$schedules=$this->K9DataSchedule->scheduleByYm($params["start"],$params["end"],array(
			
				"del_flg"  =>0,
				"recursive"=>2,
				"order"=>array("K9DataSchedule.start_month_prefix ASC","K9DataSchedule.start_day ASC")
			));

			return $schedules;
		}

		function __getPrice($reserve_ids=array(),$params=array()){
		
			$controller=new K9PriceController();
			$res=$controller->__getPrice($reserve_ids,$params);
			return $res;
		}
		
		function __getSchedulePlans($reserve_ids=array()){
		
			$controller=new K9SiteController();
			$res=$controller->__getSchedulePlans($reserve_ids);
			return $res;
		}
		
		function __getPainByYmd($data,$check_ymd){
		
			$controller=new K9SiteController();
			$res=$controller->__getPainByYmd($data,$check_ymd);
			return $res;
		}
		
		function __getPriceParYmd($ymd,$price_info,$params=array()){
		
			$controller=new K9SiteController();
			$res=$controller->__getPriceParYmd($ymd,$price_info,$params);
			return $res;
		}

}
