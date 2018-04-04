<?php

App::uses('K9BaseDailyReportController','Controller');
class AdK9DailyReportOutputOfCreditRateController extends K9BaseDailyReportController{

	var $name = "K9DailyReportOutputOfCreditRate";

	function beforeFilter(){}

	public function __setOrderModels()
	{
		parent::__setOrderModels();
	}

	public function __outputCreditRateForSheet($day,$position)
	{
		$credit_details=$this->__getCreditDetails($day);
		$orders=$this->__outputCreditRateAllOrders($credit_details,$day);
		$orders_extra=$this->__outputCreditRateAllExtraOrders($credit_details,$day);

		$list=array();
		$this->__outputCreditRateValues($list,$day,$orders);
		$this->__outputCreditRateValues($list,$day,$orders_extra);
		$card_titles=Set::combine($credit_details,"{}.id","{}.card_type");

		$res=array();
		$res["card_titles"]=$card_titles;
		$res["data"]=$list;
		return $res;
	}

	private function __outputCreditRateValues(&$list=array(),$day,$data)
	{
		foreach($data as $card_id=>$v){
		
			if(!isset($list[$card_id])) $list[$card_id]=array();
			foreach($v as $order_type=>$_v){

				if(!isset($list[$card_id][$order_type])){

					$list[$card_id][$order_type]=array();
					$list[$card_id][$order_type]["value"]=0;
					$list[$card_id][$order_type]["count"]=0;
				}

				if(empty($_v)) continue;

				foreach($_v as $k=>$order){
				
					if(empty($order)) continue;
					$history_model=$this->models[$order_type]["history"];
					$rate=$this->__cardHistoryWithinaDay($order["K9MasterCard"]["K9DataHistoryPriceCard"],$day);
					$price=$order[$history_model->name]["price"];
					$list[$card_id][$order_type]["value"]+=($price*($rate/100));
					$list[$card_id][$order_type]["count"]++;
				}
			}
		}
	}

	private function __outputCreditRateAllExtraOrders($cards,$day,$res=array())
	{

		if(empty($cards)) return $res;

		foreach($cards as $card_id=>$card_detail) break;
		unset($cards[$card_id]);

		$types=array();
		$types[]=K9MasterRoomservice::$CATEGORY_ROOMSERVICE;
		$types[]=K9MasterFood::$CATEGORY_FOOD;
		$types[]=K9MasterBeverage::$CATEGORY_DRINK;
		$subdate=date("Ymd",strtotime("- {$card_detail[K9DataHistoryPriceCard::$TYPE_INCOME_DAY]} day",strtotime($day)));
		$_res=$this->__outputCreditRateByCardtypeWithEachExtraOrders($subdate,$card_id,$types);

		$res[$card_id]=$_res;
		return $this->__outputCreditRateAllExtraOrders($cards,$day,$res);
	}

	private function __outputCreditRateAllOrders($cards,$day,$res=array())
	{

		if(empty($cards)) return $res;

		foreach($cards as $card_id=>$card_detail) break;
		unset($cards[$card_id]);

		$subdate=date("Ymd",strtotime("- {$card_detail[K9DataHistoryPriceCard::$TYPE_INCOME_DAY]} day",strtotime($day)));
		$types=array_keys($this->models);
		$_res=$this->__outputCreditRateByCardtypeWithEachOrders($subdate,$card_id,$types);

		$res[$card_id]=$_res;
		return $this->__outputCreditRateAllOrders($cards,$day,$res);
	}

	private function __getExtraOrder($card_id,$subdate)
	{
		$food_model       =$this->models[K9MasterFood::$CATEGORY_FOOD];
		$beverage_model   =$this->models[K9MasterBeverage::$CATEGORY_DRINK];
		$roomservice_model=$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE];

		$food_order_model         =$food_model["extra_order"];
		$beverage_order_model     =$beverage_model["extra_order"];
		$roomservice_order_model  =$roomservice_model["extra_order"];
		$food_history_model       =$food_model["history"];
		$beverage_history_model   =$beverage_model["history"];
		$roomservice_history_model=$roomservice_model["history"];
		$food_master_model        =$food_model["master"];
		$beverage_master_model    =$beverage_model["master"];
		$roomservice_master_model =$roomservice_model["master"];

