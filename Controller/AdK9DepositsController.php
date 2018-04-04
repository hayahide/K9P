<?php

class AdK9DepositsController extends AppController{

	var $name = "K9Deposits";

	var $uses = ["K9DataReservation",
				 "K9MasterDipositReason",
	         	 "K9DataDipositReststaySchedule",
	         	 "K9DataDipositSchedule",
	         	 "K9DataSchedule",
				 "K9MasterCard",
	         	 "K9DataReststaySchedule"];

	function beforeFilter(){
	
		parent::beforeFilter();
	}

	public function getDiposits()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		
		$post=$this->data;
		//$post["schedule_id"]=1;
		//$post["staytype"]="stay";
		$schedule_id=$post["schedule_id"];
		$staytype   =$post["staytype"];

		$data=$this->__getDiposits($schedule_id,$staytype);

		$res=array();
		$res["data"]=$data;
		Output::__outputYes($res);
	}

	private function __menuCashTypes()
	{
		$data=$this->K9MasterCard->getCards(0);
		$data=Set::combine($data,"{n}.K9MasterCard.id","{n}.K9MasterCard.card_type");
		return $data;
	}

	private function __getDiposits($schedule_id,$staytype)
	{
		$data     =$this->__getDipositRegisterd($schedule_id,$staytype);
		$reasons  =$this->__menuReasons();
		$cashtypes=$this->__menuCashTypes();

		$res=array();
		$res["data"]=$data;
		$res["menus"]["reason"]  =$reasons;
		$res["menus"]["cashtype"]=$cashtypes;
		return $res;
	}

	private function __menuReasons()
	{
		$lang=Configure::read('Config.language');
		$fields=array("K9MasterDipositReason.id","K9MasterDipositReason.title_{$lang} as title");
		$data=$this->K9MasterDipositReason->find("all",array("fields"=>$fields));
		if(empty($data)) return array();
		return Set::combine($data,"{n}.K9MasterDipositReason.id","{n}.K9MasterDipositReason.title");
	}

	public function dipositSubscribe()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;
		//return;

		$staytype     =$post["staytype"];
		$schedule_id  =$post["schedule_id"];
		$models=$this->__stayTypeModel($staytype);
		$diposit_model=$models["diposit"];

		$datasource=$diposit_model->getDataSource();
		$datasource->begin();

		$new_values   =isset($post["new_values"])   ?$post["new_values"]:false;
		$edit_values  =isset($post["edit_values"])  ?$post["edit_values"]:false;
		$remove_values=isset($post["remove_values"])?$post["remove_values"]:false;

		if(!empty($new_values)){
		
			$res=$this->__insertNewDiposits($diposit_model,$new_values,$schedule_id);
			if(empty($res)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		}

		if(!empty($remove_values)){
		
			$res=$this->__removeDiposits($diposit_model,$remove_values);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		if(!empty($edit_values)){
		
			$res=$this->__editDiposits($diposit_model,$edit_values);
			if(empty($res)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		}

		$datasource->commit();

		$res=array();
		Output::__outputYes($res);
	}

	private function __removeDiposits(Model $diposit_model,$values)
	{
		$inserts=array();
		foreach($values as $k=>$data_id){
		
			$count=count($inserts);
			$inserts[$count]["id"]=$data_id;
			$inserts[$count]["del_flg"]=1;
			$inserts[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
		}

		try{
		
			$diposit_model->multiInsert($inserts);

		}catch(Exception $e){
		
			$res["message"]=$e->getMessage();
			$res["status"]=false;
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	private function __editDiposits(Model $diposit_model,$values)
	{
		if(empty($values)) return true;

		$data=array_shift($values);

		$save=array();
		$save["id"]         =$data["data_id"];
		$save["value"]      =$data["value"];
		$save["reason_id"]  =$data["reason_id"];
		$save["remarks"]    =$data["remarks"];
		$save["cash_type_id"]=$data["cash_type_id"];
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		$diposit_model->id=$data["data_id"];
		$res=$diposit_model->save($save);
		if(empty($res)) return false;
		return $this->__editDiposits($diposit_model,$values);
	}

	private function __insertNewDiposits(Model $diposit_model,$values,$schedule_id,$inserts=array())
	{
		if(empty($values)) return $inserts;

		$data=array_shift($values);

		$save=array();
		$save["schedule_id"]=$schedule_id;
		$save["value"]      =$data["value"];
		$save["reason_id"]  =$data["reason_id"];
		$save["remarks"]    =$data["remarks"];
		$save["cash_type_id"]=$data["cash_type_id"];
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		$diposit_model->id=null;
		$res=$diposit_model->save($save);
		if(empty($res)) return false;

		$inserts[]=$res[$diposit_model->name];
		return $this->__insertNewDiposits($diposit_model,$values,$schedule_id,$inserts);
	}

	protected function __stayTypeModel($staytype)
	{
		$diposit_model;
		$schedule_model=parent::__stayTypeModel($staytype);
		$schedule_diposit_model=parent::__stayTypeDipositModel($staytype);

		$res=array();
		$res["diposit"] =$schedule_diposit_model;
		$res["schedule"]=$schedule_model;
		return $res;
	}

	private function __getDipositRegisterd($schedule_id,$staytype)
	{
		$data=$this->__getInformations($schedule_id,$staytype);
		if(empty($data)) return array();

		$models=$this->__stayTypeModel($staytype);
		$diposit_model =$models["diposit"];
		$schedule_model=$models["schedule"];

		$list=array();
		foreach($data as $k=>$v){
		
			$id=$v[$diposit_model->name]["id"];
			$list[$id]["diposit"]["remarks"]=$v[$diposit_model->name]["remarks"];
			$list[$id]["diposit"]["value"]  =$v[$diposit_model->name]["value"];
			$list[$id]["diposit"]["reason_id"]=$v[$diposit_model->name]["reason_id"];
			$list[$id]["diposit"]["cash_type_id"]=$v[$diposit_model->name]["cash_type_id"];
			$list[$id]["diposit"]["regist_date"] =localDatetime($v[$diposit_model->name]["regist_date"]);
			$list[$id]["employee"]["name"]  =$v["K9MasterEmployee"]["first_name"];
			$list[$id]["employee"]["id"]    =$v["K9MasterEmployee"]["id"];
		}

		return $list;
	}

	private function __getInformations($schedule_id,$staytype)
	{

		$models=$this->__stayTypeModel($staytype);
		$diposit_model =$models["diposit"];
		$schedule_model=$models["schedule"];

		$conditions=array();
		$conditions["and"]["{$diposit_model->name}.schedule_id"]=$schedule_id;
		$conditions["and"]["{$diposit_model->name}.del_flg"]=0;
		$diposit_model->unbindModel(array("belongsTo"=>array($schedule_model->name,"K9MasterDipositReason")));
		$order=array("{$diposit_model->name}.created ASC");
		$data=$diposit_model->find("all",array(
		
			"conditions"=>$conditions,
			"order"=>$order,
		));

		return $data;
	}

}//END class

?>
