<?php

App::uses("K9BasePricesController","Controller");

class AdK9ExtraOrdersController extends AppController{

	var $name = "K9ExtraOrders";

	var $uses = ["K9DataExtraBeverageOrder",
		         "K9DataExtraFoodOrder",
				 "K9DataExtraRoomserviceOrder",
				 "K9DataExtraTobaccoOrder",
				 "K9MasterFood",
				 "K9MasterCategory",
				 "K9MasterBeverage",
				 "K9MasterTobacco",
				 "K9MasterCard",
				 "K9DataExtraOrder",
				 "K9MasterRoomservice",
				 "K9DataHistoryPriceBeverage",
				 "K9DataHistoryPriceFood",
				 "K9DataHistoryPriceTobacco",
				 "K9DataHistoryPriceRoomservice"
			     ];

	function beforeFilter(){
	
		parent::beforeFilter();
		$this->__setModels();
	}

	public function __setModels()
	{
		$models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["master"]=$this->K9MasterRoomservice;
		$models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["order"] =$this->K9DataExtraRoomserviceOrder;
		$models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["history"] =$this->K9DataHistoryPriceRoomservice;
		$models[K9MasterFood::$CATEGORY_FOOD]["master"]=$this->K9MasterFood;
		$models[K9MasterFood::$CATEGORY_FOOD]["order"] =$this->K9DataExtraFoodOrder;
		$models[K9MasterFood::$CATEGORY_FOOD]["history"] =$this->K9DataHistoryPriceFood;
		$models[K9MasterBeverage::$CATEGORY_DRINK]["master"]=$this->K9MasterBeverage;
		$models[K9MasterBeverage::$CATEGORY_DRINK]["order"] =$this->K9DataExtraBeverageOrder;
		$models[K9MasterBeverage::$CATEGORY_DRINK]["history"] =$this->K9DataHistoryPriceBeverage;
		$models[K9MasterTobacco::$CATEGORY_TOBACCO]["master"]=$this->K9MasterTobacco;
		$models[K9MasterTobacco::$CATEGORY_TOBACCO]["order"] =$this->K9DataExtraTobaccoOrder;
		$models[K9MasterTobacco::$CATEGORY_TOBACCO]["history"] =$this->K9DataHistoryPriceTobacco;
		$this->models=$models;
	}

	public function getExtraInformations()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$ymd= isset($post["ymd"])?$post["ymd"]:date("Ymd");

