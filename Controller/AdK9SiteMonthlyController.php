<?php
/*
 * Copyright 2015 SPC Viet Nam Co., Ltd.
 * All right reserved.
 */

/**
 * @Author: Nguyen Chat Hien
 * @Date:   2016-08-17 14:28:44
 * @Last Modified by:   Nguyen Chat Hien
 * @Last Modified time: 2017-07-05 11:21:17
 */
/**
 * Login Controller
 *
 */


require_once "Schedule".DS."ScheduleGetLastEditReservationUser.php";
require_once "Schedule".DS."ScheduleLog.php";

require_once "TimeoutInvestigation".DS."TimeoutInvestigation.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationKeys.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationExec.php";

App::uses("K9PriceController","Controller");
App::uses("K9BasePricesController","Controller");
App::uses("K9ScheduleBaseController","Controller");

class AdK9SiteMonthlyController extends K9ScheduleBaseController{

		var $name = 'K9SiteMonthly';
		var $uses = [

			"K9MasterRoomType",
			"K9MasterRoomSituation",
			"K9MasterRoom",
			"K9MasterEmployee",
			"K9MasterColor",
			"K9DataSchedulePlan",
			"K9DataSchedule",
			"K9MasterCheckinColor",
			"K9DataReservation",
			"K9DataPriceRoomType",
			"K9DataPriceParRoom",
			"K9DataGuest",
			"K9DataUnavailableRoom"
		];

		public function beforeFilter() {

			parent::beforeFilter();
		}

		function index($start_date=""){

			if(!isEnableDateByYmd($start_date."01")) $start_date=date("Ym");

			$s_stime=strtotime($start_date."01");
			$start=date("Y-m-d",$s_stime);
			$end  =date("Y-m-t",strtotime($start));

			$stay_schedule_block_num=$this->K9MasterRoom->getScheduleBlockNum();
			$res_informaton=$this->__getInformations($start,$end,$stay_schedule_block_num);
			$color_list =$res_informaton["data"]["color_list"];
			$reserve_ids=(isset($res_informaton["data"]["reserve_ids"])?$res_informaton["data"]["reserve_ids"]:array());
			$informations=$res_informaton["informations"];

			$instance       =ScheduleLog::getInstance($this);
			$last_edit_time =$instance->getLastEditTime();
			$last_start_user=$instance->getLastStartUser();
			$last_edit_user_id=$instance->getLastEditUser();
			$edit_time_expired_ms=$instance->getLastEditTimeExpireMs();

			//■user edited last imformation.
			$last_modified_user=array();
			if(!empty($last_edit_user_id)){

				$last_modified_user=$this->__getLastModifiedInformations($last_edit_user_id);
				$last_modified_user["last_edit_ms"]=$last_edit_time;
				$last_modified_user["edit_time_expired"]=$edit_time_expired_ms;
			}

			$user_id=$this->Auth->user("employee_id");
			$is_authority=$this->__checkAuthorityToEdit($user_id)?1:0;

			$edit_informations=array();
			if(!empty($reserve_ids)) $edit_informations=$this->__getReserveEditInfomrations($reserve_ids);

			$effect_room_types=$this->__getEffectRoomTypes();
			$effect_rooms=$this->__getEffectRooms(date("Ymd"));
			$rooms=$effect_rooms["rooms"];

			$holiday_base_year=date("Y",strtotime($start));
			$holidays=$this->__getCambodiaHolidaysForYear($holiday_base_year);

			$etc_blocks=$this->__etcBlocks($stay_schedule_block_num);
			$rooms+=$etc_blocks;
			$schedule_block_num=count($rooms);

			$unavailable=$this->__getUnavailableSchedules($start,$end);

			$situations          =mstepJsonEncode($this->__getReservationSituations());
			$rooms		         =mstepJsonEncode($rooms);
			$effect_room_types 	 =mstepJsonEncode($effect_room_types);
			$effect_rooms        =mstepJsonEncode($effect_rooms);
			$edit_informations 	 =mstepJsonEncode($edit_informations);
			$informations     	 =mstepJsonEncode($informations);
			$last_modified_user  =mstepJsonEncode($last_modified_user);
			$color_list          =mstepJsonEncode($color_list);
			$client_data_sessions=mstepJsonEncode(CLIENT_DATA_SESSION);
			$holidays            =mstepJsonEncode($holidays);
			$unavailable         =mstepJsonEncode($unavailable);

			$this->set(compact("informations",
							   "schedule_block_num",
							   "unavailable",
							   "situations",
							   "rooms",
							   "worker_id",
							   "effect_rooms",
							   "holidays",
							   "effect_room_types",
							   "color_list",
							   "last_edit_time",
							   "start_date",
							   "edit_time_expired_ms",
							   "last_start_user",
							   "last_modified_user",
							   "is_authority",
							   "base_start_ms",
							   "edit_informations",
							   "client_data_sessions",
							   "server_time_ms"));
		}

		function getSubDateApi(){

			if(!$this->isPostRequest()) exit;

			//■基準日,何ヶ月分
			$post=$this->data;
			//$post=$this->__getTestPostData();

			$base_start_date=isset($post["date"]) ? $post["date"] : date("Ym");

			$s_stime=strtotime($base_start_date."01");
			$start=date("Y-m-d",$s_stime);
			$end  =date("Y-m-t",strtotime($start));
			$stay_schedule_block_num=$this->K9MasterRoom->getScheduleBlockNum();

			$data=$this->__getInformations($start,$end,$stay_schedule_block_num);
			$reserve_ids=(isset($res_informaton["data"]["reserve_ids"])?$res_informaton["data"]["reserve_ids"]:array());
			$edit_informations=array();
			if(!empty($reserve_ids)) $edit_informations=$this->__getReserveEditInfomrations($reserve_ids);

			$unavailable=$this->__getUnavailableSchedules($start,$end);

			$informations=$data["informations"];
			$res["data"]["edit_informations"]=$edit_informations;
			$res["data"]["informations"]=$informations;
			$res["data"]["unavailable"] =$unavailable;
			Output::__outputYes($res);
		}

