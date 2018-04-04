<?php

App::uses('K9BaseDailyReportController','Controller');
App::uses('K9BasePricesController','Controller');
class AdK9DailyReportOrderHistoriesController extends K9BaseDailyReportController{

	var $name = "K9DailyReportOrderHistories";

	function beforeFilter(){}

	public function __setOrderModels()
	{
		parent::__setOrderModels();
	}

	public function __orderHistory($days,$reserve_id="")
	{

		$days=is_array($days)?$days:array($days);

		$spa        =$this->__orderHistoryEach(K9MasterSpa::$CATEGORY_SPA,$days,$reserve_id);
		$food       =$this->__orderHistoryEach(K9MasterFood::$CATEGORY_FOOD,$days,$reserve_id);
		$drink      =$this->__orderHistoryEach(K9MasterBeverage::$CATEGORY_DRINK,$days,$reserve_id);
		$limousine  =$this->__orderHistoryEach(K9MasterLimousine::$CATEGORY_LIMOUSINE,$days,$reserve_id);
		$laundary   =$this->__orderHistoryEach(K9MasterLaundry::$CATEGORY_LAUNDRY,$days,$reserve_id);
		$tobacco    =$this->__orderHistoryEach(K9MasterTobacco::$CATEGORY_TOBACCO,$days,$reserve_id);
		$roomservice=$this->__orderHistoryEach(K9MasterRoomservice::$CATEGORY_ROOMSERVICE,$days,$reserve_id);

		$list=array();
		foreach($days as $k=>$day){
		
			if(!isset($list[$day])) $list[$day]=array();
			$list[$day][K9MasterSpa::$CATEGORY_SPA]        =isset($spa[$day])?$spa[$day]:array();
			$list[$day][K9MasterFood::$CATEGORY_FOOD]       =isset($food[$day])?$food[$day]:array();
			$list[$day][K9MasterBeverage::$CATEGORY_DRINK]      =isset($drink[$day])?$drink[$day]:array();
			$list[$day][K9MasterLimousine::$CATEGORY_LIMOUSINE]  =isset($limousine[$day])?$limousine[$day]:array();
			$list[$day][K9MasterLaundry::$CATEGORY_LAUNDRY]    =isset($laundary[$day])?$laundary[$day]:array();
			$list[$day][K9MasterTobacco::$CATEGORY_TOBACCO]    =isset($tobacco[$day])?$tobacco[$day]:array();
			$list[$day][K9MasterRoomservice::$CATEGORY_ROOMSERVICE]=isset($roomservice[$day])?$roomservice[$day]:array();
		}

		return $list;
	}

	private function __getOrderHistories($category,$day,$reserve_id="")
	{
		//here might be sutressful....
		//foolish of hasOne no need to use you.
		$association=$this->K9MasterCard->association["hasOne"]["K9DataHistoryPriceCard"];
		$association["order"]=array("K9DataHistoryPriceCard.enter_date DESC");
		$association["conditions"]["and"]["K9DataHistoryPriceCard.type"]=K9DataHistoryPriceCard::$TYPE_INCOME_DAY;
		$association["conditions"]["and"]["K9DataHistoryPriceCard.del_flg"]=0;
		$this->K9MasterCard->bindModel(array("hasMany"=>array("K9DataHistoryPriceCard"=>$association)));

		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));
		$this->models[$category]["history"]->unbindModel(array("belongsTo"=>array("K9MasterEmployee",$this->models[$category]["master"]->name)));
		$data=$this->models[$category]["order"]->getHistoryByDate($day,array(
		
			"recursive"=>2,
			"reserve_id"=>$reserve_id,
			"count"=>1
		));

		if(empty($data)) return array();

		$list=array();
		foreach($data as $k=>$v){

			$order_name=$this->models[$category]["order"]->name;
			$ymd=date("Ymd",strtotime($v[$order_name]["created"]));
			if(!isset($list[$ymd])) $list[$ymd]=array();
			$list[$ymd][]=$v;
		}

		return $list;
	}

	private function __orderHistoryEach($category,$day,$reserve_id="")
	{

		$data=$this->__getOrderHistories($category,$day,$reserve_id);
		if(empty($data)) return array();

		$lang=Configure::read('Config.language');
		foreach($data as $date=>$__data){

			switch(true){
			
			case(in_array($category,array(K9MasterFood::$CATEGORY_FOOD,K9MasterTobacco::$CATEGORY_TOBACCO))):

				$list[$date]=array();
				foreach($__data as $k=>$v){

					$type=$v["K9MasterCard"]["type"];
					$late_date=$this->__cardHistoryWithinaDay($v["K9MasterCard"]["K9DataHistoryPriceCard"],$day);

					$aliase=$v[$this->models[$category]["master"]->name]["K9MasterCategory"]["aliase"];
					if(!isset($list[$date][$aliase])) $list[$date][$aliase]=array();
					if(!isset($list[$date][$aliase][$type])) $list[$date][$aliase][$type]=array();
					$count=count($list[$date][$aliase][$type]);

					$master_data=$v[$this->models[$category]["master"]->name];
					$name=isset($master_data["name_{$lang}"])?$master_data["name_{$lang}"]:$master_data["name"];
					$list[$date][$aliase][$type][$count]["count"]=$v[$this->models[$category]["order"]->name]["count"];
					$list[$date][$aliase][$type][$count]["name"] =$name;
					$list[$date][$aliase][$type][$count]["price"]=(Double)($list[$date][$aliase][$type][$count]["count"]*$v[$this->models[$category]["history"]->name]["price"]);
					$list[$date][$aliase][$type][$count]["cash"]["cash_type"]=$v["K9MasterCard"]["card_type"];
					$list[$date][$aliase][$type][$count]["cash"][K9DataHistoryPriceCard::$TYPE_INCOME_DAY]=$late_date;
					$list[$date][$aliase][$type][$count]["cash"]["type"]=$type;
				}

				break;

			default:

				$list[$date]=array();
				foreach($__data as $k=>$v){

					$type=$v["K9MasterCard"]["type"];

					if(!isset($list[$date][$type])) $list[$date][$type]=array();
					$count=count($list[$date][$type]);

					$late_date=$this->__cardHistoryWithinaDay($v["K9MasterCard"]["K9DataHistoryPriceCard"],$day);

					$list[$date][$type][$count]["count"]=$v[$this->models[$category]["order"]->name]["count"];
					$list[$date][$type][$count]["name"] =$v[$this->models[$category]["master"]->name]["name"];
					$list[$date][$type][$count]["price"]=(Double)($list[$date][$type][$count]["count"]*$v[$this->models[$category]["history"]->name]["price"]);
					$list[$date][$type][$count]["cash"]["cash_type"]=$v["K9MasterCard"]["card_type"];
					$list[$date][$type][$count]["cash"][K9DataHistoryPriceCard::$TYPE_INCOME_DAY]=$late_date;
					$list[$date][$type][$count]["cash"]["type"]=$type;
				}
				break;
			}
		}

		return $list;
	}

}//END class

?>
