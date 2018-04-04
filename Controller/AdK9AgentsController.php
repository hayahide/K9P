<?php

class AdK9AgentsController extends AppController{

	var $name = "K9Agents";
    var $uses=["K9DataReservation","K9MasterAgencyType","K9MasterReservationSalesource"];

	function beforeFilter(){
	
		parent::beforeFilter();
		$this->loadModel("K9DataCompany");
	}

	public function agentSubscribe()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$last_id=$this->__agentSubscribe($post);
		if(empty($last_id)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$res=array();
		$res["data"]["id"]=$last_id;
		Output::__outputYes($res);
	}

	public function agentListSubscribe()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$edit_items  =isset($post["edit_items"])  ?$post["edit_items"]:null;
		$remove_items=isset($post["remove_items"])?$post["remove_items"]:null;

	    $datasource=$this->K9DataCompany->getDataSource();
	    $datasource->begin();

		if(!empty($edit_items)){

			$res=$this->__editAgency($edit_items);
			if(empty($res)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		} 

		if(!empty($remove_items)){
		
			$res=$this->__removeAgency($remove_items);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));

			$res=$this->__resetAgencyIdForReservation($remove_items);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		$datasource->commit();

		$res=array();
		$res["data"]=$this->__getAgents(null);
		Output::__outputYes($res);
	}

	private function __resetAgencyIdForReservation($agent_ids=array())
	{
		try{ $this->K9DataReservation->resetToDefaultCompanyId($agent_ids);

		}catch(Exception $e){

			$res=array();
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res=array();
		$res["status"]=true;
		return $res;
	}

	private function __removeAgency($remove_items)
	{
		$list=array();
		foreach($remove_items as $k=>$agency_id){

			$count=count($list);
			$list[$count]["id"]     =$agency_id;
			$list[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
			$list[$count]["del_flg"]=1;
		}

		try{ $this->K9DataCompany->multiInsert($list);

		}catch(Exception $e){
		
			$res=array();
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res=array();
		$res["status"]=true;
		return $res;
	}

	private function __editAgency($edit_items,$results=array())
	{
		if(empty($edit_items)) return $results;

		$edit_item=array_shift($edit_items);
		$ymd=date("Ymd",strtotime($edit_item["register_date"]));
		if(!isYmd($ymd)) return false;

		$save=array();
		$save["id"]           =$edit_item["id"];
		$save["name"]         =$edit_item["name"];
		$save["vtno"]         =$edit_item["vtno"];
		$save["address"]      =$edit_item["address"];
		$save["salesource_id"]=$edit_item["salesource_id"];
		$save["register_date"]=$ymd;
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		$this->K9DataCompany->id=$edit_item["id"];
		if(!$res=$this->K9DataCompany->save($save)) return false;
		$results[]=$res["K9DataCompany"];
		return $this->__editAgency($edit_items,$results);
	}

	private function __agentSubscribe($data=array())
	{
		$save=array();
		if(isset($data["agent_id"])) $save["id"]=$data["agent_id"];
		$save["name"]=$data["agent_companyname"];
		$save["phone"]=$data["agent_contact"];
		$save["fax"]=$data["agent_fax"];
		$save["agency_id"]=$data["agent_type"];
		$save["credit_limited"]=$data["agent_credit_limited"];
		$save["number_due_date"]=$data["agent_number_duedate"];
		$save["register_date"]=date("Y-m-d",strtotime($data["agent_registdate"]));
		$save["vtno"]=$data["agent_vatno"];
		$save["salesource_id"]=$data["agent_salesource"];
		$save["address"]=$data["agent_address"];
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		if(!$res=$this->K9DataCompany->save($save)) return false;
		if(!isset($res["K9DataCompany"]["id"])) return $this->K9DataCompany->getLastInsertID();
		return $res["K9DataCompany"]["id"];
	}

	public function getAgentMenus()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$res=array();
		$res["data"]=$this->__getMenus();
		Output::__outputYes($res);
	}

	public function getAgentInformation()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$agent_id=isset($post["agent_id"])?$post["agent_id"]:null;
		$res=$this->__getAgentInformation($agent_id);
		Output::__outputYes($res);
	}

	private function __getAgentInformation($agent_id)
	{
		$res=array();
		$res["data"]["data"]=$this->__getAgents($agent_id);
		$res["data"]["menu"]=$this->__getMenus();
		return $res;
	}

	private function __getAgentTypeMenus()
	{

		$conditions=array();
		$conditions["and"]["K9MasterAgencyType.del_flg"]=0;
		$this->K9MasterAgencyType->unbindFully();
		$order=array("K9MasterAgencyType.name ASC");
		$data=$this->K9MasterAgencyType->find("all",array( "conditions"=>$conditions,"order"=>$order ));
		if(empty($data)) return array();
		return Set::combine($data,"{n}.K9MasterAgencyType.id","{n}.K9MasterAgencyType.name");
	}

	private function __getMenus()
	{
		$res=array();
		$res["agents"]=$this->__getAgentTypeMenus();
		$res["salesources"]=$this->K9MasterReservationSalesource->getSaleSource();
		return $res;
	}

	private function __getAgents($id=array())
	{

		$this->K9DataCompany->belongsTo["K9MasterAgencyType"]["order"]=array("K9MasterAgencyType.name ASC");
		$data=$this->K9DataCompany->getAgentsByIds($id,array(K9DataCompany::$STATUS_PUBLIC));
		if(empty($data)) return array();

		$list=array();
		foreach($data as $k=>$v){
		
			$id=$v["K9DataCompany"]["id"];
			$agent_type_id=$v["K9MasterAgencyType"]["id"];
			if(!isset($list[$agent_type_id])) $list[$agent_type_id]=array();
			$list[$agent_type_id][$id]["agency"]["name"]           =escapeJsonString($v["K9DataCompany"]["name"]);
			$list[$agent_type_id][$id]["agency"]["phone"]          =escapeJsonString($v["K9DataCompany"]["phone"]);
			$list[$agent_type_id][$id]["agency"]["fax"]            =escapeJsonString($v["K9DataCompany"]["fax"]);
			$list[$agent_type_id][$id]["agency"]["address"]        =escapeJsonString($v["K9DataCompany"]["address"]);
			$list[$agent_type_id][$id]["agency"]["vtno"]           =escapeJsonString($v["K9DataCompany"]["vtno"]);
			$list[$agent_type_id][$id]["agency"]["id"]             =$v["K9DataCompany"]["agency_id"];
			$list[$agent_type_id][$id]["agency"]["credit_limited"] =escapeJsonString($v["K9DataCompany"]["credit_limited"]);
			$list[$agent_type_id][$id]["agency"]["number_due_date"]=escapeJsonString($v["K9DataCompany"]["number_due_date"]);
			$list[$agent_type_id][$id]["agency"]["register_date"]  =localDateNormalUtime(strtotime($v["K9DataCompany"]["register_date"]));
			$list[$agent_type_id][$id]["agency"]["salesource_id"]  =$v["K9DataCompany"]["salesource_id"];
			$list[$agent_type_id][$id]["staff"]["id"]              =$v["K9MasterEmployee"]["id"];
			$list[$agent_type_id][$id]["staff"]["name"]            =$v["K9MasterEmployee"]["first_name"];
		}

		return $list;
	}

}//END class

?>