		function getAddDateApi(){

			if(!$this->isPostRequest()) exit;

			//■基準日,何ヶ月分
			$post  = $this->data;
			//$post=$this->__getTestPostData();
			$base_start_date=isset($post["date"]) ? $post["date"] : date("Ym");

			$s_stime=strtotime($base_start_date."01");
			$start=date("Y-m-d",$s_stime);
			$end  =date("Y-m-t",strtotime($start));

			$stay_schedule_block_num=$this->K9MasterRoom->getScheduleBlockNum();

			$data=$this->__getInformations($start,$end,$stay_schedule_block_num);
			$reserve_ids=(isset($res_informaton["data"]["reserve_ids"])?$res_informaton["data"]["reserve_ids"]:array());
			$edit_informations=array();
			if(!empty($reserve_ids)) $edit_informations=$this->__getReserveEditInfomrations($reserve_ids);

			$unavailable=$this->__getUnavailableSchedules($start,$end);

			$informations=$data["informations"];
			$res["data"]["edit_informations"]=$edit_informations;
			$res["data"]["informations"]=$informations;
			$res["data"]["unavailable"] =$unavailable;
			Output::__outputYes($res);
		}

		function getDateRange(){

			if(!$this->isPostRequest()) exit;

			$post=$this->data;
			$start=(!isset($post["start"]))?date("Ymd"):$post["start"];
			$end  =(!isset($post["end"]))?date("Ymd",strtotime("+14 day",strtotime($start))):$post["end"];
			$informations=$this->__getDateRange($start,$end);
			$res["data"]["informations"]=$informations;
			Output::__outputYes($res);
		}

		function __getDateRange($start,$end){

			$stay_schedule_block_num=$this->K9MasterRoom->getScheduleBlockNum();
			$data=$this->__getInformations($start,$end,$stay_schedule_block_num);
			$informations=$data["informations"];
			if(empty($informations)) $informations=$this->__makeDatePeriod($start,$end);
			return $informations;
		}

		function __getReserveEditInfomrations($reserve_ids=array()){

			$this->K9DataReservation->unbindFully();
			$data=$this->K9DataReservation->findAllByIdAndDelFlg($reserve_ids,0);
			$data=Set::combine($data,"{n}.K9DataReservation.id","{}.K9DataReservation");
			$instance=ScheduleGetLastEditReservationUser::getInstance($this);
			$instance->setReservationInformations($data);
			$edit_informations=$instance->getEditUsersInformations();
			return $edit_informations;
		}

		// for class.
		function __getLastModifiedInformations($last_edit_user_id){

			$master_user=$this->K9MasterEmployee->findById($last_edit_user_id);
			$last_modified_user["user"]["first_name"] =stripslashes($master_user["K9MasterEmployee"]["first_name"]);
			$last_modified_user["user"]["middle_name"]=stripslashes($master_user["K9MasterEmployee"]["middle_name"]);
			$last_modified_user["user"]["last_name"]  =stripslashes($master_user["K9MasterEmployee"]["last_name"]);
			$last_modified_user["user"]["other_name"] =stripslashes($master_user["K9MasterEmployee"]["other_name"]);
			return $last_modified_user;
		}

		function __checkAuthorityToEdit($user_id){

			$time_key=$this->Session->read(TimeoutInvestigationKeys::makeTimeSesKey(UNIQUE_KEY));
			App::uses("K9SiteManagesEditAuthoritiesController","Controller");
			$controller=new K9SiteManagesEditAuthoritiesController();
			$is_edit=$controller->__checkAuthorityToEditWithDeadline($user_id,$time_key);
			return $is_edit;
		}

		function __getEffectRoomTypes()
		{
			$lang=Configure::read('Config.language');
			$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
			if(!$data=$this->K9MasterRoomType->getEffectRoomTypes(false)) return array();
			return Set::combine($data,"{n}.K9MasterRoomType.id","{n}.K9MasterRoomType.{$roomtype_name}");
		}

		function __getColorList($is_set=false){

			App::uses("K9ColorController", "Controller");
			$controller=new K9ColorController();
			$color_list=$controller->__getColorList();
			return $color_list;
		}

		function __getPrice($reserve_ids=array(),$params=array()){

			$controller=new K9PriceController();
			$res=$controller->__getPrice($reserve_ids,$params);
			return $res;
		}

		function __getCambodiaHolidaysForYear($base_year){

			App::uses("K9GoogleHolidaysController", "Controller");
			$controller = new K9GoogleHolidaysController();
			$controller->beforeFilter();
			$res =$controller->__getCambodiaHolidaysForYear($base_year);
			$data=$controller->__convertFormatHolidays($res);
			return $data;
		}

		function __getPeriod($date,$range=4,$format="Ym"){

			$base_ym=date("Ym",strtotime($date."01"));
			$base_time=strtotime($base_ym."01");
			$ym_dates[]=$base_ym;
			for($i=1;$i<$range;$i++){

				$ym_dates[]=date($format,strtotime("- {$i} month",$base_time));
				$ym_dates[]=date($format,strtotime("+ {$i} month",$base_time));
			}

			sort($ym_dates);
			return $ym_dates;
		}

		function __getInformations($start,$end,$schedule_block_num){

			App::uses("K9SiteController","Controller");
			$controller=new K9SiteController();
			$res=$controller->__getInformations($start,$end,$schedule_block_num);
			return $res;
		}

}
