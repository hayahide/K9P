<?php

App::uses('K9BaseDailyReportController','Controller');
class AdK9DailyReportAttendanceController extends K9BaseDailyReportController{

	var $name = "K9DailyReportAttendanceController";

	function beforeFilter(){}

	public function __setOrderModels()
	{
		parent::__setOrderModels();
	}

	public function __getAttendanceRecords($day)
	{

		$association=$this->K9MasterEmployee->association;
		$association_attendance=$association["hasOne"]["K9DataEmployeeAttendance"];
		$association_attendance["conditions"]["and"]["DATE_FORMAT({$this->K9DataEmployeeAttendance->name}.enter_date,'%Y%m%d')"]=$day;
		$association_attendance["conditions"]["and"]["{$this->K9DataEmployeeAttendance->name}.del_flg"]=0;
		$this->K9MasterEmployee->bindModel(array("hasOne"=>array("K9DataEmployeeAttendance"=>$association_attendance)));
		return $this->K9MasterEmployee->getMembersByThedayHired($day);
	}

}//END class

?>
