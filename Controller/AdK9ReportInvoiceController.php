<?php

App::uses('K9BaseDailyReportController','Controller');
class AdK9ReportInvoiceController extends K9BaseDailyReportController{

	var $name = "K9ReportInvoice";
	var $uses=array(
	
		"K9DataExtraOrder",
		"K9DataExtraFoodOrder",
		"K9DataExtraBeverageOrder",
		"K9DataExtraRoomserviceOrder",
		"K9DataExtraTobaccoOrder",
		"K9DataHistoryPriceFood",
		"K9DataHistoryPriceBeverage",
		"K9DataHistoryPriceTobacco",
		"K9DataHistoryPriceRoomservice",
	);

	function beforeFilter(){
	
		parent::beforeFilter();

		ini_set("memory_limit",-1);
		set_time_limit(0);

		$this->__excelInit();
		$this->__useModels();
		$this->__setOrderModels();
	}

	public function report()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		
		$post=$this->data;
		$order_id=isset($post["order_id"])?$post["order_id"]:false;
		//$order_id=1;
		if(empty($order_id)) exit;

		$this->__invoiceNum($order_id);
		$this->__issueDay();

		$data=$this->__getOrderInformations($order_id);
		$res=$this->__putData($data);
		$res=$this->__setSum(array( "start_position"=>$res["start_position"],"end_position"=>$res["end_position"]));

		$day=date("Ymd",strtotime($data["main"]["target_date"]));
		$res=$this->__setTax($day,array( "start_position"=>$res["end_position"] ));

		$end_position=$res["end_position"];
		$company=$this->__getCompany();
		$end_position=$this->__setCompany($end_position+3,$company[K9DataMetaValue::$INVOICE_COMPANYNAME]);
		$end_position=$this->__setCompanyAddress($end_position+2,$company[K9DataMetaValue::$INVOICE_COMPANYADRESS]);
		$end_position=$this->__setCompanyPhone($end_position+3,$company[K9DataMetaValue::$INVOICE_COMPANYPHONE]);

		$this->__setLogo($end_position);

        $file=CLIENT."_invoice_{$day}.xlsx";
        $writer=PHPExcel_IOFactory::createWriter($this->book,'Excel2007');
        $dir=WWW_ROOT."excel_tmp".DS;
        if(!is_dir($dir)) mkdir($dir,0777);
        $path=$dir.$file;
        $writer->save($path);

