<?php

App::import('Utility', 'Sanitize');
App::uses('K9BasePricesController','Controller');

class AdK9MasterItemsController extends AppController {

	var $name="K9MasterItems";

	var $uses = [

		"K9MasterApart",
		"K9MasterBeverage",
		"K9MasterFood",
		"K9MasterLaundry",
		"K9MasterLimousine",
		"K9MasterReststay",
		"K9MasterRoom",
		"K9MasterCategory",
		"K9MasterRoomType",
		"K9MasterSpa",
		"K9MasterTobacco",
		"K9MasterRoomservice",
		"K9MasterCategory",
		"K9DataSchedulePlan",
		"K9DataReservation",
		"K9DataSchedule",
		"K9DataReststaySchedule",
		"K9DataOrderFood",
		"K9DataOrderBeverage",
		"K9DataOrderSpa",
		"K9DataOrderLimousine",
		"K9DataOrderLaundry",
		"K9DataOrderTobacco",
		"K9DataOrderRoomservice",
		"K9DataExtraOrder",
		"K9DataExtraFoodOrder",
		"K9DataExtraBeverageOrder",
		"K9DataExtraRoomserviceOrder"
	];

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function masterItemSubscribeForRoom()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$type=$post["type"];
		if(K9MasterRoom::$CATEGORY_ROOM!=$type) exit;

		$edit_values=$post["edit_values"];
	    $datasource=$this->K9MasterRoom->getDataSource();
	    $datasource->begin();

		$res=$this->__editRoomValues($edit_values);
		if(empty($res)) Output::__outputNo(array("message"=>array("正常に処理が終了しませんでした")));
		$datasource->commit();

