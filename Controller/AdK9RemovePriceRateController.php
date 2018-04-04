<?php

class AdK9RemovePriceRateController extends AppController {

	var $name = 'K9RemovePriceRate';
	var $uses = [

		"K9DataPriceParRoom",
		"K9DataPriceRoomType",
		"K9MasterRoomType",
		"K9MasterRoom",
		"K9DataSchedule",
		"K9DataReservation",
		"K9DataHistoryPriceRoomType",
		"K9DataHistoryPriceRoom"
	];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function removePriceRate(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$type=$post["type"];
		switch($type){
		case("room"):

			$res=$this->__removePriceRateRoom($post);
			break;

		case("room-type"):

			$res=$this->__removePriceRateRoomType($post);
			break;

		default:

			$res["status"]=false;
			$res["error"]=__("登録処理に失敗しました(1)");
			break;
		}

		if(empty($res["status"])) Output::__outputNo(array("message"=>$res["error"]));
		Output::__outputYes();
	}

	function __removePriceRateRoom($data)
	{

		$data_id=$data["data_id"];
		if(!$this->K9DataPriceParRoom->removePlan($data_id)){

			$res["status"]=false;
			$res["error"]=__("登録処理に失敗しました(2)");
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	function __removePriceRateRoomType($data)
	{
		$data_id=$data["data_id"];
		if(!$this->K9DataPriceRoomType->removePlan($data_id)){

			$res["status"]=false;
			$res["error"]=__("登録処理に失敗しました(3)");
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

}
