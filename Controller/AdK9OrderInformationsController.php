<?php

App::uses('K9BasePricesController','Controller');
class AdK9OrderInformationsController extends AppController{

    var $name = "K9OrderInformations";
    var $uses = [

		"K9MasterCard",
		"K9DataSchedule",
		"K9MasterFood",
		"K9MasterBeverage",
		"K9MasterSpa",
		"K9MasterTobacco",
		"K9MasterLimousine",
		"K9MasterLaundry",
		"K9MasterRoomservice",
		"K9MasterCategory",
		"K9DataReservation",
		"K9DataHistoryPriceFood",
		"K9DataHistoryPriceBeverage",
		"K9DataHistoryPriceSpa",
		"K9DataHistoryPriceTobacco",
		"K9DataHistoryPriceLaundry",
		"K9DataHistoryPriceLimousine",
		"K9DataHistoryPriceRoomservice",
		"K9DataOrderFood",
		"K9DataOrderBeverage",
		"K9DataOrderSpa",
		"K9DataOrderLimousine",
		"K9DataOrderLaundry",
		"K9DataOrderTobacco",
		"K9DataOrderRoomservice",
		"K9DataSchedule",
		"K9DataReststaySchedule"
    ];

	function beforeFilter(){
	
		parent::beforeFilter();
	}

	function getOrderInformations(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		
		$post=$this->data;
		$reservation_hash=$post["reservation_hash"];
		$date=$post["date"];
		$type=$post["type"];

		switch($type){
		case(K9MasterFood::$CATEGORY_FOOD);

			$res=$this->__informations(
				$this->K9MasterFood,
				$this->K9DataOrderFood,
				$this->K9DataHistoryPriceFood,
				$type,
				$reservation_hash,
				$date);
			break;

		case(K9MasterBeverage::$CATEGORY_DRINK);

			$res=$this->__informations(
				$this->K9MasterBeverage,
				$this->K9DataOrderBeverage,
				$this->K9DataHistoryPriceBeverage,
				$type,
				$reservation_hash,
				$date);
			break;

		case(K9MasterTobacco::$CATEGORY_TOBACCO);

			$res=$this->__informations(
				$this->K9MasterTobacco,
				$this->K9DataOrderTobacco,
				$this->K9DataHistoryPriceTobacco,
				$type,
				$reservation_hash,
				$date);
			break;

		case(K9MasterRoomservice::$CATEGORY_ROOMSERVICE);

			$res=$this->__informations(
				$this->K9MasterRoomservice,
				$this->K9DataOrderRoomservice,
				$this->K9DataHistoryPriceRoomservice,
				$type,
				$reservation_hash,
				$date);
			break;

		case(K9MasterSpa::$CATEGORY_SPA);

			$res=$this->__informations(
				$this->K9MasterSpa,
				$this->K9DataOrderSpa,
				$this->K9DataHistoryPriceSpa,
				$type,
				$reservation_hash,
				$date);
			break;

		case(K9MasterLimousine::$CATEGORY_LIMOUSINE);

			$res=$this->__informations(
				$this->K9MasterLimousine,
				$this->K9DataOrderLimousine,
				$this->K9DataHistoryPriceLimousine,
				$type,
				$reservation_hash,
				$date);
			break;

		case(K9MasterLaundry::$CATEGORY_LAUNDRY);

			$res=$this->__informations(
				$this->K9MasterLaundry,
				$this->K9DataOrderLaundry,
				$this->K9DataHistoryPriceLaundry,
				$type,
				$reservation_hash,
				$date);
			break;

		default:
			exit;
			break;
		}

		$card_types=$this->__getCardTypes();
		$res["data"]["menus"]["cash_types"]=$card_types;
		Output::__outputYes($res);
	}

