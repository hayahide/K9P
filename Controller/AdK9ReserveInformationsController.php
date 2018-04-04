<?php

class AdK9ReserveInformationsController extends AppController {

	var $name = 'K9ReserveInformations';
	var $uses = [

		"K9DataReservation",
		"K9DataGuest",
	];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function getReservationInformations(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		//$post["hash"]="8c7f5199f5f4e6c627c8def2300c3add3f6f5276481a1362c25cdc61e71d1f1e";
		$hash=$post["hash"];
		$reservation=$this->K9DataReservation->getAllReserveInfomrationsByHash($hash,array(
		
			"recursive"=>2
		));

		$today=date("Ymd");
		$info=array();
		foreach($reservation as $k=>$v){
		
			$k9_reservation=$v["K9DataReservation"];
			$k9_guest      =$v["K9DataGuest"];
			$k9_schedule   =$v["K9DataSchedule"];
			$k9_plans      =$v["K9DataSchedulePlan"];
			$reserve_id=$k9_reservation["id"];

			$room_id=$this->__getRoomId($k9_plans);

			$guest_modified=date("Ymd",strtotime($k9_guest["modified"]));
			$info[$reserve_id]["reservation"]["hash"]    =$k9_reservation["hash"];
			$info[$reserve_id]["reservation"]["remarks"] =$k9_reservation["remarks"];
			$info[$reserve_id]["reservation"]["color_id"]=$k9_reservation["color_id"];
			$info[$reserve_id]["reservation"]["company_id"]=$k9_reservation["company_id"];
			$info[$reserve_id]["reservation"]["salesource_id"]=$k9_reservation["salesource_id"];
			$info[$reserve_id]["guest"]["id"]            =$k9_guest["id"];
			$info[$reserve_id]["guest"]["first_name"]    =$k9_guest["first_name"];
			$info[$reserve_id]["guest"]["middle_name"]   =$k9_guest["middle_name"];
			$info[$reserve_id]["guest"]["last_name"]     =$k9_guest["last_name"];
			$info[$reserve_id]["guest"]["remarks"]       =$k9_guest["remarks"];
			$info[$reserve_id]["guest"]["hash"]          =$k9_guest["hash"];
			$info[$reserve_id]["guest"]["tel"]           =decData($k9_guest["contact_tel"]);
			$info[$reserve_id]["guest"]["email"]         =decData($k9_guest["contact_email"]);
			$info[$reserve_id]["room"]["room_id"]        =$room_id;

			if(empty($k9_schedule)) continue;

			$counter=0;
			$target_dates=array();
			foreach($k9_schedule as $k=>$v){

				$ymd     =$v["start_month_prefix"].sprintf("%02d",$v["start_day"]);
				$info[$reserve_id]["schedule"]["info"][$counter]["ymd"]=$ymd;
				$info[$reserve_id]["schedule"]["info"][$counter]["color_id"]=$k9_reservation["color_id"];
				$info[$reserve_id]["schedule"]["info"][$counter]["remarks"]=$v["remarks"];
				$info[$reserve_id]["schedule"]["info"][$counter++]["id"]=$v["id"];
				if($today>$ymd) continue;
				$target_dates[]=$ymd;
			}

			//本日以降の日程
			$separate_days=separateRangeDays($target_dates);
			$info[$reserve_id]["schedule"]["range"]=$separate_days;
		}

		$res=array();
		$res["data"]=$info;
		Output::__outputYes($res);
	}

	//ここ、いずれ仕様変更される
	function __getRoomId($plans){

		if(empty($plans)) throw new Exception(__("部屋の登録情報が見つかりません"));

		//とりあえず最後のだけ
		$room_id=$plans[count($plans)-1]["room_id"];
		return $room_id;
	}

}
