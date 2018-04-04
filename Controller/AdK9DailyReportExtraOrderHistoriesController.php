<?php

App::uses('K9BaseDailyReportController','Controller');
App::uses('K9BasePricesController','Controller');
class AdK9DailyReportExtraOrderHistoriesController extends K9BaseDailyReportController{

	var $name = "K9DailyReportExtraOrderHistories";

	function beforeFilter(){}

	public function __setOrderModels()
	{
		parent::__setOrderModels();
		$this->loadModel("K9DataExtraOrder");
	}

	public function __orderHistory($day)
	{
		$data=$this->__getOrderHistories($day);
		$data=$this->__orderHistoryEach($data,$day);
		return $data;
	}

	private function __getOrderHistories($day)
	{

		$food              =$this->models[K9MasterFood::$CATEGORY_FOOD]["extra_order"];
		$beverage          =$this->models[K9MasterBeverage::$CATEGORY_DRINK]["extra_order"];
		$roomservice       =$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["extra_order"];
		$tobacco           =$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["extra_order"];

		$food_master       =$this->models[K9MasterFood::$CATEGORY_FOOD]["master"];
		$beverage_master   =$this->models[K9MasterBeverage::$CATEGORY_DRINK]["master"];
		$roomservice_master=$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["master"];
		$tobacco_master    =$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["master"];

		$food_history       =$this->models[K9MasterFood::$CATEGORY_FOOD]["history"];
		$beverage_history   =$this->models[K9MasterBeverage::$CATEGORY_DRINK]["history"];
		$roomservice_history=$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["history"];
		$tobacco_history    =$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["history"];

		$food->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder")));
		$beverage->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder")));
		$roomservice->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder")));
		$tobacco->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder")));

		$association=$this->K9MasterCard->association["hasOne"]["K9DataHistoryPriceCard"];
		$association["order"]=array("K9DataHistoryPriceCard.enter_date DESC");
		$association["conditions"]["and"]["K9DataHistoryPriceCard.type"]=K9DataHistoryPriceCard::$TYPE_INCOME_DAY;
		$association["conditions"]["and"]["K9DataHistoryPriceCard.del_flg"]=0;
		$this->K9MasterCard->bindModel(array("hasMany"=>array("K9DataHistoryPriceCard"=>$association)));

		$this->K9DataExtraOrder->hasMany[$food->name]["conditions"]["and"]["{$food->name}.del_flg"]=0;
		$this->K9DataExtraOrder->hasMany[$beverage->name]["conditions"]["and"]["{$beverage->name}.del_flg"]=0;
		$this->K9DataExtraOrder->hasMany[$roomservice->name]["conditions"]["and"]["{$roomservice->name}.del_flg"]=0;
		$this->K9DataExtraOrder->hasMany[$tobacco->name]["conditions"]["and"]["{$tobacco->name}.del_flg"]=0;

		$food_history->unbindModel(array("belongsTo"=>array($food_master->name,"K9MasterEmployee")));
		$beverage_history->unbindModel(array("belongsTo"=>array($beverage_master->name,"K9MasterEmployee")));
		$roomservice_history->unbindModel(array("belongsTo"=>array($roomservice_master->name,"K9MasterEmployee")));
		$tobacco_history->unbindModel(array("belongsTo"=>array($tobacco_master->name,"K9MasterEmployee")));

		$data=$this->K9DataExtraOrder->getExtraOrder($day,array(
		
			"recursive"=>3
		));

		if(empty($data)) return array();

		$list=array();
		foreach($data as $k=>$v){

			$ymd=date("Ymd",strtotime($v["K9DataExtraOrder"]["target_date"]));
			if(!isset($list[$ymd])) $list[$ymd]=array();
			$list[$ymd][]=$v;
		}

		return $list;
	}