		$res=array();
		$res=$this->__getMasterDataByTypeWithMenus($type);
		Output::__outputYes($res);
	}

	function masterItemSubscribe()
	{
		if(!$this->isPostRequest()) exit;

		//$post=$this->__getTestPostData();
		$post=$this->data;
		$type=$post["type"];

		$is_type_ok=$this->__checkType($type);
		if(empty($is_type_ok)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));

		$remove_menu_ids  =isset($post["remove_menu_ids"])   ?$post["remove_menu_ids"]:false;
		$add_menu_objects =isset($post["add_menu_objects"])  ?$post["add_menu_objects"]:false;
		$edit_menu_objects=isset($post["edit_menu_objects"]) ?$post["edit_menu_objects"]:false;

		$models=$this->__getUseModels($type);
		$master_model =$models["master_model"];
		$history_model=$models["history_model"];

	    $datasource=$master_model->getDataSource();
	    $datasource->begin();

		if(!empty($edit_menu_objects)){
		
			$res=$this->__subscribeEditRemarks($type,$edit_menu_objects);
			if(empty($res["status"])) Output::__outputNo(array("message"=>__($res["message"])));
		}

		if(!empty($add_menu_objects)){

			$aliases=array_unique(Set::extract($add_menu_objects,"{}.aliase"));
			$category_ids=$category=$this->__getCategoryByAliase($aliases);
			if(empty($category_ids)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
	
			$res=$this->__subscribeEachCategory($type,$category_ids,$add_menu_objects);
			if(empty($res["status"])) Output::__outputNo(array("message"=>__($res["message"])));

			$master_new_inserts=$res["data"];
			$res=$this->__subscribeOrderHistory($history_model,$master_new_inserts);
			if(empty($res["status"])) Output::__outputNo(array("message"=>__($res["message"])));
		}

		if($remove_menu_ids){

			//about extra.
			$res=$this->__checkIfCanRemoveOfExtra($type,$remove_menu_ids);
			if(empty($res["status"])) Output::__outputNo(array("message"=>__($res["message"])));

			//check if item can be removed.
			$res=$this->__checkIfCanRemove($type,$remove_menu_ids);
			if(empty($res["status"])) Output::__outputNo(array("message"=>__($res["message"])));

			$res=$this->__removeMaster($type,$master_model,$remove_menu_ids);
			if(empty($res["status"])) Output::__outputNo(array("message"=>__($res["message"])));
		}

		$datasource->commit();

		//面倒なので既存の返す
		$res=array();
		$res=$this->__getMasterDataByTypeWithMenus($type);
		Output::__outputYes($res);
	}

	function __removeMaster($type,Model $master_model,$remove_menu_ids)
	{

		$update=array();

		foreach($remove_menu_ids as $k=>$v){
		
			$count=count($update);
			$update[$count]["id"]     =$v;
			$update[$count]["del_flg"]=1;
			$update[$count]["del_date"]=date("YmdHis");
			$update[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
		}

		try{
		
			$master_model->multiInsert($update);

		}catch(Exception $e){
		
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	function __subscribeOrderHistory(Model $history_model,$data)
	{

		$foreignKey=$history_model->foreignKey;

		$today=date("Y-m-d");
		$inserts=array();
		foreach($data as $k=>$v){
		
			$count=count($inserts);
			$inserts[$count][$foreignKey]=$v["data_id"];
			$inserts[$count]["price"]    =$v["price"];
			$inserts[$count]["start"]    =$today;
			$inserts[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
		}

		try{
		
			$history_model->multiInsert($inserts);

		}catch(Exception $e){
		
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	function __subscribeEditRemarks($type,$edit_menu_objects)
	{

		try{

			$lang=Configure::read('Config.language');
			$models=$this->__getUseModels($type);
			$master_model=$models["master_model"];
			$name_column=$master_model->hasField("name_{$lang}")?"name_{$lang}":"name";

			$list=array();
			foreach($edit_menu_objects as $k=>$v){
			
				$count=count($list);
				$list[$count]["id"]        =$v["id"];
				$list[$count]["remarks"]   =Sanitize::escape($v["remarks"]);
				$list[$count]["item_num"]  =Sanitize::escape($v["item_num"]);
				$list[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
				$list[$count][$name_column]=Sanitize::escape($v["name"]);
			}

			$master_model->multiInsert($list);

		}catch(Exception $e){ 
	   
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	function __subscribeEachCategory($type,$category_ids,$add_menu_objects)
	{

		try{

			$models=$this->__getUseModels($type);
			$master_model=$models["master_model"];
			$data_results=$this->__subscribe($master_model,$category_ids,$add_menu_objects);
	
		}catch(Exception $e){ 
	   
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["data"]=$data_results;
		$res["status"]=true;
		return $res;
	}

	// itemnumの重複確認はしてません(unique restriction on the table.)
	// one function of what shuld set for checking if itemnum is unique is not set now.
	function __subscribe(Model $master_model,$category_ids,$add_menu_objects,$results=array()){

		if(empty($add_menu_objects)) return $results;

		$add_menu_object=array_shift($add_menu_objects);

		$save["name"]       =Sanitize::escape($add_menu_object["title_ja"]);
		$save["name_eng"]   =Sanitize::escape($add_menu_object["title_eng"]);

		if($master_model->hasField("item_num")) $save["item_num"]=$add_menu_object["itemnum"];
		$save["remarks"]    =Sanitize::escape($add_menu_object["remarks"]);
		$save["category_id"]=$category_ids[$add_menu_object["aliase"]];
		$save["final_employee_entered"]=$this->Auth->user("employee_id");

		$master_model->id=null;
		if(!$res=$master_model->save($save)) return false;

		$last_id=$master_model->getLastInsertID();
		$data["data_id"]=$last_id;
		$data["price"]  =$add_menu_object["price"];
		$results[]=$data;
		return $this->__subscribe($master_model,$category_ids,$add_menu_objects,$results);
	}

	function __getCategoryByAliase($aliase)
	{
		
		if(!$category=$this->K9MasterCategory->findAllByAliase($aliase)) return false;
		$category_ids=Set::combine($category,"{n}.K9MasterCategory.aliase","{n}.K9MasterCategory.id");
		return $category_ids;
	}

	function __checkType($type){

		return (in_array($type,array(

			K9MasterRoomType::$CATEGORY_ROOMTYPE,
			K9MasterRoom::$CATEGORY_ROOM,
			K9MasterReststay::$CATEGORY_RESTSTAY,
			K9MasterSpa::$CATEGORY_SPA,
			K9MasterLimousine::$CATEGORY_LIMOUSINE,
			K9MasterLaundry::$CATEGORY_LAUNDRY,
			K9MasterFood::$CATEGORY_FOOD,
			K9MasterBeverage::$CATEGORY_DRINK,
			K9MasterTobacco::$CATEGORY_TOBACCO,
			K9MasterRoomservice::$CATEGORY_ROOMSERVICE

		)));
	}

	function __getUseModels($type){

		$controller=new K9BasePricesController();
		$res=$controller->__getUseModels($type);
		return $res;
	}

	function __getMasterDataByTypeWithMenus($type){

		$controller=new K9BasePricesController();
		$res=$controller->__getMasterDataByTypeWithMenus($type);
		return $res;
	}

	private function __checkIfCanRemoveForRoomTypeOfExtraData(Model $order_model,$remove_menu_ids=array())
	{

		$hasOne=$this->K9DataExtraOrder->hasMany[$order_model->name];
		$hasOne["conditions"]["and"]["{$order_model->name}.master_id"]=$remove_menu_ids;
		$hasOne["conditions"]["and"]["{$order_model->name}.del_flg"]  =0;
		$association[$order_model->name]=$hasOne;

		$this->K9DataExtraOrder->unbindFully();
		$this->K9DataExtraOrder->bindModel(array("hasOne"=>$association));

		$today=date("Ymd");
		$conditions=array();
		$conditions["and"]["DATE_FORMAT(K9DataExtraOrder.target_date,'%Y%m%d') >= "]=$today;
		$data=$this->K9DataExtraOrder->find("all",array( "conditions"=>$conditions ));
		return $data;
	}

	private function __checkIfCanRemoveForRoomTypeOfExtra($data)
	{
		$err_master_ids=array();
		foreach($data as $k=>$v){
		
			if(empty($v["K9DataExtraFoodOrder"])) continue;
			$err_master_ids[]=$v["K9DataExtraFoodOrder"]["master_id"];
		}

		$err_master_ids=array_unique($err_master_ids);
		return $err_master_ids;
	}

	private function __checkIfCanRemoveOfExtra($type,$remove_menu_ids=array())
	{

		switch($type){
		
		case(K9MasterFood::$CATEGORY_FOOD):

			$master_ids=array();
			$data=$this->__checkIfCanRemoveForRoomTypeOfExtraData($this->K9DataExtraFoodOrder,$remove_menu_ids);
			if(!empty($data)) $master_ids=$this->__checkIfCanRemoveForRoomTypeOfExtra($data);
			break;

		case(K9MasterBeverage::$CATEGORY_DRINK):

			$master_ids=array();
			$data=$this->__checkIfCanRemoveForRoomTypeOfExtraData($this->K9DataExtraBeverageOrder,$remove_menu_ids);
			if(!empty($data)) $master_ids=$this->__checkIfCanRemoveForRoomTypeOfExtra($data);
			break;

		case(K9MasterRoomservice::$CATEGORY_ROOMSERVICE):

			$master_ids=array();
			$data=$this->__checkIfCanRemoveForRoomTypeOfExtraData($this->K9DataExtraRoomserviceOrder,$remove_menu_ids);
			if(!empty($data)) $master_ids=$this->__checkIfCanRemoveForRoomTypeOfExtra($data);
			break;
		}

		if(empty($master_ids)){
		
			$res=array();
			$res["status"]=true;
			return $res;
		}

		$res=array();
		$res["status"]=true;
		$res["message"]=__("削除予定の項目は使用中、もしくは今後使われる予定です");
		return $res;
	}

	private function __checkIfCanRemove($type,$remove_menu_ids=array())
	{

		switch($type){
		
		case(K9MasterRoomType::$CATEGORY_ROOMTYPE):

			$res=array();
			$use_reserver_ids=$this->__checkIfCanRemoveForRoomType($remove_menu_ids);
			if(!is_array($use_reserver_ids) AND !empty($use_reserver_ids)){
			
				$res["status"]=true;
				return $res;
			}

			$res["status"]=false;
			$res["message"]=__("削除予定の部屋タイプは使用中、もしくは今後使われる予定です");
			return $res;
			break;

		case(K9MasterFood::$CATEGORY_FOOD):
			$res=array();
			$use_reserver_ids=$this->__checkIfCanRemoveForOrder($this->K9DataOrderFood,$remove_menu_ids);
			break;
		case(K9MasterBeverage::$CATEGORY_DRINK):
			$res=array();
			$use_reserver_ids=$this->__checkIfCanRemoveForOrder($this->K9DataOrderBeverage,$remove_menu_ids);
			break;
		case(K9MasterSpa::$CATEGORY_SPA):
			$res=array();
			$use_reserver_ids=$this->__checkIfCanRemoveForOrder($this->K9DataOrderSpa,$remove_menu_ids);
			break;
		case(K9MasterLimousine::$CATEGORY_LIMOUSINE):
			$res=array();
			$use_reserver_ids=$this->__checkIfCanRemoveForOrder($this->K9DataOrderLimousine,$remove_menu_ids);
			break;
		case(K9MasterLaundry::$CATEGORY_LAUNDRY):
			$res=array();
			$use_reserver_ids=$this->__checkIfCanRemoveForOrder($this->K9DataOrderLaundry,$remove_menu_ids);
			break;
		case(K9MasterTobacco::$CATEGORY_TOBACCO):
			$res=array();
			$use_reserver_ids=$this->__checkIfCanRemoveForOrder($this->K9DataOrderTobacco,$remove_menu_ids);
			break;
		case(K9MasterRoomservice::$CATEGORY_ROOMSERVICE):
			$res=array();
			$use_reserver_ids=$this->__checkIfCanRemoveForOrder($this->K9DataOrderRoomservice,$remove_menu_ids);
			break;
		}

		if(!is_array($use_reserver_ids) AND !empty($use_reserver_ids)){
		
			$res["status"]=true;
			return $res;
		}

		$res["status"]=false;
		$res["message"]=__("削除予定の項目は使用中、もしくは今後使われる予定です");
		return $res;
	}

	private function __checkIfCanRemoveForAnyOrder(Model $schedule_model,Model $order_model,$remove_menu_ids=array())
	{
		$today=date("Ymd");

		//予約の中で、1対象削除項目が含まれていれば対象外とする
		$association      =$this->K9DataReservation->association;
		$association_order=$association["hasMany"][$order_model->name];
		$association_order["conditions"]["and"]["{$order_model->name}.{$order_model->masterForeignKey}"]=$remove_menu_ids;
	
		$hasOne=array();
		$hasOne[$order_model->name]=$association_order;

		//that's why hasOne is used.
		$this->K9DataReservation->bindModel(array("hasOne"=>$hasOne));
		$data=$schedule_model->getUsedSchedules($today);
		return $data;
	}

	private function __checkIfCanRemoveCheckValues(Model $order_model,$data=array())
	{

		$error_reservation_ids=array();
		foreach($data as $k=>$v){

			$plans=$v["K9DataReservation"][$order_model->name];
			if(empty($plans)) continue;
			$error_reservation_ids[]=$v["K9DataReservation"]["id"];
		}

		if(empty($error_reservation_ids)) return true;
		return $error_reservation_ids;
	}

	private function __checkIfCanRemoveForOrdersTargetReservationId(Model $schedule_model,Model $order_model,$remove_menu_ids=array())
	{

		$data=$this->__checkIfCanRemoveForAnyOrder($schedule_model,$order_model,$remove_menu_ids);
		if(empty($data)) return true;

		$reserve_ids=$this->__checkIfCanRemoveCheckValues($order_model,$data);
		return $reserve_ids;
	}

	private function __checkIfCanRemoveForRoomTypeData(Model $schedule_model,$room_ids)
	{

		$today=date("Ymd");
		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));

		$association =$this->K9DataReservation->association;
		$scheduleplan=$association["hasMany"]["K9DataSchedulePlan"];
		$scheduleplan["conditions"]["and"]["K9DataSchedulePlan.room_id"]=$room_ids;
		$scheduleplan["conditions"]["and"]["K9DataSchedulePlan.del_flg"]=0;
		$hasOne[$this->K9DataSchedulePlan->name]=$scheduleplan;

		//that's why hasOne is used.
		$this->K9DataReservation->bindModel(array("hasOne"=>$hasOne));

		$data=$schedule_model->getUsedSchedules($today);
		return $data;
	}

	private function __checkIfCanRemoveForRoomTypeTargetReservationId(Model $schedule_model,$room_ids)
	{

		$data=$this->__checkIfCanRemoveForRoomTypeData($schedule_model,$room_ids);
		if(empty($data)) return true;

		$reserve_ids=$this->__checkIfCanRemoveCheckValues($this->K9DataSchedulePlan,$data);
		return $reserve_ids;
	}

	private function __checkRoomIdsByRoomType($room_type_ids=array())
	{
		$conditions=array();
		$conditions["and"]["K9MasterRoomType.id"]=$room_type_ids;
		$this->K9MasterRoomType->unbindModel(array("belongsTo"=>array("K9MasterCategory")));
		$rooms=$this->K9MasterRoomType->find("first",array( "conditions"=>$conditions ));
		if(empty($rooms)) return array();
		$room_ids=Set::extract($rooms["K9MasterRoom"],"{}.id");
		return $room_ids;
	}

	private function __checkIfCanRemoveForOrder(Model $order_model,$remove_menu_ids=array())
	{

		$stay_reserve_ids    =$this->__checkIfCanRemoveForOrdersTargetReservationId($this->K9DataSchedule,$order_model,$remove_menu_ids);
		$reststay_reserve_ids=$this->__checkIfCanRemoveForOrdersTargetReservationId($this->K9DataReststaySchedule,$order_model,$remove_menu_ids);

		$reserve_ids=array();
		if(is_array($stay_reserve_ids))     $reserve_ids=array_merge($reserve_ids,$stay_reserve_ids);
		if(is_array($reststay_reserve_ids)) $reserve_ids=array_merge($reserve_ids,$reststay_reserve_ids);
		if(empty($reserve_ids)) return true;
		return $reserve_ids;
	}

	private function __checkIfCanRemoveForRoomType($remove_menu_ids)
	{

		//調査対象Room ID 無し
		$room_ids=$this->__checkRoomIdsByRoomType($remove_menu_ids);
		if(empty($room_ids)) return true;

		//調査対象なし
		//本日、もしくは以降の日程
		$stay_reserve_ids    =$this->__checkIfCanRemoveForRoomTypeTargetReservationId($this->K9DataSchedule,$room_ids);
		$reststay_reserve_ids=$this->__checkIfCanRemoveForRoomTypeTargetReservationId($this->K9DataReststaySchedule,$room_ids);

		$reserve_ids=array();
		if(is_array($stay_reserve_ids))     $reserve_ids=array_merge($reserve_ids,$stay_reserve_ids);
		if(is_array($reststay_reserve_ids)) $reserve_ids=array_merge($reserve_ids,$reststay_reserve_ids);
		if(empty($reserve_ids)) return true;
		return $reserve_ids;
	}

	public function __getAliaseMapping($types=array())
	{
		$categories=$this->K9MasterCategory->getCategoriesByAliase($types);
		$categories=Set::combine($categories,"{n}.K9MasterCategory.aliase","{n}.K9MasterCategory.id");
		return $categories;
	}

	private function __editRoomParValues($edit_values,$categories)
	{
		if(empty($edit_values)) return true;
		$edit_value=array_shift($edit_values);

		$this->K9MasterRoom->id=$edit_value["master_id"];
		$save["id"]          =$edit_value["master_id"];
		$save["room_type_id"]=$edit_value["room_type_id"];
		$save["floor"]       =$edit_value["floor"];
		$save["type"]        =$edit_value["type"];
		$save["remarks"]     =$edit_value["remarks"];
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		$save["category_id"]=$categories[$edit_value["type"]];
		$res=$this->K9MasterRoom->save($save);
		if(empty($res)) return false;
		return $this->__editRoomParValues($edit_values,$categories);
	}

	private function __editRoomValues($edit_values)
	{
		$aliases=array_unique(Set::extract($edit_values,"{}.type"));
		$categories=$this->__getAliaseMapping($aliases);

		if(empty($edit_values)) return true;
		return $this->__editRoomParValues($edit_values,$categories);
	}

}
