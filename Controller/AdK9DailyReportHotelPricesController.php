<?php

App::uses('K9BaseDailyReportController','Controller');

class AdK9DailyReportHotelPricesController extends K9BaseDailyReportController{

	var $name = "K9DailyReportHotelPricesController";

	function beforeFilter(){}

	private function __getReservationWithSchedules($reserve_ids)
	{

		$association=$this->K9DataReservation->association;
		$stay=$association["hasMany"]["K9DataSchedule"];
		$stay["conditions"]["and"]["K9DataSchedule.del_flg"]=0;
		$stay["order"]=array("K9DataSchedule.start_month_prefix ASC","K9DataSchedule.start_day ASC");

		$rest=$association["hasMany"]["K9DataReststaySchedule"];
		$rest["conditions"]["and"]["K9DataReststaySchedule.del_flg"]=0;
		$rest["order"]=array("K9DataReststaySchedule.start_month_prefix ASC","K9DataReststaySchedule.start_day ASC");

		$conditions["and"]["K9DataReservation.id"]=$reserve_ids;
		$this->K9DataReservation->unbindModel(array("belongsTo"=>"K9DataGuest"));
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedule"=>$stay,"K9DataReststaySchedule"=>$rest)));

		$payment=$association["hasOne"];
		$this->K9DataReservation->bindModel(array("hasOne"=>$payment));
		$this->K9DataCheckoutPayment->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataSchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataReststaySchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));

		$data=$this->K9DataReservation->find("all",array(
		
			"recursive"=>2,
			"conditions"=>$conditions
		));

		return $data;
	}

	public function __hotelPrices($reserve_ids,$day)
	{

		$data=array();
		if(!empty($reserve_ids)) $data=$this->__getReservationWithSchedules($reserve_ids);

		if(empty($data)) return;

		$stime=strtotime($day);
		$target_ym=date("Ym",$stime);
		$target_d =date("j",$stime);

		$reserve_ids     =array();
		$sort_dates      =array();
		$target_schedules=array();
		foreach($data as $k=>$v){

			$reservation=$v["K9DataReservation"];
			$schedule_model=$this->__stayTypeModel($v["K9DataReservation"]["staytype"]);
			$schedules=$v[$schedule_model->name];
			$first_schedule=$schedules[0];

			//日程が始まった日かを確認
			if($first_schedule["start_month_prefix"]!=$target_ym OR $first_schedule["start_day"]!=$target_d) continue;

			$reserve_ids[]=$reservation["id"];
			$last_schedule=$schedules[count($schedules)-1];
			$start_ymd=makeYmdByYmAndD($first_schedule["start_month_prefix"],$first_schedule["start_day"]);
			$end_ymd  =makeYmdByYmAndD($last_schedule["start_month_prefix"],$last_schedule["start_day"]);
			$sort_dates[]=$start_ymd;
			$sort_dates[]=$end_ymd;
			$target_schedules=array_merge($schedules,$target_schedules);
		}

		if(empty($reserve_ids)) return;

		$reservation_payment=Set::combine($data,"{n}.K9DataCheckoutPayment.reserve_id","{n}.K9DataCheckoutPayment.K9MasterCard.type");
		$data_reservations=Set::combine($data,"{n}.K9DataReservation.id","{n}.K9DataReservation");
		$schedule_plans=$this->__getSchedulePlans($reserve_ids);

		sort($sort_dates);
		$start_date=$sort_dates[0];
		$end_date  =$sort_dates[count($sort_dates)-1];
		$price_info=$this->__getPrice($reserve_ids,array(
		
			"start_date"=>$start_date,
			"end_date"  =>$end_date
		));

		$price_reststay_info=$this->__getReststayPrice(array(
		
			"start_date"=>$day,
			"end_date"  =>$day
		));

		$prices=array();
		foreach($target_schedules as $k=>$v){
		
			$reserve_id=$v["reserve_id"];
			$reservation=$data_reservations[$reserve_id];
			$ymd=makeYmdByYmAndD($v["start_month_prefix"],$v["start_day"]);
			$cash_type=$reservation_payment[$reservation["id"]];

			if(!isset($prices[$cash_type]["price"])) $prices[$cash_type]["price"]=0;
			if(!isset($prices[$cash_type]["reserve_ids"])) $prices[$cash_type]["reserve_ids"]=array();

			$weekday_price=weekdayPrice($reservation["weekday_price"],$ymd);
			if(!empty($weekday_price)){

				$prices[$cash_type]["price"]+=$weekday_price;
				$prices[$cash_type]["reserve_ids"][]=$reserve_id;
				continue;
			}

			$weekend_price=weekendPrice($reservation["weekend_price"],$ymd);
			if(!empty($weekend_price)){

				$prices[$cash_type]["price"]+=$weekend_price;
				$prices[$cash_type]["reserve_ids"][]=$reserve_id;
				continue;
			}

			$k9_plans=$this->__getPainByYmd($schedule_plans[$reserve_id],$ymd);
			$staytype=$reservation["staytype"];

			switch($staytype){
			
			case("stay"):

				$priceinfo=$this->__getPriceParYmd($ymd,$price_info,array("room_id"=>$k9_plans["room_id"],"room_type_id"=>$k9_plans["room_type_id"]));
				$price=$priceinfo["price"];
				$prices[$cash_type]["price"]+=$price;
				$prices[$cash_type]["reserve_ids"][]=$reserve_id;
				break;

			case("rest"):

				$priceinfo=$this->__getRestPriceParYmd($ymd,$price_reststay_info);
				$price=$priceinfo["price"];
				$prices[$cash_type]["price"]+=$price;
				$prices[$cash_type]["reserve_ids"][]=$reserve_id;
				break;
			}
		}

		return $prices;
	}

}//END class

?>
