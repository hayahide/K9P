<?php

class AdK9SavePriceRateController extends AppController {

	var $name = 'K9SavePriceRate';
	var $uses = [

		"K9DataPriceParRoom",
		"K9DataPriceRoomType",
		"K9MasterRoomType",
		"K9MasterRoom",
		"K9DataSchedule",
		"K9DataReservation",
		"K9DataHistoryPriceRoomType",
		"K9DataHistoryPriceRoom"
	];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function savePriceRate(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$type=$post["type"];
		switch($type){
		case("room"):

			$res=$this->__savePriceRateRoom($post);
			break;

		case("room-type"):

			$res=$this->__savePriceRateRoomType($post);
			break;

		default:

			$res["status"]=false;
			$res["error"]=__("登録処理に失敗しました");
			break;
		}

		if(empty($res["status"])) Output::__outputNo(array("message"=>$res["error"]));

		$today=date("Ymd");
		if($type=="room")      $res["data"]["room"]     =$this->__getRoomPlans($today);
		if($type=="room-type") $res["data"]["room_type"]=$this->__getRoomTypePlans($today);
		Output::__outputYes($res);
	}

	function __postValidate($data){

		$today=date("Y-m-d");
		if(empty($data["start_date"]) AND empty($data["end_date"])) throw new Exception(__("日程のデータが不正です(1)"));
		if(!empty($data["par"]) AND !empty($data["price"])) throw new Exception(__("設定数値が不正です(1)"));
		if(isset($data["room_id"]) AND empty($data["room_id"])) throw new Exception(__("部屋情報が不正です(1)"));
		if(isset($data["room_type_id"]) AND empty($data["room_type_id"])) throw new Exception(__("部屋情報が不正です(2)"));
		if((!empty($data["par"]) AND !is_numeric($data["par"])) || (!empty($data["price"]) AND !is_numeric($data["price"]))) throw new Exception(__("設定数値が不正です(2)"));

		if(strtotime($today)>strtotime($data["end_date"])) throw new Exception(__("日程のデータが不正です(2)"));

		switch($data["type"]){
		
		case("room"):

			$start=date("Ymd",strtotime($data["start_date"]));
			$end=date("Ymd",strtotime($data["end_date"]));
			$this->K9DataPriceParRoom->unbindFully();
			$res=$this->K9DataPriceParRoom->getDataByRoomIdWithRelationDays($data["room_id"],$start,$end);
			if(count($res)>0){

				foreach($res as $k=>$v){

					if(isset($data["data_id"]) AND $v["K9DataPriceParRoom"]["id"]==$data["data_id"]) continue; 
					$start_ymd=explode("-",$v["K9DataPriceParRoom"]["start"]);
					$end_ymd  =explode("-",$v["K9DataPriceParRoom"]["end"]);
					$ymd_tpl  =__("<<y>>/<<m>>/<<d>>");
					$start=str_replace(array("<<y>>","<<m>>","<<d>>"),array($start_ymd[0],$start_ymd[1],$start_ymd[2]),$ymd_tpl);
					$end=str_replace(array("<<y>>","<<m>>","<<d>>"),array($end_ymd[0],$end_ymd[1],$end_ymd[2]),$ymd_tpl);
					throw new Exception(__("設定した期間に既に同じ部屋で金額設定がされています"));
				}
			} 
			break;

		case("room-type"):

			$start=date("Ymd",strtotime($data["start_date"]));
			$end=date("Ymd",strtotime($data["end_date"]));
			$this->K9DataPriceRoomType->unbindFully();
			$res=$this->K9DataPriceRoomType->getDataByRoomIdWithRelationDays($data["room_type_id"],$start,$end);
			if(count($res)>0){

				foreach($res as $k=>$v){

					if(isset($data["data_id"]) AND $v["K9DataPriceRoomType"]["id"]==$data["data_id"]) continue; 
					$start_ymd=explode("-",$v["K9DataPriceRoomType"]["start"]);
					$end_ymd  =explode("-",$v["K9DataPriceRoomType"]["end"]);
					$ymd_tpl  =__("<<y>>/<<m>>/<<d>>");
					$start=str_replace(array("<<y>>","<<m>>","<<d>>"),array($start_ymd[0],$start_ymd[1],$start_ymd[2]),$ymd_tpl);
					$end=str_replace(array("<<y>>","<<m>>","<<d>>"),array($end_ymd[0],$end_ymd[1],$end_ymd[2]),$ymd_tpl);
					throw new Exception(__("設定した期間に既に同じ部屋で金額設定がされています"));
				}
			} 
			break;

		}

		return true;
	}

