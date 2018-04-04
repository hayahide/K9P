<?php

App::uses("K9SiteController", "Controller");
class AdK9PriceFinalController extends AppController {

	var $name = 'K9Price';
	var $uses = [

		"K9DataSchedule",
		"K9DataPriceParRoom",
		"K9DataPriceRoomType",
		"K9MasterRoomType",
		"K9DataReservation",
		"K9DataSchedulePlan",
		"K9DataHistoryPriceRoomType",
		"K9DataHistoryPriceRoom"
	];

	var $statusUseModels=array(
	
		"1"=>"K9DataHistoryPriceRoom",
		"2"=>"K9DataHistoryPriceRoomType",
		"3"=>"K9DataPriceParRoom",
		"4"=>"K9DataPriceRoomType"
	);

	public function beforeFilter() {

		parent::beforeFilter();
	}

	// status1 : k9_data_room_price_history
	// status2 : k9_data_room_type_price_history
	// status3 : k9_data_price_par_rooms 
	// status4 : k9_data_price_room_types

	function totalPriceForReservation(){

		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post["reserve_id"]=1;
		$reserve_id=$post["reserve_id"];

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$reservation_data=$this->K9DataReservation->getReservationWhenFinal($reserve_id);
		$price_values=$this->__priceValues($reserve_id,$reservation_data);
		$total_value=$price_values["total"];
		$price_values=$price_values["price_values"];

		$status_info=array();
		foreach($price_values as $ymd=>$v){

			$count=isset($status_info[$v["price_base_status"]])?count($status_info[$v["price_base_status"]]):0;
			$status_info[$v["price_base_status"]][$count]["data_id"]    =$v["data_id"];
			$status_info[$v["price_base_status"]][$count]["schedule_id"]=$v["schedule_id"];
			$status_info[$v["price_base_status"]][$count]["status"]=$v["status"];
		} 

		$price_details=$this->__priceDetailForStatus($status_info);
		if(empty($price_details)) throw new Exception("price detail is nothing ".__FUNCTION__);

		foreach($price_values as $ymd=>$v){

			$price_base_status=$v["price_base_status"];
			$data_id=$v["data_id"];
			$price_values[$ymd]["price_detail"]=$price_details[$price_base_status][$data_id];
		}

		$res["price_values"]=$price_values;
		$res["total_value"]=$total_value;
		$res["priority_price"]=$reservation_data["K9DataReservation"]["priority_price"];
		Output::__outputYes($res);
	}

	function __priceDetailForStatus($status_info){

		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
	
		$res=array();
		foreach($status_info as $status=>$values){

			$ids=Set::extract($values,"{}.data_id");
			$useModel=$this->statusUseModels[$status];
			$model=$this->{$useModel};

			$w=null;
			$w["{$model->name}.id"]=$ids;
			$data=$model->find("all",array(
			
				"conditions"=>$w,
				"recursive"=>2
			));

			switch($status){

			case(1):

				foreach($data as $k=>$v){
				
					$res[$status][$v["K9DataHistoryPriceRoom"]["id"]]["price"]=$v["K9DataHistoryPriceRoom"]["price"];
					$res[$status][$v["K9DataHistoryPriceRoom"]["id"]]["start"]=date("Ymd",strtotime($v["K9DataHistoryPriceRoom"]["start"]));
					$res[$status][$v["K9DataHistoryPriceRoom"]["id"]]["room_type"]=$v["K9MasterRoom"]["K9MasterRoomType"][$roomtype_name];
				}
				break;

			case(2):

				foreach($data as $k=>$v){
				
					$res[$status][$v["K9DataHistoryPriceRoomType"]["id"]]["price"]=$v["K9DataHistoryPriceRoomType"]["price"];
					$res[$status][$v["K9DataHistoryPriceRoomType"]["id"]]["start"]=date("Ymd",strtotime($v["K9DataHistoryPriceRoomType"]["start"]));
					$res[$status][$v["K9DataHistoryPriceRoomType"]["id"]]["room_type"]=$v["K9MasterRoom"]["K9MasterRoomType"][$roomtype_name];
				}
				break;

			case(3):

				foreach($data as $k=>$v){
				
					$res[$status][$v["K9DataPriceParRoom"]["id"]]["price"]=$v["K9DataPriceParRoom"]["price"];
					$res[$status][$v["K9DataPriceParRoom"]["id"]]["start"]=date("Ymd",strtotime($v["K9DataPriceParRoom"]["start"]));
					$res[$status][$v["K9DataPriceParRoom"]["id"]]["end"]=date("Ymd",strtotime($v["K9DataPriceParRoom"]["end"]));
					$res[$status][$v["K9DataPriceParRoom"]["id"]]["room_type"]=$v["K9MasterRoom"]["K9MasterRoomType"][$roomtype_name];
				}
				break;

			case(4):

				foreach($data as $k=>$v){
				
					$res[$status][$v["K9DataPriceRoomType"]["id"]]["price"]=$v["K9DataPriceRoomType"]["price"];
					$res[$status][$v["K9DataPriceRoomType"]["id"]]["start"]=date("Ymd",strtotime($v["K9DataPriceRoomType"]["start"]));
					$res[$status][$v["K9DataPriceRoomType"]["id"]]["end"]=date("Ymd",strtotime($v["K9DataPriceRoomType"]["end"]));
					$res[$status][$v["K9DataPriceRoomType"]["id"]]["room_type"]=$v["K9MasterRoomType"][$roomtype_name];
				}
				break;
			}
		}

		return $res;
	}

