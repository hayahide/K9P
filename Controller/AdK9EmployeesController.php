<?php

App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
class AdK9EmployeesController extends AppController{

	var $name = "K9Employees";
    var $uses = [

		"K9MasterEmployee",
		"K9MasterEmployeeAccount"
    ];

	function beforeFilter(){
	
		parent::beforeFilter();

		if(!HEADER_EMPLOYEE) exit;
	}

	function saveEmployee(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		if(isset($post["user"])){

			try{ 
				
				$employee=$this->__validateUser($post["user"]);

			}catch(Exception $e){ Output::__outputNo(array("message"=>$e->getMessage())); }
		}

		try{ 
			
			$this->__validateData($post);

		}catch(Exception $e){ Output::__outputNo(array("message"=>$e->getMessage())); }

	    $datasource=$this->K9MasterEmployee->getDataSource();
	    $datasource->begin();

		try{
		
			$employee_id=null;
			if(isset($employee["K9MasterEmployee"]["id"])) $employee_id=$employee["K9MasterEmployee"]["id"];
			$res=$this->__saveEmployee($post,$employee_id);

		}catch(Exception $e){ Output::__outputNo(array("message"=>$e->getMessage())); }

		$hash       =$res["K9MasterEmployee"]["hash"];
		$employee_id=$res["K9MasterEmployee"]["id"];

		try{ 
			
			$this->__saveEmployeeAccount($post,$employee_id);

		}catch(Exception $e){ Output::__outputNo(array("message"=>$e->getMessage())); }

		$datasource->commit();

		$res=array();
		$res["data"]["hash"]=$hash;
		Output::__outputYes($res);
	}

	function __saveEmployee($post,$employee_id){

		$ymdhis=date("YmdHis");

		switch(true){
		case(empty($employee_id)):
			$this->K9MasterEmployee->create();
			break;
		default:
			$save["id"]=$employee_id;
			break;
		}

		$save["hash"]=makeHash($ymdhis);
		$save["first_name"]=$post["first_name"];
		$save["middle_name"]=$post["middle_name"];
		$save["job_title"]=$post["jobtitle"];
		$save["last_name"]=$post["last_name"];
		$save["authority"]=$post["authority"];
		$save["remark"]=$post["remark"];
		$save["modified"]=$ymdhis;
		if(!$res=$this->K9MasterEmployee->save($save)) throw new Exception(__("登録処理に失敗しました"."(3)"));
		if(empty($res["K9MasterEmployee"]["id"])) $res["K9MasterEmployee"]["id"]=$this->K9MasterEmployee->getLastInsertID();
		return $res;
	}

	function __saveEmployeeAccount($post,$employee_id){
	
		$account=$post["account"];
		$save["employee_id"]=$employee_id;
		$save["username"]=$account;
		if(!empty($post["password"])) $save["password"]=makePassword($post['password']);
		if(!$this->K9MasterEmployeeAccount->save($save)) throw new Exception(__("登録処理に失敗しました"."(4)"));
		return true;
	}

	function __validateUser($data){
	
		if(!isset($data["hash"])) return array();
		if(!$employee=$this->K9MasterEmployee->findByHash($data["hash"])) throw new Exception(__("登録処理に失敗しました")."(1)");
		return $employee;
	}

	function __validateData($data){

		$is_new=!isset($data["user"]["hash"]);

		if(!in_array($data["authority"],array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF,K9MasterEmployee::$AUTH_STAFF)))
		   	throw new Exception(__("登録処理に失敗しました"."(2)"));

		if((!empty($data["password"]) OR !empty($data["password_conf"])) AND ($data["password"]!=$data["password_conf"])) 
			throw new Exception(__("パスワードが一致しません"));

		if($is_new AND in_array($data["authority"],array(K9MasterEmployee::$AUTH_CHIEF,K9MasterEmployee::$AUTH_STAFF)) AND (empty($data["password"]) OR empty($data["password_conf"])))
		   	throw new Exception(__("パスワードの設定が不正です"));

		if(empty($data["account"])){

			$message=__("#data#が未入力です");
			$message=str_replace("#data#",__("アカウント"),$message);
			throw new Exception($message);
		} 

		if(!empty($data["password"]) AND 8>strlen($data["password"])){

			$message=__("#data#は#num#文字以上で入力して下さい");
			$message=str_replace(array("#data#","#num#"),array(__("パスワード"),8),$message);
			throw new Exception($message);
		} 

		if(6>strlen($data["account"])){

			$message=__("#data#は#num#文字以上で入力して下さい");
			$message=str_replace(array("#data#","#num#"),array(__("アカウント"),8),$message);
			throw new Exception($message);
		} 

		if(!preg_match("#^[0-9a-zA-Z]+$#",$data["account"])){

			$message=__("#data#は英数字のみで入力して下さい");
			$message=str_replace("#data#",__("アカウント"),$message);
			throw new Exception($message);
		}  