		$res=array();
		$res["data"]["file"]=pathinfo($path)["basename"];
		$res["data"]["url"] =ROOT_DOMAIN.DS."webroot".DS."excel_tmp".DS.$res["data"]["file"];
		Output::__outputYes($res);
	}

	private function __setCompanyPhone($position,$data)
	{
		$this->__putDataForSheet($data,array(
		
			"position"=>$position,
			"alpha"   =>"D",
			"align"   =>"left"
		));

		return $position;
	}
	
	private function __setCompanyAddress($position,$data)
	{
		$this->__putDataForSheet($data,array(
		
			"position"=>$position,
			"alpha"   =>"D",
			"align"   =>"left"
		));

		return $position;
	}

	private function __setCompany($position,$data)
	{

		$this->__putDataForSheet($data,array(
		
			"position"=>$position,
			"alpha"   =>"D",
			"align"   =>"left"
		));

		return $position;
	}

	private function __invoiceNum($order_id)
	{
		$time=date("YmdHis");
		$invoice_num="k9{$order_id}_{$time}";
		$invoice_num=$this->K9DataExtraOrder->saveInvoiceNum($order_id);
		if(empty($invoice_num)) return false;

		$position=4;
		$this->__putDataForSheet($invoice_num,array(
		
			"position"=>$position,
			"alpha"   =>"H",
			"align"   =>"center"
		));

		return true;
	}

	private function __issueDay()
	{
		$position=5;
		$today=strtotime(date("Ymd"));
		$date=localDateNormalUtime($today);
		$this->__putDataForSheet($date,array(
		
			"position"=>$position,
			"alpha"   =>"H",
			"align"   =>"center"
		));

		return true;
	}

	private function __setTax($day,$data)
	{
		$position=$data["start_position"]+1;
		$tax=$this->__getTaxvalueWithRange($day);
		$this->__putDataForSheet($tax,array(
		
			"position"=>$position,
			"alpha"   =>"G",
			"align"   =>"right"
		));

		$res=array();
		$res["end_position"]=$position;
		return $res;
	}

	private function __setSum($data)
	{
		$position=$data["end_position"]+2;
		$formula="=SUM(G{$data["start_position"]}:G{$data["end_position"]})";
		$this->__putDataForSheet($formula,array(
		
			"position"=>$position,
			"alpha"   =>"G",
			"align"   =>"right"
		));

		$res=array();
		$res["end_position"]=$position;
		return $res;
	}

	private function __putData($data)
	{
	
		$start_position=9;
		$main  =$data["main"];
		$values=$data["data"];
		$end_position=$this->__putEachTypeData($main,$values,$start_position);

		$res=array();
		$res["start_position"]=$start_position;
		$res["end_position"]  =$end_position;
		return $res;
	}

	private function __putEachTypeData($main,$values=array(),$position)
	{
		if(empty($values)){

			return $position;
		}

		$lang=Configure::read('Config.language');
		foreach($values as $type=>$value) break;
		unset($values[$type]);

		$history_model=$this->models[$type]["history"];
		$master_model =$this->models[$type]["master"];
		$master_name  =$master_model->hasField("name_{$lang}")?"name_{$lang}":"name";

		foreach($value as $k=>$v){

			$count     =$v["count"];
			$item_title=preg_replace("#\(.*\)|【.*】#","",$v[$master_model->name][$master_name]);
			$item_title=trim($item_title);
			$price     =$v[$history_model->name]["price"];
			$remarks   =$v["remarks"];

			$this->__putDataForSheet($item_title,array(
			
				"position"=>$position,
				"alpha"   =>"B",
				"align"   =>"left"
			));

			$this->__putDataForSheet($count,array(
			
				"position"=>$position,
				"alpha"   =>"D",
				"align"   =>"right"
			));

			$this->__putDataForSheet($price,array(
			
				"position"=>$position,
				"alpha"   =>"F",
				"align"   =>"right"
			));

			$this->__putDataForSheet($remarks,array(
			
				"position"=>$position,
				"alpha"   =>"H",
				"align"   =>"left"
			));

			$this->__addRow($position);
			$position++;
		}

		return $this->__putEachTypeData($main,$values,$position);
	}

	private function __addRow($position)
	{
		$sheet=$this->book->getActiveSheet();
		$next_position=$position+1;
		$sheet->insertNewRowBefore($next_position,1);

		$cells=["B:C","D","F","G","H"];
		for($i=0;$i<count($cells);$i++){

			$cell=$cells[$i];
			$split_cell=explode(":",$cell);
			$first_alpha=$split_cell[0];
			$comp_cell="{$first_alpha}{$next_position}";
			$sheet->duplicateStyle($sheet->getStyle("{$first_alpha}{$position}"),$comp_cell);
			if(count($split_cell)>1) $sheet->mergeCells("{$split_cell[0]}{$next_position}:{$split_cell[1]}{$next_position}");
			$formula="=IF(F{$next_position}=\"\",\"\",D{$next_position}*F{$next_position})";
			$this->__putDataForSheet($formula,array(
			
				"position"=>$next_position,
				"alpha"   =>"G",
				"align"   =>"right"
			));
		}
	}

	private function __getOrderInformations($order_id)
	{
		$conditions=array();
		$conditions["and"]["K9DataExtraOrder.id"]=$order_id;

		$this->models[K9MasterFood::$CATEGORY_FOOD]["order"]->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder","K9MasterCard")));
		$this->models[K9MasterBeverage::$CATEGORY_DRINK]["order"]->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder","K9MasterCard")));
		$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["order"]->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder","K9MasterCard")));
		$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["order"]->unbindModel(array("belongsTo"=>array("K9MasterEmployee","K9DataExtraOrder","K9MasterCard")));
		$data=$this->K9DataExtraOrder->find("first",array( "conditions"=>$conditions,"recursive"=>2 ));
		if(empty($data)) return false;

		$res=array();
		$res["main"]=$data["K9DataExtraOrder"];
		$res["data"][K9MasterFood::$CATEGORY_FOOD]              =$data[$this->K9DataExtraFoodOrder->name];
		$res["data"][K9MasterBeverage::$CATEGORY_DRINK]         =$data[$this->K9DataExtraBeverageOrder->name];
		$res["data"][K9MasterTobacco::$CATEGORY_TOBACCO]        =$data[$this->K9DataExtraTobaccoOrder->name];
		$res["data"][K9MasterRoomservice::$CATEGORY_ROOMSERVICE]=$data[$this->K9DataExtraRoomserviceOrder->name];
		return $res;
	}

	private function __useModels()
	{
		$this->loadModel("K9MasterFood");
		$this->loadModel("K9MasterBeverage");
		$this->loadModel("K9MasterTobacco");
		$this->loadModel("K9MasterRoomservice");
		$this->loadModel("K9DataMetaValue");
	}

	protected function __excelInit(){

		$lang=Configure::read('Config.language');
		$path=MASTER_DATA."k9_invoice_other_{$lang}.xlsx";
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

	protected function __setOrderModels()
	{
		$this->models[K9MasterFood::$CATEGORY_FOOD]["master"]     =$this->K9MasterFood;
		$this->models[K9MasterFood::$CATEGORY_FOOD]["history"]    =$this->K9DataHistoryPriceFood;
		$this->models[K9MasterFood::$CATEGORY_FOOD]["order"]=$this->K9DataExtraFoodOrder;
		$this->models[K9MasterBeverage::$CATEGORY_DRINK]["master"]     =$this->K9MasterBeverage;
		$this->models[K9MasterBeverage::$CATEGORY_DRINK]["history"]    =$this->K9DataHistoryPriceBeverage;
		$this->models[K9MasterBeverage::$CATEGORY_DRINK]["order"]=$this->K9DataExtraBeverageOrder;
		$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["master"] =$this->K9MasterTobacco;
		$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["history"]=$this->K9DataHistoryPriceTobacco;
		$this->models[K9MasterTobacco::$CATEGORY_TOBACCO]["order"]=$this->K9DataExtraTobaccoOrder;
		$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["master"]     =$this->K9MasterRoomservice;
		$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["history"]    =$this->K9DataHistoryPriceRoomservice;
		$this->models[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]["order"]=$this->K9DataExtraRoomserviceOrder;
	}

	private function __putDataForSheet($value,$params=array())
	{

		$position=$params["position"];
		$alpha   =$params["alpha"];
		$align   =isset($params["align"])?$params["align"]:"left";
		$cell    ="{$alpha}{$position}";

		$sheet=$this->__getSheet();
		$sheet->setCellValue($cell,$value);
		$this->__setAlign($sheet->getStyle($cell),$align);
	}

    protected function __getSheet()
    {
        return $this->book->getActiveSheet($this->sheet);
    }

	private function __getTaxvalueWithRange($day)
	{
		App::uses('K9SettingTaxController','Controller');
		$controller=new K9SettingTaxController();
		return $controller->__getTaxvalueWithRange($day);
	}

	private function __getCompany()
	{
		$values=array();
		$values[]=K9DataMetaValue::$INVOICE_COMPANYNAME;
		$values[]=K9DataMetaValue::$INVOICE_COMPANYPHONE;
		$values[]=K9DataMetaValue::$INVOICE_COMPANYADRESS;
		$conditions["and"]["K9DataMetaValue.meta_key"]=$values;
		$res=$this->K9DataMetaValue->find("all",array( "conditions"=>$conditions ));
		$list=Set::combine($res,"{n}.K9DataMetaValue.meta_key","{n}.K9DataMetaValue.meta_value");
		return $list;
	}

	private function __setLogo($end_position)
	{
		$image_cell="C".($end_position+4);
		$objDrawing=new PHPExcel_Worksheet_Drawing();
		$objDrawing->setPath(MASTER_DATA."k9_invoice_log.png");
		$objDrawing->setCoordinates($image_cell);
		$objDrawing->setWorksheet($this->__getSheet());
		return $image_cell;
	}

}//END class

?>