		$food_order_model->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder")));
		$beverage_order_model->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder")));
		$roomservice_order_model->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder")));

		$food_history_model->unbindModel(array("belongsTo"=>array("K9MasterEmployee",$food_master_model->name)));
		$beverage_history_model->unbindModel(array("belongsTo"=>array("K9MasterEmployee",$beverage_master_model->name)));
		$roomservice_history_model->unbindModel(array("belongsTo"=>array("K9MasterEmployee",$roomservice_master_model->name)));

		$association=$this->K9MasterCard->association["hasOne"]["K9DataHistoryPriceCard"];
		$association["order"]=array("K9DataHistoryPriceCard.enter_date DESC");
		$association["conditions"]["and"]["K9DataHistoryPriceCard.type"]=K9DataHistoryPriceCard::$TYPE_RATE;
		$association["conditions"]["and"]["K9DataHistoryPriceCard.del_flg"]=0;
		$this->K9MasterCard->bindModel(array("hasMany"=>array("K9DataHistoryPriceCard"=>$association)));

		$this->__getExtraOrderAddHasMany($food_order_model,$card_id,$subdate);
		$this->__getExtraOrderAddHasMany($beverage_order_model,$card_id,$subdate);
		$this->__getExtraOrderAddHasMany($roomservice_order_model,$card_id,$subdate);

