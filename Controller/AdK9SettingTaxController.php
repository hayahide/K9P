<?php

class AdK9SettingTaxController extends AppController{

	var $name = "K9SettingTax";

    var $uses = [

		"K9DataHistoryRateTax"
    ];

	function beforeFilter(){
	
		parent::beforeFilter();
	}

	public function __getTaxvalueWithRange($day)
	{
		$value=0;
		$data=$this->K9DataHistoryRateTax->getTax();
		if(empty($data)) return $value;

		foreach($data as $k=>$v){
		
			if(date("Ymd",strtotime($v["K9DataHistoryRateTax"]["enter_date"]))>$day) continue;
			$value=$v["K9DataHistoryRateTax"]["value"];
			break;
		}

		return $value;
	}

	public function __getTaxSettingWithRange()
	{

		$data=$this->__getTax();
		if(empty($data)) return 0;
		return $data[0]["K9DataHistoryRateTax"]["value"];
	}

	private function __getTax()
	{
		$data=$this->K9DataHistoryRateTax->getTax();
		return $data;
	}

	public function taxSubscribe()
	{

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$tax=$post["tax"];

		$last_tax_rate=$this->__getTax();
		$res=$this->__taxSubscribe($tax);
		if(empty($res)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$res=array();
		$res["data"]["tax"]=$tax;
		Output::__outputYes($res);
	}

	private function __taxSubscribe($tax)
	{
		$last_tax_rate=$this->__getTax();
		
		$save=array();

		switch(true){
		
		//値が同じ
		case(count($last_tax_rate)>0 AND $last_tax_rate[0]["K9DataHistoryRateTax"]["value"]==$tax):

			return true;
			break;

		//同じ日、値は違う
		case(count($last_tax_rate)>0 AND date("Ymd",strtotime($last_tax_rate[0]["K9DataHistoryRateTax"]["enter_date"]))==date("Ymd")):

			$save["id"]=$last_tax_rate[0]["K9DataHistoryRateTax"]["id"];
			$save["value"]=$tax;
			$save["enter_date"]=date("YmdHis");
			$save["final_employee_entered"]=$this->Auth->user("employee_id");
			break;

		//同じ日ではい
		default:

			$this->K9DataHistoryRateTax->id=null;
			$save["value"]=$tax;
			$save["enter_date"]=date("YmdHis");
			$save["final_employee_entered"]=$this->Auth->user("employee_id");
			break;
		}

		return $this->K9DataHistoryRateTax->save($save);
	}

}//END class

?>
