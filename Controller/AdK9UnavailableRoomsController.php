<?php

class AdK9UnavailableRoomsController extends AppController{

	var $name = "K9UnavailableRooms";
    var $uses = ["K9DataUnavailableRoom","K9DataReservation","K9MasterRoomSituation","K9DataSchedule","K9DataReststaySchedule"];

	function beforeFilter(){
	
		parent::beforeFilter();

		$this->__useModel();
	}

	private function __useModel()
	{
		$this->loadModel("K9MasterRoomSituation");
	}

	public function getUnavailableReasons()
	{
		if(!$this->isPostRequest()) exit;
		$data=$this->__getUnavailableReasons();

		$res=array();
		$res["data"]["menu"]=$data;
		Output::__outputYes($res);
	}

	public function getUnavailabledata()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;
		$data_id=(isset($post["data_id"]) AND is_numeric($post["data_id"]))?$post["data_id"]:exit;

		$data=$this->__getUnavailabledata($data_id);

		$res=array();
		$res["data"]=$data;
		Output::__outputYes($res);
	}

	private function __getUnavailabledata($data_id)
	{

		$conditions=array();
		$conditions["and"]["K9DataUnavailableRoom.id"]=$data_id;
		$conditions["and"]["K9DataUnavailableRoom.del_flg"]=0;
		$this->K9DataUnavailableRoom->unbindFully();
		$data=$this->K9DataUnavailableRoom->find("first",array("conditions"=>$conditions));
		if(empty($data)) return array();

		$res=array();
		$res["reason_id"] =$data["K9DataUnavailableRoom"]["reason_id"];
		$res["start_date"]=date("Ymd",strtotime($data["K9DataUnavailableRoom"]["start_date"]));
		$res["end_date"]  =date("Ymd",strtotime($data["K9DataUnavailableRoom"]["end_date"]));
		$res["remarks"]   =$data["K9DataUnavailableRoom"]["remarks"];
		$res["data_id"]   =$data["K9DataUnavailableRoom"]["id"];
		return $res;
	}

	public function unAvailableReasonRemove()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		
		$post=$this->data;
		$id=(isset($post["data_id"]) AND is_numeric($post["data_id"]))?$post["data_id"]:exit;
		$res=$this->K9DataUnavailableRoom->removeById($id);
		if(empty($res)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$res=array();
		Output::__outputYes($res);
	}

	public function unAvailableReasonSubscribe()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		try{

			$data=$this->__unAvailableReasonSubscribe($post);

		}catch(Exception $e){ Output::__outputNo(array("message"=>$e->getMessage())); }

		$res=array();
		$res["data"]["id"]=$data["id"];
		Output::__outputYes($res);
	}

	private function __checkIfExists($start,$end,$params=array())
	{
		$data=$this->K9DataUnavailableRoom->getDataBasedonRange($start,$end,$params);
		if(!empty($data)) return true;
		return false;
	}

	private function __checkIfExistsOfSchedule(Model $schedule_model,$room_id,$start,$end)
	{
		//any schedules are set on schedule within the period.
		$conditions=array();
		//$start="20180101";
		$schedule_model->unbindFully();
		$conditions["and"]["CONCAT({$schedule_model->name}.start_month_prefix,lpad({$schedule_model->name}.start_day,2,0)) between ? AND ?"]=array($start,$end);
		$conditions["and"]["{$schedule_model->name}.del_flg"]=0;

		//the last day of schedule indivisually.
		$conditions[]="1=1 group by {$schedule_model->name}.reserve_id";
		$fields=array("{$schedule_model->name}.reserve_id","MAX(CONCAT({$schedule_model->name}.start_month_prefix,lpad({$schedule_model->name}.start_day,2,0))) as date");
		$data=$schedule_model->find("all",array("conditions"=>$conditions,"fields"=>$fields));
		if(empty($data)) return true;

		//target reservations.
		$reserve_ids=array_unique(Set::extract($data,"{}.{$schedule_model->name}.reserve_id"));
		$schedule_lastdays=Set::combine($data,"{n}.{$schedule_model->name}.reserve_id","{}.0.date");

		//$room_id=3;
		$conditions=array();
		$association=$this->K9DataReservation->association["hasMany"]["K9DataSchedulePlan"];
		//$association["conditions"]["K9DataSchedulePlan.room_id"]=$room_id;
		$association["conditions"]["K9DataSchedulePlan.del_flg"]=0;
		$association["order"]=array("K9DataSchedulePlan.start DESC");
		$conditions["and"]["K9DataReservation.id"]=$reserve_ids;
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedulePlan"=>$association)));
		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));
		$data=$this->K9DataReservation->find("all",array("conditions"=>$conditions));
		//there is no posivily can't find reservation based on reservation_ids;
		//if(empty($data)) throw new Exception(__("正常に処理が終了しませんでした"));

		//it shuld be ununique data dosen't come from frontside but just in case this function is being working here.
		$res=$this->__checkIfHasAlreadySetOn($room_id,$data,$schedule_lastdays,array( "start"=>$start,"end"=>$end ));
		if(empty($res)) return false;
		return true;
	}

	private function __checkIfHasAlreadySetOn($room_id,$data,$schedule_lastdays,$range=array())
	{
		if(empty($data)) return true;

		//this plan shuld be done "order DESC"
		$__data=array_shift($data);
		$reservation=$__data["K9DataReservation"];
		$plans=$__data["K9DataSchedulePlan"];
		$the_lastday=$schedule_lastdays[$reservation["id"]];

		$is_ng=false;
		$tmp_lastday=$the_lastday;
		foreach($plans as $k=>$plan){
		
			$start=date("Ymd",strtotime($plan["start"]));

			if($room_id!=$plan["room_id"]){

				//before start day of each plans.
				$tmp_lastday=date("Ymd",strtotime("-1 day",strtotime($start)));
				continue;
			}

			//in
			if(($range["end"]>=$tmp_lastday AND $tmp_lastday>=$range["start"])){
			
				$is_ng=true;
				break;
			}

			//in
			if(($range["end"]>=$start AND $start>=$range["start"])){
			
				$is_ng=true;
				break;
			}

			//over
			if($tmp_lastday>=$range["end"] AND $range["start"]>=$start){

				$is_ng=true;
				break;
			}
		}

		if($is_ng) return false;
		return $this->__checkIfHasAlreadySetOn($room_id,$data,$schedule_lastdays,$range);
	}

	private function __unAvailableReasonSubscribe($data)
	{
		//if the some data set as the same condition is exists.
		$id=(isset($data["id"]) AND is_numeric($data["id"]))?$data["id"]:null;
		$room_id=$data["roomid"];
		$start=date("Ymd",strtotime($data["date"]["start"]));
		$end  =date("Ymd",strtotime($data["date"]["end"]));
		$res=$this->__checkIfExists($start,$end,array( "room_id"=>$room_id,"exclusive"=>$id ));
		if(!empty($res)) throw new Exception(__("指定期間には既に設定済みです"));

		//if the some schedule data set as the same condition is exists.
		$res=$this->__checkIfExistsOfSchedule($this->K9DataSchedule,$room_id,$start,$end);
		if(empty($res)) throw new Exception(__("指定した期間には宿泊予約が設定されています"));

		$res=$this->__checkIfExistsOfSchedule($this->K9DataReststaySchedule,$room_id,$start,$end);
		if(empty($res)) throw new Exception(__("指定した期間には宿泊予約が設定されています"));

		$save=array();
		if($id) $save["id"]=$id=$id;
		$save["start_date"]=$start;
		$save["end_date"]  =$end;
		$save["reason_id"] =$data["reasonid"];
		$save["room_id"]   =$data["roomid"];
		$save["remarks"]   =$data["remarks"];
		$this->K9DataUnavailableRoom->id=($id?$id:null);
		if(!$data=$this->K9DataUnavailableRoom->save($save)) throw new Exception(__("正常に処理が終了しませんでした"));
		return $data[$this->K9DataUnavailableRoom->name];
	}

	private function __getUnavailableReasons()
	{

		$lang=Configure::read('Config.language');

		$conditions=array();
		$conditions["and"]["K9MasterRoomSituation.type"]=K9MasterRoomSituation::$TYPE_UNAVAILABLE;
		$situation=$this->K9MasterRoomSituation->hasField("situation_{$lang}")?"situation_{$lang}":"situation";
		$fields=array("K9MasterRoomSituation.id","K9MasterRoomSituation.{$situation}");
		$data=$this->K9MasterRoomSituation->find("all",array("conditions"=>$conditions,"fields"=>$fields));
		return Set::combine($data,"{n}.K9MasterRoomSituation.id","{n}.K9MasterRoomSituation.{$situation}");
	}

}//END class

?>
