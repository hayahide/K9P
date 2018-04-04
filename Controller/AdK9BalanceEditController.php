<?php

class AdK9BalanceEditController extends AppController{

	var $name = "K9BalanceEdit";

    var $uses = [];

	function beforeFilter(){
	
		parent::beforeFilter();

		$this->loadModel("K9DataFund");
	}

	public function getBalanceHistory()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;
		$month=isset($post["month"])?$post["month"]:1;

		$balance_histories=$this->__getBalanceHistory($month);

		$res=array();
		$res["data"]=$balance_histories;
		Output::__outputYes($res);
	}

	private function __subscribeOfBalance($type,$value,$last_data)
	{
		if(!is_numeric($value)) return true;
		
		switch(true){

		case(empty($last_data[$type])):

			$res=$this->K9DataFund->balanceSubscribe($type,$value,$this->Auth->user("employee_id"));
			if(empty($res)) return false;
			break;

		case($last_data[$type]["K9DataFund"]["cash"]!=$value):

			switch(date("Ymd",strtotime($last_data[$type]["K9DataFund"]["enter_time"]))==date("Ymd")){
			
			case(true):

				$id=$last_data[$type]["K9DataFund"]["id"];
				$res=$this->K9DataFund->balanceEdit($id,$type,$value,$this->Auth->user("employee_id"));
				if(empty($res)) return false;
				break;

			case(false):

				$res=$this->K9DataFund->balanceSubscribe($type,$value,$this->Auth->user("employee_id"));
				if(empty($res)) return false;
				break;
			}
	
			break;

		case($last_data[$type]["K9DataFund"]["cash"]==$value):

			//更新時間の上書きがメイン
			$id=$last_data[$type]["K9DataFund"]["id"];
			$res=$this->K9DataFund->balanceEdit($id,$type,$value,$this->Auth->user("employee_id"));
			if(empty($res)) return false;
			break;
		}

		return true;
	}

	public function balanceSubscribe()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$month=isset($post["month"])?$post["month"]:1;
		$cash_value=isset($post[K9DataFund::$CASH])?$post[K9DataFund::$CASH]:false;
		$bank_value=isset($post[K9DataFund::$BANK])?$post[K9DataFund::$BANK]:false;

		$day=date("Ymd");
		$last_cash=$this->K9DataFund->getLastCashs($day);

		$datasource=$this->K9DataFund->getDataSource();
		$datasource->begin();

		$res=$this->__subscribeOfBalance(K9DataFund::$BANK,$bank_value,$last_cash);
		if(empty($res)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$this->__subscribeOfBalance(K9DataFund::$CASH,$cash_value,$last_cash);
		if(empty($res)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$datasource->commit();

		$res=array();
		$res["data"]=$this->__getBalanceHistory($month);
		Output::__outputYes($res);
	}

	private function __makeDataList($data=array())
	{

		$list=array();
		foreach($data as $k=>$v){
		
			$cash_type=$v["K9DataFund"]["cash_type"];
			if(!isset($list[$cash_type])) $list[$cash_type]=array();
			$count=count($list[$cash_type]);
			$list[$cash_type][$count]["data"]["id"]  =$v["K9DataFund"]["id"];
			$list[$cash_type][$count]["data"]["cash"]=(Double)$v["K9DataFund"]["cash"];
			$list[$cash_type][$count]["data"]["enter_time"]=localDatetime($v["K9DataFund"]["enter_time"]);
			$list[$cash_type][$count]["employee"]["name"]=$v["K9MasterEmployee"]["first_name"];
		}

		return $list;
	}

	private function __getBalanceHistory($month)
	{
		
		$start_ymd=date("Ymd",strtotime("- {$month} month",time()));
		$end_ymd  =date("Ymd");
		$data=$this->K9DataFund->getBalanceHistoriesWithRange($start_ymd,$end_ymd,array(
		
			"order"=>array("{$this->K9DataFund->name}.enter_time DESC")
		));

		if(empty($data)){
		
			$res=array();
			$res[K9DataFund::$BANK]=array();
			$res[K9DataFund::$CASH]=array();
			return $res;
		}

		return $this->__makeDataList($data);
	}

}//END class

?>
