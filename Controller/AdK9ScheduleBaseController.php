<?php

class AdK9ScheduleBaseController extends AppController{

	function beforeFilter() {

		parent::beforeFilter();
	}

	function beforeRender() {

		parent::beforeRender();
	}

	function __getBasePrice($ymd,$price_info=array(),$params=array()){

		$room_id       =$params["room_id"];
		$room_type_id  =$params["room_type_id"];

		$base_room_type_price=$price_info["base_room_type_price"];
		$base_room_price=$price_info["base_room_price"];
		$room_type=$price_info["room_type"];
		$room=$price_info["room"];

		$base_room_dates=array_keys($base_room_price[$room_id]);
		$base_room_last_date=$base_room_dates[count($base_room_dates)-1];
		$base_price=0;

		// from room.
		switch(true){
		
		case(isset($base_room_price[$room_id][$ymd]) AND !empty($base_room_price[$room_id][$ymd]["price"])):

			$base_price=$base_room_price[$room_id][$ymd];
			return $base_price;
			break;

		case(!empty($base_room_price[$room_id][$base_room_last_date]["price"])):

			$base_price=$base_room_price[$room_id][$base_room_last_date];
			return $base_price;
			break;
		}

		$base_room_type_dates=array_keys($base_room_type_price[$room_type_id]);
		$base_room_type_last_date=$base_room_type_dates[count($base_room_type_dates)-1];
	
		// from room_type
		switch(true){
		
		case(isset($base_room_type_price[$room_type_id][$ymd]) AND !empty($base_room_type_price[$room_type_id][$ymd]["price"])):

			$base_price=$base_room_type_price[$room_type_id][$ymd];
			return $base_price;
			break;

		case(!empty($base_room_type_price[$room_type_id][$base_room_type_last_date]["price"])):

			$base_price=$base_room_type_price[$room_type_id][$base_room_type_last_date];
			return $base_price;
			break;
		}

		return $base_price;
	}

	function __getPriceParYmd($ymd,$price_info,$params=array()){

		$room_id       =$params["room_id"];
		$room_type_id  =$params["room_type_id"];

		$base_price=$this->__getBasePrice($ymd,$price_info,$params);

		$room_type=$price_info["room_type"];
		$room=$price_info["room"];

		switch(true){
		
		case(isset($room[$room_id][$ymd]) AND !empty($room[$room_id][$ymd]["rate_par"])):

			$real_price=round($base_price["price"]*($room[$room_id][$ymd]["rate_par"]/100),1);

			$res["price"]  =$real_price;
			$res["par"]    =$room[$room_id][$ymd]["rate_par"];
			$res["data_id"]=$room[$room_id][$ymd]["data_id"];
			$res["status"] =$room[$room_id][$ymd]["status"];
			$res["price_base_status"]=$room[$room_id][$ymd]["price_base_status"];
			$res["base_price"]=$base_price["price"];

			return $res;
			break;

		case(isset($room[$room_id][$ymd]) AND !empty($room[$room_id][$ymd]["rate_price"])):

			$real_price=$room[$room_id][$ymd]["rate_price"];
			$res["price"]  =$real_price;
			$res["par"]    =0;
			$res["data_id"]=$room[$room_id][$ymd]["data_id"];
			$res["status"] =$room[$room_id][$ymd]["status"];
			$res["price_base_status"]=$room[$room_id][$ymd]["price_base_status"];
			$res["base_price"]=$base_price["price"];
			return $res;
			break;

		case(isset($room_type[$room_type_id][$ymd]) AND !empty($room_type[$room_type_id][$ymd]["rate_par"])):

			$real_price=round($base_price["price"]*($room_type[$room_type_id][$ymd]["rate_par"]/100),1);
			$res["price"]  =$real_price;
			$res["par"]    =$room_type[$room_type_id][$ymd]["rate_par"];
			$res["data_id"]=$room_type[$room_type_id][$ymd]["data_id"];
			$res["status"] =$room_type[$room_type_id][$ymd]["status"];
			$res["price_base_status"]=$room_type[$room_type_id][$ymd]["price_base_status"];
			//$res["base_price"]=$room[$room_id][$ymd]["base_price"];
			$res["base_price"]=$base_price["price"];
			return $res;
			break;

		case(isset($room_type[$room_type_id][$ymd]) AND !empty($room_type[$room_type_id][$ymd]["rate_price"])):

			//v(__LINE__);
			$real_price=$room_type[$room_type_id][$ymd]["rate_price"];
			$res["price"]  =$real_price;
			$res["par"]    =0;
			$res["data_id"]=$room_type[$room_type_id][$ymd]["data_id"];
			$res["status"] =$room_type[$room_type_id][$ymd]["status"];
			$res["price_base_status"]=$room_type[$room_type_id][$ymd]["price_base_status"];
			//$res["base_price"]=$room[$room_id][$ymd]["base_price"];
			$res["base_price"]=$base_price["price"];
			return $res;
			break;
		}

		$res["price"]=$base_price["price"];
		$res["par"]  =0;
		$res["data_id"]=$base_price["data_id"];
		$res["status"]=$base_price["status"];
		$res["price_base_status"]=$base_price["price_base_status"];
		$res["base_price"]=$base_price["base_price"];
		return $res;
	}