		if(!empty($data["password"]) AND (!preg_match("#^[0-9a-zA-Z]+$#",$data["password"]))){

			$message=__("#data#は英数字のみで入力して下さい");
			$message=str_replace("#data#",__("パスワード"),$message);
			throw new Exception($message);
		} 

		return true;
	}

	private function __getEmployees()
	{

		$employee=$this->K9MasterEmployee->association["hasOne"]["K9MasterEmployeeAccount"];
		$this->K9MasterEmployee->hasOne["K9MasterEmployeeAccount"]=$employee;
		$employee=$this->K9MasterEmployee->getEmployees(0,array( "order"=>array("K9MasterEmployee.id ASC") ));
		return $employee;
	}

	function getEmployees()
	{

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$employee   =$this->__getEmployees();
		$jobtitles  =$this->__getJobtitles();
		$authorities=$this->__getAuthorities();
		foreach($authorities as $authority_k=>$authority_v) $data[$authority_k]=array();

		foreach($employee as $k=>$v){
		
			$auth=$v["K9MasterEmployeeAccount"]["authority"];
			$employee_id=$v["K9MasterEmployee"]["id"];
			$data[$auth][$employee_id]["account"]["username"]    =escapeJsonString($v["K9MasterEmployeeAccount"]["username"]);
			$data[$auth][$employee_id]["account"]["registed_day"]=date("Ymd",strtotime($v["K9MasterEmployeeAccount"]["created"]));
			$data[$auth][$employee_id]["account"]["authority"]   =$v["K9MasterEmployeeAccount"]["authority"];
			$data[$auth][$employee_id]["employee"]["first_name"] =escapeJsonString($v["K9MasterEmployee"]["first_name"]);
			$data[$auth][$employee_id]["employee"]["middle_name"]=escapeJsonString($v["K9MasterEmployee"]["middle_name"]);
			$data[$auth][$employee_id]["employee"]["last_name"]  =escapeJsonString($v["K9MasterEmployee"]["last_name"]);
			$data[$auth][$employee_id]["employee"]["email"]      =escapeJsonString($v["K9MasterEmployee"]["email"]);
			$data[$auth][$employee_id]["employee"]["tel"]        =escapeJsonString($v["K9MasterEmployee"]["tel"]);
			$data[$auth][$employee_id]["employee"]["id"]         =$employee_id;
			$data[$auth][$employee_id]["employee"]["remark"]     =escapeJsonString($v["K9MasterEmployee"]["remark"]);
			$data[$auth][$employee_id]["employee"]["hash"]       =$v["K9MasterEmployee"]["hash"];
			$data[$auth][$employee_id]["employee"]["jobtitle"]   =$v["K9MasterEmployee"]["job_title"];
			$data[$auth][$employee_id]["employee"]["jobtitle_value"]=__($jobtitles[$v["K9MasterEmployee"]["job_title"]]);
		}

		$res["data"]["data"]=$data;
		$res["data"]["menu"]["authorities"]=$authorities;
		$res["data"]["menu"]["jobtitles"]  =$jobtitles;
		Output::__outputYes($res);
	}

	public function __getJobtitles()
	{
		$job_titles=getTSVJobtitlesList();

		$list=array();
		$list[K9MasterEmployee::$JOBTITLE_COOKING]     =__($job_titles[K9MasterEmployee::$JOBTITLE_COOKING]);
		$list[K9MasterEmployee::$JOBTITLE_MANAGER]     =__($job_titles[K9MasterEmployee::$JOBTITLE_MANAGER]);
		$list[K9MasterEmployee::$JOBTITLE_FRONT]       =__($job_titles[K9MasterEmployee::$JOBTITLE_FRONT]);
		$list[K9MasterEmployee::$JOBTITLE_HOUSEKEEPER] =__($job_titles[K9MasterEmployee::$JOBTITLE_HOUSEKEEPER]);
		$list[K9MasterEmployee::$JOBTITLE_SERVICE]     =__($job_titles[K9MasterEmployee::$JOBTITLE_SERVICE]);
		return $list;
	}

	public function __getAuthorities()
	{

		$authorities=getTSVAuthoritiesList();

		$list=array();
		$list[K9MasterEmployee::$AUTH_MASTER]=__($authorities[K9MasterEmployee::$AUTH_MASTER]);
		$list[K9MasterEmployee::$AUTH_CHIEF] =__($authorities[K9MasterEmployee::$AUTH_CHIEF]);
		$list[K9MasterEmployee::$AUTH_STAFF] =__($authorities[K9MasterEmployee::$AUTH_STAFF]);
		return $list;
	}

	function getPassword(){

		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post["hash"]="fdsasdfdfasdfdfasdffda";
		$hash=$post["hash"];

		if(!$data=$this->K9MasterEmployee->getEmployeeByHash($hash)){
		
			$res["message"]=__("情報の取得が行えませんでした");
			Output::__outputNo();
		}

		$pass=$data["K9MasterEmployeeAccount"]["password"];
		$res["data"]["password"]=$pass;
		Output::__outputYes($res);
	}

}//END class

?>