	public function orderSubscribe(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$schedule_id=$post["schedule_id"];

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		//for rest stay.
		$schedule_id=toFormalScheduleId($schedule_id);

		$reservation_hash=$post["reservation_hash"];
		$order=$post["order"];
		$type =$post["type"];

		$this->K9DataReservation->unbindFully();
		$reservation=$this->K9DataReservation->getReservationByHash($reservation_hash);
		if(empty($reservation)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		switch($type){
		case(K9MasterFood::$CATEGORY_FOOD);

			$res=$this->__subscribe(
			$this->__stayTypeModel($reservation["K9DataReservation"]["staytype"]),
			$this->K9MasterFood,
			$this->K9DataOrderFood,
			$this->K9DataHistoryPriceFood,
			$schedule_id,
			$order);
			break;

		case(K9MasterBeverage::$CATEGORY_DRINK);

			$res=$this->__subscribe(
			$this->__stayTypeModel($reservation["K9DataReservation"]["staytype"]),
			$this->K9MasterBeverage,
			$this->K9DataOrderBeverage,
			$this->K9DataHistoryPriceBeverage,
			$schedule_id,
			$order);
			break;

		case(K9MasterTobacco::$CATEGORY_TOBACCO);

			$res=$this->__subscribe(
			$this->__stayTypeModel($reservation["K9DataReservation"]["staytype"]),
			$this->K9MasterTobacco,
			$this->K9DataOrderTobacco,
			$this->K9DataHistoryPriceTobacco,
			$schedule_id,
			$order);
			break;

		case(K9MasterRoomservice::$CATEGORY_ROOMSERVICE);

			$res=$this->__subscribe(
			$this->__stayTypeModel($reservation["K9DataReservation"]["staytype"]),
			$this->K9MasterRoomservice,
			$this->K9DataOrderRoomservice,
			$this->K9DataHistoryPriceRoomservice,
			$schedule_id,
			$order);
			break;

		case(K9MasterSpa::$CATEGORY_SPA);

			$res=$this->__subscribe(
			$this->__stayTypeModel($reservation["K9DataReservation"]["staytype"]),
			$this->K9MasterSpa,
			$this->K9DataOrderSpa,
			$this->K9DataHistoryPriceSpa,
			$schedule_id,
			$order);
			break;

		case(K9MasterLimousine::$CATEGORY_LIMOUSINE);

			$res=$this->__subscribe(
			$this->__stayTypeModel($reservation["K9DataReservation"]["staytype"]),
			$this->K9MasterLimousine,
			$this->K9DataOrderLimousine,
			$this->K9DataHistoryPriceLimousine,
			$schedule_id,
			$order);
			break;

		case(K9MasterLaundry::$CATEGORY_LAUNDRY);

			$res=$this->__subscribe(
			$this->__stayTypeModel($reservation["K9DataReservation"]["staytype"]),
			$this->K9MasterLaundry,
			$this->K9DataOrderLaundry,
			$this->K9DataHistoryPriceLaundry,
			$schedule_id,
			$order);
			break;

		default:
			exit;
			break;
		}

		if(empty($res["status"])) Output::__outputNo($res);
		Output::__outputYes($res);
	}

	public function __subscribe(Model $schedule_model,Model $master_model,Model $order_model,Model $history_model,$schedule_id,$order)
	{

		if(!$schedule=$this->__getSchedule($schedule_model,$schedule_id)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした"))); 

		$reserve_id=$schedule[$schedule_model->name]["reserve_id"];
		$reservation_hash=$schedule["K9DataReservation"]["hash"];
		$date=$schedule[$schedule_model->name]["start_month_prefix"].sprintf("%02d",$schedule[$schedule_model->name]["start_day"]);
		$created=date("Y-m-d 00:00:00",strtotime($date));

		try{
		
			$this->__saveOrder($master_model,$order_model,$history_model,$order,$schedule_id,array(
			
				"reserve_id"=>$reserve_id,
				"date"      =>$date,
				"created"   =>$created
			));

		}catch(Exception $e){
		
			$res=array();
			$res["message"]=$e->getMessage();
			$res["status"]=false;
			return $res;
		}

		$order_history=$this->__getSelected($master_model,$order_model,$history_model,$reservation_hash,$date);

		$res=array();
		$res["data"]["order"]=$order_history;
		$res["status"]=true;
		return $res;
	}

	function __saveOrder(Model $master_model,Model $order_model,Model $history_model,$order,$schedule_id,$params=array())
	{

		$reserve_id=$params["reserve_id"];
		$ymd=$params["date"];
		$created=$params["created"];

		$order_model->unbindFully();
		$order_history=$order_model->getHistories($reserve_id,$ymd,array());

		$update=array();
		$insert=array();
		$ids=Set::combine($order_history,"{n}.{$order_model->name}.{$history_model->foreignKey}","{n}.{$order_model->name}.id");
		$target_ids=array_keys($order);

		$now=date("YmdHis");
		$created=date("Y-m-d 00:00:00",strtotime($ymd));

		$prices=getPriceByHistory($history_model,$master_model,$target_ids,$ymd);

		foreach($order as $id=>$data){

			if(isset($ids[$id])){

				$count=count($update);
				$update[$count]["id"]=$ids[$id];
				$update[$count]["count"]=$data["count"];
				$update[$count]["cash_type_id"]=$data["cash_type_id"];
				$update[$count]["reserve_id"]=$reserve_id;
				$update[$count][$history_model->foreignKey]=$id;
				$update[$count]["created"]=$created;
				$update[$count]["enter_time"]=$now;
				$update[$count]["price_id"]=$prices[$id]["data"]["id"];
				continue;
			}

			$count=count($insert);
			$insert[$count]["count"]=$data["count"];
			$insert[$count]["cash_type_id"]=$data["cash_type_id"];
			$insert[$count]["reserve_id"]=$reserve_id;
			$insert[$count][$history_model->foreignKey]=$id;
			$insert[$count]["created"]=$created;
			$insert[$count]["enter_time"]=$now;
			$insert[$count]["price_id"]=$prices[$id]["data"]["id"];
		}

		try{

			if(!empty($insert)) $order_model->multiInsert($insert);
			if(!empty($update)) $order_model->multiInsert($update);

		}catch(Exception $e){
		
			throw new Exception($e->getMessage());
		}

		return true;
	}

	function __getInformations(Model $master_model,$type){

		$spa_categories=$this->K9MasterCategory->getCategories($type);
		$names     =Set::combine($spa_categories,"{n}.K9MasterCategory.id","{n}.K9MasterCategory.name");
		$aliases   =Set::combine($spa_categories,"{n}.K9MasterCategory.id","{n}.K9MasterCategory.aliase");

		$lang=Configure::read('Config.language');
		$category_name=($master_model->hasField("name_{$lang}"))?"name_{$lang}":"name";
		$categories=Set::combine($spa_categories,"{n}.K9MasterCategory.aliase","{n}.K9MasterCategory.{$category_name}");

		$conditions=array();
		$order=array("{$master_model->name}.item_num ASC");
		$data=$master_model->find("all",array(
		
			"conditions"=>$conditions,
			"order"=>$order
		));

		$list=array();
		foreach($data as $k=>$v){

			$v=$v[$master_model->name];
			$category=$names[$v["category_id"]];
			$aliase  =$aliases[$v["category_id"]];
			if(!isset($list[$aliase])) $list[$aliase]=array();

			$count=count($list[$aliase]);
			$list[$aliase][$count]["master_id"]=$v["id"];
			$list[$aliase][$count]["name"]     =escapeJsonString(isset($v["name_{$lang}"])?$v["name_{$lang}"]:$v["name"]);
			$list[$aliase][$count]["remarks"]  =escapeJsonString($v["remarks"]);
			$list[$aliase][$count]["item_num"] =$v["item_num"];
		}

		$res["menus"]=$categories;
		$res["data"]=$list;
		return $res;
	}

	function __informations(Model $master_model,Model $order_model,Model $history_model,$type,$reservation_hash,$date){

		$list=$this->__getInformations($master_model,$type);

		$order_history=$this->__getSelected($master_model,$order_model,$history_model,$reservation_hash,$date);

		$all_ids=array();
		foreach($list["data"] as $aliase=>$v) $all_ids=array_merge($all_ids,Set::extract($v,"{}.master_id"));
		
		$prices=getPriceByHistory($history_model,$master_model,$all_ids,$date);

		$res=array();
		$res["data"]["list"]["menus"]=$list["menus"];
		$res["data"]["list"]["list"] =$list["data"];
		$res["data"]["order"]["data"]=$order_history;
		$res["data"]["price"]["data"]=$prices;
		return $res;
	}

	function __getSelected(Model $master_model,Model $order_model,Model $history_model,$reservation_hash,$date){

		$this->K9DataReservation->unbindFully();
		if(!$reservation=$this->K9DataReservation->findByHash($reservation_hash)) throw new Exception(__("正常に処理が終了しませんでした"));

		$reserve_id=$reservation["K9DataReservation"]["id"];
		$history=$this->__getOrderHistory($master_model,$order_model,$history_model,$reserve_id,$date);
		return $history;
	}

	function __getOrderHistory(Model $master_model,Model $order_model,Model $history_model,$reserve_id,$date){

		// countが0より大きいデータを対象とする処理を追加 todo.
		$order_model->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$history=$order_model->getHistories($reserve_id,$date,array(
		
			"recursive"=>2,
			"count"    =>1,
			"order"    =>array("{$order_model->name}.created DESC")
		));

		if(empty($history)) return array();

		$list=array();
		$lang=Configure::read('Config.language');
		foreach($history as $k=>$v){

			$aliase=$v[$master_model->name]["K9MasterCategory"]["aliase"];
			if(!isset($list[$aliase])) $list[$aliase]=array();

			$count=count($list[$aliase]);
			$id=$v[$order_model->name][$history_model->foreignKey];
			$name=$master_model->hasField("name_{$lang}")?"name_{$lang}":"name";
			$category_name=$this->K9MasterCategory->hasField("name_{$lang}")?"name_{$lang}":"name";

			$master_id=$v[$master_model->name]["id"];
			$list[$aliase][$master_id]["data"]["count"]=$v[$order_model->name]["count"];
			$list[$aliase][$master_id]["data"]["master_id"]=$v[$master_model->name]["id"];
			$list[$aliase][$master_id]["cash"]["card_type"]=$v["K9MasterCard"]["card_type"];
			$list[$aliase][$master_id]["cash"]["type"]=$v["K9MasterCard"]["type"];
			$list[$aliase][$master_id]["cash"]["id"]  =$v["K9MasterCard"]["id"];
			$list[$aliase][$master_id]["menu"]["menu_id"]=$count;
			$list[$aliase][$master_id]["menu"]["name"]   =escapeJsonString($v[$master_model->name][$name]);
			$list[$aliase][$master_id]["menu"]["remarks"]=escapeJsonString($v[$master_model->name]["remarks"]);
			$list[$aliase][$master_id]["category"]["id"]=$v[$master_model->name]["K9MasterCategory"]["id"];
			$list[$aliase][$master_id]["category"]["name"]=$v[$master_model->name]["K9MasterCategory"][$category_name];
			$list[$aliase][$master_id]["category"]["aliase"]=$v[$master_model->name]["K9MasterCategory"]["aliase"];
		}

		return $list;
	}

	public function __getSchedule(Model $schedule_model,$schedule_id)
	{

		$schedule=$schedule_model->findByIdAndDelFlg($schedule_id,0);
		return $schedule;
	}

	private function __getCardTypes(){

		$cards=$this->K9MasterCard->getCards(0);
		return Set::combine($cards,"{n}.K9MasterCard.id","{n}.K9MasterCard.card_type");
	}


}//END class

?>
