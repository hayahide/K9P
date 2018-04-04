<?php

App::uses('K9BasePricesController','Controller');
App::uses('K9PriceController','Controller');
App::uses('K9RestPriceController','Controller');
App::uses('K9SiteController','Controller');

class AdK9BaseDailyReportController extends AppController{

    var $uses = [

		"K9MasterCard",
		"K9DataFund",
		"K9MasterEmployee",
		"K9DataSchedule",
		"K9DataOutputPayment",
		"K9DataEmployeeAttendance",
		"K9DataReststaySchedule",
		"K9DataReservation",
		"K9DataHistoryPriceSpa",
		"K9DataOrderSpa",
		"K9DataHistoryPriceCard",
		"K9MasterSpa",
		"K9MasterLimousine",
		"K9DataOrderLimousine",
		"K9DataHistoryPriceLimousine",
		"K9MasterLaundry",
		"K9DataOrderLaundry",
		"K9DataHistoryPriceLaundry",
		"K9MasterFood",
		"K9DataOrderFood",
		"K9DataHistoryPriceFood",
    	"K9MasterBeverage",
		"K9DataOrderBeverage",
		"K9DataHistoryPriceBeverage",
    	"K9MasterTobacco",
		"K9DataOrderTobacco",
		"K9DataHistoryPriceTobacco",
    	"K9MasterRoomservice",
		"K9DataOrderRoomservice",
		"K9DataHistoryPriceRoomservice",
		"K9DataCheckoutPayment",
		"K9DataExtraOrder",
		"K9DataExtraRoomserviceOrder",
		"K9DataExtraFoodOrder",
		"K9DataExtraBeverageOrder",
		"K9DataExtraTobaccoOrder",
	];

	var $models=array();

	function beforeFilter(){
	
		parent::beforeFilter();
	}

	protected function __init()
	{
		$this->__setOrderModels();
		$this->__excelInit();
	}

    protected function __getSheet()
    {
        return $this->book->getActiveSheet($this->sheet);
    }

	protected function __excelInit(){

		$lang=Configure::read('Config.language');
		$path=MASTER_DATA."k9_report_{$lang}.xls";
		$this->__getIntitalBool($path,$book,$lang);
		$this->book=$book;
	}

	protected function __getIntitalBool($path,&$book,$lang)
	{
		$book=PHPExcel_IOFactory::load($path);
		$sheet=$book->setActiveSheetIndex(($lang=="jpn"?0:0));
		$sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$sheet->getPageSetup()->setHorizontalCentered(false);
		$sheet->getPageSetup()->setVerticalCentered(false);
	}

	protected function __getReststayPrice($params=array()){

		$controller=new K9RestPriceController();
		$res=$controller->__getReststayPrice($params);
		return $res;
	}

	protected function __getPrice($reserve_ids=array(),$params=array()){

		$controller=new K9PriceController();
		$res=$controller->__getPrice($reserve_ids,$params);
		return $res;
	}

	protected function __getPainByYmd($data,$check_ymd){

		$controller=new K9SiteController();
		$res=$controller->__getPainByYmd($data,$check_ymd);
		return $res;
	}

	protected function __getSchedulePlans($reserve_ids=array()){

		$controller=new K9SiteController();
		$res=$controller->__getSchedulePlans($reserve_ids);
		return $res;
	}

	protected function __getPriceParYmd($ymd,$price_info,$params=array()){
	
		$controller=new K9SiteController();
		$res=$controller->__getPriceParYmd($ymd,$price_info,$params);
		return $res;
	}

	protected function __getRestPriceParYmd($ymd,$price_info,$params=array()){

		$controller=new K9SiteController();
		$res=$controller->__getRestPriceParYmd($ymd,$price_info,$params);
		return $res;
	}

	protected function __getPositionByCell($cell){

		$res=preg_match("#([0-9]+)#",$cell,$match);
		if(empty($res)) throw new Exception("Wrong Cell Value.");
		return $match[1];
	}