		$conditions=array();
		$conditions["and"]["DATE_FORMAT(K9DataExtraOrder.target_date,'%Y%m%d')"]=$subdate;
		$data=$this->K9DataExtraOrder->find("all",array(
		
			"conditions"=>$conditions,
			"recursive"=>3
		));
		return $data;
	}

	private function __getExtraOrderAddHasMany(Model $order_model,$card_id,$subdate)
	{
		$order_name=$order_model->name;
		$this->K9DataExtraOrder->hasMany[$order_name]["conditions"]=array();
		$this->K9DataExtraOrder->hasMany[$order_name]["conditions"][0]["and"]["{$order_name}.cash_type_id"]=$card_id;
		$this->K9DataExtraOrder->hasMany[$order_name]["conditions"][1]["or"]["{$order_name}.del_flg"]=0;
		$this->K9DataExtraOrder->hasMany[$order_name]["conditions"][1]["or"]["and"]["{$order_name}.del_flg"]=1;
		$this->K9DataExtraOrder->hasMany[$order_name]["conditions"][1]["or"]["and"]["DATE_FORMAT({$order_name}.del_date,'%Y%m%d') >= "]=$subdate;
	}

	private function __outputCreditRateByCardtypeWithEachExtraOrders($subdate,$card_id,$types=array(),$res=array())
	{
		$food_model=$this->models[K9MasterFood::$CATEGORY_FOOD];
		$food_order_model  =$food_model["extra_order"];
		$food_master_model =$food_model["master"];
		$food_history_model=$food_model["history"];

		$beverage_model=$this->models[K9MasterBeverage::$CATEGORY_DRINK];
		$beverage_order_model  =$beverage_model["extra_order"];
		$beverage_master_model =$beverage_model["master"];
		$beverage_history_model=$beverage_model["history"];

		$roomservice_model=$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE];
		$roomservice_order_model  =$roomservice_model["extra_order"];
		$roomservice_master_model =$roomservice_model["master"];
		$roomservice_history_model=$roomservice_model["history"];

		$data=$this->__getExtraOrder($card_id,$subdate);

		$list=array();
		foreach($data as $k=>$v){

			$roomservice=$v[$roomservice_order_model->name];
			$food       =$v[$food_order_model->name];
			$beverage   =$v[$beverage_order_model->name];

			if(!empty($roomservice)){

				foreach($roomservice as $k=>$v){
				
					$cash_type_id=$v["cash_type_id"];
					$type=$v[$roomservice_master_model->name]["K9MasterCategory"]["type"];
					if(!isset($list[$type])) $list[$type]=array();
					$list[$type][]=$v;
				}
			}

			if(!empty($food)){
			
				foreach($food as $k=>$v){
				
					$cash_type_id=$v["cash_type_id"];
					$type=$v[$food_master_model->name]["K9MasterCategory"]["type"];
					if(!isset($list[$type])) $list[$type]=array();
					$list[$type][]=$v;
				}
			}

			if(!empty($beverage)){

				foreach($beverage as $k=>$v){
				
					$cash_type_id=$v["cash_type_id"];
					$type=$v[$beverage_master_model->name]["K9MasterCategory"]["type"];
					if(!isset($list[$type])) $list[$type]=array();
					$list[$type][]=$v;
				}
			}
		}

		return $list;
	}

	private function __outputCreditRateByCardtypeWithEachOrders($subdate,$card_id,$types=array(),$res=array())
	{

		if(empty($types)) return $res;

		$type=array_shift($types);
		$models=$this->models[$type];
		$order_model =$models["order"];
		$master_model=$models["master"];
		$history_model=$models["history"];

		$order_model->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$master_model->unbindModel(array("belongsTo"=>array("K9MasterCategory")));
		$history_model->unbindModel(array("belongsTo"=>array($master_model->name,"K9MasterEmployee")));

		//here might be sutressful....
		//foolish of hasOne no need to use you.
		$association=$this->K9MasterCard->association["hasOne"]["K9DataHistoryPriceCard"];
		$association["order"]=array("K9DataHistoryPriceCard.enter_date DESC");
		$association["conditions"]["and"]["K9DataHistoryPriceCard.type"]=K9DataHistoryPriceCard::$TYPE_RATE;
		$association["conditions"]["and"]["K9DataHistoryPriceCard.del_flg"]=0;
		$this->K9MasterCard->bindModel(array("hasMany"=>array("K9DataHistoryPriceCard"=>$association)));

		$data=$order_model->getOrderByCardtypeWithBeforeDate($card_id,$subdate,array(
		
			"recursive"=>2,
			"count"=>1
		));

		$res[$type]=$data;
		return $this->__outputCreditRateByCardtypeWithEachOrders($subdate,$card_id,$types,$res);
	}

	private function __getCreditDetails($day)
	{
		$list=array();

		//here might be sutressful....
		//foolish of hasOne no need to use you.
		$association=$this->K9MasterCard->association["hasOne"]["K9DataHistoryPriceCard"];
		$association["order"]=array("K9DataHistoryPriceCard.enter_date DESC");
		$association["conditions"]["and"]["K9DataHistoryPriceCard.del_flg"]=0;
		$this->K9MasterCard->bindModel(array("hasMany"=>array("K9DataHistoryPriceCard"=>$association)));
		$data=$this->K9MasterCard->getCards(null,array( "type"=>"card" ));

		foreach($data as $k=>$v){

			$type_list=array();
			foreach($v["K9DataHistoryPriceCard"] as $_k=>$_v) $type_list[$_v["type"]][]=$_v;

			$income_late_days=isset($type_list[K9DataHistoryPriceCard::$TYPE_INCOME_DAY])?$type_list[K9DataHistoryPriceCard::$TYPE_INCOME_DAY]:array();
			$rates=isset($type_list[K9DataHistoryPriceCard::$TYPE_RATE])?$type_list[K9DataHistoryPriceCard::$TYPE_RATE]:array();
			$income_late_day=$this->__cardHistoryWithinaDay($income_late_days,$day);
			$rate           =$this->__cardHistoryWithinaDay($rates,$day);

			$list[$v["K9MasterCard"]["id"]]["card_type"]      =$v["K9MasterCard"]["card_type"];
			$list[$v["K9MasterCard"]["id"]][K9DataHistoryPriceCard::$TYPE_INCOME_DAY]=$income_late_day;
			$list[$v["K9MasterCard"]["id"]][K9DataHistoryPriceCard::$TYPE_RATE]      =$rate;
			$list[$v["K9MasterCard"]["id"]]["id"]             =$v["K9MasterCard"]["id"];
		}

		return $list;
	}


}//END class

?>
