<?php

App::uses('K9TotalCostsController','Controller');
App::uses('K9SettingTaxController','Controller');

class AdK9CheckoutController extends AppController {

	var $name = 'K9Checkout';
	var $uses = [ "K9DataReservation","K9DataSchedule","K9DataReststaySchedule","K9MasterRoom","K9DataSchedulePlan"];

	public function beforeFilter() {

		parent::beforeFilter();
		$this->loadModel("K9MasterRoomSituation");
		$this->loadModel("K9DataHistoryPriceCard");
	}

	function __getReservationByHash($hash){

		$association=$this->K9DataReservation->association;

		//最新の1件
		$scheduleplan=$association["hasMany"]["K9DataSchedulePlan"];
		$scheduleplan["order"]=array("K9DataSchedulePlan.start DESC");
		$scheduleplan["conditions"]=array("K9DataSchedulePlan.del_flg"=>'0');
		$scheduleplan["limit"]=1;

		$this->K9DataReservation->bindModel(array("hasOne"=>array("K9DataSchedulePlan"=>$scheduleplan)));
		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));
		$data=$this->K9DataReservation->getReservationByHash($hash,0,array( "recursive"=>2 ));
		return $data;
	}

	function checkout(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$reservation_hash=$post["hash"];
		$reserve_id=$post["id"];

		if(!$reservation=$this->__getReservationByHash($reservation_hash)){

			Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		}

		$datasource=$this->K9DataReservation->getDataSource();
		$datasource->begin();

		if(0>strtotime($reservation["K9DataReservation"]["checkin_time"]))  Output::__outputNo(array("message"=>__("チェックインされていません")));
		if(strtotime($reservation["K9DataReservation"]["checkout_time"])>0) Output::__outputNo(array("message"=>__("既にチェックアウト済みです")));
		if($reserve_id!=$reservation["K9DataReservation"]["id"])            Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		if(!$time=$this->K9DataReservation->checkout($reserve_id))          Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		//refresh room situation for only new plan.
		if(!$this->K9MasterRoom->updateRoomSituation($reservation["K9DataSchedulePlan"]["room_id"],K9MasterRoomSituation::$SITUATION_CLEAN)){

			Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		}

		$datasource->commit();

		//view for total cost.
		$total_costs=$this->__getTotalCost($reservation["K9DataReservation"]["id"],$reservation["K9DataReservation"]["staytype"]);

		//$time=date("Ymd");
		$res["data"]=array();
		$res["data"]["time"]=localDatetime($time);
		$res["data"]["price"]["order"]=$total_costs["order"];
		$res["data"]["price"]["hotel"]=$total_costs["hotel"];
		$res["data"]["price"]["tax"]=$this->__getTaxvalueWithRange(date("Ymd"));
		Output::__outputYes($res);
	}

	private function __getTaxvalueWithRange($day)
	{
		$controller=new K9SettingTaxController();
		return $controller->__getTaxvalueWithRange($day);
	}

	private function __getTotalCost($reserve_id,$staytype)
	{
		$controller=new K9TotalCostsController();
		return $controller->__getTotalCost($reserve_id,$staytype);
	}

}
