<?php

App::import('Utility', 'Sanitize');

class AdK9BasePricesController extends AppController {

	var $name = 'K9BasePrices';
	var $uses = [

		"K9MasterSpa",
		"K9MasterRoom",
		"K9MasterRoomType",
		"K9MasterFood",
		"K9MasterCategory",
		"K9MasterRoomservice",
		"K9MasterBeverage",
		"K9MasterLimousine",
		"K9MasterLaundry",
		"K9MasterReststay",
		"K9MasterTobacco",
		"K9DataHistoryPriceSpa",
		"K9DataHistoryPriceLaundry",
		"K9DataHistoryPriceLimousine",
		"K9DataHistoryPriceRoomservice",
		"K9DataHistoryPriceFood",
		"K9DataHistoryPriceBeverage",
		"K9DataHistoryPriceRoom",
		"K9DataHistoryPriceRoomType",
		"K9DataHistoryPriceReststay",
		"K9DataHistoryPriceTobacco"
	];

	public function beforeFilter() {

		parent::beforeFilter();
		$this->loadOrderMasterModels();
	}

	private function __getMasterCategory($type)
	{
		$data=$this->K9MasterCategory->getCategories($type);
		return $data;
	}

	function getMasterData(){

		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();

		$type=$post["type"];
		$res=$this->__getMasterDataByTypeWithMenus($type);
		Output::__outputYes($res);
	}

	function masterPriceSubscribe()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$order=$post["order"];
		$type=$post["type"];

		$last_edit_time=$post["last_edit_time"];
		$local_time_key=$post["local_time_key"];
		$this->__isEditAuthorityOutput($last_edit_time,$local_time_key);

		$models=$this->__getUseModels($type);
		$history_model=$models["history_model"];
		$master_model =$models["master_model"];

		try{
		
			$this->__savePrice($order,$history_model,$master_model);

		}catch(Exception $e){
		
			$res["message"]=$e->getMessage();
			Output::__outputNo($res);
		}