		$res=$this->__getExtraInfomrations($ymd);
		Output::__outputYes($res);
	}

	public function orderItemSubscribe()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		$post=$this->data;
		
		$ymd= isset($post["ymd"])?$post["ymd"]:date("Ymd");

		$remove_items  =isset($post["remove_items"])?$post["remove_items"]:false;
		$edit_items    =isset($post["edit_items"])?$post["edit_items"]:false;
		$edit_new_items=isset($post["edit_new_items"])?$post["edit_new_items"]:false;
		$new_items     =isset($post["new_items"])?$post["new_items"]:false;

	    $datasource=$this->K9DataExtraFoodOrder->getDataSource();
	    $datasource->begin();

		if(!empty($remove_items)){

			$res=$this->__orderItemRemoves($remove_items);
			if(empty($res)) Output::__outputNo(array("message"=>__("正常に処理が終了しませんでした")));
		} 

		if(!empty($edit_new_items)){

			$res=$this->__orderItemUpdateAddNewItem($edit_new_items,$ymd);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		if(!empty($edit_items)){

			$res=$this->__orderItemUpdate($edit_items,$ymd);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		if(!empty($new_items)){
		
			$res=$this->__insertNewItem($new_items,$ymd);
			if(empty($res["status"])) Output::__outputNo(array("message"=>$res["message"]));
		}

		$datasource->commit();

		$res=array();
		$res["data"]=$this->__getExtraInfomrations($ymd);
		Output::__outputYes($res);
	}

	private function __orderItemUpdateAddNewItem($data,$ymd)
	{

		$list=array();
		foreach($data as $group_id=>$values){

			foreach($values as $k=>$v){

				if(!isset($list[$v["type"]])) $list[$v["type"]]=array();
				$count=count($list[$v["type"]]);
				$list[$v["type"]][$count]["master_id"]=$v["master_id"];
				$list[$v["type"]][$count]["group_id"] =$group_id;
				$list[$v["type"]][$count]["count"]    =$v["count"];
				$list[$v["type"]][$count]["remarks"]  =$v["remarks"];
				$list[$v["type"]][$count]["cash_type_id"]=$v["cash_type_id"];
				$list[$v["type"]][$count]["final_employee_entered"]=$this->Auth->user("employee_id");
			}
		}

		$this->__addPriceId($list,$ymd);
		return $this->__orderItemUpdateAddNewEachItem($list);
	}

	private function __orderItemUpdateAddNewEachItem($list)
	{
		if(empty($list)){

			$res=array();
			$res["status"]=true;
			return $res;
		}

		foreach($list as $type=>$v) break;
		unset($list[$type]);
		$order_model=$this->models[$type]["order"];

		try{

			$order_model->multiInsert($v);

		}catch(Exception $e){

			$res=array();
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		return $this->__orderItemUpdateAddNewEachItem($list);
	}

	private function __getTypePricies($data,$ymd)
	{
		$prices=array();
		foreach($data as $type=>$master_ids){
		
			$history_model=$this->models[$type]["history"];
			$master_model =$this->models[$type]["master"];
			$type_prices=getPriceByHistory($history_model,$master_model,$master_ids,$ymd);
			$prices[$type]=$type_prices;
		}
		return $prices;
	}

	private function __insertNewItem($data,$ymd)
	{
		$group_id=$this->K9DataExtraOrder->firstInsert($ymd);

		$list=array();
		foreach($data as $k=>$v){
		
			$type=$v["type"];
			unset($v["type"]);
			if(!isset($list[$type])) $list[$type]=array();
			$count=count($list[$type]);
			$list[$type][$count]["master_id"]             =$v["master_id"];
			$list[$type][$count]["count"]                 =$v["count"];
			$list[$type][$count]["remarks"]               =$v["remarks"];
			$list[$type][$count]["cash_type_id"]          =$v["cash_type_id"];
			$list[$type][$count]["group_id"]              =$group_id;
			$list[$type][$count]["final_employee_entered"]=$this->Auth->user("employee_id");
		}

		$this->__addPriceId($list,$ymd);

		foreach($list as $type=>$values){
		
			try{

				$this->models[$type]["order"]->multiInsert($values);

			}catch(Exception $e){

				$res=array();
				$res["status"]=false;
				$res["message"]=$e->getMessage();
				return $res;
			}
		}

		$res=array();
		$res["status"]=true;
		return $res;
	}

	//変更されたorder_idしか来ない
	private function __orderItemUpdate($items,$ymd)
	{
		$updaterecords=$this->__orderItemUpdateData($items,$ymd);

		if(!empty($updaterecords["remove"])){
		
			$res=$this->__updateItemRemoves($updaterecords["remove"]);
			if(empty($res["status"])) return $res;
		}

		if(!empty($updaterecords["update"])){
		
			$res=$this->__updateItem($updaterecords["update"]);
			if(empty($res["status"])) return $res;
		}

		if(!empty($updaterecords["insert"])){

			$res=$this->__insertItem($updaterecords["insert"]);
			if(empty($res["status"])) return $res;
		}

		$res=array();
		$res["status"]=true;
		return $res;
	}

	private function __insertItem($data)
	{
		if(empty($data)){
		
			$res=array();
			$res["status"]=true;
			return $res;
		}

		foreach($data as $type=>$values) break;
		unset($data[$type]);

		$res=$this->__insertItemByType($type,$values);
		if(empty($res["status"])) return $res;
		return $this->__insertItem($data);
	}

	private function __insertItemByType($type,$values)
	{
		$order_model=$this->models[$type]["order"];

		try{
		
			$order_model->multiInsert($values);

		}catch(Exception $e){

			$res=array();
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res=array();
		$res["status"]=true;
		return $res;
	}

	private function __updateItem($data)
	{
		if(empty($data)){
		
			$res=array();
			$res["status"]=true;
			return $res;
		}

		foreach($data as $type=>$values) break;
		unset($data[$type]);
		$res=$this->__updateItemByType($type,$values);
		if(empty($res["status"])) return $res;
		return $this->__updateItem($data);
	}

	private function __updateItemByType($type,$values)
	{
		$order_model=$this->models[$type]["order"];

		try{
		
			$order_model->multiInsert($values);

		}catch(Exception $e){

			$res=array();
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res=array();
		$res["status"]=true;
		return $res;
	}

	private function __updateItemRemoves($data)
	{

		if(empty($data)){

			$res=array();
			$res["status"]=true;
			return $res;
		}

		foreach($data as $type=>$values) break;
		unset($data[$type]);

		$list=array();
		$order_model=$this->models[$type]["order"];
		foreach($values as $k=>$order_id){

			$count=count($list);
			$list[$count]["id"]=$order_id;
			$list[$count]["del_flg"]=1;
			$list[$count]["del_date"]=date("YmdHis");
			$list[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
		}

		try{
		
			$order_model->multiInsert($list);

		}catch(Exception $e){

			$res=array();
			$res["status"]=false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		return $this->__updateItemRemoves($data);
	}

	private function __orderItemUpdateData($items,$ymd)
	{
		$insert=array();
		$update=array();
		$remove=array();
		foreach($items as $group_id=>$v){
		
			foreach($v as $initial_type=>$_v){
			
				foreach($_v as $initial_order_id=>$__v){

					$type               =$__v["type"];
					$initial_type       =$__v["initial_type"];
					$initial_order_id   =$__v["initial_order_id"];
					$initial_category_id=$__v["initial_category_id"];
					$is_change=($__v["type"]!=$initial_type OR $__v["category"]!=$initial_category_id);

					switch($is_change){
					
					case(true):

						$remove[$initial_type][]=$initial_order_id;
						if(!isset($insert[$type])) $insert[$type]=array();
						$count=count($insert[$type]);
						$insert[$type][$count]["group_id"] =$group_id;
						$insert[$type][$count]["master_id"]=$__v["master_id"];
						$insert[$type][$count]["count"]    =$__v["count"];
						$insert[$type][$count]["cash_type_id"]=$__v["cash_type_id"];
						$insert[$type][$count]["final_employee_entered"]=$this->Auth->user("employee_id");
						$insert[$type][$count]["remarks"]=$__v["remarks"];
						break;

					case(false):

						if(!isset($update[$initial_type])) $update[$initial_type]=array();
						$count=count($update[$initial_type]);
						$update[$initial_type][$count]["id"]          =$initial_order_id;
						$update[$initial_type][$count]["master_id"]   =$__v["master_id"];
						$update[$initial_type][$count]["cash_type_id"]=$__v["cash_type_id"];
						$update[$initial_type][$count]["final_employee_entered"]=$this->Auth->user("employee_id");
						$update[$initial_type][$count]["remarks"]     =$__v["remarks"];
						$update[$initial_type][$count]["count"]       =$__v["count"];
						break;
					}
				}
			}
		}

		if(!empty($update)) $this->__addPriceId($update,$ymd);
		if(!empty($insert)) $this->__addPriceId($insert,$ymd);

		$res=array();
		$res["remove"]=$remove;
		$res["update"]=$update;
		$res["insert"]=$insert;
		return $res;
	}

	private function __addPriceId(&$data,$ymd)
	{
		foreach($data as $type=>&$values){

			$type_master_ids[$type]=Set::extract($values,"{}.master_id");
			$prices=$this->__getTypePricies($type_master_ids,$ymd);
			foreach($values as $k=>&$value) $value["price_id"]=$prices[$type][$value["master_id"]]["data"]["id"];
			unset($value,$values);
		}
	}

	private function __orderItemRemoves($items)
	{
		if(empty($items)) return true;
		foreach($items as $group_id=>$value) break;
		unset($items[$group_id]);
		$res=$this->__orderItemRemoveByType($value);
		if(empty($res)) return false;
		return $this->__orderItemRemoves($items);
	}

	private function __orderItemRemoveByType($items)
	{
		if(empty($items)) return true;
		foreach($items as $type=>$value) break;
		unset($items[$type]);
		$res=$this->__orderItemRemove($value);
		if(empty($res)) return false;
		return $this->__orderItemRemoveByType($items);
	}

	private function __orderItemRemove($items)
	{
		if(empty($items)) return true;

		foreach($items as $initial_order_id=>$item) break;
		unset($items[$initial_order_id]);

		$master_model=$this->models[$item["initial_type"]]["master"];
		$order_model =$this->models[$item["initial_type"]]["order"];
		$order_id=$item["initial_order_id"];

		$save["id"]=$order_id;
		$save["del_flg"]=1;
		$save["final_employee_entered"]=$this->Auth->user("employee_id");
		$save["del_date"]=date("YmdHis");
		if(!$order_model->save($save)) return false;
		return $this->__orderItemRemove($items);
	}

	private function __getExtraInfomrations($ymd)
	{

		$orders         =$this->__getExtraInfomrationOrders($ymd);
		$categories     =$this->__getCategories();
		$main_categories=$this->__getMainCategories();
		$group_tiltes   =$this->__getGroupTitles($orders);
		$master_menus   =$this->__getMasterMenus($ymd);
		$cashtypes      =$this->__getCashTypes();

		$res=array();
		$res["data"]["data"]["order"]          =$orders;
		$res["data"]["menu"]["categories"]     =$categories;
		$res["data"]["menu"]["main_categories"]=$main_categories;
		$res["data"]["menu"]["group"]          =$group_tiltes;
		$res["data"]["menu"]["menus"]          =$master_menus;
		$res["data"]["menu"]["cashtype"]       =$cashtypes;
		return $res;
	}

	private function __getCashTypes()
	{

		$conditions=array();
		$conditions["and"]["K9MasterCard.del_flg"]=0;
		$data=$this->K9MasterCard->find("all",array( "conditions"=>$conditions ));
		$list=Set::combine($data,"{n}.K9MasterCard.id","{n}.K9MasterCard.card_type");
		return $list;
	}

	private function __getMasterMenus($ymd)
	{
		$master_roomservice=$this->__getMasterMenu(K9MasterRoomservice::$CATEGORY_ROOMSERVICE,$ymd);
		$master_food       =$this->__getMasterMenu(K9MasterFood::$CATEGORY_FOOD,$ymd);
		$master_beverage   =$this->__getMasterMenu(K9MasterBeverage::$CATEGORY_DRINK,$ymd);
		$master_tobacco    =$this->__getMasterMenu(K9MasterTobacco::$CATEGORY_TOBACCO,$ymd);

		$res=array();
		$res[K9MasterFood::$CATEGORY_FOOD]=$master_food;
		$res[K9MasterBeverage::$CATEGORY_DRINK]=$master_beverage;
		$res[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]=$master_roomservice;
		$res[K9MasterTobacco::$CATEGORY_TOBACCO]=$master_tobacco;
		return $res;
	}

	private function __getMasterMenu($type,$ymd)
	{
		$master_model =$this->models[$type]["master"];
		$history_model=$this->models[$type]["history"];
	
		$conditions=array();
		$conditions["or"][0]["and"]["{$master_model->name}.del_flg"]=0;
		$conditions["or"][1]["and"]["DATE_FORMAT({$master_model->name}.del_date,'%Y%m%d') >= "]=$ymd;
		$conditions["or"][1]["and"]["{$master_model->name}.del_flg"]=1;
		$order=array("{$master_model->name}.item_num ASC");
		$menus=$master_model->find("all",array(
		
			"conditions"=>$conditions,
			"order"=>$order
		));


		$master_ids=Set::extract($menus,"{}.{$this->models[$type]["master"]->name}.id");
		$prices=getPriceByHistory($history_model,$master_model,$master_ids,$ymd);

		$lang=Configure::read('Config.language');
		$name=$master_model->hasField("name_{$lang}")?"name_{$lang}":"name";

		foreach($menus as $k=>$v){

			$master_id=$v[$master_model->name]["id"];
			$price    =$prices[$master_id]["data"]["price"];
			$item_num =$v[$master_model->name]["item_num"];
			$title    =$v[$master_model->name][$name];
			$list[$v["K9MasterCategory"]["aliase"]]["_{$v[$master_model->name]["id"]}"]="【{$item_num}】({$price}$) {$title}";
		}

		return $list;
	}

	private function __getGroupTitles($orders=array())
	{
		$group_ids=array();
		$group_ids=array_merge(array_keys($orders[K9MasterFood::$CATEGORY_FOOD]),$group_ids);
		$group_ids=array_merge(array_keys($orders[K9MasterBeverage::$CATEGORY_DRINK]),$group_ids);
		$group_ids=array_merge(array_keys($orders[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]),$group_ids);
		$group_ids=array_merge(array_keys($orders[K9MasterTobacco::$CATEGORY_TOBACCO]),$group_ids);

		$this->K9DataExtraOrder->unbindModel(array("hasMany"=>array($this->models[K9MasterFood::$CATEGORY_FOOD]["order"]->name)));
		$this->K9DataExtraOrder->unbindModel(array("hasMany"=>array($this->models[K9MasterBeverage::$CATEGORY_DRINK]["order"]->name)));
		$this->K9DataExtraOrder->unbindModel(array("hasMany"=>array($this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["order"]->name)));
		$this->K9DataExtraOrder->unbindModel(array("hasMany"=>array($this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["order"]->name)));

		$conditions=array();
		$conditions["and"]["K9DataExtraOrder.id"]=$group_ids;
		$order=array("K9DataExtraOrder.target_date DESC");
		$data=$this->K9DataExtraOrder->find("all",array( "conditions"=>$conditions,"order"=>$order ));
		$list=Set::combine($data,"{n}.K9DataExtraOrder.id","{n}.K9DataExtraOrder.regist_date");
		$list=array_map(function($a){ return localDatetime($a); },$list);
		return $list;
	}

	private function __getMainCategories()
	{
		$list=array();
		$list[K9MasterFood::$CATEGORY_FOOD]       =__("フード");
		$list[K9MasterBeverage::$CATEGORY_DRINK]      =__("ドリンク");
		$list[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]=__("ルームサービス");
		$list[K9MasterTobacco::$CATEGORY_TOBACCO]=__("タバコ");
		return $list;
	}

	private function __getCategories()
	{
		$lang=Configure::read('Config.language');
		$categories=$this->K9MasterCategory->find("all",array());	

		$list=array();
		$name=($this->K9MasterCategory->hasField("name_{$lang}"))?"name_{$lang}":"name";
		foreach($categories as $k=>$v){
		
			$type=$v["K9MasterCategory"]["type"];
			if(!isset($list["menu"][$type]))    $list["menu"][$type]=array();
			if(!isset($list["aliases"][$type])) $list["aliases"][$type]=array();
			if(!isset($list["types"]))          $list["types"]=array();
			$list["menu"][$type][$v["K9MasterCategory"]["id"]]=$v["K9MasterCategory"][$name];
			$list["aliases"][$type][$v["K9MasterCategory"]["id"]]=$v["K9MasterCategory"]["aliase"];
			$list["types"][$v["K9MasterCategory"]["id"]]=$v["K9MasterCategory"]["type"];
		}

		return $list;
	}

	private function __getExtraInfomrationOrders($date)
	{

		$roomservice=$this->__getOrders(K9MasterRoomservice::$CATEGORY_ROOMSERVICE,$date);
		$food       =$this->__getOrders(K9MasterFood::$CATEGORY_FOOD,$date);
		$beverage   =$this->__getOrders(K9MasterBeverage::$CATEGORY_DRINK,$date);
		$tobacco    =$this->__getOrders(K9MasterTobacco::$CATEGORY_TOBACCO,$date);

		$list=array();
		$list[K9MasterFood::$CATEGORY_FOOD]              =$food;
		$list[K9MasterBeverage::$CATEGORY_DRINK]         =$beverage;
		$list[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]=$roomservice;
		$list[K9MasterTobacco::$CATEGORY_TOBACCO]        =$tobacco;
		return $list;
	}

	private function __getOrders($type,$date)
	{

		$master_model=$this->models[$type]["master"];
		$order_model =$this->models[$type]["order"];
		$history_model=$this->models[$type]["history"];

		$conditions=array();
		$conditions["and"]["DATE_FORMAT(K9DataExtraOrder.target_date,'%Y%m%d')"]=$date;
		$conditions["and"]["{$order_model->name}.del_flg"]=0;
		$order=array("K9DataExtraOrder.target_date DESC");
		$this->K9DataExtraOrder->unbindModel(array("hasMany"=>array($this->models[K9MasterFood::$CATEGORY_FOOD]["order"]->name)));
		$this->K9DataExtraOrder->unbindModel(array("hasMany"=>array($this->models[K9MasterBeverage::$CATEGORY_DRINK]["order"]->name)));
		$this->K9DataExtraOrder->unbindModel(array("hasMany"=>array($this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["order"]->name)));
		$this->K9DataExtraOrder->unbindModel(array("hasMany"=>array($this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["order"]->name)));

		$history_model->unbindModel(array("belongsTo"=>array($master_model->name,"K9MasterEmployee")));
		$data=$order_model->find("all",array(
		
			"conditions"=>$conditions,
			"order"     =>$order,
			"recursive" =>2,
		));

		if(empty($data)) return array();

		$list=array();
		foreach($data as $k=>$v){

			$group_id=$v["K9DataExtraOrder"]["id"];
			$category_id=$v[$master_model->name]["K9MasterCategory"]["id"];
			if(!isset($list[$group_id])) $list[$group_id]=array();
			if(!isset($list[$group_id][$category_id])) $list[$group_id][$category_id]=array();

			$id=$v[$order_model->name]["id"];
			$list[$group_id][$category_id][$id]["order"]["id"]          =$id;
			$list[$group_id][$category_id][$id]["order"]["price"]       =$v[$history_model->name]["price"];
			$list[$group_id][$category_id][$id]["order"]["count"]       =$v[$order_model->name]["count"];
			$list[$group_id][$category_id][$id]["order"]["remarks"]     =$v[$order_model->name]["remarks"];
			$list[$group_id][$category_id][$id]["order"]["cash_type_id"]=$v[$order_model->name]["cash_type_id"];
			$list[$group_id][$category_id][$id]["master"]["id"]         =$v[$master_model->name]["id"];
			$list[$group_id][$category_id][$id]["category"]["type"]     =$v[$master_model->name]["K9MasterCategory"]["type"];
			$list[$group_id][$category_id][$id]["category"]["aliase"]   =$v[$master_model->name]["K9MasterCategory"]["aliase"];
			$list[$group_id][$category_id][$id]["category"]["id"]       =$v[$master_model->name]["K9MasterCategory"]["id"];
			$list[$group_id][$category_id][$id]["employee"]["id"]       =$v["K9MasterEmployee"]["id"];
			$list[$group_id][$category_id][$id]["employee"]["name"]     =$v["K9MasterEmployee"]["first_name"];
		}

		return $list;
	}


}//END class

?>