	function __getPainByYmd($data,$check_ymd){

		$starts=array();
		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";

		foreach($data as $k=>$v){
		
			$plan=isset($v["K9DataSchedulePlan"])?$v["K9DataSchedulePlan"]:$v;

			$reserve_id=$plan["reserve_id"];
			$plan_id   =$plan["id"];
			$room_id   =$plan["room_id"];
			$remarks   =$plan["remarks"];
			$room_num  =$v["K9MasterRoom"]["room_num"];
			$room_floor=$v["K9MasterRoom"]["floor"];
			$type      =$v["K9MasterRoom"]["type"];
			$room_remark=$v["K9MasterRoom"]["remarks"];
			$room_type =$v["K9MasterRoom"]["K9MasterRoomType"][$roomtype_name];
			$room_type_id=$v["K9MasterRoom"]["K9MasterRoomType"]["id"];
			$room_current_situation_id=$v["K9MasterRoom"]["situation_id"];
			$room_current_situation=$v["K9MasterRoom"]["K9MasterRoomSituation"]["situation"];
			$room_position=$v["K9MasterRoom"]["position"];

			$ymd=date("Ymd",strtotime($plan["start"]));
			$starts[$ymd]["id"]=$plan_id;
			$starts[$ymd]["reserve_id"]=$reserve_id;
			$starts[$ymd]["remarks"]=$remarks;
			$starts[$ymd]["room_id"]=$room_id;
			$starts[$ymd]["room_num"]=$room_num;
			$starts[$ymd]["room_floor"]=$room_floor;
			$starts[$ymd]["room_remark"]=$room_remark;
			$starts[$ymd]["room_type"]=$room_type;
			$starts[$ymd]["room_type_id"]=$room_type_id;
			$starts[$ymd]["room_situation"]=$room_current_situation;
			$starts[$ymd]["room_situation_id"]=$room_current_situation_id;
			$starts[$ymd]["room_position"]    =$room_position;
			$starts[$ymd]["type"]    =$type;
		}

		ksort($starts);

		$dates=array_keys($starts);
		$start=$dates[0];
		$end  =$dates[count($starts)-1];
		if($start>$check_ymd){

			throw new Exception("it's wrong data.");
		} 

		if($check_ymd==$start) return $starts[$start];
		if($check_ymd>=$end)   return $starts[$end];

		for($i=0;$i<count($dates);$i++){

			$__start=$dates[$i];
			$__next =$dates[$i+1];
			if(($check_ymd>=$__start) AND ($__next>$check_ymd)) break;
		}

		return $starts[$__start];
	}

	function __getSchedulePlans($reserve_ids=array()){

		$w=null;
		$w["and"]["K9DataSchedulePlan.reserve_id"]=$reserve_ids;
		$w["and"]["K9DataSchedulePlan.del_flg"]=0;
		$data=$this->K9DataSchedulePlan->find("all",array( "conditions"=>$w,"recursive"=>3 ));

		$res=array();
		foreach($data as $k=>$v){
		
			$reserve_id=$v["K9DataSchedulePlan"]["reserve_id"];
			$res[$reserve_id][]=$v;
		}

		return $res;
	}

	protected function __getSiteSchedules(Model $schedule_model,$start_date,$end_date){

		$reservation_association=$this->K9DataReservation->association;
		$reservation_employee=$reservation_association["belongsTo"]["K9MasterEmployee"];

		$reservation_paymenttype=$reservation_association["hasOne"]["K9DataCheckoutPayment"];
		$reservation_paymenttype["conditions"]["del_flg"]=0;

		$diposit_model=$this->__stayTypeDipositModel($schedule_model->stayType);

		$this->K9DataCheckoutPayment->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataReservation->bindModel(array("hasOne"=>array("K9DataCheckoutPayment"=>$reservation_paymenttype)));
		$this->K9DataReservation->bindModel(array("belongsTo"=>array("K9MasterEmployee"=>$reservation_employee)));
		$diposit_model->unbindModel(array("belongsTo"=>array($schedule_model->name,"K9MasterCard","K9MasterDipositReason","K9MasterEmployee")));
		$order=array("{$schedule_model->name}.start_month_prefix ASC","{$schedule_model->name}.start_day ASC");
		return $schedule_model->scheduleByYm($start_date,$end_date,array(
		
			"del_flg"  =>0,
			"recursive"=>3,
			"order"    =>$order
		));
	}

