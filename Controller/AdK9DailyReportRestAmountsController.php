<?php

App::uses('K9BaseDailyReportController','Controller');

class AdK9DailyReportRestAmountsController extends K9BaseDailyReportController{

	var $name = "K9DailyReportRestAmounts";

	function beforeFilter(){}

	public function __setOrderModels()
	{
		parent::__setOrderModels();
	}

	public function __culcRestAmount($day)
	{
		$amounts=array();
		$cash_situation=$this->K9DataFund->getLastCashs($day);

		//銀行残高
		$amounts["bank"]=isset($cash_situation["bank"]["K9DataFund"])?$cash_situation["bank"]["K9DataFund"]["cash"]:0;

		//ホテル残高
		$amounts["cash"]=isset($cash_situation["cash"]["K9DataFund"])?$cash_situation["cash"]["K9DataFund"]["cash"]:0;

		//ホテル側(ホテル残高最終日付から)
		$cash_last_inputtime=isset($cash_situation["cash"]["K9DataFund"])?$cash_situation["cash"]["K9DataFund"]["enter_time"]:null;

		$amounts[K9MasterRoom::$HOTEL]=$this->__getCulcRestHotelAmount($cash_last_inputtime,$day);

		//注文側
		$amounts["order"]=$this->__getCulkRestOrderAmount($cash_last_inputtime,$day);

		return $amounts;
	}

	private function __getScheduleDateRange($data=array())
	{
		$max_day=null;
		$min_day=null;
		foreach($data as $k=>$v){
		
			$schedule_model=$this->__stayTypeModel($v["K9DataReservation"]["staytype"]);
			$schedules=$v["K9DataReservation"][$schedule_model->name];
			$start=makeYmdByYmAndD($schedules[0]["start_month_prefix"],$schedules[0]["start_day"]);
			$end  =makeYmdByYmAndD($schedules[count($schedules)-1]["start_month_prefix"],$schedules[count($schedules)-1]["start_day"]);

			if(empty($min_day)) $min_day=$start;
			if(empty($max_day)){
			
				$max_day=$end;
				continue;
			}

			$min_day=min($min_day,$start);
			$max_day=max($max_day,$end);
		}

		$res["min"]=$min_day;
		$res["max"]=$max_day;
		return $res;
	}

	private function __getCulcOrderCashByType($type,$cash_last_inputtime)
	{
		//here might ReportRebe sutressful....
		//foolish of hasOne no need to use you.
		$association=$this->K9MasterCard->association["hasOne"]["K9DataHistoryPriceCard"];
		$association["order"]=array("K9DataHistoryPriceCard.enter_date DESC");
		$association["conditions"]["and"]["K9DataHistoryPriceCard.type"]=K9DataHistoryPriceCard::$TYPE_INCOME_DAY;
		$association["conditions"]["and"]["K9DataHistoryPriceCard.del_flg"]=0;
		$this->K9MasterCard->bindModel(array("hasMany"=>array("K9DataHistoryPriceCard"=>$association)));

		$order_model =$this->models[$type]["order"];
		$master_model=$this->models[$type]["master"];
		$history_model=$this->models[$type]["history"];

		$history_model->unbindModel(array("belongsTo"=>array($master_model->name,"K9MasterEmployee")));
		$order_model->unbindModel(array("belongsTo"=>array("K9DataReservation",$master_model->name)));
		$data=$order_model->getOrderAfterEntertime($cash_last_inputtime,array(
		
			"recursive"=>2,
			"count"=>1
		));

		return $data;
	}

	private function __filterOrderWithCreditType($data,$day,$type)
	{

		if(empty($data)) return $data;

		$enable_order=array();
		//$today=date("Ymd");
		$target_day=$day;
		foreach($data as $k=>$v){

			//現金は、入金の猶予は存在しない
			//残高に含まれてない
			if($v["K9MasterCard"]["type"]=="cash"){
			
				$enable_order[$v["K9MasterCard"]["card_type"]][]=$v;
				continue;
			}

			//クレジット入金の日数を考慮(オーダー受けた日が基準)
			$order_model=$this->models[$type]["order"];
			$income_late_day=$this->__cardHistoryWithinaDay($v["K9MasterCard"]["K9DataHistoryPriceCard"],$day);
			$add_income_late_day=date("Ymd",strtotime("+ {$income_late_day} day",strtotime($v[$order_model->name]["created"])));

			//入金日が本日以降である場合、除外
			if($add_income_late_day>$target_day) continue;

			//入金されている、が残高に含まれてない
			$enable_order[$v["K9MasterCard"]["card_type"]][]=$v;
		}

		return $enable_order;
	}

