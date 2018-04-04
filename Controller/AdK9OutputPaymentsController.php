<?php

class AdK9OutputPaymentsController extends AppController{

	var $name = "K9OutputPayments";

    var $uses = [

		"K9DataOutputPayment" ];

	function beforeFilter(){
	
		parent::beforeFilter();
	}

	public function outputPaymentSubscribe()
	{
		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$current_data=isset($post["current_data"])?$post["current_data"]:false;
		$new_data    =isset($post["new_data"])?$post["new_data"]:false;
		$remove_ids  =isset($post["remove_ids"])?$post["remove_ids"]:false;
		$ymd=$post["ymd"];

		$datasource=$this->K9DataOutputPayment->getDataSource();
		$datasource->begin();

		if(!empty($current_data)){

			$res=$this->__saveOutputPaymentCurrent($current_data);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		if(!empty($new_data)){

			$res=$this->__saveOutputPaymentNewData($new_data,$ymd);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		if(!empty($remove_ids)){

			$res=$this->__removeOutputPayment($remove_ids);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		$datasource->commit();

		//面倒なので既存のデータを返すw
		$data=$this->__getData($ymd);

		$res["data"]=$data;
		Output::__outputYes($res);
	}

	private function __removeOutputPayment($data)
	{
		
		$remove=array();
		foreach($data as $k=>$data_id){

			$count=count($remove);
			$remove[$count]["id"]=$data_id;
			$remove[$count]["del_flg"]=1;
			$remove[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
		}

		try{
		
			$this->K9DataOutputPayment->multiInsert($remove);

		}catch(Exception $e){

			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	private function __saveOutputPaymentNewData($data,$ymd,$_res=array())
	{
		if(empty($data)){

			$res=array();
			$res["status"]=true;
			$res["data"]=$_res;
			return $res;
		}

		$__data=array_shift($data);

		$save["store"]  =$__data["store"];
		$save["item"]   =$__data["item"];
		$save["price"]  =$__data["price"];
		$save["remarks"]=$__data["remarks"];
		$save["buyer_id"]=$__data["buyer_id"];
		$save["day"]    =$ymd;
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		$this->K9DataOutputPayment->id=null;
		if(!$__res=$this->K9DataOutputPayment->save($save)){
		
			$res=array();
			$res["status"]=false;
			$res["message"]=__("正常に処理が終了しませんでした");
			return $res;
		} 

		$_res[]=$__res["K9DataOutputPayment"];
		return $this->__saveOutputPaymentNewData($data,$ymd,$_res);
	}

	private function __saveOutputPaymentCurrent($data)
	{

		$inserts=array();
		foreach($data as $data_id=>$v){
		
			$count=count($inserts);
			$inserts[$count]["id"]=$data_id;
			$inserts[$count]["store"]=$v["store"];
			$inserts[$count]["item"] =$v["item"];
			$inserts[$count]["price"]=$v["price"];
			$inserts[$count]["buyer_id"]=$v["buyer_id"];
			$inserts[$count]["remarks"]=$v["remarks"];
			$inserts[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
		}

		try{
		
			$this->K9DataOutputPayment->multiInsert($inserts);

		}catch(Exception $e){

			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	public function getOutputPayment()
	{

		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();

		$ymd=$post["ymd"];
		$data=$this->__getData($ymd);

		$employees=$this->K9MasterEmployee->getEmployees();
		$employees=Set::combine($employees,"{n}.K9MasterEmployee.id","{n}.K9MasterEmployee.first_name");

		$res=array();
		$res["data"]["data"]=$data;
		$res["data"]["menu"]["employee"]=$employees;
		Output::__outputYes($res);
	}

	private function __getData($ymd)
	{
		$list=array();
		$data=$this->__getOutputPayment($ymd);
		if(empty($data)) return $list;

		foreach($data as $k=>$v){
		
			$id=$v["K9DataOutputPayment"]["id"];
			$list[$id]["data"]["store"]=$v["K9DataOutputPayment"]["store"];
			$list[$id]["data"]["item"] =$v["K9DataOutputPayment"]["item"];
			$list[$id]["data"]["price"]=$v["K9DataOutputPayment"]["price"];
			$list[$id]["data"]["buyer_id"]=$v["K9DataOutputPayment"]["buyer_id"];
			$list[$id]["data"]["remarks"]=$v["K9DataOutputPayment"]["remarks"];
			$list[$id]["employee"]["id"]=$v["K9MasterEmployee"]["id"];
			$list[$id]["employee"]["first_name"]=$v["K9MasterEmployee"]["first_name"];
		}

		return $list;
	}

	public function __getOutputPayment($ymd)
	{
		
		$conditions["and"]["K9DataOutputPayment.day"]=date("Y-m-d",strtotime($ymd));
		$conditions["and"]["K9DataOutputPayment.del_flg"]=0;
		return $this->K9DataOutputPayment->find("all",array(
		
			"conditions"=>$conditions,
			"recursive" =>1
		));
	}

}//END class

?>
