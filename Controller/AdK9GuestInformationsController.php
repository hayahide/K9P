<?php

App::uses('K9ReservationSubscribeController','Controller');
App::uses('K9GuestSearchController','Controller');
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');

class AdK9GuestInformationsController extends AppController {

	var $name = 'K9GuestInformations';
	var $uses = [

		"K9DataGuest",
	];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function saveGuestInformation()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;

		$guest_hash=$post["guest_hash"];
		unset($post["guest_hash"]);

		//$guest_hash="8fc52eea1be8e1c1f71e1cf6cfe787c21b2a525e57bb2a277cc92e2a1da1bc23";
		if(!$current_guest=$this->K9DataGuest->getGuestInformationWithHash($guest_hash)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		$guest_id=$current_guest["K9DataGuest"]["id"];

		try{
		
			$guest_info=$this->__guestSubscribe($guest_id,array(

				"title"           =>$post["title"],
				"parson-firstname"=>$post["first_name"],
				"parson-lastname" =>$post["last_name"],
				"parson-passport" =>$post["passport"],
				"parson-incidential"=>$post["incidential"],
				"parson-language"    =>$post["language_num"],
				"contact-address"    =>$post["contact_address"],
				"contact-city"       =>$post["contact_city"],
				"contact-state"      =>$post["contact_state"],
				"contact-nationality"=>$post["contact_nationality"],
				"contact-country"    =>$post["contact_country"],
				"contact-tel"        =>$post["contact_tel"],
				"contact-email"      =>$post["contact_email"],
				"contact-zip-code"   =>$post["contact_zip_code"],
				"contact-tel"        =>$post["contact_tel"],
				"remark-guest-note"  =>$post["remarks"]
			));

		}catch(Exception $e){

			Output::__outputNo(array("message"=>$e->getMessage()));
		}

		$guest_info=$this->__getGuest(array( "guest_hash"=>$guest_info["hash"] ));
		$list=$this->__getGuestInformations($guest_info);

		$res=array();
		$res["data"]=$list;
		Output::__outputYes($res);
	}

	function __guestSubscribe($guest_id,$parson){
	
		$controller=new K9ReservationSubscribeController();
		$res=$controller->__guestSubscribe($guest_id,$parson,array(
		
			"employee_id"=>$this->Auth->user("employee_id"),
			"hash"       =>$this->Auth->user()["K9MasterEmployee"]["hash"]
		));
		return $res;
	}

	function __getGuestInformations($guests){
	
		$controller=new K9GuestSearchController();
		$res=$controller->__getGuestInformations($guests);
		return $res;
	}

	function __getGuest($data){
	
		$controller=new K9GuestSearchController();
		$res=$controller->__getGuest($data);
		return $res;
	}

}