	private function __orderHistoryEach($data,$day)
	{

		$food_order         =$this->models[K9MasterFood::$CATEGORY_FOOD]["extra_order"];
		$beverage_order     =$this->models[K9MasterBeverage::$CATEGORY_DRINK]["extra_order"];
		$roomservice_order  =$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["extra_order"];
		$tobacco_order      =$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["extra_order"];

		$food_master        =$this->models[K9MasterFood::$CATEGORY_FOOD]["master"];
		$beverage_master    =$this->models[K9MasterBeverage::$CATEGORY_DRINK]["master"];
		$roomservice_master =$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["master"];
		$tobacco_master     =$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["master"];

		$food_history       =$this->models[K9MasterFood::$CATEGORY_FOOD]["history"];
		$beverage_history   =$this->models[K9MasterBeverage::$CATEGORY_DRINK]["history"];
		$roomservice_history=$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["history"];
		$tobacco_history    =$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["history"];

		$list=array();
		$lang=Configure::read('Config.language');

		foreach($data as $date=>$__data){

			foreach($__data as $k=>$value){
			
				$roomservice=$value[$roomservice_order->name];
				$food=$value[$food_order->name];
				$beverage=$value[$beverage_order->name];
				$tobacco=$value[$tobacco_order->name];

				if(!isset($list[$date][K9MasterFood::$CATEGORY_FOOD]))               $list[$date][K9MasterFood::$CATEGORY_FOOD]=array();
				if(!isset($list[$date][K9MasterBeverage::$CATEGORY_DRINK]))          $list[$date][K9MasterBeverage::$CATEGORY_DRINK]=array();
				if(!isset($list[$date][K9MasterRoomservice::$CATEGORY_ROOMSERVICE])) $list[$date][K9MasterRoomservice::$CATEGORY_ROOMSERVICE]=array();
				if(!isset($list[$date][K9MasterTobacco::$CATEGORY_TOBACCO]))         $list[$date][K9MasterTobacco::$CATEGORY_TOBACCO]=array();

				if(!empty($roomservice)){

					foreach($roomservice as $k=>$v){

						$cash_type=$v["K9MasterCard"]["type"];

						$type=$v[$roomservice_master->name]["K9MasterCategory"]["type"];
						if(!isset($list[$date][$type][$cash_type])) $list[$date][$type][$cash_type]=array();
						$count=count($list[$date][$type][$cash_type]);
	
						$late_date=$this->__cardHistoryWithinaDay($v["K9MasterCard"]["K9DataHistoryPriceCard"],$day);
						$name=$roomservice_master->hasField("name_{$lang}")?"name_{$lang}":"name";
						$list[$date][$type][$cash_type][$count]["count"]=$v["count"];
						$list[$date][$type][$cash_type][$count]["name"] =$v[$roomservice_master->name][$name];
						$list[$date][$type][$cash_type][$count]["price"]=(Double)($v["count"]*$v[$roomservice_history->name]["price"]);
						$list[$date][$type][$cash_type][$count]["cash"]["cash_type"]=$v["K9MasterCard"]["card_type"];
						$list[$date][$type][$cash_type][$count]["cash"][K9DataHistoryPriceCard::$TYPE_INCOME_DAY]=$late_date;
						$list[$date][$type][$cash_type][$count]["cash"]["type"]=$cash_type;
					}
				}

				if(!empty($beverage)){

					foreach($beverage as $k=>$v){

						$cash_type=$v["K9MasterCard"]["type"];

						$type=$v[$beverage_master->name]["K9MasterCategory"]["type"];
						if(!isset($list[$date][$type][$cash_type])) $list[$date][$type][$cash_type]=array();
						$count=count($list[$date][$type][$cash_type]);

						$late_date=$this->__cardHistoryWithinaDay($v["K9MasterCard"]["K9DataHistoryPriceCard"],$day);
						$name=$beverage_master->hasField("name_{$lang}")?"name_{$lang}":"name";
						$list[$date][$type][$cash_type][$count]["count"]=$v["count"];
						$list[$date][$type][$cash_type][$count]["name"] =$v[$beverage_master->name][$name];
						$list[$date][$type][$cash_type][$count]["price"]=(Double)($v["count"]*$v[$beverage_history->name]["price"]);
						$list[$date][$type][$cash_type][$count]["cash"]["cash_type"]=$v["K9MasterCard"]["card_type"];
						$list[$date][$type][$cash_type][$count]["cash"][K9DataHistoryPriceCard::$TYPE_INCOME_DAY]=$late_date;
						$list[$date][$type][$cash_type][$count]["cash"]["type"]=$cash_type;
					}
				}

				if(!empty($tobacco)){

					foreach($tobacco as $k=>$v){

						$cash_type=$v["K9MasterCard"]["type"];
						$late_date=$this->__cardHistoryWithinaDay($v["K9MasterCard"]["K9DataHistoryPriceCard"],$day);

						$type  =$v[$tobacco_master->name]["K9MasterCategory"]["type"];
						$aliase=$v[$tobacco_master->name]["K9MasterCategory"]["aliase"];
						if(!isset($list[$date][$type][$aliase])) $list[$date][$type][$aliase]=array();
						if(!isset($list[$date][$type][$aliase][$cash_type])) $list[$date][$type][$aliase][$cash_type]=array();
						$count=count($list[$date][$type][$aliase][$cash_type]);

						$master_data=$v[$tobacco_master->name];
						$name=isset($master_data["name_{$lang}"])?$master_data["name_{$lang}"]:$master_data["name"];
						$list[$date][$type][$aliase][$cash_type][$count]["count"]=$v["count"];
						$list[$date][$type][$aliase][$cash_type][$count]["name"] =$name;
						$list[$date][$type][$aliase][$cash_type][$count]["price"]=(Double)($v["count"]*$v[$tobacco_history->name]["price"]);
						$list[$date][$type][$aliase][$cash_type][$count]["cash"]["cash_type"]=$v["K9MasterCard"]["card_type"];
						$list[$date][$type][$aliase][$cash_type][$count]["cash"][K9DataHistoryPriceCard::$TYPE_INCOME_DAY]=$late_date;
						$list[$date][$type][$aliase][$cash_type][$count]["cash"]["type"]=$cash_type;
					}
				}
	
				if(!empty($food)){

					foreach($food as $k=>$v){

						$cash_type=$v["K9MasterCard"]["type"];
						$late_date=$this->__cardHistoryWithinaDay($v["K9MasterCard"]["K9DataHistoryPriceCard"],$day);

						$type  =$v[$food_master->name]["K9MasterCategory"]["type"];
						$aliase=$v[$food_master->name]["K9MasterCategory"]["aliase"];
						if(!isset($list[$date][$type][$aliase])) $list[$date][$type][$aliase]=array();
						if(!isset($list[$date][$type][$aliase][$cash_type])) $list[$date][$type][$aliase][$cash_type]=array();
						$count=count($list[$date][$type][$aliase][$cash_type]);

						$master_data=$v[$food_master->name];
						$name=isset($master_data["name_{$lang}"])?$master_data["name_{$lang}"]:$master_data["name"];
						$list[$date][$type][$aliase][$cash_type][$count]["count"]=$v["count"];
						$list[$date][$type][$aliase][$cash_type][$count]["name"] =$name;
						$list[$date][$type][$aliase][$cash_type][$count]["price"]=(Double)($v["count"]*$v[$food_history->name]["price"]);
						$list[$date][$type][$aliase][$cash_type][$count]["cash"]["cash_type"]=$v["K9MasterCard"]["card_type"];
						$list[$date][$type][$aliase][$cash_type][$count]["cash"][K9DataHistoryPriceCard::$TYPE_INCOME_DAY]=$late_date;
						$list[$date][$type][$aliase][$cash_type][$count]["cash"]["type"]=$cash_type;
					}
				}
			}
		}

		return $list;
	}

}//END class

?>
