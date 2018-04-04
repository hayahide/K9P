<?php
/*
 * Copyright 2017 SPCVN Co., Ltd.
 * All right reserved.
*/

/**
 * @Author: Naoki Kiyosawa
 * @Date:   2017-10-31 17:38:35
 */

App::uses('AppModel', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AdK9DataScheduleLog extends AppModel{

    var $name = "K9DataScheduleLog";
    var $useTable = "k9_data_schedule_logs";
    var $primaryKey = "id";
	var $useDbConfig="default";

	function updateEditCurrentTime($id){

		if(empty($id)) return false;
		$save["id"]=$id;
		$save["edit_time"]=date("Y/m/d H:i:s");
		return $this->save($save);
	}

	// reset time.
	function timeInitialize($data){

		$save["id"]   =$data["id"];
		$save["edit_user_id"]=$data["user_id"];

		//■0にしないと、終了判定が出来ない
		if(!empty($data["edit_time"])) $save["edit_time"]=$data["edit_time"];
		$save["edit_time_expired_ms"]=0;
		$save["edit_time_expired"]=0;
		return $this->save($save);
	}

	function editTime($data){

		$save["bin_key"]          =$data["bin_key"];
		$save["id"]               =$data["id"];
		$save["start_user_id"]    =$data["user_id"];
		$save["edit_time_expired_ms"]=$data["edit_time_expired_ms"];
		$save["edit_time_expired"]   =date("Y/m/d H:i:s",$data["edit_time_expired_ms"]/1000);
		$save["user_agent"]=$_SERVER["HTTP_USER_AGENT"];
		$save["remote_addr"]=$_SERVER["REMOTE_ADDR"];
		return $this->save($save);
	}

	function getData(){

		if(!$data=$this->findOne()) return false;
		$id=$data[$this->name]["id"];
		return $this->findById($id);
	}

}