	protected function __getReservationSituations()
	{
		$lang=Configure::read('Config.language');
		$name=$this->K9MasterCheckinColor->hasField("type_{$lang}")?"type_{$lang}":"type_eng";
		$fields=array("K9MasterCheckinColor.type","K9MasterCheckinColor.{$name} as type_name","K9MasterCheckinColor.bgcolor","K9MasterCheckinColor.fontcolor");
		$this->K9MasterCheckinColor->unbindFully();
		$data=$this->K9MasterCheckinColor->find("all",array( "fields"=>$fields ));
		if(empty($data)) return array();

		$list=array();
		foreach($data as $k=>$v){
		
			$list[$v["K9MasterCheckinColor"]["type"]]["type"]=escapeJsonString($v["K9MasterCheckinColor"]["type_name"]);
			$list[$v["K9MasterCheckinColor"]["type"]]["bgcolor"]=$v["K9MasterCheckinColor"]["bgcolor"];
			$list[$v["K9MasterCheckinColor"]["type"]]["fontcolor"]=$v["K9MasterCheckinColor"]["fontcolor"];
		}

		return $list;
	}

	protected function __getUnavailableSchedules($start,$end)
	{
		$data=$this->K9DataUnavailableRoom->getAllDataBasedonRange($start,$end,array("format"=>"%Y-%m-%d"));
		if(empty($data)) return array();
		return $this->__getUnavailableEachSchedule($data);
	}

	private function __getUnavailableEachSchedule($data=array(),$res=array())
	{
		if(empty($data)) return $res;
		$__data=array_shift($data);

		$start=$__data["K9DataUnavailableRoom"]["start_date"];
		$end  =$__data["K9DataUnavailableRoom"]["end_date"];
		$period=makeDatePeriod($start,$end);

		$lang=Configure::read('Config.language');
		$situation=$this->K9MasterRoomSituation->hasField("situation_{$lang}")?"situation_{$lang}":"situation";

		foreach($period as $date=>$v){

			if(!isset($res[$date])) $res[$date]=array();
			$res[$date][$__data["K9DataUnavailableRoom"]["id"]]["data"]["room_id"]=$__data["K9DataUnavailableRoom"]["room_id"];
			$res[$date][$__data["K9DataUnavailableRoom"]["id"]]["data"]["reason_id"]=$__data["K9DataUnavailableRoom"]["reason_id"];
			$res[$date][$__data["K9DataUnavailableRoom"]["id"]]["data"]["remarks"]=$__data["K9DataUnavailableRoom"]["remarks"];
			$res[$date][$__data["K9DataUnavailableRoom"]["id"]]["situation"]["situation"]=$__data["K9MasterRoomSituation"][$situation];
			$res[$date][$__data["K9DataUnavailableRoom"]["id"]]["situation"]["bgcolor"]=$__data["K9MasterRoomSituation"]["bgcolor"];
			$res[$date][$__data["K9DataUnavailableRoom"]["id"]]["situation"]["fontcolor"]=$__data["K9MasterRoomSituation"]["fontcolor"];
		}

		return $this->__getUnavailableEachSchedule($data,$res);
	}

	protected function __getEffectRooms($target_date){

		App::uses("K9RoomSituationsController","Controller");
		$controller=new K9RoomSituationsController();
		$controller->loadModel("K9MasterRoomType");
		$controller->loadModel("K9MasterRoom");
		$controller->loadModel("K9MasterRoomSituation");
		$res=$controller->__getEffectRooms($target_date);
		return $res;
	}

	protected function __etcBlocks($schedule_block_num)
	{
		$list=array();

		$type="etc";
		$etc_block_nums=$schedule_block_num*ETC_BLOVK_NUM;
		for($i=0;$i<$etc_block_nums;$i++){

			$list["_{$i}"]["room_num"]="E{$i}";
			$list["_{$i}"]["type"]=$type;
		}

		return $list;
	}


}

?>