	function __savePriceRateRoom($data)
	{

		try{ $this->__postValidate($data);
		}catch(Exception $e){
		
			$res["status"]=false;
			$res["error"]=$e->getMessage();
			return $res;
		}

		$start_date=empty($data["start_date"])?$data["end_date"]  :$data["start_date"];
		$end_date  =empty($data["end_date"])  ?$data["start_date"]:$data["end_date"];

		if(!empty($data["data_id"])) $save["id"]=$data["data_id"];
		$save["start"]=date("Ymd",strtotime($start_date));
		$save["end"]  =date("Ymd",strtotime($end_date));
		$save["par"]  =$data["par"];
		$save["price"]=$data["price"];
		$save["room_id"]=$data["room_id"];
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		if(!$this->K9DataPriceParRoom->save($save)){

			$res["status"]=false;
			$res["error"]=__("登録処理に失敗しました");
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	function __savePriceRateRoomType($data)
	{

		try{ $this->__postValidate($data);
		}catch(Exception $e){
		
			$res["status"]=false;
			$res["error"]=$e->getMessage();
			return $res;
		}

		$start_date=empty($data["start_date"])?$data["end_date"]  :$data["start_date"];
		$end_date  =empty($data["end_date"])  ?$data["start_date"]:$data["end_date"];

		if(!empty($data["data_id"])) $save["id"]=$data["data_id"];
		$save["start"]=date("Ymd",strtotime($start_date));
		$save["end"]  =date("Ymd",strtotime($end_date));
		$save["par"]  =$data["par"];
		$save["price"]=$data["price"];
		$save["room_type_id"]=$data["room_type_id"];
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		if(!$this->K9DataPriceRoomType->save($save)){

			$res["status"]=false;
			$res["error"]=__("登録処理に失敗しました");
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	function getPriceRate(){

		$today=date("Ymd");
		$room_plan     =$this->__getRoomPlans($today);
		$room_type_plan=$this->__getRoomTypePlans($today);

		$res["data"]["room"]=$room_plan;
		$res["data"]["room_type"]=$room_type_plan;
		Output::__outputYes($res);
	}

	function __getRoomTypePlans($date){

		$data=array();
		$room=$this->__getEffectCurrentRoomTypePlan($date);
		if(empty($room)) return $data;

		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
		
		foreach($room as $k=>$v){

			$parroom  =$v["K9DataPriceRoomType"];
			$room_type_info=$v["K9MasterRoomType"];
			$employee=$v["K9MasterEmployee"];
			$data_id=$parroom["id"];
			$data[$data_id]["data"]["id"]   =$data_id;
			$data[$data_id]["data"]["start"]=date("Ymd",strtotime($parroom["start"]));
			$data[$data_id]["data"]["end"]  =date("Ymd",strtotime($parroom["end"]));
			$data[$data_id]["data"]["price"]=$parroom["price"];
			$data[$data_id]["data"]["par"]  =$parroom["par"];
			$data[$data_id]["room"]["type"] =$room_type_info[$roomtype_name];
			$data[$data_id]["room"]["id"]   =$room_type_info["id"];
			$data[$data_id]["employee"]["id"]=$employee["id"];
			$data[$data_id]["employee"]["name"]=$employee["first_name"];
		}

		return $data;
	}

	function __getRoomPlans($date){

		$data=array();
		$room=$this->__getEffectCurrentRoomPlan($date);
		if(empty($room)) return $data;
		
		foreach($room as $k=>$v){

			$parroom  =$v["K9DataPriceParRoom"];
			$room_info=$v["K9MasterRoom"];
			$employee=$v["K9MasterEmployee"];
			$room_type_info=$v["K9MasterRoom"]["K9MasterRoomType"];
			$data_id=$parroom["id"];
			$data[$data_id]["data"]["id"]            =$data_id;
			$data[$data_id]["data"]["start"]         =date("Ymd",strtotime($parroom["start"]));
			$data[$data_id]["data"]["end"]           =date("Ymd",strtotime($parroom["end"]));
			$data[$data_id]["data"]["price"]         =$parroom["price"];
			$data[$data_id]["data"]["par"]           =$parroom["par"];
			$data[$data_id]["room"]["room_num"]      =$room_info["room_num"];
			$data[$data_id]["room"]["room_type_id"]  =$room_info["room_type_id"];
			$data[$data_id]["room"]["room_type"]     =$room_type_info["room_type"];
			$data[$data_id]["room"]["id"]            =$room_info["id"];
			$data[$data_id]["employee"]["id"]        =$employee["id"];
			$data[$data_id]["employee"]["name"]      =$employee["first_name"];
		}

		return $data;
	}

	function __getEffectCurrentRoomTypePlan($date){
	
		$this->K9MasterRoomType->unbindModel(array("belongsTo"=>array("K9DataHistoryPriceRoomType")));
		$room_type=$this->K9DataPriceRoomType->getEffectCurrentPlan($date);
		return $room_type;
	}

	function __getEffectCurrentRoomPlan($date){
	
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation")));
		$room=$this->K9DataPriceParRoom->getEffectCurrentPlan($date);
		return $room;
	}


}
