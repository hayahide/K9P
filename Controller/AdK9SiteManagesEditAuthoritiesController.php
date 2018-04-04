<?php

require_once "TimeoutInvestigation".DS."TimeoutInvestigation.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationKeys.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationExec.php";
require_once "Schedule".DS."ScheduleLog.php";

class AdK9SiteManagesEditAuthoritiesController extends AppController{

    var $name = "K9SiteManagesEditAuthorities";
    var $uses = [

		"K9DataScheduleLog",
		"K9MasterEmployee"
    ];

    function beforeFilter(){

        parent::beforeFilter();
    }

	function __startSelectForUpdate(&$datasource){

		$datasource=$this->K9DataScheduleLog->getDataSource();
	    $datasource->begin();

		if(!$one=$this->K9DataScheduleLog->findOne()) return;
		$id=$one["K9DataScheduleLog"]["id"];
		$table=$this->K9DataScheduleLog->useTable;
		$query="select * from {$table} where id=\"{$id}\" FOR UPDATE;";
		$this->K9DataScheduleLog->query($query);
	}

	function __roop(){

		$client_name=CLIENT_DATA_SESSION["short_name"];
		$dir=TMP."editlog".DS.$client_name.DS;
		if(!is_dir($dir)) mkdir($dir);
		$file=$dir."time.log";
		$min=5;

		if(!is_file($file)){
		
			$log_time=strtotime("+ {$min} seconds",time());
			file_put_contents($file,$log_time,LOCK_EX);
			return;
		}

		$counter=0;
		$expire_log_time=file_get_contents($file);
		if(!is_numeric($expire_log_time)){

	        $log_time=strtotime("+ {$min} seconds",time());
            file_put_contents($file,$log_time,LOCK_EX);
			return;
		}

		while(1){
		
			$time=time();
			if($time>$expire_log_time){

				$next_log_time=strtotime("+ {$min} seconds",$time);
				file_put_contents($file,$next_log_time,LOCK_EX);
				return;
			}

			sleep(1);

			//just in case.
			if($counter++>($min*2)) return;
		}
	}

