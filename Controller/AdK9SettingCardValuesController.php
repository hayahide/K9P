<?php

class AdK9SettingCardValuesController extends AppController{

	var $name = "K9SettingCardValues";

    var $uses = [

		"K9DataHistoryPriceCard"
    ];

	function beforeFilter(){
	
		parent::beforeFilter();
		$this->__setUseModel();
	}

	public function __setUseModel()
	{
		$this->loadModel("K9MasterCard");
	}

	public function __getCardInformationWithRate($card_id=null)
	{
		return $this->__getCardInformationWithType(K9DataHistoryPriceCard::$TYPE_RATE,$card_id);
	}

	public function __getCardInformationWithRange($card_id=null)
	{
		return $this->__getCardInformationWithType(K9DataHistoryPriceCard::$TYPE_INCOME_DAY,$card_id);
	}

	private function __getCardInformationWithType($type,$card_id=null)
	{
		//foolish of hasOne no need to use you.
		$association=$this->K9MasterCard->association["hasOne"]["K9DataHistoryPriceCard"];
		$association["order"]=array("K9DataHistoryPriceCard.enter_date DESC");
		$association["conditions"]["and"]["K9DataHistoryPriceCard.type"]=$type;
		$association["conditions"]["and"]["K9DataHistoryPriceCard.del_flg"]=0;
		$this->K9MasterCard->bindModel(array("hasMany"=>array("K9DataHistoryPriceCard"=>$association)));
		$cards=$this->K9MasterCard->getInformationOfCard($card_id);
		return $cards;
	}

	public function __getCardSettingsWithRate()
	{

		$cards=$this->__getCardInformationWithRate();

		$list=array();
		foreach($cards as $k=>$v){
		
			$id=$v["K9MasterCard"]["id"];
			$count=count($list);
			$list[$id]["card_type"]=$v["K9MasterCard"]["card_type"];
			$list[$id]["rate"]     =isset($v["K9DataHistoryPriceCard"][0]["value"])?$v["K9DataHistoryPriceCard"][0]["value"]:0;
		}

		return $list;
	}

	public function __getCardSettingsWithRange()
	{

		$cards=$this->__getCardInformationWithRange();

		$list=array();
		foreach($cards as $k=>$v){
		
			$id=$v["K9MasterCard"]["id"];
			$count=count($list);
			$list[$id]["card_type"]      =$v["K9MasterCard"]["card_type"];
			$list[$id]["income_late_day"]=isset($v["K9DataHistoryPriceCard"][0]["value"])?$v["K9DataHistoryPriceCard"][0]["value"]:0;
		}

		return $list;
	}

	public function cardInfoSubscribe()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		//v($post);
		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$card_id=$post["card_id"];
		$allow_types[]=K9MasterCard::$CASHTYPE_UNIONPAY;
		$allow_types[]=K9MasterCard::$CASHTYPE_VISA;
		$allow_types[]=K9MasterCard::$CASHTYPE_MASTER;
		$allow_types[]=K9MasterCard::$CASHTYPE_JCB;

		if(!in_array($card_id,$allow_types)) exit;

		$range  =(isset($post["range"]))?$post["range"]:false;
		$rate   =(isset($post["rate"])) ?$post["rate"]:false;
		$employee_id=$this->Auth->user("employee_id");

	    $datasource=$this->K9DataHistoryPriceCard->getDataSource();
	    $datasource->begin();

		$res=$this->__saveHistoryByType(K9DataHistoryPriceCard::$TYPE_RATE,$card_id,$rate,$employee_id);
		if(empty($res)) Output::__outputNo(__("正常に処理が終了しませんでした"));

		$res=$this->__saveHistoryByType(K9DataHistoryPriceCard::$TYPE_INCOME_DAY,$card_id,$range,$employee_id);
		if(empty($res)) Output::__outputNo(__("正常に処理が終了しませんでした"));

		$datasource->commit();

		$res=array();
		$res["data"]["cards"]["rate"] =$this->__getCardSettingsWithRate();
		$res["data"]["cards"]["range"]=$this->__getCardSettingsWithRange();
		Output::__outputYes($res);
	}

	private function __saveHistoryByType($type,$card_id,$value,$employee_id)
	{

		switch($type){

		case(K9DataHistoryPriceCard::$TYPE_RATE):

			$last_data=$this->__getCardInformationWithRate($card_id);
			break;

		case(K9DataHistoryPriceCard::$TYPE_INCOME_DAY):

			$last_data=$this->__getCardInformationWithRange($card_id);
			break;
		}

		$history=$last_data[0]["K9DataHistoryPriceCard"];
		$is_recorded=(isset($history[0]) AND is_numeric($history[0]["value"]));

		//no changed.
		if(!is_numeric($value)) return true;
		if(($is_recorded AND $history[0]["value"]==$value)) return true;

		$save["card_id"]=$card_id;
		$save["value"]  =$value;
		$save["type"]   =$type;
		$save["enter_date"]=date("YmdHis");
		$save["final_employee_entered"]=$employee_id;

		//same day.
		$today=date("Ymd");
		$this->K9DataHistoryPriceCard->id=null;
		if($is_recorded AND date("Ymd",strtotime($history[0]["enter_date"]))==$today) $this->K9DataHistoryPriceCard->id=$history[0]["id"];

		return $this->K9DataHistoryPriceCard->save($save);
	}

}//END class

?>
