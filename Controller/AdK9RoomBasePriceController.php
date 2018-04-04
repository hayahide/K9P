<?php

class AdK9RoomBasePriceController extends AppController {

	var $name = 'K9RoomBasePrice';
	var $uses = ["K9MasterRoom","K9DataHistoryPriceRoom"];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function saveBaseRoomPrice()
	{
		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		
		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$room_id=$post["room_id"];
		$price  =$post["price"];
		$remark =$post["remark"];

		try{

			$last_data=$this->__getLastPrice($room_id);
		}catch(Exception $e){
		
			$res["message"]=$e->getMessage();
			Output::__outputNo($res);
		}

		// no price change.
		$data_id=null;
		if($last_data["price"]==$price) $data_id=$last_data["id"];

		try{

			$last_data=$this->__saveBasePrice($data_id,array(
			
				"room_id"=>$room_id,
				"price"  =>$price,
				"remark" =>$remark
			));

		}catch(Exception $e){
		
			$res["message"]=$e->getMessage();
			Output::__outputNo($res);
		}

		Output::__outputYes();
	}

	function __saveBasePrice($data_id,$data){

		if(!empty($data_id)) $save["id"]=$data_id;
		$save["room_id"]=$data["room_id"];
		$save["price"]  =$data["price"];
		$save["remarks"]=$data["remark"];
		if(empty($data_id)) $save["start"]=date("YmdHis");
		if(!$this->K9DataHistoryPriceRoom->save($save)) throw new Exception(__("正常に処理が終了しませんでした"));
		return true;
	}

	function __getLastPrice($room_id){

		$data=$this->__getPriceList($room_id);
		if(empty($data)) throw new Exception(__("情報の取得が行えませんでした"));
		return $data[count($data)-1]["K9DataHistoryPriceRoom"];
	}

	function __getPriceList($room_id){

		$w=null;
		$w["and"]["K9DataHistoryPriceRoom.room_id"]=$room_id;
		$this->K9DataHistoryPriceRoom->unbindModel(array("belongsTo"=>array("K9MasterRoom")));
		$data=$this->K9DataHistoryPriceRoom->find("all",array(
		
			"conditions"=>$w,
			"order"=>array("K9DataHistoryPriceRoom.start ASC")
		));

		return $data;
	}

}