	private function __allCulcOrderByTypes($cash_last_inputtime,$types,$day,$results=array())
	{

		if(empty($types)) return $results;

		$type=array_shift($types);
		$order=$this->__getCulcOrderCashByType($type,$cash_last_inputtime);
		$order=$this->__filterOrderWithCreditType($order,$day,$type);
		$results[$type]=$order;
		return $this->__allCulcOrderByTypes($cash_last_inputtime,$types,$day,$results);
	}

	private function __totalAmountOfOrders($orders)
	{
		$amounts=array();
		foreach($orders as $type=>$v){

			if(empty($v)) continue;
			foreach($v as $cash_type=>$_v){

				if(!isset($amounts[$cash_type])) $amounts[$cash_type]=0;

				$order_model  =$this->models[$type]["order"];
				$history_model=$this->models[$type]["history"];
				$amount_values=array();
				foreach($_v as $k=>$__v) $amount_values[]=$__v[$order_model->name]["count"]*$__v[$history_model->name]["price"];
				$__amount=array_sum($amount_values);
				$amounts[$cash_type]+=$__amount;
			}
		}

		return $amounts;
	}

	private function __culcRestStayAmountWithSchedules($data,$day)
	{

		if(empty($data)) return;

		$reservation_payment=Set::combine($data,"{n}.K9DataCheckoutPayment.reserve_id","{n}.K9MasterCard.type");
		$data_reservations=Set::combine($data,"{n}.K9DataReservation.id","{n}.K9DataReservation");
		$reserve_ids=array_keys($data_reservations);
		$schedule_plans=$this->__getSchedulePlans($reserve_ids);

		//日程の範囲
		$range=$this->__getScheduleDateRange($data);

		$price_info=$this->__getPrice($reserve_ids,array(
		
			"start_date"=>$range["min"],
			"end_date"  =>$range["max"]
		));

		$price_reststay_info=$this->__getReststayPrice(array(
		
			"start_date"=>$day,
			"end_date"  =>$day
		));

		$prices=array();
		foreach($data as $k=>$v){
		
			$reserve_id=$v["K9DataReservation"]["id"];
			$reservation=$data_reservations[$reserve_id];
			$staytype=$reservation["staytype"];

			$credit_type=$v["K9MasterCard"]["type"];
			$card_type=$v["K9MasterCard"]["card_type"];
			if(!isset($prices[$credit_type][$card_type])) $prices[$credit_type][$card_type]=0;

			$schedule_model=$this->__stayTypeModel($reservation["staytype"]);
			$schedules=$v["K9DataReservation"][$schedule_model->name];

			foreach($schedules as $k=>$schedule){

				$ymd=makeYmdByYmAndD($schedule["start_month_prefix"],$schedule["start_day"]);

				$weekday_price=weekdayPrice($reservation["weekday_price"],$ymd);
				if(!empty($weekday_price)){

					$prices[$credit_type][$card_type]+=$weekday_price;
					continue;
				}

				$weekend_price=weekendPrice($reservation["weekend_price"],$ymd);
				if(!empty($weekend_price)){

					$prices[$credit_type][$card_type]+=$weekend_price;
					continue;
				}

				$cash_type=$reservation_payment[$reserve_id];
				if(!isset($prices[$credit_type][$card_type])) $prices[$credit_type][$card_type]=0;

				$schedule_plan=$schedule_plans[$reserve_id];
				$k9_plans=$this->__getPainByYmd($schedule_plans[$reserve_id],$ymd);

				switch($staytype){
				
				case("stay"):

					$price=$this->__getPriceParYmd($ymd,$price_info,array(

						"room_id"                =>$k9_plans["room_id"],
						"room_type_id"           =>$k9_plans["room_type_id"],

					))["price"];

					$prices[$credit_type][$card_type]+=$price;
					break;

				case("rest"):

					$price=$this->__getRestPriceParYmd($ymd,$price_reststay_info)["price"];
					$prices[$credit_type][$card_type]+=$price;
					break;
				}
			}
		}

		return $prices;
	}

