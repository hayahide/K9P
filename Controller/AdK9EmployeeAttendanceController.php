<?php

App::import('Utility', 'Sanitize');

class AdK9EmployeeAttendanceController extends AppController{

	var $name = "K9EmployeeAttendance";
    var $uses = [ "K9DataEmployeeAttendance","K9MasterEmployee" ];

	
	function beforeFilter(){
	
		parent::beforeFilter();
	}

	public function getMemberList()
	{

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		
		$post=$this->data;
		$day =isset($post["day"])?$post["day"]:date("Ymd");

		$res=array();
		$res["data"]=$this->__getMemberRecords($day);
		Output::__outputYes($res);
	}

	private function __getMemberRecords($day)
	{
		$members=$this->__getMembersByThedayHired($day);

		$attendance_records=array();
		if(!empty($members)) $attendance_records=$this->__getMembersAttendanceByThedayRecored($day,array_keys($members));

		$registerd_records=array();
		if(!empty($attendance_records)){

			$final_employee_entereds=array_unique(Set::extract($attendance_records,"{n}.final_employee_entered"));
			$registerd_records=$this->__getAttendanceRegisterd($final_employee_entereds);
		} 

		$res=array();
		$res["members"]   =$members;
		$res["attendance"]=$attendance_records;
		$res["registerd"] =$registerd_records;
		return $res;
	}

	private function __getAttendanceRegisterd($final_employee_entereds=array())
	{

		$this->K9MasterEmployee->unbindFully();
		$data=$this->K9MasterEmployee->findAllById($final_employee_entereds);
		if(empty($data)) return array();
		return Set::combine($data,"{n}.K9MasterEmployee.id","{n}.K9MasterEmployee.first_name");
	}

	private function __getMembersAttendanceByThedayRecored($day,$employee_ids=array())
	{

		$this->K9DataEmployeeAttendance->unbindFully();
		$data=$this->K9DataEmployeeAttendance->getMembersAttendanceByThedayRecored($day,$employee_ids);
		if(empty($data)) return array();

		$list=array();
		foreach($data as $k=>$v){
		
			$_v=$v["K9DataEmployeeAttendance"];
			$employee_id=$_v["employee_id"];
			$list[$employee_id]["enter_hour"]            =$_v["enter_hour"];
			$list[$employee_id]["enter_min"]             =$_v["enter_min"];
			$list[$employee_id]["leave_hour"]            =$_v["leave_hour"];
			$list[$employee_id]["leave_min"]             =$_v["leave_min"];
			$list[$employee_id]["rest_min"]              =$_v["rest_min"];
			$list[$employee_id]["is_dayoff"]             =$_v["is_dayoff"];
			$list[$employee_id]["remarks"]               =escapeJsonString($_v["remarks"]);
			$list[$employee_id]["final_employee_entered"]=$_v["final_employee_entered"];

			$start=$day.sprintf("%02d",$list[$employee_id]["enter_hour"]).sprintf("%02d",$list[$employee_id]["enter_min"])."00";
			$end  =$day.sprintf("%02d",$list[$employee_id]["leave_hour"]).sprintf("%02d",$list[$employee_id]["leave_min"])."00";

			$hm=timeDiffWith_H_M($day,$start,$end);
			$list[$employee_id]["work_time"]["hour"]=$hm["hour"];
			$list[$employee_id]["work_time"]["min"] =$hm["min"];
		}

		return $list;
	}

	private function __getMembersByThedayHired($day)
	{

		$list=$this->K9MasterEmployee->getMembersByThedayHired($day);
		if(empty($list)) return array();
		return Set::combine($list,"{n}.K9MasterEmployee.id","{n}.K9MasterEmployee.first_name");
	}

	public function saveAttendance()
	{
		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();
		
		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);
		
		$day=$post["date"];
		$values=$post["values"];

		$save_records=$this->__attendanceSubscribe($day,$values);
	    $datasource=$this->K9DataEmployeeAttendance->getDataSource();
	    $datasource->begin();

