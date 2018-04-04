<?php

class AdK9ColorController extends AppController{

    var $name = "K9Color";
    var $uses = [

		"K9MasterColor",
		"K9MasterCheckinColor",
		"K9DataSchedule"
    ];

	function beforeFilter()
	{
		$this->loadModel("K9MasterCheckinColor");
	}

	function __getCheckinStatus($is_checkin,$is_checkout){

		switch(true){
		
			case($is_checkin AND $is_checkout):
				$status=K9MasterCheckinColor::$CHECKOUT;
				break;
			case($is_checkin AND !$is_checkout):
				$status=K9MasterCheckinColor::$CHECKIN;
				break;
			default:
				$status=K9MasterCheckinColor::$UNCHECKIN;
				break;
		}

		return $status;
	}

	function __getColorList(){

		$name=$this->K9MasterColor->name;
		if(!$colors=$this->$name->findAll()) return array();
		return Set::combine($colors,"{n}.{$name}.id","{n}.{$name}.name");
	}

	function __saveScheduleColor($colors=array()){

		$count=0;
		$insert=array();
		foreach($colors as $schedule_id=>$color_id){

			$insert[$count]["id"]=$schedule_id;
			$insert[$count++]["color_id"]=$color_id;
		}

		try{

			$this->K9DataSchedule->multiInsert($insert);
		
		}catch(Exception $e){

			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

}//END class

?>
