<?php

require_once "Schedule".DS."ScheduleLog.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigation.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationKeys.php";
require_once "TimeoutInvestigation".DS."TimeoutInvestigationExec.php";

App::uses('K9ScheduleBaseController','Controller');
class AdK9ScheduleSaveManagersController extends K9ScheduleBaseController{

	var $name = 'K9ScheduleSaveManagers';
	var $uses = [

		"K9MasterEmployee",
		"K9DataSchedulePlan",
		"K9DataSchedule",
		"K9DataReststaySchedule",
		"K9DataReservation",
		"K9MasterRoom"
	];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function saveSchedule(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$user_id           =$post["user_id"];
		$start_date        =$post["start_date"];
		$end_date          =$post["end_date"];
		$is_dandori_done   =$post["is_finish"];
		$around_values     =isset($post["around_values"])      ?$post["around_values"]:array();
		$data              =(isset($post["effective_date"]))   ?$post["effective_date"]:array();
		$remove_reserve_ids=(isset($post["remove_reserve_ids"])?$post["remove_reserve_ids"]:array());
		$reserve_ids       =!empty($data)?array_unique(array_values(Set::extract($data,"{}.reserve_id"))):array();

		$local_time_key    =isset($post["local_time_key"])     ?$post["local_time_key"]:false;
		$last_edit_time    =isset($post["last_edit_time"])     ?$post["last_edit_time"]:false;
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);
		//transaction
		$datasource=$this->K9DataSchedule->getDataSource();
		$datasource->begin();

		$insert_data=$this->__makeInsertData($data,$remove_reserve_ids);
		$res=$this->__saveSchedule($insert_data);

		if(empty($res["status"])) Output::__outputStatus($res["errorNo"]);
		$schedule_insert_ids=$res["insert_ids"];

        if(empty($res["status"])) Output::__outputStatus($res["errorNo"]);

		if($remove_reserve_ids=$this->__getRemoveScheduleIds($reserve_ids)){

			$res=$this->K9DataReservation->deleteReserveByIds($remove_reserve_ids);
			if(empty($res["status"])) Output::__outputStatus(5);
		}

		// commit.
		$datasource->commit();

		$last_edit_time=$this->__closeDandoriHandlings($is_dandori_done,$user_id);

		$effect_rooms=$this->__getEffectRooms();
		$schedule_block_num=count($effect_rooms["rooms"]);;
	
		// new informations.
		$start_date=date("Y-m-d",strtotime($start_date));
		$end_date  =date("Y-m-d",strtotime($end_date));
		$informations=$this->__getInformations($start_date,$end_date,$schedule_block_num);