		$res=array();
		$res["data"]=array();
		Output::__outputYes($res);
	}

	function __getUseModels($type)
	{
		switch($type){

		case(K9MasterTobacco::$CATEGORY_TOBACCO):
			$master_model =$this->K9MasterTobacco;
			$history_model=$this->K9DataHistoryPriceTobacco;
			break;
		case(K9MasterRoom::$CATEGORY_ROOM):
			$master_model =$this->K9MasterRoom;
			$history_model=$this->K9DataHistoryPriceRoom;
			break;
		case(K9MasterRoomType::$CATEGORY_ROOMTYPE):
			$master_model =$this->K9MasterRoomType;
			$history_model=$this->K9DataHistoryPriceRoomType;
			break;
		case(K9MasterSpa::$CATEGORY_SPA):
			$master_model =$this->K9MasterSpa;
			$history_model=$this->K9DataHistoryPriceSpa;
			break;
		case(K9MasterRoomservice::$CATEGORY_ROOMSERVICE):
			$master_model =$this->K9MasterRoomservice;
			$history_model=$this->K9DataHistoryPriceRoomservice;
			break;
		case(K9MasterFood::$CATEGORY_FOOD):
			$master_model =$this->K9MasterFood;
			$history_model=$this->K9DataHistoryPriceFood;
			break;
		case(K9MasterBeverage::$CATEGORY_DRINK):
			$master_model =$this->K9MasterBeverage;
			$history_model=$this->K9DataHistoryPriceBeverage;
			break;
		case(K9MasterLimousine::$CATEGORY_LIMOUSINE):
			$master_model =$this->K9MasterLimousine;
			$history_model=$this->K9DataHistoryPriceLimousine;
			break;
		case(K9MasterLaundry::$CATEGORY_LAUNDRY):
			$master_model =$this->K9MasterLaundry;
			$history_model=$this->K9DataHistoryPriceLaundry;
			break;
		case(K9MasterReststay::$CATEGORY_RESTSTAY):
			$master_model =$this->K9MasterReststay;
			$history_model=$this->K9DataHistoryPriceReststay;
			break;
		default:
			exit;
			break;
		}

		$res["master_model"]=$master_model;
		$res["history_model"]=$history_model;
		return $res;
	}

	function __savePrice($order,$history_model,$master_model)
	{
		$target_ids=array_keys($order);
		$latest_prices=$this->__getLatestPrices($history_model,$master_model,$target_ids);

		$today=date("Ymd");
		$price_insert=array();
		$price_update=array();
		$remark_update=array();
		foreach($order as $id=>$values){

			$latest_price  =$latest_prices[$id]["data"]["price"];
			$latest_remarks=$latest_prices[$id]["data"]["remarks"];

			$is_edit=false;
			$is_price_edit =false;
			$is_remark_edit=false;
			if($latest_price!=$values["price"])     $is_price_edit =true;
			if($latest_remarks!=$values["remarks"]) $is_remark_edit=true;

			if(!empty($is_price_edit)){
			
				switch(true){
				
				case($today==date("Ymd",strtotime($latest_prices[$id]["data"]["start"]))):

					$is_edit=true;
					$count=count($price_update);
					$price_update[$count]["id"]   =$latest_prices[$id]["data"]["history_id"];
					$price_update[$count]["price"]=$values["price"];
					$price_update[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
					break;
				default:
					$count=count($price_insert);
					$price_insert[$count]["price"]=$values["price"];
					$price_insert[$count]["start"]=$today;
					$price_insert[$count]["remarks"]=$values["remarks"];
					$price_insert[$count][$history_model->foreignKey]=$id;
					$price_insert[$count]["final_employee_entered"]=$this->Auth->user("employee_id");
					break;
				}
			}

			if($is_edit OR !empty($is_remark_edit)){

				$count=count($remark_update);
				$remark_update[$count]["id"]     =$latest_prices[$id]["data"]["history_id"];
				$remark_update[$count]["remarks"]=Sanitize::escape($values["remarks"]);
			}
		}

	    $datasource=$history_model->getDataSource();
	    $datasource->begin();

		if(!empty($price_update)){
		
			try{

				$history_model->multiInsert($price_update);

			}catch(Exception $e){

				$datasource->rollback();
				throw new Exception($e->getMessage());
			}
		}

		if(!empty($price_insert)){
		
			try{

				$history_model->multiInsert($price_insert);

			}catch(Exception $e){
			
				$datasource->rollback();
				throw new Exception($e->getMessage());
			}
		}

		if(!empty($remark_update)){
		
			try{

				$history_model->multiInsert($remark_update);

			}catch(Exception $e){
			
				$datasource->rollback();
				throw new Exception($e->getMessage());
			}
		}

		$datasource->commit();
		return true;
	}

	public function __getMasterDataByTypeWithMenus($type)
	{
		$res=$this->__getMasterDataByType($type);
		$categories=$this->__getMasterCategory($type);

		$lang=Configure::read('Config.language');
		$category_name=$this->K9MasterCategory->hasField("name_{$lang}")?"name_{$lang}":"name";
		$category_names=Set::combine($categories,"{n}.K9MasterCategory.aliase","{n}.K9MasterCategory.{$category_name}");
		$res["data"]["list"]["menus"]=$category_names;
		return $res;
	}

	function __getMasterDataByType($type){

		switch($type){
		case(K9MasterTobacco::$CATEGORY_TOBACCO);
			$res=$this->__getMasterTobacco();
			break;
		case(K9MasterFood::$CATEGORY_FOOD);
			$res=$this->__getMasterFood();
			break;
		case(K9MasterBeverage::$CATEGORY_DRINK);
			$res=$this->__getMasterDrink();
			break;
		case(K9MasterSpa::$CATEGORY_SPA);
			$res=$this->__getMasterSpa();
			break;
		case(K9MasterRoomservice::$CATEGORY_ROOMSERVICE);
			$res=$this->__getMasterRoomservice();
			break;
		case(K9MasterLimousine::$CATEGORY_LIMOUSINE);
			$res=$this->__getMasterLimousine();
			break;
		case(K9MasterLaundry::$CATEGORY_LAUNDRY);
			$res=$this->__getMasterLaundry();
			break;
		case(K9MasterRoom::$CATEGORY_ROOM);
			$res=$this->__getMasterRoom();
			break;
		case(K9MasterRoomType::$CATEGORY_ROOMTYPE);
			$res=$this->__getMasterRoomType();
			break;
		case(K9MasterReststay::$CATEGORY_RESTSTAY);
			$res=$this->__getMasterReststay();
			break;
		default:
			exit;
			break;
		}

		return $res;
	}

	function __getMasterTobacco(){

		$params=array();
		$params["order"]=array("{$this->K9MasterTobacco->name}.item_num ASC");
		$data=$this->__getAllData($this->K9MasterTobacco,$params);
		$data=$this->__getMasterData($data,$this->K9MasterTobacco,$this->K9DataHistoryPriceTobacco);
		return $data;
	}

	function __getMasterRoomservice(){

		$params=array();
		$params["order"]=array("{$this->K9MasterRoomservice->name}.item_num ASC");
		$data=$this->__getAllData($this->K9MasterRoomservice,$params);
		$data=$this->__getMasterData($data,$this->K9MasterRoomservice,$this->K9DataHistoryPriceRoomservice);
		return $data;
	}

	function __getMasterSpa(){

		$params=array();
		$params["order"]=array("{$this->K9MasterSpa->name}.item_num ASC");
		$data=$this->__getAllData($this->K9MasterSpa,$params);
		$data=$this->__getMasterData($data,$this->K9MasterSpa,$this->K9DataHistoryPriceSpa);
		return $data;
	}

	function __getMasterFood(){

		$params=array();
		$params["order"]=array("{$this->K9MasterFood->name}.item_num ASC");
		$data=$this->__getAllData($this->K9MasterFood,$params);
		$data=$this->__getMasterData($data,$this->K9MasterFood,$this->K9DataHistoryPriceFood);
		return $data;
	}

	function __getMasterDrink(){

		$params=array();
		$params["order"]=array("{$this->K9MasterBeverage->name}.item_num ASC");
		$data=$this->__getAllData($this->K9MasterBeverage,$params);
		$data=$this->__getMasterData($data,$this->K9MasterBeverage,$this->K9DataHistoryPriceBeverage);
		return $data;
	}

	function __getMasterLaundry(){

		$params=array();
		$params["order"]=array("{$this->K9MasterLaundry->name}.item_num ASC");
		$data=$this->__getAllData($this->K9MasterLaundry,$params);
		$data=$this->__getMasterData($data,$this->K9MasterLaundry,$this->K9DataHistoryPriceLaundry);
		return $data;
	}

	function __getMasterLimousine(){

		$params=array();
		$params["order"]=array("{$this->K9MasterLimousine->name}.item_num ASC");
		$data=$this->__getAllData($this->K9MasterLimousine,$params);
		$data=$this->__getMasterData($data,$this->K9MasterLimousine,$this->K9DataHistoryPriceLimousine);
		return $data;
	}

	function __getMasterReststay(){

		$data=$this->__getAllData($this->K9MasterReststay);
		$data=$this->__getMasterData($data,$this->K9MasterReststay,$this->K9DataHistoryPriceReststay);
		return $data;
	}

	function __getMasterRoom(){

		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation")));
		$this->K9MasterRoomType->unbindModel(array("belongsTo"=>array("K9DataHistoryPriceRoomType","K9MasterCategory")));

		$this->K9MasterRoomType->unbindModel(array("hasMany"=>array("K9MasterRoom")));
		$data=$this->__getAllData($this->K9MasterRoom,array( "recursive"=>2 ));
		$data=$this->__getMasterData($data,$this->K9MasterRoom,$this->K9DataHistoryPriceRoom);

		$data["data"]["list"]["list"]["menu_types"]     =$this->__getRoomTypes();
		$data["data"]["list"]["list"]["residence_types"]=$this->__getResidenceTypes();
		return $data;
	}

	function __getMasterRoomType(){

		$this->K9MasterRoomType->unbindModel(array("belongsTo"=>array("K9DataHistoryPriceRoomType")));
		$data=$this->__getAllData($this->K9MasterRoomType);
		$data=$this->__getMasterData($data,$this->K9MasterRoomType,$this->K9DataHistoryPriceRoomType);
		return $data;
	}

	public function __getMasterData($data,Model $master_model,Model $history_model)
	{

		$target_ids=Set::extract($data,"{}.{$master_model->name}.id");
		$latest_prices=$this->__getLatestPrices($history_model,$master_model,$target_ids);

		$method="__unitingData{$master_model->name}";

		switch(method_exists($this,$method)){
		
		case(true):

			$unit_data=$this->$method($master_model,$data);
			break;
		default:
			$unit_data=$this->__unitingData($master_model,$data);
			break;
		}

		$res=array();
		$res["data"]["list"]["list"]=$unit_data;
		$res["data"]["price"]["data"]=$latest_prices;
		return $res;
	}

	function __unitingDataK9MasterRoomType(Model $master_model,$data=array()){

		$list=array();
		$lang=Configure::read('Config.language');
		$name=$master_model->hasField("name_{$lang}")?"name_{$lang}":"name";
		foreach($data as $k=>$v){

			$id=$v[$master_model->name]["id"];
			$category=$v["K9MasterCategory"];
			$employee=$v["K9MasterEmployee"];
			$aliase=$category["aliase"];
			if(!isset($list[$aliase])) $list[$aliase]=array();

			$info=$v[$master_model->name];

			$count=count($list[$aliase]);
			$list[$aliase][$count]["id"]     =$id;
			$list[$aliase][$count]["name"]   =$info[$name];
			$list[$aliase][$count]["remarks"]=escapeJsonString($v[$master_model->name]["remarks"]);
			$list[$aliase][$count]["employee"]["name"]=escapeJsonString($employee["first_name"]);
		}

		return $list;
	}

	function __unitingDataK9MasterRoom(Model $master_model,$data=array()){

		$list=array();
		$lang=Configure::read('Config.language');
		$roomtype_name=$master_model->hasField("name_{$lang}")?"name_{$lang}":"name";
		foreach($data as $k=>$v){

			$id=$v[$master_model->name]["id"];
			$category=$v["K9MasterCategory"];
			$employee=$v["K9MasterEmployee"];
			$aliase=$category["aliase"];
			if(!isset($list[$aliase])) $list[$aliase]=array();

			$room_type=$v["K9MasterRoomType"];
			$info=$v[$master_model->name];
			$name=__("部屋番号")." {$info["room_num"]}({$room_type[$roomtype_name]})";

			$count=count($list[$aliase]);
			$list[$aliase][$count]["id"]     =$id;
			$list[$aliase][$count]["name"]   =escapeJsonString($name);
			$list[$aliase][$count]["room_num"]=$v["K9MasterRoom"]["room_num"];
			$list[$aliase][$count]["room_type_id"]=$room_type["id"];
			$list[$aliase][$count]["type"]    =$v["K9MasterRoom"]["type"];
			$list[$aliase][$count]["floor"]   =$v["K9MasterRoom"]["floor"];
			$list[$aliase][$count]["remarks"] =escapeJsonString($v[$master_model->name]["remarks"]);
			$list[$aliase][$count]["employee"]["name"]=escapeJsonString($employee["first_name"]);
		}

		return $list;
	}

	private function __getResidenceTypes()
	{
		$tsv=getTSVResidence();

		$list=array();
		$list[K9MasterRoom::$HOTEL]=__($tsv[K9MasterRoom::$HOTEL]);
		$list[K9MasterRoom::$APART]=__($tsv[K9MasterRoom::$APART]);
		return $list;
	}

	private function __getRoomTypes()
	{
		$conditions=array();
		$conditions["and"]["K9MasterRoomType.del_flg"]=0;
		$this->K9MasterRoomType->unbindFully();
		$data=$this->K9MasterRoomType->find("all",array(
		
			"conditions"=>$conditions
		));

		if(empty($data)) return array();

		$lang=Configure::read('Config.language');
		$name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
		$menu_types=Set::combine($data,"{n}.K9MasterRoomType.id","{n}.K9MasterRoomType.{$name}");
		return $menu_types;
	}

	function __unitingDataK9MasterReststay(Model $master_model,$data=array()){

		$list=array();

		$lang=Configure::read('Config.language');
		foreach($data as $k=>$v){

			$id=$v[$master_model->name]["id"];
			$category=$v["K9MasterCategory"];
			$employee=$v["K9MasterEmployee"];
			$aliase=$category["aliase"];
			if(!isset($list[$aliase])) $list[$aliase]=array();

			$count=count($list[$aliase]);
			$list[$aliase][$count]["id"]     =$id;
			$list[$aliase][$count]["name"]   =isset($v[$master_model->name]["name_{$lang}"])?$v[$master_model->name]["name_{$lang}"]:$v[$master_model->name]["name"];
			$list[$aliase][$count]["name"]   =escapeJsonString($list[$aliase][$count]["name"]);
			$list[$aliase][$count]["remarks"]=escapeJsonString($v[$master_model->name]["remarks"]);
			$list[$aliase][$count]["employee"]["name"]=escapeJsonString($employee["first_name"]);
		}

		return $list;
	}

	function __unitingData(Model $master_model,$data=array()){

		$list=array();

		$lang=Configure::read('Config.language');

		foreach($data as $k=>$v){

			$id=$v[$master_model->name]["id"];
			$category=$v["K9MasterCategory"];
			$employee=$v["K9MasterEmployee"];
			$aliase=$category["aliase"];
			if(!isset($list[$aliase])) $list[$aliase]=array();

			$count=count($list[$aliase]);
			$list[$aliase][$count]["id"]      =$id;
			$list[$aliase][$count]["name"]    =isset($v[$master_model->name]["name_{$lang}"])?$v[$master_model->name]["name_{$lang}"]:$v[$master_model->name]["name"];
			$list[$aliase][$count]["remarks"] =$v[$master_model->name]["remarks"];
			$list[$aliase][$count]["item_num"]=$v[$master_model->name]["item_num"];
			$list[$aliase][$count]["employee"]["name"]=escapeJsonString($employee["first_name"]);
		}

		return $list;
	}

	function __getAllData(Model $master_model,$params=array())
	{

		if(!$this->K9MasterEmployee) $this->loadModel("K9MasterEmployee");

		$belongsTo=&$master_model->belongsTo;
		$belongsTo[$this->K9MasterEmployee->name]["className"] =$this->K9MasterEmployee->name;
		$belongsTo[$this->K9MasterEmployee->name]["foreignKey"]="final_employee_entered";
		$params["conditions"]["and"]["{$master_model->name}.del_flg"]=0;
		$data=$master_model->find("all",$params);
		return $data;
	}

	function __getLatestPrices(Model $history_model,Model $master_model,$target_ids=array())
	{

		$conditions["and"]["{$history_model->name}.{$history_model->foreignKey}"]=$target_ids;
		$history_data=$history_model->find("all",array(
		
			"conditions"=>$conditions,
			"order"=>array("{$history_model->name}.start DESC")
		));

		$latest_prices=array();
		foreach($history_data as $k=>$v){

			$_v=$v[$history_model->name];
			$employee=$v["K9MasterEmployee"];
			$target_id=$_v[$history_model->foreignKey];
			if(1>count($target_ids)) break;
			if(!in_array($target_id,$target_ids)) continue;

			$index=array_search($target_id,$target_ids);
			unset($target_ids[$index]);

			$latest_prices[$target_id]["data"]["id"]=$target_id;
			$latest_prices[$target_id]["data"]["price"]=$_v["price"];
			$latest_prices[$target_id]["data"]["start"]=$_v["start"];
			$latest_prices[$target_id]["data"]["history_id"]=$_v["id"];
			$latest_prices[$target_id]["data"]["remarks"]   =$_v["remarks"];
			$latest_prices[$target_id]["master"]["id"]=$v[$master_model->name]["id"];
			$latest_prices[$target_id]["master"]["remarks"]=$v[$master_model->name]["remarks"];
			$latest_prices[$target_id]["employee"]["id"]=$employee["id"];
			$latest_prices[$target_id]["employee"]["name"]=$employee["first_name"];
		}

		return $latest_prices;
	}

}
