<?php

require_once "Schedule".DS."ScheduleLog.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigation.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationKeys.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationExec.php";

App::uses('K9ScheduleBaseController','Controller');
class AdK9SiteManagesInitialEditTimesController extends K9ScheduleBaseController{

        var $name = "K9SiteManagesInitialEditTimes";
        var $uses = [

            "TblMstepScheduleLog",
			"TblMstepMasterUser"
        ];

        function beforeFilter(){

            parent::beforeFilter();
        }

		//edit_time は変更しない
		//no need to change the value of edit_time.
		function initScheduleLog(){
	
			$post     =$this->data;
			//$post=$this->__getTestPostData();

			$user_id  =$post["user_id"];
			$local_time_key=isset($post["local_time_key"])?$post["local_time_key"]:false;
			$last_edit_time =isset($post["last_edit_time"])?$post["last_edit_time"]:false;
			$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

			$instance=ScheduleLog::getInstance($this);
			$current_time=time();
			if(!$instance->timeInitialize($user_id,$current_time)) Output::__outputNo();
			$instance->clear();
			$last_edit_time=$instance->getLastEditTime();
	
			$output["last_edit_time"]=$last_edit_time;
			$output["time_key"]=$current_time;
			Output::__outputYes($output);
		}

}//END class

?>