	function __priceValues($reserve_id,$reservation_data=array()){

		$first_date=$reservation_data["K9DataSchedule"][count($reservation_data["K9DataSchedule"])-1];
		$last_date=$reservation_data["K9DataSchedule"][0];
		$last_ymd=$last_date["start_month_prefix"].sprintf("%02d",$last_date["start_day"]);
		$schedules=$reservation_data["K9DataSchedule"];
		$price_values=$this->__getPriceValues($reserve_id,$schedules,array(

			"start_date"=>$first_date["start_month_prefix"].sprintf("%02d",$first_date["start_day"]),
			"end_date"  =>$last_ymd,
		));
		return $price_values;
	}

	function finalActionForReservation(){

		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		$post["hash"]="fadfasdfasdfasdf";
		$hash=$post["hash"];
		$reservation_data=$this->K9DataReservation->getReservationWhenFinal($hash);
		$first_date=$reservation_data["K9DataSchedule"][count($reservation_data["K9DataSchedule"])-1];
		try{ $this->dataValudation($reservation_data);
		}catch(Exception $e){ 
	   
			$res["error"]=$e->getMessage();
			Output::__outputNo($res);
		}

		//forced.
		$employee_id=$this->Auth->user("employee_id");
		$reserve_id=$reservation_data["K9DataReservation"]["id"];
		$price_values=$this->__priceValues($reserve_id,$reservation_data);

		if(!empty($reservation_data["K9DataReservation"]["priority_price"])){
		
			$priority_price=$reservation_data["K9DataReservation"]["priority_price"];
			$this->__savePriceRecords($reserve_id,$priority_price,$price_values);
			return;
		}

		$total_value=$price_values["total"];
		if(!$this->__savePriceRecords($reserve_id,$price_values)){

			$res["error"]=4;
			Output::__outputNo($res);
		}

		Output::__outputYes();
	}

	function __savePriceRecords($reserve_id,$price_values){

		$datasource=$this->K9DataReservation->getDataSource();
		$datasource->begin();

		$employee_id=$this->Auth->user("employee_id");
		if(!$this->K9DataReservation->editLastUser($reserve_id,$employee_id)){
		
			$datasource->rollback();
			return false;
		}

		$res=$this->__saveFinalPriceInformations($price_values["price_values"]);
		if(empty($res["status"])){

			$datasource->rollback();
			return false;
		}
		
		$datasource->commit();
		return true;
	}

	function __saveFinalPriceInformations($data=array()){

		$counter=0;
		$inserts=array();
		foreach($data as $k=>$v){

			$inserts[$counter]["id"]			  =$v["schedule_id"];
			$inserts[$counter]["final_price"]     =$v["price"];
			$inserts[$counter]["final_price_type"]=$v["status"];
			$inserts[$counter++]["final_price_id"]=$v["data_id"];
		}

		try{ $this->K9DataSchedule->multiInsert($inserts);
		}catch(Exception $e){
		
			$res["error"]=$e->getMessage();
			$res["status"]=false;
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	function dataValudation($reservation_data=array()){

		if(empty($reservation_data)){

			throw new Exception(1);
		} 

		$today=date("Ymd");
		$today="20171116";
		$last_date=$reservation_data["K9DataSchedule"][0];
		$last_ymd=$last_date["start_month_prefix"].sprintf("%02d",$last_date["start_day"]);
		if($last_ymd>$today){

			throw new Exception(2);
		}

		if(!empty($reservation_data["K9DataReservation"]["final_price"])){

			throw new Exception(3);
		}

		return true;
	}

	function __getPriceValues($reserve_id,$schedules=array(),$params=array()){

		$reserve_id=(!is_array($reserve_id))?array($reserve_id):$reserve_id;
		$schedule_plans=$this->__getSchedulePlans($reserve_id);
		$price_info=$this->__getPrice($reserve_id,array(
		
			"start_date"=>$params["start_date"],
			"end_date"  =>$params["end_date"]
		));

		$total=0;
		$price_values=array();
		foreach($schedules as $k=>$v){
		
			$ymd=$v["start_month_prefix"].sprintf("%02d",$v["start_day"]);
			$reserve_id=$v["reserve_id"];
			$schedule_plan=$schedule_plans[$reserve_id];
			$k9_plans=$this->__getPainByYmd($schedule_plan,$ymd);

			$room_id     =$k9_plans["room_id"];
			$room_type_id=$k9_plans["room_type_id"];
			$room_type   =$k9_plans["room_type"];
			$foom_floor  =$k9_plans["room_floor"];

			$price=$this->__getPriceParYmd($ymd,$price_info,array(

				"room_id"     =>$room_id,
				"room_type_id"=>$room_type_id,
			));

			$price_values[$ymd]["price"]      =$price["price"];
			$price_values[$ymd]["data_id"]    =$price["data_id"];
			$price_values[$ymd]["schedule_id"]=$v["id"];
			$price_values[$ymd]["status"]     =$price["status"];
			$price_values[$ymd]["price_base_status"]=$price["price_base_status"];
			$total+=$price["price"];
		}

		$res["price_values"]=$price_values;
		$res["total"]=$total;
		return $res;
	}

	function __getSchedulePlans($reserve_ids=array()){

		$controller=new K9SiteController();
		return $controller->__getSchedulePlans($reserve_ids);
	}

	function __getPrice($reserve_ids=array(),$params=array()){

		$controller=new K9PriceController();
		$res=$controller->__getPrice($reserve_ids,$params);
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