		$output["last_edit_time"]=$last_edit_time;
		$output["data"]["schedule_ids"]=$schedule_insert_ids;
		$output["data"]["informations"]=$informations["informations"];
		Output::__outputYes($output);
	}

	function __getRemoveScheduleIds($reserve_ids){

		$data=$this->__getReservationBothScheduleTypesHasOne($reserve_ids);

		//有効なシュケジュールが1つでもあるかで判定(id null判定)
		$remove_reserve_ids=array();
		foreach($data as $k=>$v){

			$staytype=$v["K9DataReservation"]["staytype"];
			$schedule_model=$this->__stayTypeModel($staytype);
			if(!empty($v[$schedule_model->name]["id"])) continue;
			if(!empty($v["K9DataReservation"]["del_flg"])) continue;
			$remove_reserve_ids[]=$v["K9DataReservation"]["id"];
		}

		return $remove_reserve_ids;
	}

	function __makeInsertData($data,$remove_reserve_ids=array(),$staytypes=array()){

		$insert_types=array();
		foreach($data as $object_id=>$v){

			$staytype=$v["staytype"];
			$v["object_id"]=$object_id;
			$insert_types[$staytype][$v["type"]][]=$v;
		}

		if(empty($remove_reserve_ids)) return $insert_types;

		$data=$this->__getReservationBothScheduleTypesHasMany($remove_reserve_ids);

		foreach($data as $k=>$v){
		
			$staytype=$v["K9DataReservation"]["staytype"];
			$staytype_model=$this->__stayTypeModel($v["K9DataReservation"]["staytype"]);
			$schedules=$v[$staytype_model->name];
			foreach($schedules as $k=>$v){

				$schedule_id=$v["id"];
				$insert_data["type"]="NORMAL";
				$insert_data["schedule_id"]=$v["id"];
				$insert_data["is_enable"]  =0;
				$insert_data["reserve_id"] =$v["reserve_id"];
				$insert_data["day"]=$v["start_month_prefix"].sprintf("%02d",$v["start_day"]);
				$insert_types[$staytype][$insert_data["type"]][]=$insert_data;
			}
		}

		return $insert_types;
	}

	private function __getReservationBothScheduleTypes($reserve_ids=array())
	{
		$this->K9DataReservation->unbindModel(array("belongsTo"=>"K9DataGuest"));
		$data=$this->K9DataReservation->getReservation($reserve_ids,0);
		return $data;
	}

	private function __getReservationBothScheduleTypesHasMany($reserve_ids=array())
	{

		$hasMany=$this->K9DataReservation->association["hasMany"];
		$stay=$hasMany["K9DataSchedule"];
		$stay["conditions"]["and"]["K9DataSchedule.del_flg"]=0;
		$rest=$hasMany["K9DataReststaySchedule"];
		$rest["conditions"]["and"]["K9DataReststaySchedule.del_flg"]=0;
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedule"=>$stay,"K9DataReststaySchedule"=>$rest)));
		$data=$this->__getReservationBothScheduleTypes($reserve_ids);
		return $data;
	}

	private function __getReservationBothScheduleTypesHasOne($reserve_ids=array())
	{

		$hasMany=$this->K9DataReservation->association["hasMany"];
		$stay=$hasMany["K9DataSchedule"];
		$stay["conditions"]["and"]["K9DataSchedule.del_flg"]=0;
		$rest=$hasMany["K9DataReststaySchedule"];
		$rest["conditions"]["and"]["K9DataReststaySchedule.del_flg"]=0;
		$this->K9DataReservation->bindModel(array("hasOne"=>array("K9DataSchedule"=>$stay,"K9DataReststaySchedule"=>$rest)));
		$data=$this->__getReservationBothScheduleTypes($reserve_ids);
		return $data;
	}

	function __saveScheduleNORMAL($data,Model $schedule_model){

		$insert=array();
		$worker_inserts=array();
		$truck_inserts=array();
		$check_date_positions=array();

		foreach($data as $k=>$v){

			$schedule_id=toFormalScheduleId($v["schedule_id"]);
			$del_flg=(!empty($v["is_enable"])?0:1);
			$insert[$k]["id"]=$schedule_id;
			$insert[$k]["del_flg"]=$del_flg;

			if(isset($v["day"])){

				$_s=strtotime($v["day"]);
				$insert[$k]["start_month_prefix"]=date("Ym",$_s);
				$insert[$k]["start_day"]         =date("j",$_s);
				$insert[$k]["start_date"]        =date("Y-m-d",$_s);
			}
		}

		try{

			$schedule_model->multiInsert($insert);
		
		}catch(Exception $e){
		
			$res["status"]=false;
			return $res;
		}

		// empty array because here is for update.
		$res["status"]=true;
		$res["insert_ids"]=array();
		return $res;
	}

	function __saveScheduleCOPY($datas,Model $schedule_model,$insert_ids=array()){

		if(1>count($datas)){

			$res["status"]=true;
			$res["insert_ids"]=$insert_ids;
			return $res;
		}

		$data=array_shift($datas);
		if(empty($data["is_enable"])){

			return $this->__saveScheduleCOPY($datas,$schedule_model,$insert_ids);
		}

		$_s=strtotime($data["day"]);
		$insert["start_month_prefix"]=date("Ym",$_s);
		$insert["start_day"] =date("j",$_s);
		$insert["start_date"]=date("Y-m-d",$_s);
		$insert["reserve_id"]  =$data["reserve_id"];
		$this->K9DataSchedule->id=null;
		if(!$schedule_model->save($insert)){

			$res["status"]=false;
			return $res;
		}

		$schedule_id=$this->K9DataSchedule->getLastInsertID();
		$insert_ids[$data["object_id"]]=$schedule_id;
		return $this->__saveScheduleCOPY($datas,$schedule_model,$insert_ids);
	}

	function __saveSchedule($data=array()){

		$insert_ids  =array();
		$reserve_ids =array();
		$target_dates=array();

		foreach($data as $staytype=>$values){

			foreach($values as $type=>$v){

				$type=strtoupper($type);
				$schedule_model=$this->__stayTypeModel($staytype);
				$method="__saveSchedule{$type}";
				if(!method_exists($this,$method)) continue;
				$res=$this->{$method}($v,$schedule_model);

				$reserve_ids=array_merge($reserve_ids,Set::extract($v,"{}.reserve_id"));
				$target_dates=array_merge($target_dates,Set::extract($v,"{}.day"));
	
				if(empty($res["status"])){
	
					$res=array();
					$res["status"]=__("正常に処理が終了しませんでした");
					return $res;
				}
	
				$insert_ids+=$res["insert_ids"];
			}
		}

		$res["status"]=true;
        $res["reserve_ids"]=$reserve_ids;
        $res["target_dates"]=$target_dates;
		$res["insert_ids"]=$insert_ids;
		return $res;
	}

	function __getInformations($start,$end,$schedule_block_num) {

		App::uses("K9SiteController", "Controller");
		$controller = new K9SiteController();
		$res=$controller->__getInformations($start,$end,$schedule_block_num);
		return $res;
	}
}
