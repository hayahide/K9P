<?php

App::import('Utility', 'Sanitize');

require_once "tsv.php";
require_once "output.php";
require_once "SetModel.php";
require_once "userAgent.php";

require_once "Schedule".DS."ScheduleLog.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigation.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationKeys.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationExec.php";

require_once(dirname(ROOT).DS."vendor".DS."autoload.php");

/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses("K9LoginTimeRecordController","Controller");

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package     app.Controller
 * @link        http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AdAppController extends Controller{

	var $ext = ".html";
	var $uses = ["K9MasterEmployee","K9MasterClient" ];
	var $authorities=array();

	public $components = array(

		'Session',
		'RequestHandler',
		'Auth' => array(
			'loginAction' => array( 'controller' => 'K9Login', 'action' => 'index'),
			'loginRedirect' =>array('controller' => 'K9Site', 'action' => 'index'),
			'logoutRedirect'=>array('controller' => 'K9Login', 'action' => 'index'),
			'authenticate' => array(

				'Form' => array(
					'userModel' => 'K9MasterEmployeeAccount',
					'fields' => array(
						'username' => 'username',
						'password' => 'password'),
						'passwordHasher' => array(
						'className'=>"Simple",
						'hashType' => 'sha256'
					)
				)
			),
			'authorize' => array('Controller'),
			'unauthorizedRedirect' => array(

				'admin' => false,
				'controller' => 'K9Login',
				'action' => 'access_denied'
			)
		),
	);

	function beforeFilter(){

		CakeSession::$requestCountdown=1;

		parent::beforeFilter();

		$this->loadModel("K9MasterEmployee");
		$this->loadModel("K9DataSchedule");
		$this->loadModel("K9DataReststaySchedule");
		$this->loadModel("K9DataDipositSchedule");
		$this->loadModel("K9DataDipositReststaySchedule");

		$this->loadOrderMasterModels();

		if($this->isPostRequest()){

			$this->__checkToken();
			$this->__postLog($this->data);
			$this->__savePostContents();
		}

		//define or globals
		/*==================================================================*/
		$this->__setLang();
		$this->__aclDefine();
		$this->__setAuthorities();
		/*==================================================================*/

		// define client info session
		$this->Session->write('CLIENT_INFO',Configure::read('CLIENT_INFO'));
		if(!defined("UNIQUE_KEY")) define("UNIQUE_KEY",CLIENT);

		// Update 2017.01.10 Hung Nguyen start
		// add sub_master for role
		$this->check_authentication = in_array($this->Auth->user("authority"),$this->authorities);
		if($this->check_authentication===true) $this->role_num=1;

		$this->set('role',$this->check_authentication);
        $this->set("master_authorities",$this->authorities);
		$this->set('is_edit', $this->role_num);
	}

	private function __aclDefine()
	{

		if(isset($GLOBALS["auth"])) return;
		$GLOBALS["auth"]["HEADER_EMPLOYEE"]     =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["HEADER_PRICERATE"]    =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["HEADER_ROOMSITUATION"]=array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["HEADER_BASEPRICE"]    =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["HEADER_COMPANY"]      =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["HEADER_OUTPUTPAYMENT"]=array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["HEADER_EXTRAORDER"]   =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["HEADER_DEPOSIT"]      =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["SETTING"]["EDIT"]   =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["SETTING"]["TAX"]    =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["SETTING"]["CARD"]   =array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);
		$GLOBALS["auth"]["SETTING"]["INVOICE"]=array(K9MasterEmployee::$AUTH_MASTER,K9MasterEmployee::$AUTH_CHIEF);

		define("HEADER_EMPLOYEE",     in_array($this->Auth->user("authority"),$GLOBALS["auth"]["HEADER_EMPLOYEE"]));
		define("HEADER_PRICERATE",    in_array($this->Auth->user("authority"),$GLOBALS["auth"]["HEADER_PRICERATE"]));
		define("HEADER_ROOMSITUATION",in_array($this->Auth->user("authority"),$GLOBALS["auth"]["HEADER_ROOMSITUATION"]));
		define("HEADER_BASEPRICE",    in_array($this->Auth->user("authority"),$GLOBALS["auth"]["HEADER_BASEPRICE"]));
		define("HEADER_COMPANY",      in_array($this->Auth->user("authority"),$GLOBALS["auth"]["HEADER_COMPANY"]));
		define("HEADER_OUTPUTPAYMENT",in_array($this->Auth->user("authority"),$GLOBALS["auth"]["HEADER_OUTPUTPAYMENT"]));
		define("HEADER_EXTRAORDER",   in_array($this->Auth->user("authority"),$GLOBALS["auth"]["HEADER_EXTRAORDER"]));
		define("HEADER_DEPOSIT",      in_array($this->Auth->user("authority"),$GLOBALS["auth"]["HEADER_DEPOSIT"]));
		define("SETTING_EDIT",           in_array($this->Auth->user("authority"),$GLOBALS["auth"]["SETTING"]["EDIT"]));
		define("SETTING_EDIT_TAX",       in_array($this->Auth->user("authority"),$GLOBALS["auth"]["SETTING"]["TAX"]));
		define("SETTING_EDIT_CARD",      in_array($this->Auth->user("authority"),$GLOBALS["auth"]["SETTING"]["CARD"]));
		define("SETTING_EDIT_INVOICE",   in_array($this->Auth->user("authority"),$GLOBALS["auth"]["SETTING"]["INVOICE"]));
	}

	protected function loadOrderMasterModels()
	{

		$this->loadModel("K9MasterRoom");
		$this->loadModel("K9MasterRoomType");
		$this->loadModel("K9MasterFood");
		$this->loadModel("K9MasterBeverage");
		$this->loadModel("K9MasterSpa");
		$this->loadModel("K9MasterLimousine");
		$this->loadModel("K9MasterReststay");
		$this->loadModel("K9MasterLaundry");
		$this->loadModel("K9MasterTobacco");
		$this->loadModel("K9MasterRoomservice");
	}

	protected function __stayTypeDipositModel($staytype){

		switch($staytype){
		
		case($this->K9DataSchedule->stayType):
			return $this->K9DataDipositSchedule;
			break;
		case($this->K9DataReststaySchedule->stayType):
			return $this->K9DataDipositReststaySchedule;
			break;
		}

		return false;
	}

	protected function __stayTypeModel($staytype){
	
		switch($staytype){
		
		case($this->K9DataSchedule->stayType):
			return $this->K9DataSchedule;
			break;
		case($this->K9DataReststaySchedule->stayType):
			return $this->K9DataReststaySchedule;
			break;
		}

		return false;
	}

	protected function __setLang(){

		$langs=array_keys(LANG);
		$lang=$this->Session->read("lang");

		switch(!empty($lang) AND in_array($lang,$langs)){
		
		case(true):
			break;

		default:
			$lang=DEF_LANG;
			break;
		}

		Configure::write('Config.language',$lang);
		return;
	}

	/**
	 * Determines if authorized.
	 *
	 * @author Nguyen Chat Hien
	 */
	public function isAuthorized($user) {

		if(isset($user['authority']) && in_array($this->Auth->user("authority"),$this->authorities)) return true;
		throw new unAuthorizedException();
	}

	private function __checkToken()
	{
		if(!isset($this->data["token"]))   Output::__outputNo(array( "message"=> __("正常に処理が終了しませんでした")."(t1)"));
		if(!$this->Session->read("token")) Output::__outputNo(array( "message"=> __("正常に処理が終了しませんでした")."(t2)"));
		if($this->Session->read("token")!=$this->data["token"]) Output::__outputNo(array( "message"=> __("正常に処理が終了しませんでした")."(t2)"));
		return true;
	}

	private function __setToken(){

		if($this->isPostRequest()) return;
		if(!$this->isControllersRequest()) return;
		setTokenWithSession($this);
	}

	private function isControllersRequest(){
	
		if(strtolower($this->name)!=strtolower($this->params["controller"])) return false;
		return true;
	}

	#
	# @author Kiyosawa
	# @date 2011/05/07 14:44:59
	function beforeRender() {

		parent::beforeRender();

		$user_id=$this->Auth->user("employee_id");
		$user=$this->K9MasterEmployee->findById($user_id);

		$device_type=$this->__getDeviceType();
		$this->__setToken();

		$this->set("device_type",$device_type);
		$this->set("user_id"    ,$this->Auth->user("employee_id"));
		$this->set("first_name" ,($user["K9MasterEmployee"]["first_name"]));
		$this->set("today"      ,date("Y/m/d 00:00:00"));
		$this->set('authority'  ,$this->Auth->user("authority"));
		$this->set("controller" ,$this->params["controller"]);

		$lang_location=Configure::read("Config.language");
		$this->set("lang_location",$lang_location);
	}

	function __setAuthorities(){

		$this->authorities[]=K9MasterEmployee::$AUTH_MASTER;
		$this->authorities[]=K9MasterEmployee::$AUTH_CHIEF;
		$this->authorities[]=K9MasterEmployee::$AUTH_STAFF;
	}

	protected function isPostRequest(){

		return $this->request->is("post");
	}

	protected function __getTestPostData()
	{
		if(!in_array(DEVELOP_MODE,array("local"))) return $this->data;
		$path=getLogPath($this);
		$data=file_get_contents($path);
		return unserialize($data);
	}

	protected function __getDeviceType(){

		return getDeviceType();
	}

	protected function __savePostContents()
	{
		savePostContents($this);
	}

	public function __postLog($data,$values=array())
	{

		if(!isset($GLOBALS["POSTLOG"][$this->params["controller"]])) return;
		if(!isset($GLOBALS["POSTLOG"][$this->params["controller"]][$this->params["action"]])) return;
		if(empty($GLOBALS["POSTLOG"][$this->params["controller"]][$this->params["action"]]))  return;
		if(!in_array(DEVELOP_MODE,array("local","dev","web"))) return;

		$log_values=toSingleDimension($data);

		$ip=isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER["REMOTE_ADDR"];
		$gi=geoip_open(MASTER_DATA.'GeoIP.dat',GEOIP_STANDARD);
		$country=geoip_country_code_by_addr($gi,$ip);
		geoip_close($gi);

		$logpath["local"]=LOGS."post".DS;
        $logpath["dev"]  =$logpath["local"];
        $logpath["web"]  =dirname(ROOT).DS."k9postlog".DS;
        $dir=$logpath[DEVELOP_MODE];
        $file=date("Ymd").".log";
        $path=$dir.$file;
        if(!is_dir($dir)) mkdir($dir);

		$log="/*******************************************\n";
		$log_values["enter_time"]=date("Y-m-d H:i:s");
		$log_values["enter_employee_id"]="{$this->Auth->user("employee_id")}";
		$log_values["enter_user_agent"]="{$_SERVER["HTTP_USER_AGENT"]}";
		$log_values["page"]="{$this->params["controller"]}".DS.$this->params["action"];
		$log_values["country"]=$country;
		foreach($log_values as $k=>$v) $log.="{$k}\t##{$v}##\n";
		$log.="******************************************/";
		$log.="\n\n";
		file_put_contents($path,$log,FILE_APPEND | LOCK_EX);
		return $values;
	}

	function __isEditAuthority($user_id,$last_edit_time,$local_time_key){

		//same key.
        $instance_s=ScheduleLog::getInstance($this);
        $current_last_edit_time=$instance_s->getLastEditTime();
		if($last_edit_time!=$current_last_edit_time) return false;

		//authority with user_id and timekey which registed on session.
		$instance_t=new TimeoutInvestigationExec($this,$this->Session);
		$check_result=$instance_t->checkLastEditTimeSesKey(UNIQUE_KEY,$user_id);

		if(empty($check_result)){

			if(empty($local_time_key)) return false;
			$bin_key=$instance_s->getBinKey();
			$dec=TimeoutInvestigationKeys::decBinKey($bin_key,$local_time_key);
			if($user_id!=$dec["user_id"] OR $dec["time_key"]=!$local_time_key) return false;
		}

		return true;
	}

	public function __isEditAuthorityOutput($last_edit_time,$local_time_key)
	{
		$employee_id=$this->Auth->user("employee_id");
		if($this->__isEditAuthority($employee_id,$last_edit_time,$local_time_key)) return true;
		
		$res=array();
		$res["message"]=__("編集権限を既に失っております(リロード致します)");
		$res["is_reload"]=1;
		Output::__outputNo($res);
	}

	public function __closeDandoriHandlings($is_done,$user_id){
	
		switch(true){

			case(!empty($is_done)):

				$last_edit_time=time();
				$instance=ScheduleLog::getInstance($this);
				if(!$instance->timeInitialize($user_id,$last_edit_time)) Output::__outputStatus(1);
				$last_edit_time=($last_edit_time*1000);
				break;
			default:

				$last_edit_time=$this->__getRefreshLastEditTime();
				break;
		}

		return $last_edit_time;
	}

	function __getRefreshLastEditTime(){
	
		$instance=ScheduleLog::getInstance($this);
		$res=$instance->updateEditCurrentTime();
		return strtotime($res["K9DataScheduleLog"]["edit_time"])*1000;
	}
}

?>
