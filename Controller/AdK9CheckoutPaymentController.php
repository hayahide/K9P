<?php

class AdK9CheckoutPaymentController extends AppController {

	var $name = 'K9CheckoutPayment';
	var $uses = [ "K9MasterCard","K9DataCheckoutPayment","K9DataReservation" ];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	public function getCheckoutPayment()
	{
		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();
		$reservation_hash=$post["reservation_hash"];
	
		$reservation =$this->__getReservationByHash($reservation_hash);
		if(empty($reservation)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$payment_data=$this->__getCurrentCheckoutPayment($reservation["K9DataReservation"]["id"]);
		$cash_types  =$this->__getCashtypeList();

		$res=array();
		$res["data"]["menus"]=$cash_types;
		$res["data"]["data"]=array();

		if(!empty($payment_data)){

			$res["data"]["data"]["cash_type_id"]=$payment_data["K9DataCheckoutPayment"]["cash_type_id"];
			$res["data"]["data"]["purchase_flg"]=$payment_data["K9DataCheckoutPayment"]["purchase_flg"]?1:0;
			$res["data"]["data"]["type"]        =$payment_data["K9MasterCard"]["type"];
		}

		Output::__outputYes($res);
	}

	private function __getCurrentCheckoutPayment($reserve_id)
	{

		$conditions=array();
		$conditions["and"]["K9DataCheckoutPayment.reserve_id"]=$reserve_id;
		$conditions["and"]["K9DataCheckoutPayment.del_flg"]=0;

		$this->K9DataCheckoutPayment->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$data=$this->K9DataCheckoutPayment->find("first",array(
		
			"conditions"=>$conditions
		));

		if(empty($data)) return array();
		return $data;
	}

	private function __getReservationByHash($reservation_hash)
	{

		$conditions=array();
		$conditions["and"]["K9DataReservation.hash"]=$reservation_hash;
		$conditions["and"]["K9DataReservation.del_flg"]=0;
		$this->K9DataReservation->unbindFully();
		$data=$this->K9DataReservation->find("first",array(
		
			"conditions"=>$conditions
		));

		return $data;
	}

	private function __getCashtypeList()
	{

		$card=$this->K9MasterCard->getCards(0,array(
		
			"order"=>"asc"
		));

		$list=array();
		foreach($card as $k=>$v){
		
			$type=$v["K9MasterCard"]["type"];
			$list[$type][$v["K9MasterCard"]["id"]]=$v["K9MasterCard"]["card_type"];
		}

		return $list;
	}

	public function paymentWaySubscribe()
	{
		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();
		
		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$reservation_hash=$post["reservation_hash"];
		$reservation=$this->__getReservation($reservation_hash);
		if(empty($reservation)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$payment_res=$this->__savePaymentWay(array(
		
			"reserve_id"    =>$reservation["K9DataReservation"]["id"],
			"cash_type_id"  =>$post["cash_type_id"],
			"purchase_flg"  =>$post["purchase_flg"],
			"employee_id"   =>$this->Auth->user("employee_id")
		));

		$res["data"]["id"]=$payment_res["id"];
		Output::__outputYes($res);
	}

	public function __getReservation($reservation_hash)
	{
		$this->K9DataReservation->unbindFully();
		return $this->K9DataReservation->getReservationByHash($reservation_hash,0,array( "recursive"=>1	));
	}

	public function __savePaymentWay($data=array())
	{

		$reservation_id=$data["reserve_id"];
		$cash_type_id  =$data["cash_type_id"];
		$purchase_flg  =$data["purchase_flg"];
		$employee_id   =$data["employee_id"];
		$payment_info  =$this->__getPaymentInformation($reservation_id);

		$save=array();
		if(!empty($payment_info)){

			$save["id"]=$payment_info["K9DataCheckoutPayment"]["id"];

			//off => on になった瞬間を記録とし、支払いを受けた事をこの時間として証明する
			if(empty($payment_info["K9DataCheckoutPayment"]["purchase_flg"]) AND !empty($purchase_flg)) $save["purchase_time"]=date("YmdHis");
		} 

		$save["purchase_flg"]=$purchase_flg;
		$save["cash_type_id"]=$cash_type_id;
		$save["reserve_id"]  =$reservation_id;
		$save["final_employee_entered"]=$employee_id;
		if(!$res=$this->K9DataCheckoutPayment->save($save)) return false;
		return $res["K9DataCheckoutPayment"];
	}

	public function __getPaymentInformation($reservation_id)
	{

		$this->K9DataCheckoutPayment->unbindModel(array("belongsTo"=>array("K9DataReservation","K9MasterCard")));
		$conditions["and"]["{$this->K9DataCheckoutPayment->name}.reserve_id"]=$reservation_id;
		$data=$this->K9DataCheckoutPayment->find("first",array( "conditions"=>$conditions ));
		return $data;
	}

}