	function editStart(){

		if(!$this->isPostRequest()) exit;

		$this->__roop();
		$datasource="";
		$this->__startSelectForUpdate($datasource);

	    $post=$this->data;
		//$post=$this->__getTestPostData();

		$user_id       =$post["user_id"];
		$last_edit_time=$post["last_edit_time"]; //ms
		$local_time_key=(isset($post["local_time_key"])?$post["local_time_key"]:false); //time(ms/1000)

        $instance=ScheduleLog::getInstance($this);
        $current_last_edit_time=$instance->getLastEditTime();
		$start_user_id         =$instance->getLastStartUser();
		$last_edit_user_id     =$instance->getLastEditUser();
		$bin_key               =$instance->getBinKey();
		$last_modified         =$instance->findValue;
		$is_edit               =empty($last_modified);

        //reload.
        if($last_edit_time!=$current_last_edit_time){

            $this->K9MasterEmployee->unbindFully();
            $login_user=$this->K9MasterEmployee->findById($last_edit_user_id);
            $login_user=$login_user["K9MasterEmployee"];
            $user_informations["name"]="{$login_user["first_name"]} {$login_user["last_name"]}";
            Output::__outputStatus(2,array(

                "user_informations"=>$user_informations

            ),array(

                "##USER##"=>$user_informations["name"]
            ));
        }

		$current_time_ms=TimeoutInvestigation::currentMsTime();
		
		// out of effective time.
		// 時間制限オーバーの場合、他人が編集可能
		if(!$is_edit){
			
			$instance=new TimeoutInvestigationExec($this,$this->Session);
			$is_edit=$instance->checkEffectiveTime();
		}

		// same user.
		// 同ユーザの場合、Keyが正常か確認
		if(!$is_edit){

			$time_key=$this->Session->read(TimeoutInvestigationKeys::makeTimeSesKey(UNIQUE_KEY));
			$is_edit=$this->__checkAuthorityToEdit($user_id,$start_user_id,$bin_key,$time_key);
		}

		// from localStorage
		if(!$is_edit AND !empty($local_time_key)){

			$dec=TimeoutInvestigationKeys::decBinKey($bin_key,$local_time_key);
			$is_edit=($user_id==$dec["user_id"] AND $local_time_key==$dec["time_key"]);
		}

		if($is_edit){
			
			$time_key=($current_time_ms/1000);
			$instance=ScheduleLog::getInstance($this);
			if(!$instance->editTime($user_id,$current_time_ms,$time_key)) Output::__outputStatus(1);
			$datasource->commit();

			$last_edit_time=$instance->getLastEditTime();

			// session.
			$this->Session->write(TimeoutInvestigationKeys::makeTimeSesKey(UNIQUE_KEY),$time_key);
			
			$output["last_modified_ms"]=$current_time_ms;
			$output["last_edit_time"]  =$last_edit_time;
			$output["time_key"]        =$time_key;
			Output::__outputYes($output);
		}

		//■being previous edit user.
		$user_informations=array();
		$time_informations=array();
		$this->K9MasterEmployee->unbindFully();
		$login_user=$this->K9MasterEmployee->findById($start_user_id);
		$login_user=$login_user["K9MasterEmployee"];
		$user_informations["name"]="{$login_user["first_name"]} {$login_user["last_name"]}";
		$user_informations["user_agent"] =$last_modified["K9DataScheduleLog"]["user_agent"];
		$user_informations["remote_addr"]=$last_modified["K9DataScheduleLog"]["remote_addr"];
		$time_informations["deadline"]=((Int)$last_modified["K9DataScheduleLog"]["edit_time_expired_ms"]+TimeoutInvestigation::effectiveTime());

		//誰か編集している^M
		Output::__outputStatus(0,array(

		  "user_informations"=>$user_informations,
		  "time_informations"=>$time_informations

		),array(

		  "##USER##"      =>$user_informations["name"],
		  "##DEADLINE##"  =>date("Y年m月d日 H時i分s秒",$time_informations["deadline"]/1000),
		  "##USER_AGENT##"=>$user_informations["user_agent"]
		));
	}

	//■有効時間は確認する必要は無い
	function __checkAuthorityToEdit($current_user_id,$start_user_id,$bin_key,$time_key){

		//開始した時間(1970からの秒)
		//K9DataScheduleLog/edit_time_expired_ms と同期
		//この時間が記録されている

		$is_edit=false;
		if(!$time_key) return $is_edit;
		if(!$bin_key)  return $is_edit;
		if($current_user_id!=$start_user_id) return $is_edit;
		if(TimeoutInvestigationExec::checkSessionKey($current_user_id,$time_key,$bin_key)) $is_edit=true;
		return $is_edit;
	}

	function checkAuthorityToEdit(){
	
		if(!$this->isPostRequest()) exit;

		$_post=$_POST;
		$user_id=$_post["user_id"];
		$time_key=$this->Session->read(TimeoutInvestigationKeys::makeTimeSesKey(UNIQUE_KEY));
		$is_edit=$this->__checkAuthorityToEdit($user_id,$time_key);
		$res["is_edit"]=empty($is_edit)?"NO":"YES";
		Output::__outputYes($res);
	}

	function __checkAuthorityToEditWithDeadline($user_id,$time_key){

		$schedule_log=$this->K9DataScheduleLog->findOne();
		if(empty($schedule_log)) return false;

		$start_user_id=$schedule_log["K9DataScheduleLog"]["start_user_id"];
		$bin_key=$schedule_log["K9DataScheduleLog"]["bin_key"];
		$is_edit=$this->__checkAuthorityToEdit($user_id,$start_user_id,$bin_key,$time_key);

		$deadline=(Int)$schedule_log["K9DataScheduleLog"]["edit_time_expired_ms"]+TimeoutInvestigation::effectiveTime();
		$current_ms=TimeoutInvestigation::currentMsTime();
		if(!empty($is_edit) AND ($deadline>$current_ms)) return true;
		return false;
	}

}//END class

?>