	protected function __setAlign($style,$position){

		switch($position){
		case("right"):
			$align=PHPExcel_Style_Alignment::HORIZONTAL_RIGHT;
			break;
		case("center"):
			$align=PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
			break;
		default:
			$align=PHPExcel_Style_Alignment::HORIZONTAL_LEFT;
			break;
		}

		$style->getAlignment()->setHorizontal($align);
	}

	protected function __setOrderModels()
	{
		$this->loadModel("K9MasterSpa");
		$this->loadModel("K9MasterLimousine");
		$this->loadModel("K9MasterLaundry");
		$this->loadModel("K9MasterFood");
		$this->loadModel("K9MasterBeverage");
		$this->loadModel("K9MasterTobacco");
		$this->loadModel("K9MasterRoomservice");
		$this->loadModel("K9MasterRoom");
		$this->loadModel("K9MasterRoomType");
		$this->loadModel("K9MasterReststay");

		$this->models[K9MasterSpa::$CATEGORY_SPA]["master"] =$this->K9MasterSpa;
		$this->models[K9MasterSpa::$CATEGORY_SPA]["history"]=$this->K9DataHistoryPriceSpa;
		$this->models[K9MasterSpa::$CATEGORY_SPA]["order"]  =$this->K9DataOrderSpa;
		$this->models[K9MasterLimousine::$CATEGORY_LIMOUSINE]["master"] =$this->K9MasterLimousine;
		$this->models[K9MasterLimousine::$CATEGORY_LIMOUSINE]["history"]=$this->K9DataHistoryPriceLimousine;
		$this->models[K9MasterLimousine::$CATEGORY_LIMOUSINE]["order"]  =$this->K9DataOrderLimousine;
		$this->models[K9MasterLaundry::$CATEGORY_LAUNDRY]["master"] =$this->K9MasterLaundry;
		$this->models[K9MasterLaundry::$CATEGORY_LAUNDRY]["history"]=$this->K9DataHistoryPriceLaundry;
		$this->models[K9MasterLaundry::$CATEGORY_LAUNDRY]["order"]  =$this->K9DataOrderLaundry;
		$this->models[K9MasterFood::$CATEGORY_FOOD]["master"]     =$this->K9MasterFood;
		$this->models[K9MasterFood::$CATEGORY_FOOD]["history"]    =$this->K9DataHistoryPriceFood;
		$this->models[K9MasterFood::$CATEGORY_FOOD]["order"]      =$this->K9DataOrderFood;
		$this->models[K9MasterFood::$CATEGORY_FOOD]["extra_order"]=$this->K9DataExtraFoodOrder;
		$this->models[K9MasterBeverage::$CATEGORY_DRINK]["master"]     =$this->K9MasterBeverage;
		$this->models[K9MasterBeverage::$CATEGORY_DRINK]["history"]    =$this->K9DataHistoryPriceBeverage;
		$this->models[K9MasterBeverage::$CATEGORY_DRINK]["order"]      =$this->K9DataOrderBeverage;
		$this->models[K9MasterBeverage::$CATEGORY_DRINK]["extra_order"]=$this->K9DataExtraBeverageOrder;
		$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["master"] =$this->K9MasterTobacco;
		$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["history"]=$this->K9DataHistoryPriceTobacco;
		$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["order"]  =$this->K9DataOrderTobacco;
		$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["extra_order"]=$this->K9DataExtraTobaccoOrder;
		$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["master"]     =$this->K9MasterRoomservice;
		$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["history"]    =$this->K9DataHistoryPriceRoomservice;
		$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["order"]      =$this->K9DataOrderRoomservice;
		$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["extra_order"]=$this->K9DataExtraRoomserviceOrder;
	}

	protected function __cardHistoryWithinaDay($data,$day)
	{
		if(empty($data)) return 0;

		$history=array_shift($data);
		$ymd=date("Ymd",strtotime($history["enter_date"]));
		if($day>=$ymd) return $history["value"];
		return $this->__cardHistoryWithinaDay($data,$day);
	}


}//END class

?>
