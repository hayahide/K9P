<?php

class AdK9SearchBookingController extends AppController{

	var $name = "K9SearchBooking";

    var $uses = ["K9DataReservation","K9DataSchedule","K9DataReststaySchedule"];

	function beforeFilter(){
	
		parent::beforeFilter();
	}

	public function searchWithBookingNum()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$bookingnum =$post["bookingnum"];
		$current_day=$post["current_day"];
		if(!isEnableDateByYmd($current_day)) exit;

		if(!$reservation=$this->__getReservationByBookingNum($bookingnum)) Output::__outputNo(array("message"=>__("問い合わせに対する予約は存在しません")));
		$ymd=makeYmdByYmAndD($reservation["Schedule"]["start_month_prefix"],$reservation["Schedule"]["start_day"]);

		$diff=day_diff($current_day,$ymd);
		if($current_day>$ymd) $diff*=-1; 

		$res=array();
		$res["data"]["reservation"]["id"]=$reservation["K9DataReservation"]["id"];
		$res["data"]["date"]["start_day"]=$ymd;
		$res["data"]["date"]["day_diff"] =$diff;
		Output::__outputYes($res);
	}

	private function __getReservationByBookingNum($bookingnum)
	{
		// both schedule types check out.
		$association=&$this->K9DataReservation->association["hasMany"]["K9DataReststaySchedule"];
		$association["order"]=array("K9DataReststaySchedule.start_month_prefix ASC","K9DataReststaySchedule.start_day ASC");
		$association["conditions"]["and"]["K9DataReststaySchedule.del_flg"]=0;

		$association=&$this->K9DataReservation->association["hasMany"]["K9DataSchedule"];
		$association["order"]=array("K9DataSchedule.start_month_prefix ASC","K9DataSchedule.start_day ASC");
		$association["conditions"]["and"]["K9DataSchedule.del_flg"]=0;

		// get the first day which this reservation started.
		$this->K9DataReservation->hasOne["K9DataReststaySchedule"]=$this->K9DataReservation->association["hasMany"]["K9DataReststaySchedule"];;
		$this->K9DataReservation->hasOne["K9DataSchedule"]=$this->K9DataReservation->association["hasMany"]["K9DataSchedule"];;

		$conditions=array();
		$conditions["and"]["K9DataReservation.booking_id"]=$bookingnum;
		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));
		$data=$this->K9DataReservation->find("first",array( "conditions"=>$conditions ));
		if(empty($data)) return false;

		$schedule_model=$this->__stayTypeModel($data["K9DataReservation"]["staytype"]);

		$res=array();
		$res["K9DataReservation"]=$data["K9DataReservation"];
		$res["Schedule"]=$data[$schedule_model->name];
		return $res;
	}

}//END class

?>
