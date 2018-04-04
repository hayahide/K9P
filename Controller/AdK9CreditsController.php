<?php

class AdK9CreditsController extends AppController{

	var $name = "K9Credits";

    var $uses = [

		"K9DataCredit",
		"K9DataReservation",
		"K9DataSchedule",
		"K9MasterCard",
		"K9DataReststaySchedule"
    ];

	function beforeFilter(){
	
		parent::beforeFilter();
	}

	public function getCreditSituation()
	{

		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();
		
		$reservation_hash=$post["reservation_hash"];
		$credit_data=$this->__getCreditSituation($reservation_hash);
		if(empty($credit_data["status"])) Output::__outputNo(array("message"=>$credit_data["message"]));
		//v($credit_data);

		$cashtypes_menu=$this->__getCardTypes();
		$res["data"]["date"]     =$credit_data["data"]["date"];
		$res["data"]["credit"]   =$credit_data["data"]["credit"];
		$res["data"]["cashtypes"]=$cashtypes_menu;
		Output::__outputYes($res);
	}

	function __getCardTypes(){

		$cards=$this->K9MasterCard->getCards(0);

		$list=array();
		foreach($cards as $k=>$v){

			switch($v["K9MasterCard"]["type"]){
			
			case("cash"):

				$list[$v["K9MasterCard"]["id"]]=$v["K9MasterCard"]["card_type"];
				break;

			case("card"):

				$list[$v["K9MasterCard"]["id"]]="{$v["K9MasterCard"]["type"]}({$v["K9MasterCard"]["card_type"]})";
				break;
			}
		} 

		return $list;
	}

	function __getCreditSituation($reservation_hash)
	{

		$reservation_data=$this->__getReservationByHash($reservation_hash);
		if(empty($reservation_data)){

			$res["status"] =false;
			$res["message"]=__("正常に処理が終了しませんでした");
			return $res;
		} 

		$staytype=$reservation_data["K9DataReservation"]["staytype"];
		$schedule_model=$this->__stayTypeModel($staytype);
		$schedules=$reservation_data[$schedule_model->name];
		
		$target_dates=array();
		foreach($schedules as $k=>$v) $target_dates[]=$v["start_month_prefix"].sprintf("%02d",$v["start_day"]);

		$cashtypes_menu=$this->__getCardTypes();

		$credit_data=$this->__getCreditsByTargetDates($reservation_data["K9DataReservation"]["id"],$target_dates);

		$schedule_menu=$this->__scheduleMenus($schedules);

		$res["status"]=true;
		$res["data"]["date"]  =$schedule_menu;
		$res["data"]["credit"]=$credit_data;
		$res["data"]["cashtypes"]=$cashtypes_menu;
		return $res;
	}

	public function creditSubscribe()
	{

		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();
		
		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);
		
		$reservation_hash=$post["reservation_hash"];
		$new_credits    =isset($post["new"])?$post["new"]:false;
		$current_credits=isset($post["current"])?$post["current"]:false;
		$remove_menu_ids=isset($post["remove_menu_ids"])?$post["remove_menu_ids"]:false;

		$reservation=$this->__getReservationByHash($reservation_hash);
		if(empty($reservation)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$staytype=$reservation["K9DataReservation"]["staytype"];
		$schedule_model=$this->__stayTypeModel($staytype);

	    $datasource=$this->K9DataCredit->getDataSource();
	    $datasource->begin();

		if(!empty($current_credits)){
		
			$res=$this->K9DataCredit->updateCredits($current_credits,$this->Auth->user("employee_id"));
		}

		if(!empty($new_credits)){

			$reserve_id=$reservation["K9DataReservation"]["id"];
			$res=$this->K9DataCredit->insertCredits($new_credits,$reserve_id,$this->Auth->user("employee_id"));
		}

		if(!empty($remove_menu_ids)){

			$res=$this->K9DataCredit->removeCredits($remove_menu_ids,$this->Auth->user("employee_id"));
		}

		$datasource->commit();
		$credit_data=$this->__getCreditSituation($reservation_hash);

		$res=array();
		$res["data"]=$credit_data;
		Output::__outputYes($res);
	}

	public function __scheduleMenus($schedules)
	{
		
		$list=array();
		foreach($schedules as $k=>$v){

			$ymd=$v["start_month_prefix"].sprintf("%02d",$v["start_day"]);
			$list[$ymd]=localDate($ymd);
		} 

		return $list;
	}

	public function __getCreditsByTargetDates($reserve_id,$target_dates=array())
	{
		
		$this->K9DataSchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataReststaySchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));

		$conditions["and"]["K9DataCredit.reserve_id"]=$reserve_id;
		$conditions["and"]["DATE_FORMAT(K9DataCredit.enter_date,'%Y%m%d')"]=$target_dates;
		$conditions["and"]["K9DataCredit.del_flg"]    =0;
		$data=$this->K9DataCredit->find("all",array(
		
			"conditions"=>$conditions,
			"recursive" =>2
		));

		if(empty($data)) return array();

		$list=array();
		foreach($data as $k=>$v){

			$ymd=date("Ymd",strtotime($v["K9DataCredit"]["enter_date"]));
			if(!isset($list[$ymd])) $list[$ymd]=array();

			$credit_id=$v["K9DataCredit"]["id"];
			$list[$ymd][$credit_id]["credit"]["id"]        =$v["K9DataCredit"]["id"];
			$list[$ymd][$credit_id]["credit"]["price"]     =$v["K9DataCredit"]["price"];
			$list[$ymd][$credit_id]["credit"]["enter_date"]=localDatetime($v["K9DataCredit"]["enter_date"]);
			$list[$ymd][$credit_id]["credit"]["remarks"]   =escapeJsonString($v["K9DataCredit"]["remarks"]);
			$list[$ymd][$credit_id]["credit"]["cash_type_id"]=$v["K9DataCredit"]["cash_type_id"];
			$list[$ymd][$credit_id]["employee"]["id"]      =$v["K9MasterEmployee"]["id"];
			$list[$ymd][$credit_id]["employee"]["name"]    =$v["K9MasterEmployee"]["first_name"];
		}

		return $list;
	}

	function __getReservationByHash($reservation_hash){

		$association=$this->K9DataReservation->association;

		$schedule_association=$association["hasMany"][$this->K9DataSchedule->name];
		$schedule_association["conditions"]=array("{$this->K9DataSchedule->name}.del_flg"=>"0");
		$schedule_association["order"]=array("{$this->K9DataSchedule->name}.start_month_prefix ASC","{$this->K9DataSchedule->name}.start_day ASC");

		$reststay_schedule_association=$association["hasMany"][$this->K9DataReststaySchedule->name];
		$reststay_schedule_association["conditions"]=array("{$this->K9DataReststaySchedule->name}.del_flg"=>"0");
		$reststay_schedule_association["order"]=array("{$this->K9DataReststaySchedule->name}.start_month_prefix ASC","{$this->K9DataReststaySchedule->name}.start_day ASC");

		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedule"=>$schedule_association,"K9DataReststaySchedule"=>$reststay_schedule_association)));
		$conditions["and"]["{$this->K9DataReservation->name}.hash"]=$reservation_hash;
		$conditions["and"]["{$this->K9DataReservation->name}.del_flg"]=0;
		$data=$this->K9DataReservation->find("first",array(
		
			"conditions"=>$conditions,
		));

		return $data;
	}

}//END class

?>
