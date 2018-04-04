<?php

class AdK9LoginTimeRecordController extends AppController{

	var $name = 'K9LoginTimeRecord';
	var $uses = [ "K9MasterEmployee" ];

	//move to another controller.
	function __updateLastLoginHistory($user_id){

		if(session_id()==="") session_start();
		$session_id=session_id();
		if(!$user_id) return;

		$this->K9MasterEmployee->id = $user_id;
		$last_session_id=$this->K9MasterEmployee->field('session_id');
		if($session_id==$last_session_id) return;

		//Update session id
		$this->K9MasterEmployee->saveField('session_id', $session_id);

		//Update last_login DB master
		$now = date("Y-m-d H:i:s");
		$client_id = $_SESSION["CLIENT_INFO"]["id"];
		$this->K9MasterClient->id = $client_id;
		$this->K9MasterClient->saveField('last_login', $now);
	}

}
