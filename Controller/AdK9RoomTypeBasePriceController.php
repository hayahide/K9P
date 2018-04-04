<?php

class AdK9RoomTypeBasePriceController extends AppController {

	var $name = 'K9RoomTypeBasePrice';
	var $uses = ["K9MasterRoomType","K9DataHistoryPriceRoomType"];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function saveBaseRoomTypePrice()
	{
		if(!$this->isPostRequest()) exit;

		$post=$this->data;

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$room_type_id=$post["room_type_id"];
		$price=$post["price"];
		$remark=$post["remark"];

		try{

			$last_data=$this->__getLastPrice($room_type_id);
		}catch(Exception $e){
		
			$res["message"]=$e->getMessage();
			Output::__outputNo($res);
		}

		// no price change.
		$data_id=null;
		if($last_data["price"]==$price) $data_id=$last_data["id"];

		try{

			$last_data=$this->__saveBasePrice($data_id,array(
			
				"room_type_id"=>$room_type_id,
				"price"       =>$price,
				"remark"      =>$remark
			));

		}catch(Exception $e){
		
			$res["message"]=$e->getMessage();
			Output::__outputNo($res);
		}

		Output::__outputYes();
	}

	function __saveBasePrice($data_id,$data){

		if(!empty($data_id)) $save["id"]=$data_id;
		$save["room_type_id"]=$data["room_type_id"];
		$save["price"]  =$data["price"];
		$save["remarks"]=$data["remark"];
		if(empty($data_id)) $save["start"]=date("YmdHis");
		if(!$this->K9DataHistoryPriceRoomType->save($save)) throw new Exception(__("正常に処理が終了しませんでした"));
		return true;
	}

	function __getLastPrice($room_type_id){

		$data=$this->__getPriceList($room_type_id);
		if(empty($data)) throw new Exception(__("情報の取得が行えませんでした"));
		return $data[count($data)-1]["K9DataHistoryPriceRoomType"];
	}

	function __getPriceList($room_type_id){

		$w=null;
		$w["and"]["K9DataHistoryPriceRoomType.room_type_id"]=$room_type_id;
		$this->K9DataHistoryPriceRoomType->unbindModel(array("belongsTo"=>array("K9MasterRoomType")));
		$data=$this->K9DataHistoryPriceRoomType->find("all",array(
		
			"conditions"=>$w,
			"order"=>array("K9DataHistoryPriceRoomType.start ASC")
		));

		return $data;
	}

}