		if(!empty($save_records["update"])){

			$res=$this->__updateRecords($save_records["update"]);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		if(!empty($save_records["insert"])){

			$res=$this->__insertRecords($save_records["insert"]);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		$datasource->commit();

		$res=array();
		$res["data"]=$this->__getMemberRecords($day);
		Output::__outputYes($res);
	}

	private function __insertRecords($data)
	{

		try{
		
			$this->K9DataEmployeeAttendance->multiInsert($data);

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

	private function __updateRecords($data)
	{

		try{
		
			$this->K9DataEmployeeAttendance->multiInsert($data);

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

	private function __attendanceSubscribe($date,$values=array())
	{

		$employee_ids=array_keys($values);
		$current_attendance_ids=array();
		$current_attendance=$this->__getCurrentAttendanceRecords($date,$employee_ids);
		if(!empty($current_attendance)) $current_attendance_ids=Set::combine($current_attendance,"{n}.K9DataEmployeeAttendance.employee_id","{n}.K9DataEmployeeAttendance.id");

		$inserts=array();
		$updates=array();
		foreach($values as $employee_id=>$v){

			//同じ時間の場合は、基本消す、保存しない
			$is_sametime=$v["enter_hour"].$v["enter_min"]==$v["leave_hour"].$v["leave_min"];
			switch(isset($current_attendance_ids[$employee_id])){
			
			case(true):

				$count=count($updates);
				$updates[$count]["id"]        =$current_attendance_ids[$employee_id];
				$updates[$count]["enter_hour"]=$v["enter_hour"];
				$updates[$count]["enter_min"] =$v["enter_min"];
				$updates[$count]["leave_hour"]=$v["leave_hour"];
				$updates[$count]["leave_min"] =$v["leave_min"];
				$updates[$count]["rest_min"]  =$v["rest_min"];
				$updates[$count]["remarks"]   =Sanitize::escape($v["remarks"]);
				$updates[$count]["is_dayoff"] =$v["is_dayoff"];
				$updates[$count]["del_flg"]   =($is_sametime?1:0);
				$updates[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
				break;

			case(false):

				if(empty($v["is_dayoff"]) AND $is_sametime) continue;
				$count=count($inserts);
				$inserts[$count]["employee_id"]=$employee_id;
				$inserts[$count]["enter_hour"]=$v["enter_hour"];
				$inserts[$count]["enter_min"] =$v["enter_min"];
				$inserts[$count]["leave_hour"]=$v["leave_hour"];
				$inserts[$count]["leave_min"] =$v["leave_min"];
				$inserts[$count]["rest_min"]  =$v["rest_min"];
				$inserts[$count]["enter_date"]=$date;
				$inserts[$count]["remarks"]   =Sanitize::escape($v["remarks"]);
				$inserts[$count]["is_dayoff"] =$v["is_dayoff"];
				$inserts[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
				break;
			}
		}

		$res=array();
		$res["update"]=$updates;
		$res["insert"]=$inserts;
		return $res;
	}

	private function __getCurrentAttendanceRecords($date,$employee_ids=array())
	{

		//消したのも含む(del_flg=1)
		//deleteはしない
		$this->K9DataEmployeeAttendance->unbindFully();

		$conditions=array();
		if(!empty($employee_ids)) $conditions["and"]["{$this->K9DataEmployeeAttendance->name}.employee_id"]=$employee_ids;
		$conditions["and"]["DATE_FORMAT({$this->K9DataEmployeeAttendance->name}.enter_date,'%Y%m%d')"]=$date;
		//$conditions["and"]["{$this->K9DataEmployeeAttendance->name}.del_flg"]=0;
		$data=$this->K9DataEmployeeAttendance->find("all",array(
		
			"conditions"=>$conditions
		));

		if(empty($data)) return array();
		
		return $data;
	}


}//END class

?>
