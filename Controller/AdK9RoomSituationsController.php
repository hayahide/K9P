<?php

class AdK9RoomSituationsController extends AppController {

	var $name = 'K9RoomSituations';
	var $uses = ["K9MasterRoomSituation","K9MasterRoom","K9DataUnavailableRoom"];

	public function beforeFilter() {

		parent::beforeFilter();
		$this->loadModel("K9MasterRoomSituation");
	}

	function getRoomSituation(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$target_ymd=(isset($post["ymd"]) AND isYmd($post["ymd"]))?$post["ymd"]:date("Ymd");
	
		$room=$this->__getRoomSituation($target_ymd);
		$data=$this->__getAvailableTypes();
		$situations=Set::combine($data,"{n}.K9MasterRoomSituation.id","{n}.K9MasterRoomSituation.situation");
		$situation_colors=Set::combine($data,"{n}.K9MasterRoomSituation.id","{n}.K9MasterRoomSituation.bgcolor");

		$res["data"]["room_situations"]=$room;
		$res["data"]["situations"]["situation"]=$situations;
		$res["data"]["situations"]["bgcolor"]  =$situation_colors;
		Output::__outputYes($res);
	}

	private function __getAvailableTypes()
	{
		$conditions=array();
		$conditions["and"]["K9MasterRoomSituation.type"]=K9MasterRoomSituation::$TYPE_AVAILABLE;
		$data=$this->K9MasterRoomSituation->find("all",array("conditions"=>$conditions));
		return $data;
	}

	function saveRoomSituation(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		if((!isset($post["room_id"]) OR !isset($post["situation_id"])) OR (!is_numeric($post["room_id"]) OR !is_numeric($post["situation_id"]))){
		
			$res["status"]=false;
			$res["message"]=__("正常に処理が終了しませんでした");
			Output::__outputNo($res);
		}

		$room_id=$post["room_id"];
		$situation_id=$post["situation_id"];

		$save["id"]=$room_id;
		$save["situation_id"]=$situation_id;
		if(!$this->K9MasterRoom->save($save)){

			$res["status"]=false;
			$res["message"]=__("正常に処理が終了しませんでした");
			Output::__outputNo($res);
		}

		$res["data"]=array();
		Output::__outputYes($res);
	}

	private function __getMasterRooms($target_date)
	{
		$association=$this->K9MasterRoom->association["hasMany"]["K9DataUnavailableRoom"];
		$association["conditions"]["DATE_FORMAT(K9DataUnavailableRoom.start_date,'%Y%m%d') <= "]=$target_date;
		$association["conditions"]["DATE_FORMAT(K9DataUnavailableRoom.end_date,'%Y%m%d') >= "]  =$target_date;
		$association["conditions"]["K9DataUnavailableRoom.del_flg"]=0;
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterCategory")));
		$this->K9MasterRoom->bindModel(array("hasOne"=>array("K9DataUnavailableRoom"=>$association)));
		$this->K9MasterRoomType->unbindModel(array("belongsTo"=>array("K9MasterCategory"),"hasMany"=>array("K9MasterRoom")));
		$rooms=$this->K9MasterRoom->find("all",array( "recursive"=>2 ));
		return $rooms;
	}

	function __getEffectRooms($target_date){

		$data=$this->__getMasterRooms($target_date);

		$rooms=array();
		$situations=array();
		$lang=Configure::read('Config.language');
		$roomtype_name =$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
		$situation_name=$this->K9MasterRoomSituation->hasField("situation_{$lang}")?"situation_{$lang}":"situation";
		foreach($data as $k=>$v){

			switch(true){
			
			case(isset($v["K9DataUnavailableRoom"]) AND !empty($v["K9DataUnavailableRoom"]["id"])):
				$situation=$v["K9DataUnavailableRoom"]["K9MasterRoomSituation"];
				break;
			default:
				$situation=$v["K9MasterRoomSituation"];
				break;
			}

			$room_id=$v["K9MasterRoom"]["id"];
			$situation_id=$situation["id"];
			$situations[$situation_id][$room_id]["room_id"]       =$v["K9MasterRoom"]["id"];
			$situations[$situation_id][$room_id]["room_type_id"]  =$v["K9MasterRoom"]["room_type_id"];
			$situations[$situation_id][$room_id]["room_type"]     =$v["K9MasterRoomType"][$roomtype_name];
			$situations[$situation_id][$room_id]["room_situation"]=$situation[$situation_name];
			$situations[$situation_id][$room_id]["room_floor"]    =$v["K9MasterRoom"]["floor"];
			$situations[$situation_id][$room_id]["room_situation_remarks"]=$situation["remarks"];
			$situations[$situation_id][$room_id]["room_situation_bgcolor"]=$situation["bgcolor"];
			$situations[$situation_id][$room_id]["room_situation_fontcolor"]=$situation["fontcolor"];
			$situations[$situation_id][$room_id]["room_situation_remarks"]=$situation["remarks"];
			$situations[$situation_id][$room_id]["room_remarks"]=$v["K9MasterRoom"]["remarks"];
			$situations[$situation_id][$room_id]["room_num"]    =$v["K9MasterRoom"]["room_num"];

			if(!isset($rooms[$room_id])) $rooms[$room_id]=array();
			$rooms[$room_id]["room_num"]=$v["K9MasterRoom"]["room_num"];
			$rooms[$room_id]["type"]    =$v["K9MasterRoom"]["type"];
		}

		$res["total"]=count($data);
		$res["data"]=$situations;
		$res["rooms"]=$rooms;
		return $res;
	}

	function __getRoomSituation($target_date){

		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
		$situation_name=$this->K9MasterRoomSituation->hasField("situation_{$lang}")?"situation_{$lang}":"situation";

		$res=array();
		$rooms=$this->__getMasterRooms($target_date);
		foreach($rooms as $k=>$v){

			$room=$v["K9MasterRoom"];
			$room_id=$room["id"];
			$room_type=$v["K9MasterRoomType"];

			switch(true){
			
			case(isset($v["K9DataUnavailableRoom"]) AND !empty($v["K9DataUnavailableRoom"]["id"])):
				$situation_remarks=$v["K9DataUnavailableRoom"]["remarks"];
				$situation=$v["K9DataUnavailableRoom"]["K9MasterRoomSituation"];
				break;
			default:
				$situation_remarks=$v["K9MasterRoom"]["remarks"];
				$situation=$v["K9MasterRoomSituation"];
				break;
			}

			$res[$room_id]["room"]["room_num"]=$room["room_num"];
			$res[$room_id]["room"]["room_type"]=$room_type[$roomtype_name];
			$res[$room_id]["room"]["room_type_id"]=$room_type["id"];
			$res[$room_id]["room"]["room_floor"]=$room["floor"];
			//$res[$room_id]["room"]["room_remarks"]=$room["remarks"];
			$res[$room_id]["room"]["is_enable"] =in_array($situation["id"],array(K9MasterRoomSituation::$SITUATION_CLEAN,K9MasterRoomSituation::$SITUATION_DIRTY));
			$res[$room_id]["situation"]["situation"]=$situation[$situation_name];
			$res[$room_id]["situation"]["situation_id"]=$situation["id"];
			$res[$room_id]["situation"]["bgcolor"]=$situation["bgcolor"];
			$res[$room_id]["situation"]["fontcolor"]=$situation["fontcolor"];
			$res[$room_id]["situation"]["remarks"]  =$situation_remarks;
		}

		return $res;
	}


}