	private function __getCheckoutPaymentWithEnterTime($date)
	{

		$association=$this->K9DataReservation->association["hasMany"];

		$schedule_rest=$association["K9DataReststaySchedule"];
		$schedule_rest["conditions"]["and"]["K9DataReststaySchedule.del_flg"]=0;
		$schedule_rest["order"]=array("K9DataReststaySchedule.start_month_prefix ASC","K9DataReststaySchedule.start_day ASC");

		$schedule_stay=$association["K9DataSchedule"];
		$schedule_stay["conditions"]["and"]["K9DataSchedule.del_flg"]=0;
		$schedule_stay["order"]=array("K9DataSchedule.start_month_prefix ASC","K9DataSchedule.start_day ASC");

		$association=$this->K9MasterCard->association["hasOne"]["K9DataHistoryPriceCard"];
		$association["order"]=array("K9DataHistoryPriceCard.enter_date DESC");
		$association["conditions"]["and"]["K9DataHistoryPriceCard.type"]=K9DataHistoryPriceCard::$TYPE_INCOME_DAY;
		$association["conditions"]["and"]["K9DataHistoryPriceCard.del_flg"]=0;
		$this->K9MasterCard->bindModel(array("hasMany"=>array("K9DataHistoryPriceCard"=>$association)));
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataReststaySchedule"=>$schedule_rest,"K9DataSchedule"=>$schedule_stay)));
		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));
	
		$data=$this->K9DataCheckoutPayment->getCheckoutPaymentWithEnterTime($date,array(
		
			"recursive"=>2
		));

		return $data;
	}

	private function __culcRestAmountWithTargetDay($data,$day)
	{

		$enable_schedules=array();
		foreach($data as $k=>$v){
		
			$reservation=$v["K9DataReservation"];
			$card_info=$v["K9MasterCard"];
			$schedule_model=$this->__stayTypeModel($reservation["staytype"]);
			$schedules=$v["K9DataReservation"][$schedule_model->name];
			$first_schedule=$schedules[0];
			$first_schedule_ymd=makeYmdByYmAndD($first_schedule["start_month_prefix"],$first_schedule["start_day"]);

			switch($card_info["type"]){
			
			case("card"):

				//カード会社の入金猶予期間による
				$income_late_day=$this->__cardHistoryWithinaDay($card_info["K9DataHistoryPriceCard"],$day);

				//この日が入金される日
				$adddate=date("Ymd",strtotime("+ {$income_late_day} day",strtotime($first_schedule_ymd)));

				//入金されてない
				if($adddate>$day) continue;

				$enable_schedules[]=$v;
				break;

			case("cash"):

				$enable_schedules[]=$v;
				break;
			}
		}

		return $enable_schedules;
	}

	private function __getCulcRestHotelAmount($cash_last_inputtime,$day)
	{

		$total_amount=0;
		$data            =$this->__getCheckoutPaymentWithEnterTime($cash_last_inputtime);
		$enable_schedules=$this->__culcRestAmountWithTargetDay($data,$day);
		$stay_amount     =$this->__culcRestStayAmountWithSchedules($enable_schedules,$day);
		if(isset($stay_amount["cash"])) $total_amount+=$stay_amount["cash"]["cash"];
		if(isset($stay_amount["card"])) $total_amount+=array_sum($stay_amount["card"]);
		return $total_amount;
	}

	private function __getCulkRestOrderAmount($cash_last_inputtime,$day)
	{

		$types=array_keys($this->models);
		$orders =$this->__allCulcOrderByTypes($cash_last_inputtime,$types,$day);
		$order_amounts=$this->__totalAmountOfOrders($orders);
		return array_sum($order_amounts);
	}


}//END class

?>
