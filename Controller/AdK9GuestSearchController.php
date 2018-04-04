<?php

class AdK9GuestSearchController extends AppController {

	var $name = 'K9GuestSearch';
	var $uses = [

		"K9DataGuest",
	];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function guestSearch(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$guests=$this->__getGuest($post);

		if(count($guests)>=100){

			$res=array();
			$res["message"]=__("候補が多過ぎます");
			$res["data"]=array();
			Output::__outputNo($res);
		}

		$list=array();
		if(!empty($guests)) $list=$this->__getGuestInformations($guests);

		$res=array();
		$res["data"]=$list;
		Output::__outputYes($res);
	}

	public function __getGuest($data)
	{

		$association=$this->K9DataGuest->association["hasMany"]["K9DataReservation"];
		$association["conditions"]["and"]["K9DataReservation.del_flg"]=0;
		$association["conditions"]["and"]["DATE_FORMAT(K9DataReservation.checkin_time,'%Y%m%d') > "]      ="00000000";
		$association["conditions"]["and"]["DATE_FORMAT(K9DataReservation.checkin_time,'%Y%m%d%H%i%s') < "]=date("YmdHis");
		$association["order"]=array("K9DataReservation.checkin_time DESC");
		$association["limit"]=2;
		$this->K9DataGuest->bindModel(array("hasMany"=>array("K9DataReservation"=>$association)));
	
		switch(true){
		
		case(isset($data["guest_hash"]) AND !empty($data["guest_hash"])):

			$guest_hash=$data["guest_hash"];
			$guests=$this->K9DataGuest->findAllByHashAndDelFlg($guest_hash,0);
			break;

		default:

			$firstname=$data["firstname"];
			$lastname =$data["lastname"];
			$guests=$this->K9DataGuest->searchGuestByNames($firstname,$lastname,array(
			
				"del_flg"=>0
			));
			break;
		}

		return $guests;
	}

	function __getGuestInformations($guests=array()){

		$list=array();
		$nametitles=getTSVNametitle();
		$incidential=getTSVClassfication();
		$language=getTSVLanguage();
		$country=getTSVCountryList();
		$nationality=getTSVNationalityList();

		foreach($guests as $k=>$v){
		
			$guest=$v["K9DataGuest"];
			$guest_modified=date("YmdHis",strtotime($guest["modified"]));
			$guest_id=$guest["id"];
			$list[$guest_id]["hash"]=$guest["hash"];

			$list[$guest_id]["passport"]=decData($guest["passport"]);
			if(empty($list[$guest_id]["passport"])) $list[$guest_id]["passport"]="";

			$list[$guest_id]["title"]          =escapeJsonString($nametitles[$guest["title"]]);
			$list[$guest_id]["title_num"]      =escapeJsonString($guest["title"]);
			$list[$guest_id]["incidential"]    =$incidential[$guest["incidential"]];
			$list[$guest_id]["incidential_num"]=$guest["incidential"];
			$list[$guest_id]["language"]       =$language[$guest["language_num"]];
			$list[$guest_id]["language_num"]   =$guest["language_num"];
			$list[$guest_id]["first_name"]     =escapeJsonString($guest["first_name"]);
			$list[$guest_id]["last_name"]      =escapeJsonString($guest["last_name"]);
			$list[$guest_id]["contact_address"]=escapeJsonString($guest["contact_address"]);
			$list[$guest_id]["contact_state"]  =escapeJsonString($guest["contact_state"]);
			$list[$guest_id]["contact_city"]   =escapeJsonString($guest["contact_city"]);
			$list[$guest_id]["contact_zip_code"]=escapeJsonString($guest["contact_zip_code"]);
			$list[$guest_id]["contact_country"]=$country[$guest["contact_country"]];
			$list[$guest_id]["contact_country_num"]=$guest["contact_country"];
			$list[$guest_id]["contact_nationality"]=$nationality[$guest["contact_nationality"]];
			$list[$guest_id]["contact_nationality_num"]=$guest["contact_nationality"];

			$list[$guest_id]["contact_tel"]=decData($guest["contact_tel"]);
			if(empty($list[$guest_id]["contact_tel"])) $list[$guest_id]["contact_tel"]="";
			$list[$guest_id]["contact_email"]=decData($guest["contact_email"]);
			if(empty($list[$guest_id]["contact_email"])) $list[$guest_id]["contact_email"]="";
			$list[$guest_id]["remarks"]=escapeJsonString($guest["remarks"]);

			if(1>count($v["K9DataReservation"])){

				$list[$guest_id]["reservation"]=array();
				continue;
			}

			foreach($v["K9DataReservation"] as $jk=>$_v){
			
				$reservation=array();
				$reservation["id"]=$_v["id"];
				$checkin_stime =strtotime($_v["checkin_time"]);
				$checkout_stime=strtotime($_v["checkout_time"]);
				$reservation["checkin_time"] =($checkin_stime>0) ?localDatetime(date("YmdHis",$checkin_stime)) :"-";
				$reservation["checkout_time"]=($checkout_stime>0)?localDatetime(date("YmdHis",$checkout_stime)):"-";
				$reservation["staytype"]     =$_v["staytype"];
				$list[$guest_id]["reservation"][]=$reservation;
			}
		}

		return $list;
	}

}
