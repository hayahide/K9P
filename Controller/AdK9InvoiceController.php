<?php

require_once(dirname(ROOT).DS."vendor".DS."autoload.php");
App::uses("K9PriceController","Controller");
App::uses("K9RestPriceController","Controller");
App::uses("K9BasePricesController","Controller");
App::uses("K9SiteController","Controller");
App::uses("K9SettingTaxController","Controller");

class AdK9InvoiceController extends AppController {

	var $name = "K9Invoice";
	var $uses = [

		"K9DataPriceParRoom",
		"K9DataPriceRoomType",
		"K9MasterRoomType",
		"K9DataReservation",
		"K9DataSchedulePlan",
		"K9MasterRoom",
		"K9DataHistoryRateTax",
		"K9DataGuest",
		"K9DataHistoryPriceRoomType",
		"K9DataHistoryPriceRoom",
		"K9DataOrderBeverage",
		"K9DataOrderFood",
		"K9MasterFood",
		"K9MasterBeverage",
		"K9DataHistoryPriceFood",
		"K9DataHistoryPriceBeverage",
		"K9MasterSpa",
		"K9DataHistoryPriceSpa",
		"K9DataHistoryPriceTobacco",
		"K9DataOrderSpa",
		"K9MasterLaundry",
		"K9DataHistoryPriceLaundry",
		"K9DataOrderLaundry",
		"K9MasterLimousine",
		"K9MasterTobacco",
		"K9DataHistoryPriceLimousine",
		"K9DataOrderLimousine",
		"K9DataOrderTobacco",
		"K9DataSchedule",
		"K9DataReststaySchedule",
		"K9MasterRoomservice",
		"K9DataHistoryPriceRoomservice",
		"K9DataOrderRoomservice",
		"K9DataCredit",
		"K9DataCompany",
		"K9MasterReservationSalesource"
	];

	var $startCell="A2";
	var $sheet=0;

	function beforeFilter(){
	
		parent::beforeFilter();

		$this->__init();
		$this->__useModel();
		$this->loadOrderMasterModels();
	}

	function __init(){

		Configure::write('Config.language','eng');
	    $this->book=PHPExcel_IOFactory::load(MASTER_DATA."k9_invoice.xlsx");
        $this->book->setActiveSheetIndex(0);

		$object=$this->__getSheet();
		$style=&$object;
		$style->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$style->getPageSetup()->setHorizontalCentered(false);
		$style->getPageSetup()->setVerticalCentered(false);
	}

	public function __useModel()
	{
		$this->loadModel("K9DataMetaValue");
	}

	function invoice(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$hash=$post["reservation_hash"];
		$data=$this->__getReservationByHash($hash);
		if(0>strtotime($data["K9DataReservation"]["checkin_time"])){
		
			$res["message"]=__("チェックインされていません");
			Output::__outputNo($res);
		}

		$staytype=$data["K9DataReservation"]["staytype"];
		$schedule_model=$this->__stayTypeModel($staytype);
		$reservation=$data["K9DataReservation"];
		$guest      =$data["K9DataGuest"];
		$agency     =$data["K9DataCompany"];
		$schedules  =$data[$schedule_model->name];
		$schedule_plans=$data["K9DataSchedulePlan"];

		$credit_price=$this->__creditPriceWithScheduleId($reservation["id"],$schedules);

		// recodes for invoice.
		/*==================================================================*/
		$invoice_recodes=$this->__invoiceRecords(array(
		
			"schedules"     =>$schedules,
			"schedule_plans"=>$schedule_plans,
			"reservation"   =>$reservation,
			"credit"        =>$credit_price
		));

		$reserve_id=$reservation["id"];

		$invoice_roomservice_recodes=$this->__invoiceOtherRecords(

			$this->K9MasterRoomservice,
			$this->K9DataHistoryPriceRoomservice,
			$this->K9DataOrderRoomservice,
			$reserve_id);

		$invoice_tobacco_recodes=$this->__invoiceOtherRecords(

			$this->K9MasterTobacco,
			$this->K9DataHistoryPriceTobacco,
			$this->K9DataOrderTobacco,
			$reserve_id);

		$invoice_drink_recodes=$this->__invoiceOtherRecords(

			$this->K9MasterBeverage,
			$this->K9DataHistoryPriceBeverage,
			$this->K9DataOrderBeverage,
			$reserve_id);

		$invoice_food_recodes=$this->__invoiceOtherRecords(

			$this->K9MasterFood,
			$this->K9DataHistoryPriceFood,
			$this->K9DataOrderFood,
			$reserve_id);

		$invoice_spa_recodes=$this->__invoiceOtherRecords(

			$this->K9MasterSpa,
			$this->K9DataHistoryPriceSpa,
			$this->K9DataOrderSpa,
			$reserve_id);

		$invoice_limousine_recodes=$this->__invoiceOtherRecords(

			$this->K9MasterLimousine,
			$this->K9DataHistoryPriceLimousine,
			$this->K9DataOrderLimousine,
			$reserve_id);

		$invoice_laundry_recodes=$this->__invoiceOtherRecords(

			$this->K9MasterLaundry,
			$this->K9DataHistoryPriceLaundry,
			$this->K9DataOrderLaundry,
			$reserve_id);

		/*==================================================================*/

		// view for header.
		/*==================================================================*/
		$this->__setHeadInformation(array(
		
			"reservation"	=>$reservation,
			"guest"      	=>$guest,
			"schedules"  	=>$schedules,
			"schedule_plans"=>$schedule_plans,
			"agency"        =>$agency, 
			"room_nums"     =>array_unique(Set::extract($invoice_recodes,"{}.room-charge.room.room_num"))
		));
		/*==================================================================*/

		// view for main informations.
		/*==================================================================*/
		$room_res=$this->__setRoomRows($invoice_recodes);
		$values=array_values($room_res["cell"]);
		$next_start_num=$values[count($values)-1]["start_num"]+1;
		$start_charge_cell=$values[0]["charge"];
		$start_credit_cell=$values[0]["credit"];
		$last_room_values=$values[count($values)-1];
		$end_charge_cell  =$last_room_values["charge"];
		$end_credit_cell  =$last_room_values["credit"];
		$last_cell_values=$last_room_values;

		if(!empty($invoice_tobacco_recodes)){

			$tobacco_res=$this->__setRows(K9MasterTobacco::$CATEGORY_TOBACCO,$next_start_num,$invoice_tobacco_recodes);
			$this->__updateLastCells($tobacco_res,$next_start_num,$end_charge_cell,$end_credit_cell,$last_cell_values);
		}

		if(!empty($invoice_food_recodes)){

			$food_res=$this->__setRows(K9MasterFood::$CATEGORY_FOOD,$next_start_num,$invoice_food_recodes);
			$this->__updateLastCells($food_res,$next_start_num,$end_charge_cell,$end_credit_cell,$last_cell_values);
		}

		if(!empty($invoice_drink_recodes)){

			$drink_res=$this->__setRows(K9MasterBeverage::$CATEGORY_DRINK,$next_start_num,$invoice_drink_recodes);
			$this->__updateLastCells($drink_res,$next_start_num,$end_charge_cell,$end_credit_cell,$last_cell_values);
		}

		if(!empty($invoice_roomservice_recodes)){

			$roomservice_res=$this->__setRows(K9MasterRoomservice::$CATEGORY_ROOMSERVICE,$next_start_num,$invoice_roomservice_recodes);
			$this->__updateLastCells($roomservice_res,$next_start_num,$end_charge_cell,$end_credit_cell,$last_cell_values);
		}

		if(!empty($invoice_spa_recodes)){

			$spa_res=$this->__setRows(K9MasterSpa::$CATEGORY_SPA,$next_start_num,$invoice_spa_recodes);
			$this->__updateLastCells($spa_res,$next_start_num,$end_charge_cell,$end_credit_cell,$last_cell_values);
		}

		if(!empty($invoice_limousine_recodes)){

			$limousine_res=$this->__setRows(K9MasterLimousine::$CATEGORY_LIMOUSINE,$next_start_num,$invoice_limousine_recodes);
			$this->__updateLastCells($limousine_res,$next_start_num,$end_charge_cell,$end_credit_cell,$last_cell_values);
		}

		if(!empty($invoice_laundry_recodes)){

			$laundry_res=$this->__setRows(K9MasterLaundry::$CATEGORY_LAUNDRY,$next_start_num,$invoice_laundry_recodes);
			$this->__updateLastCells($laundry_res,$next_start_num,$end_charge_cell,$end_credit_cell,$last_cell_values);
		}

		/*==================================================================*/
		
		//double border.
		/*==================================================================*/
		unset($last_cell_values["start_num"]);
		$border=PHPExcel_Style_Border::BORDER_DOUBLE;
		$this->__setBorderLines(array_values($last_cell_values),$border);
		/*==================================================================*/

		// cost information.
		/*==================================================================*/
		//$values=array_values($room_res["cell"]);
		//$next_start_num=$values[count($values)-1]["start_num"]+2;
		$next_start_num++;
		$tax_date=makeYmdByYmAndD($schedules[0]["start_month_prefix"],$schedules[0]["start_day"]);
		$last_cell=$this->__setPriceValues($next_start_num,$reservation,array(

			//"charge_cells"=>Set::extract($values,"{}.charge"),
			//"credit_cells"=>Set::extract($values,"{}.credit")
			"tax_date"=>$tax_date,
			"charge_cells"=>array($start_charge_cell,$end_charge_cell),
			"credit_cells"=>array($start_credit_cell,$end_credit_cell)
		));
		/*==================================================================*/

		// notification.
		/*==================================================================*/
		$message="I agree that liability for this bill is not waived and agree to be held personally liable in the event that the ndicated person, campany or organisation fails to pay for any part or the full amount of the above charges.";

		$last_cell=$this->__bottomNotification($last_cell,$message);
		/*==================================================================*/

		// issue date.
		/*==================================================================*/
		$last_cell=$this->__issueDate($last_cell);
		/*==================================================================*/

		$last_cell=$this->__setInvoiceCompany($last_cell);

		$this->__setBottomSignatureCustomer($last_cell);
		$last_cell=$this->__setBottomSignatureCashier($last_cell);

		/*==================================================================*/
		$last_cell=$this->__setLogo($last_cell);
		/*==================================================================*/

		// range of print outl
		/*==================================================================*/
		$this->__setPrintRange($last_cell);
		/*==================================================================*/

		$file=$this->K9DataReservation->saveInvoice($reservation["id"]);
		if(empty($file)) Output::__outputNo(array("message"=>array("can't get invoice for this customer.")));

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

	public function __setInvoiceCompany($last_cell)
	{

		$values=array();
		$values[]=K9DataMetaValue::$INVOICE_COMPANYNAME;
		$values[]=K9DataMetaValue::$INVOICE_COMPANYPHONE;
		$values[]=K9DataMetaValue::$INVOICE_COMPANYVATNUMBER;
		$values[]=K9DataMetaValue::$INVOICE_COMPANYADRESS;
		$conditions["and"]["K9DataMetaValue.meta_key"]=$values;
		$res=$this->K9DataMetaValue->find("all",array( "conditions"=>$conditions ));
		$list=Set::combine($res,"{n}.K9DataMetaValue.meta_key","{n}.K9DataMetaValue.meta_value");
		$last_cell=$this->__setBottomCampany($last_cell,$list[K9DataMetaValue::$INVOICE_COMPANYNAME]);
		$last_cell=$this->__setBottomAddress($last_cell,$list[K9DataMetaValue::$INVOICE_COMPANYADRESS]);
		$last_cell=$this->__setBottomVtNumber($last_cell,$list[K9DataMetaValue::$INVOICE_COMPANYVATNUMBER]);
		$last_cell=$this->__setBottomPhone($last_cell,$list[K9DataMetaValue::$INVOICE_COMPANYPHONE]);
		return $last_cell;
	}

	private function __setLogo($last_cell)
	{
		$image_posiiton=$this->__getPositionByCell($last_cell);
		$image_cell="D".($image_posiiton+4);
		$objDrawing=new PHPExcel_Worksheet_Drawing();
		$objDrawing->setPath(MASTER_DATA."k9_invoice_log.png");
		$objDrawing->setCoordinates($image_cell);
		$objDrawing->setWorksheet($this->__getSheet());
		return $image_cell;
	}

	function __updateLastCells($data,&$next_start_num,&$end_charge_cell,&$end_credit_cell,&$last_cell_values){

		$next_start_num=$data["start_num"];
		$values=array_values($data["cell"]);
		$ymd_values=$values[count($values)-1];
		$last_values=$ymd_values[count($ymd_values)-1];
		$end_charge_cell=$last_values["charge"];
		$end_credit_cell=$last_values["credit"];
		$last_cell_values=$last_values;
	}

	function __setBottomSignatureCustomer($last_cell){

		$position=$this->__getPositionByCell($last_cell);
		$next_position=$position+2;

		$start_alpha="G";
		$start_cell=$start_alpha.$next_position;
		$last_cell =++$start_alpha.$next_position;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$this->__setBorderLines(array($start_cell,$last_cell),PHPExcel_Style_Border::BORDER_THICK);

		$next_position++;
		$start_alpha="G";
		$start_cell=$start_alpha.$next_position;
		$last_cell =++$start_alpha.$next_position;
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Guest Signature");
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		return $last_cell;
	}

	function __setBottomSignatureCashier($last_cell){

		$position=$this->__getPositionByCell($last_cell);
		$next_position=$position+2;

		$start_alpha="J";
		$start_cell=$start_alpha.$next_position;
		$last_cell =++$start_alpha.$next_position;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$this->__setBorderLines(array($start_cell,$last_cell),PHPExcel_Style_Border::BORDER_THICK);

		$next_position++;
		$start_alpha="J";
		$start_cell=$start_alpha.$next_position;
		$last_cell =++$start_alpha.$next_position;
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Cashier Signature");
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		return $last_cell;
	}

	function __setBottomPhone($last_cell,$value){

		$position=$this->__getPositionByCell($last_cell);
		$next_position=$position+1;

		$start_alpha="B";
		$start_cell=$start_alpha.$next_position;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,"Phone");
		$this->__setSeparator(++$start_alpha.$next_position);

		$start_cell=++$start_alpha.$next_position;

		++$start_alpha;
		++$start_alpha;
		++$start_alpha;

		$last_cell=$start_alpha.$next_position;
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,$value);

		$object=$sheet->getStyle($start_cell);
		$style=&$object;
		$this->__setVertical($style,"top");
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		return $last_cell;
	}

	function __setBottomVtNumber($last_cell,$value){

		$position=$this->__getPositionByCell($last_cell);
		$next_position=$position+1;

		$start_alpha="B";
		$start_cell=$start_alpha.$next_position;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,"Vat Number");
		$this->__setSeparator(++$start_alpha.$next_position);

		$start_cell=++$start_alpha.$next_position;

		++$start_alpha;
		++$start_alpha;
		++$start_alpha;

		$last_cell=$start_alpha.$next_position;
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,$value);

		$object=$sheet->getStyle($start_cell);
		$style=&$object;
		$this->__setVertical($style,"top");
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		return $last_cell;
	}

	function __setBottomAddress($last_cell,$value){

		$position=$this->__getPositionByCell($last_cell);
		$next_position=$position+1;

		$start_alpha="B";
		$start_cell=$start_alpha.$next_position;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,"ADDRESS");
		$this->__setSeparator(++$start_alpha.$next_position);

		$start_cell=++$start_alpha.$next_position;

		++$start_alpha;
		++$start_alpha;
		++$start_alpha;

		$last_cell=$start_alpha.($next_position);
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,$value);

		$object=$sheet->getStyle($start_cell);
		$style=&$object;
		$this->__setVertical($style,"top");
		$style->getAlignment()->setWrapText(true);
		return $last_cell;
	}

	function __setBottomCampany($last_cell,$value){

		$position=$this->__getPositionByCell($last_cell);
		$next_position=$position+2;

		$start_alpha="B";
		$start_cell=$start_alpha.$next_position;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,"COMPANY");
		$this->__setSeparator(++$start_alpha.$next_position);

		$start_cell=++$start_alpha.$next_position;

		++$start_alpha;
		++$start_alpha;
		++$start_alpha;

		$last_cell=$start_alpha.($next_position);
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,$value);

		$object=$sheet->getStyle($start_cell);
		$style=&$object;
		$this->__setVertical($style,"top");
		$style->getAlignment()->setWrapText(true);
		return $last_cell;
	}

	function __setPrintRange($last_cell){

		$position=$this->__getPositionByCell($last_cell);

		$print_start=$this->startCell;
		$print_end  ="L".($position+3);
		$print_range="{$print_start}:{$print_end}";
		$this->__getSheet()->getPageSetup()->setPrintArea($print_range);
	}

	function __getPositionByCell($cell){

		$res=preg_match("#([0-9]+)#",$cell,$match);
		if(empty($res)) throw new Exception("Wrong Cell Value.");
		return $match[1];
	}

	function __issueDate($last_cell){

		$position=$this->__getPositionByCell($last_cell);
		$next_position=$position+2;

		$start_cell="F".$next_position;
		$last_cell ="G".$next_position;
		$sheet=$this->__getSheet();

		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Issued Date");
		$this->__setAlign($sheet->getStyle($start_cell),"center");

		$today=$this->__setInvoiceScheduleDateFormat(date("Ymd"));
		$start_cell="H".$next_position;
		$last_cell ="J".$next_position;
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,$today." ".date("G:i:s"));
		$this->__setAlign($sheet->getStyle($start_cell),"center");

		return $last_cell;
	}

	function __bottomNotification($last_cell,$message=""){
	
		$position=$this->__getPositionByCell($last_cell);
		$next_position=$position+2;
		$start_cell="C".$next_position;
		$last_cell ="J".($next_position+1);

		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$last_cell}";
		$sheet->mergeCells($merge_cell);

		$object=$sheet->getStyle($merge_cell);
		$style=&$object;

		$sheet->setCellValue($start_cell,$message);
		$this->__setAlign($style,"left");
		$this->__setVertical($style,"top");
		$style->getAlignment()->setWrapText(true);
		$position=$this->__getPositionByCell($start_cell);
		$sheet->getRowDimension($position)->setRowHeight(30);
		return $last_cell;
	}

	function __setPriceValues($last_num,$reservation,$options=array()){

		// total value.
		/*==================================================================*/
		$cells=array();
		$start_charge_target_cell=$options["charge_cells"][0];
		$end_charge_target_cell  =$options["charge_cells"][count($options["charge_cells"])-1];
		$tax_date=$options["tax_date"];

		array_push($cells,$this->__setPriceTotalTitle($last_num));
		$total_value=!empty($reservation["priority_price"])?$reservation["priority_price"]:"=SUM({$start_charge_target_cell}:{$end_charge_target_cell})";
		$total_value_cell=$this->__setPriceTotalValue($last_num,$total_value);

		$this->__setNumberFormat($this->__getSheet()->getStyle($total_value_cell));
		//$this->__getSheet()->getStyle($total_value_cell)->getFont()->getColor()->setARGB($color);
		$this->__getSheet()->getStyle($total_value_cell)->getNumberFormat()->setFormatCode("0.0");
		array_push($cells,$total_value_cell);
		/*==================================================================*/
		
		// credit value.
		/*==================================================================*/
		$start_credit_target_cell=$options["credit_cells"][0];
		$end_credit_target_cell  =$options["credit_cells"][count($options["credit_cells"])-1];
		$credit_value="=SUM({$start_credit_target_cell}:{$end_credit_target_cell})";
		$credit_value_cell=$this->__setPriceCreditValue($last_num,$credit_value);
		array_push($cells,$credit_value_cell);
		$this->__setBorderLines($cells,PHPExcel_Style_Border::BORDER_THICK);
		$this->__getSheet()->getStyle($credit_value_cell)->getNumberFormat()->setFormatCode("0.0");
		/*==================================================================*/
		
		// TaxableAmount value.
		/*==================================================================*/
		$last_num++;
		$cells=array();
		array_push($cells,$this->__setTaxableAmountTitle($last_num));
		array_push($cells,$this->__setSeparator("I".$last_num));
		array_push($cells,$this->__setUSdoller("J".$last_num));
		$taxable_amount_value="={$total_value_cell}";
		$taxable_amount_cell=$this->__setTaxableAmountValue($last_num,$taxable_amount_value);
		$this->__setNumberFormat($this->__getSheet()->getStyle($taxable_amount_cell));
		array_push($cells,$taxable_amount_cell);
		$this->__setBorderLines($cells,PHPExcel_Style_Border::BORDER_DOTTED);
		$this->__getSheet()->getStyle($taxable_amount_cell)->getNumberFormat()->setFormatCode("0.0");
		/*==================================================================*/
		
		// Tax value.
		/*==================================================================*/
		$last_num++;
		$cells=array();

		$tax_controller=new K9SettingTaxController();
		$tax=$tax_controller->__getTaxvalueWithRange($tax_date);
		array_push($cells,$this->__setTaxTitle($last_num,$tax));
		array_push($cells,$this->__setSeparator("I".$last_num));
		array_push($cells,$this->__setUSdoller("J".$last_num));
		$tax_culc_value="{$taxable_amount_value}*".round($tax/100,5);
		$tax_value_cell=$this->__setTaxValue($last_num,$tax_culc_value);
		$this->__setNumberFormat($this->__getSheet()->getStyle($tax_value_cell));
		array_push($cells,$tax_value_cell);
		$this->__setBorderLines($cells,PHPExcel_Style_Border::BORDER_DOTTED);
		$this->__getSheet()->getStyle($tax_value_cell)->getNumberFormat()->setFormatCode("0.0");
		/*==================================================================*/
		
		// Grand Total value.
		/*==================================================================*/
		$last_num++;
		$cells=array();
		array_push($cells,$this->__setGrandValueTitle($last_num));
		array_push($cells,$this->__setSeparator("I".$last_num));
		array_push($cells,$this->__setUSdoller("J".$last_num));
		$grand_value="=SUM({$taxable_amount_cell}:{$tax_value_cell})";
		$grand_value_cell=$this->__setGrandValue($last_num,$grand_value);
		$this->__setNumberFormat($this->__getSheet()->getStyle($grand_value_cell));
		array_push($cells,$grand_value_cell);
		$this->__setBorderLines($cells,PHPExcel_Style_Border::BORDER_DOTTED);
		$this->__getSheet()->getStyle($grand_value_cell)->getNumberFormat()->setFormatCode("0.0");
		/*==================================================================*/
		
		// Payment value.
		/*==================================================================*/
		$last_num++;
		$cells=array();
		array_push($cells,$this->__setPaymentTitle($last_num));
		array_push($cells,$this->__setSeparator("I".$last_num));
		array_push($cells,$this->__setUSdoller("J".$last_num));
		$payment_value="={$credit_value_cell}";
		$payment_value_cell=$this->__setPaymentValue($last_num,$payment_value);
		$this->__setNumberFormat($this->__getSheet()->getStyle($payment_value_cell));
		array_push($cells,$payment_value_cell);
		$this->__setBorderLines($cells,PHPExcel_Style_Border::BORDER_DOTTED);
		$this->__getSheet()->getStyle($payment_value_cell)->getNumberFormat()->setFormatCode("0.0");
		/*==================================================================*/
		
		// AR value.
		/*==================================================================*/
		$last_num++;
		$ar_value=0;
		$cells=array();
		array_push($cells,$this->__setARTitle($last_num));
		array_push($cells,$this->__setSeparator("I".$last_num));
		array_push($cells,$this->__setUSdoller("J".$last_num));
		$ar_value_cell=$this->__setARValue($last_num,$ar_value);
		$this->__setNumberFormat($this->__getSheet()->getStyle($ar_value_cell));
		array_push($cells,$ar_value_cell);
		$this->__setBorderLines($cells,PHPExcel_Style_Border::BORDER_DOTTED);
		$this->__getSheet()->getStyle($ar_value_cell)->getNumberFormat()->setFormatCode("0.0");
		/*==================================================================*/
		
		// Balance Due value.
		/*==================================================================*/
		$last_num++;
		$cells=array();
		array_push($cells,$this->__setBalanceDueTitle($last_num));
		array_push($cells,$this->__setSeparator("I".$last_num));
		array_push($cells,$this->__setUSdoller("J".$last_num));
		$balance_due_value="={$grand_value_cell}-{$payment_value_cell}-{$ar_value_cell}";
		$balance_due_value_cell=$this->__setBalanceDueValue($last_num,$balance_due_value);
		$this->__setNumberFormat($this->__getSheet()->getStyle($balance_due_value_cell));
		array_push($cells,$balance_due_value_cell);
		$this->__setBorderLines($cells,PHPExcel_Style_Border::BORDER_DOTTED);
		$this->__getSheet()->getStyle($balance_due_value_cell)->getNumberFormat()->setFormatCode("0.0");
		/*==================================================================*/

		return end($cells);
	}

	function __setBalanceDueTitle($start_num){

		$start_cell="G".$start_num;
		$merge_last_cell="H".$start_num;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Balance Due");
		$this->__setAlign($sheet->getStyle($merge_cell),"left");

		$object=$sheet->getStyle($merge_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$style->getFont()->setBold(true);
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $merge_cell;
	}

	function __setBalanceDueValue($start_num,$balance_due_value){

		$start_cell="K".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$balance_due_value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setARValue($start_num,$ar_value){

		$start_cell="K".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$ar_value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setARTitle($start_num){

		$start_cell="G".$start_num;
		$merge_last_cell="H".$start_num;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"A/R");
		$this->__setAlign($sheet->getStyle($merge_cell),"left");

		$object=$sheet->getStyle($merge_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$style->getFont()->setBold(true);
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $merge_cell;
	}

	function __setPaymentValue($start_num,$payment_value){

		$start_cell="K".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$payment_value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setPaymentTitle($start_num){

		$start_cell="G".$start_num;
		$merge_last_cell="H".$start_num;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Payment");

		$object=$sheet->getStyle($merge_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$style->getFont()->setBold(true);
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $merge_cell;
	}

	function __setGrandValue($start_num,$grand_value){

		$start_cell="K".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$grand_value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setGrandValueTitle($start_num){

		$start_cell="G".$start_num;
		$merge_last_cell="H".$start_num;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Grand Total");

		$object=$sheet->getStyle($merge_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$style->getFont()->setBold(true);
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $merge_cell;
	}

	function __setTaxValue($start_num,$tax_culc_value){

		$start_cell="K".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$tax_culc_value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setTaxTitle($start_num,$tax=0){

		$start_cell="G".$start_num;
		$merge_last_cell="H".$start_num;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Tax({$tax}%)");

		$object=$sheet->getStyle($merge_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$style->getFont()->setBold(true);
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $merge_cell;
	}

	function __setTaxableAmountValue($start_num,$total_value){

		$start_cell="K".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$total_value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setUSdoller($start_cell){

		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,"US$");
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setSeparator($start_cell){

		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,":");
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13)->setBold(true);
		return $start_cell;
	}

	function __setTaxableAmountTitle($start_num){

		$start_cell="G".$start_num;
		$merge_last_cell="H".$start_num;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Total Taxable Amount");

		$object=$sheet->getStyle($merge_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$style->getFont()->setBold(true);
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $merge_cell;
	}

	function __setPriceCreditValue($start_num,$credit_value){

		$start_cell="K".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$credit_value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setPriceTotalValue($start_num,$total_value){

		$start_cell="J".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$total_value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $start_cell;
	}

	function __setPriceTotalTitle($start_num){

		$start_cell="G".$start_num;
		$merge_last_cell="I".$start_num;
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);
		$sheet->setCellValue($start_cell,"Total");

		$object=$sheet->getStyle($merge_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$style->getFont()->setBold(true);
		$sheet->getStyle($start_cell)->getFont()->setSize(13);
		return $merge_cell;
	}

	function __getReservationByHash($hash){

		$association=$this->K9DataReservation->association;
		$schedule_plan=$association["hasMany"]["K9DataSchedulePlan"];
		$schedule_plan["order"]=array("K9DataSchedulePlan.start ASC");
		$schedule_plan["conditions"]["and"]["K9DataSchedulePlan.del_flg"]=0;

		$schedule=$association["hasMany"]["K9DataSchedule"];
		$schedule["conditions"]=array("K9DataSchedule.del_flg"=>'0');
		$schedule["order"]=array("K9DataSchedule.start_month_prefix ASC","K9DataSchedule.start_day ASC");

		$schedule_rest=$association["hasMany"]["K9DataReststaySchedule"];
		$schedule_rest["conditions"]=array("K9DataReststaySchedule.del_flg"=>'0');
		$schedule_rest["order"]=array("K9DataReststaySchedule.start_month_prefix ASC","K9DataReststaySchedule.start_day ASC");

		$agency=$association["belongsTo"]["K9DataCompany"];
		$this->K9DataCompany->unbindModel(array("belongsTo"=>array("K9MasterEmployee")));
		$this->K9DataReservation->bindModel(array("belongsTo"=>array("K9DataCompany"=>$agency)));

		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedulePlan"=>$schedule_plan,"K9DataSchedule"=>$schedule,"K9DataReststaySchedule"=>$schedule_rest)));
		$this->K9DataSchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation")));
		$data=$this->K9DataReservation->getReservationByHash($hash,0,array(
		
			"recursive"=>3
		));

		return $data;
	}

	function __setNumberFormat($style,$format="#,##0;[Red]-#,##0"){
	
		$style->getNumberFormat()->setFormatCode($format);
	}

	function __setVertical($style,$position){

		switch($position){
		case("top"):
			$align=PHPExcel_Style_Alignment::VERTICAL_TOP;
			break;
		case("bottom"):
			$align=PHPExcel_Style_Alignment::VERTICAL_BOTTOM;
			break;
		default:
			$align=PHPExcel_Style_Alignment::VERTICAL_CENTER;
			break;
		}

		$style->getAlignment()->setVertical($align);
	}

	function __setAlign($style,$position){

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

	public function __setRows($type,$next_start_num,$invoice_other_recodes=array())
	{
		$start_num=$next_start_num;
		$start_side_position="B";
		$current_num=$start_num;

		$counter=0;
		$count=count($invoice_other_recodes);
		$cell_info=array();
		foreach($invoice_other_recodes as $ymd=>$v){

			foreach($v as $k=>$_v){
			
				$_v["date"]=$ymd;
				$res=$this->__setRowCharge($type,$start_num,$_v,array(
				
					"all_count"=>$count,
					"counter"  =>$counter
				));

				if(!isset($cell_info[$ymd])) $cell_info[$ymd]=array();

				//$res["start_num"]=$start_num;
				$cell_info[$ymd][count($cell_info[$ymd])]=$res;

				$start_num++;
				$counter++;
			}
		}

		$res=array();
		$res["start_num"]=$start_num;
		$res["cell"]=$cell_info;
		return $res;
	}

	function __setRowCharge($type,$start_num,$params=array(),$options=array()){

		$cells=array();

		$cell_num=$this->__setInvoiceDate($start_num,array("ymd"=>$params["date"]));
		$cells["date"]=$cell_num;

		$cell_num=$this->__setInvocieRoomNum($start_num,array("room_num"=>"-"));
		$cells["room_num"]=$cell_num;

		$value="({$type}){$params["value"]["name"]}";
		$cell_num=$this->__setMenuInvoiceDescription($start_num,$value);
		$cells["description"]=$cell_num;

		$cell_num=$this->__setInvoiceNightCount($start_num,$params["value"]["count"]);
		$cells["night_count"]=$cell_num;

		$price=$params["price"]["value"];
		$cell_num=$this->__setInvoiceRate($start_num,array("base_price"=>$price));
		$cells["rate"]=$cell_num;

		$value="={$cells["night_count"]}*{$cells["rate"]}";
		//$price=$params["price"]["value"]*$params["drink"]["count"];
		$cell_num=$this->__setMenuInvoiceCharge($start_num,$value);
		$cells["charge"]=$cell_num;

		$cell_num=$this->__setInvoiceCredit($start_num,0);
		$cells["credit"]=$cell_num;

		$border=PHPExcel_Style_Border::BORDER_THIN;
		$this->__setBorderLines(array_values($cells),$border);
		return $cells;
	}

	function __setRoomRows($invoice_recodes=array()){

		$start_num="17";
		$start_side_position="B";
		$current_num=$start_num;

		$counter=0;
		$cell_info=array();

		foreach($invoice_recodes as $ymd=>$v){

			$v["room-charge"]["schedule"]["ymd"]=$ymd;
			$res=$this->__setRowRoomCharge($start_num,$v["room-charge"],array( "counter"=>$counter ));
			$this->__getSheet()->getRowDimension($start_num)->setRowHeight(30);
			$res["start_num"]=$start_num;
			$cell_info[$ymd]=$res;

			$start_num++;
			$counter++;
		}

		$res=array();
		$res["cell"]=$cell_info;
		return $res;
	}

	function __setRowRoomCharge($start_num,$params=array(),$options=array()){

		$cells=array();

		$cell_num=$this->__setInvoiceDate($start_num,array("ymd"=>$params["schedule"]["ymd"]));
		$cells["date"]=$cell_num;

		$cell_num=$this->__setInvocieRoomNum($start_num,$params["room"]);
		$cells["room_num"]=$cell_num;

		$cell_num=$this->__setInvoiceDescription($start_num,$params["room"],$params["price"]);
		$cells["description"]=$cell_num;

		$nights=1;
		$cell_num=$this->__setInvoiceNightCount($start_num,$nights);
		$cells["night_count"]=$cell_num;

		$cell_num=$this->__setInvoiceRate($start_num,array( "base_price"=>$params["price"]["price"] ));
		$cells["rate"]=$cell_num;

		$value="={$cells["night_count"]}*{$cells["rate"]}";
		$price_status=$params["price"]["status"];
		$cell_num=$this->__setInvoiceCharge($start_num,$value,$price_status);
		$cells["charge"]=$cell_num;

		$cell_num=$this->__setInvoiceCredit($start_num,$params["schedule"]["credit_price"]);
		$cells["credit"]=$cell_num;

		$border=PHPExcel_Style_Border::BORDER_THIN;
		$this->__setBorderLines(array_values($cells),$border);
		return $cells;
	}
	
	function __setBorderLines($cells,$border)
	{
		if(empty($cells)) return;
		$sheet=$this->__getSheet();
		$cell=array_shift($cells);
		$sheet->getStyle($cell)->getBorders()->getBottom()->setBorderStyle($border);
		return $this->__setBorderLines($cells,$border);
	}

	function __setInvoiceCredit($start_num,$credit){

		$start_cell="K".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$credit);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$this->__setVertical($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getNumberFormat()->setFormatCode("0.0");
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		return $start_cell;
	}

	function __setMenuInvoiceCharge($start_num,$value){

		$start_cell="J".$start_num;
		$sheet=$this->__getSheet();

		$object=$sheet->getStyle($start_cell);
		$style=&$object;

		$sheet->setCellValue($start_cell,$value);
		$this->__setAlign($style,"center");
		$this->__setVertical($style,"center");
		$this->__setNumberFormat($style);
		$style->getNumberFormat()->setFormatCode("0.0");
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		return $start_cell;
	}

	function __setInvoiceCharge($start_num,$value,$status){

		$start_cell="J".$start_num;
		$sheet=$this->__getSheet();

		$object=$sheet->getStyle($start_cell);
		$style=&$object;

		$sheet->setCellValue($start_cell,$value);
		$this->__setAlign($style,"center");
		$this->__setVertical($style,"center");
		$this->__setNumberFormat($style);
		$style->getNumberFormat()->setFormatCode("0.0");
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		if(in_array($status,array(K9PriceController::$PRICE_BASE_ROOM,K9PriceController::$PRICE_BASE_ROOM_TYPE))) return $start_cell;

		return $start_cell;
	}

	function __setInvoiceRate($start_num,$price){

		$start_cell="I".$start_num;
		$sheet=$this->__getSheet();

		$value=$price["base_price"];
		$sheet->setCellValue($start_cell,$value);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$this->__setNumberFormat($sheet->getStyle($start_cell));
		$this->__setVertical($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getNumberFormat()->setFormatCode("0.0");
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		return $start_cell;
	}

	function __setInvoiceNightCount($start_num,$count){

		$start_cell="H".$start_num;
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$count);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$this->__setVertical($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		return $start_cell;
	}

	function __setMenuInvoiceDescription($start_num,$value){

		$start_cell="E".$start_num;
		$merge_last_cell="G".$start_num;

		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);

		$sheet->setCellValue($start_cell,$value);

		$object=$sheet->getStyle($start_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$this->__setVertical($style,"top");
		$style->getAlignment()->setWrapText(true);
		$position=$this->__getPositionByCell($start_cell);
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		return $merge_cell;
	}

	function __setInvoiceDescription($start_num,$room,$price){

		$start_cell="E".$start_num;
		$merge_last_cell="G".$start_num;

		$room_num=$room["room_num"];
		$sheet=$this->__getSheet();
		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);

		$value="Room-Charge({$room["room_type"]})";
		$sheet->setCellValue($start_cell,$value);

		$object=$sheet->getStyle($start_cell);
		$style=&$object;
		$this->__setAlign($style,"left");
		$this->__setVertical($style,"center");
		$style->getAlignment()->setWrapText(true);
		$position=$this->__getPositionByCell($start_cell);
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		return $merge_cell;
	}

	function __setInvocieRoomNum($start_num,$room){

		$start_cell="D".$start_num;
		$room_num=$room["room_num"];
		$sheet=$this->__getSheet();
		$sheet->setCellValue($start_cell,$room_num);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$this->__setVertical($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		return $start_cell;
	}

	function __setInvoiceDate($start_num,$schedule){
	
		$start_alpha="B";
		$start_cell=$start_alpha.$start_num;
		$merge_last_cell="C".$start_num;
		$sheet=$this->__getSheet();

		$merge_cell="{$start_cell}:{$merge_last_cell}";
		$sheet->mergeCells($merge_cell);

		$ymd=$this->__setInvoiceScheduleDateFormat($schedule["ymd"]);
		$sheet->setCellValue($start_cell,$ymd);
		$this->__setAlign($sheet->getStyle($start_cell),"center");
		$this->__setVertical($sheet->getStyle($start_cell),"center");
		$sheet->getStyle($start_cell)->getFont()->setSize(14);
		return $merge_cell;
	}

	function __setInvoiceScheduleDateFormat($date){

		$date=date("Y-M-d",strtotime($date));
		$tpl=__("<<y>>/<<m>>/<<d>>");
		$date_split=explode("-",$date);
		$date=str_replace(array("<<y>>","<<m>>","<<d>>"),array($date_split[0],$date_split[1],$date_split[2]),$tpl);
		return $date;
	}

	function __invoiceOtherRecords(Model $master_model,Model $history_model,Model $order_model,$reserve_id){

		$lang=Configure::read('Config.language');
		$order_model->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$history=$order_model->getHistories($reserve_id,null,array(
		
			"recursive"=>2,
			"count"    =>1,
			"order"    =>array("{$order_model->name}.created DESC")
		));

		if(empty($history)) return array();

		$data=array();
		foreach($history as $k=>$v){
		
			$ymd=date("Ymd",strtotime($v[$order_model->name]["created"]));
			if(!isset($data[$ymd])) $data[$ymd]=array();
			$count=count($data[$ymd]);
			$master_data=$v[$master_model->name];
			$name=isset($master_data["name_{$lang}"])?$master_data["name_{$lang}"]:$master_data["name"];
			$data[$ymd][$count]["value"]["id"]     =$v[$order_model->name][$history_model->foreignKey];
			$data[$ymd][$count]["value"]["count"]  =$v[$order_model->name]["count"];
			$data[$ymd][$count]["value"]["name"]   =$name;
			$data[$ymd][$count]["value"]["remarks"]=$v[$master_model->name]["remarks"];
			$data[$ymd][$count]["category"]["name"]=$v[$master_model->name]["K9MasterCategory"]["name"];
			$data[$ymd][$count]["category"]["type"]=$v[$master_model->name]["K9MasterCategory"]["type"];
			$data[$ymd][$count]["price"]["value"]  =$v[$history_model->name]["price"];
			$data[$ymd][$count]["employee"]["id"]  =$v[$history_model->name]["K9MasterEmployee"]["id"];
			$data[$ymd][$count]["employee"]["name"]=$v[$history_model->name]["K9MasterEmployee"]["first_name"];
		}

		return $data;
	}

	function __invoiceRecords($params=array()){

		$schedule_plans=$params["schedule_plans"];
		$schedules     =$params["schedules"];
		$reservation   =$params["reservation"];
		$credit        =$params["credit"];
		$staytype      =$reservation["staytype"];

		$counter=0;
		$plans=array();
		foreach($schedule_plans as $k=>$v){

			$start=$v;

			$room_type=$start["K9MasterRoom"]["K9MasterRoomType"];
			unset($start["K9MasterRoom"]["K9MasterRoomType"]);
			$room=$start["K9MasterRoom"];
			$plans[$counter]["range"]["start"]=date("Ymd",strtotime($start["start"]));
			$plans[$counter]["range"]["end"]  ="";
			$plans[$counter]["reservation"]["id"]=$start["reserve_id"];
			$plans[$counter]["K9MasterRoom"]=$room;
			$plans[$counter]["K9MasterRoomType"]=$room_type;

			if(!isset($schedule_plans[$k+1])){

				break;
			}

			$next =$schedule_plans[$k+1];
			$plans[$counter++]["range"]["end"]  =date("Ymd",strtotime("-1 day",strtotime($next["start"])));
		}

		$lang=Configure::read('Config.language');
		$roomtype_name=$this->K9MasterRoomType->hasField("name_{$lang}")?"name_{$lang}":"name";
	
		$invoice_records=array();
		foreach($schedules as $k=>$v){

			$ymd=$v["start_month_prefix"].sprintf("%02d",$v["start_day"]);
			foreach($plans as $_k=>$_v){
			
				$start=$_v["range"]["start"];
				$end  =$_v["range"]["end"];

				if((!empty($end) AND ($ymd>=$start AND $end>=$ymd)) || (empty($end) AND ($ymd>=$start))){

					$credit_price=isset($credit[$ymd])?$credit[$ymd]:0;
					$invoice_records[$ymd]["room-charge"]["reservation"]["id"]   =$_v["reservation"]["id"];
					$invoice_records[$ymd]["room-charge"]["schedule"]["credit_price"]=$credit_price;
					$invoice_records[$ymd]["room-charge"]["room"]["id"]          =$_v["K9MasterRoom"]["id"];
					$invoice_records[$ymd]["room-charge"]["room"]["room_num"]    =$_v["K9MasterRoom"]["room_num"];
					$invoice_records[$ymd]["room-charge"]["room"]["room_type_id"]=$_v["K9MasterRoom"]["room_type_id"];
					$invoice_records[$ymd]["room-charge"]["room"]["floor"]       =$_v["K9MasterRoom"]["floor"];
					$invoice_records[$ymd]["room-charge"]["room"]["room_type"]   =$_v["K9MasterRoomType"][$roomtype_name];
				}
			}
		}

		$dates=array_keys($invoice_records);
		$schedule_plans=$this->__getSchedulePlans(array($reservation["id"]));
		$price_info=$this->__getPrice(array($reservation["id"]),array(
		
			"start_date"=>$dates[0],
			"end_date"  =>$dates[count($dates)-1]
		));

		$price_reststay_info=$this->__getReststayPrice(array(

			"start_date"=>$dates[0],
			"end_date"  =>$dates[count($dates)-1]
		));

		foreach($invoice_records as $ymd=>$v){

			$data=$v["room-charge"];
			$reserve_id=$data["reservation"]["id"];

			$k9_plans=$this->__getPainByYmd($schedule_plans[$reserve_id],$ymd);
			$room_id     =$k9_plans["room_id"];
			$room_type_id=$k9_plans["room_type_id"];
			$room_type   =$k9_plans["room_type"];
			$room_floor  =$k9_plans["room_floor"];

			switch($staytype){
			
			case("stay"):

				$priceinfo=$this->__getPriceParYmd($ymd,$price_info,array(

					"room_id"     =>$room_id,
					"room_type_id"=>$room_type_id,
				));

				$price  =$priceinfo["price"];
				$status =$priceinfo["status"];
				$data_id=$priceinfo["data_id"];
				break;

			case("rest"):

				$priceinfo=$this->__getRestPriceParYmd($ymd,$price_reststay_info);

				$price  =$priceinfo["price"];
				$status =K9RestPriceController::$PRICE_RESTSTAY;
				$data_id=$priceinfo["data_id"];
				break;
			}

			$this->__updateWithWeekdayOrWeekend($price,$status,$reservation,$ymd);

			$invoice_records[$ymd]["room-charge"]["price"]["price"]  =$price;
			$invoice_records[$ymd]["room-charge"]["price"]["status"] =$status;
			$invoice_records[$ymd]["room-charge"]["price"]["data_id"]=$data_id;
		}

		return $invoice_records;
	}

	function __setHeadInformation($data){

		$this->__setHeadWorkingId($data["reservation"]);
		$this->__setHeadGuetstName($data["guest"]);
		$this->__setHeadFolioNo($data["reservation"]);
		$this->__setHeadGuestNumber($data["reservation"]);
		$this->__setHeadArrival($data["reservation"]);
		$this->__setHeadDeparture($data["reservation"]);
		$this->__setHeadNightCount($data["schedules"]);
		$this->__setHeadRoom($data["room_nums"]);
		$this->__setHeadCompanyName($data["agency"]);
		$this->__setHeadCompanyVATNO($data["agency"]);
		$this->__setHeadCompanyAddress($data["agency"]);
	}

    function __getSheet()
    {
        return $this->book->getActiveSheet($this->sheet);
    }

	function __setHeadCompanyAddress($agency=array()){

		$cell="D13";
		$sheet=$this->__getSheet();
		$sheet->setCellValue($cell,$agency["address"]);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadCompanyVATNO($agency=array()){

		$cell="D12";
		$sheet=$this->__getSheet();
		$sheet->setCellValue($cell,$agency["vtno"]);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadCompanyName($agency=array()){

		$cell="D11";
		$sheet=$this->__getSheet();
		$sheet->setCellValue($cell,$agency["name"]);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadRoom($room_nums=array()){

		$cell="I6";
		$room_nums=implode(",",$room_nums);
		$sheet=$this->__getSheet();
		$sheet->setCellValue($cell,$room_nums);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadNightCount($schedules){

		$cell="I11";
		$count=count($schedules);
		$sheet=$this->__getSheet();
		$sheet->setCellValue($cell,$count);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadDeparture($reservation){

		$cell="I9";
		$sheet=$this->__getSheet();

		$date="";
		if(strtotime($reservation["checkout_time"])>0){

			$his=date("H:i:s",strtotime($reservation["checkout_time"]));
			$ymd=$this->__setInvoiceScheduleDateFormat($reservation["checkout_time"]);
			$date=$ymd." ".$his;
		}

		$sheet->setCellValue($cell,$date);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($sheet->getStyle($cell),"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadArrival($reservation){

		$cell="I8";
		$sheet=$this->__getSheet();

		$date="";
		if(strtotime($reservation["checkin_time"])>0){

			$his=date("H:i:s",strtotime($reservation["checkin_time"]));
			$ymd=$this->__setInvoiceScheduleDateFormat($reservation["checkin_time"]);
			$date=$ymd." ".$his;
		} 
			
		$sheet->setCellValue($cell,$date);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadGuestNumber($reservation)
	{
		$cell="I7";
		$sheet=$this->__getSheet();

		$adults_num=$reservation["adults_num"];
		$child_num =$reservation["child_num"];
		$total=$adults_num+$child_num;

		$sheet->setCellValue($cell,$total);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadFolioNo($reservation){
	
		$cell="D8";
		$sheet=$this->__getSheet();
		$id=CLIENT."_{$reservation["id"]}_".date("YmdHis");
		$sheet->setCellValue($cell,$id);
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __setHeadWorkingId($reservation){

		$salessource=$this->K9MasterReservationSalesource->getSaleSource();
		$value=$salessource[$reservation["salesource_id"]];

		$cell="D6";
		$name=__("未設定");
		$sheet=$this->__getSheet();
		$sheet->setCellValue($cell,__($value));
		$style=$sheet->getStyle($cell);
		$this->__setAlign($style,"left");
		$style->getAlignment()->setWrapText(true);
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __creditPriceWithScheduleId($reserve_id,$schedules){

		$target_dates=array();
		foreach($schedules as $k=>$v) $target_dates[]=$v["start_month_prefix"].sprintf("%02d",$v["start_day"]);
	
		$this->K9DataCredit->unbindFully();

		$conditions["and"]["{$this->K9DataCredit->name}.reserve_id"]=$reserve_id;
		$conditions["and"]["DATE_FORMAT({$this->K9DataCredit->name}.enter_date,'%Y%m%d')"]=$target_dates;
		$conditions["and"]["{$this->K9DataCredit->name}.del_flg"]    =0;
		$fields=array("SUM({$this->K9DataCredit->name}.price) as price","DATE_FORMAT({$this->K9DataCredit->name}.enter_date,'%Y%m%d') as enter_date");

		$data=$this->K9DataCredit->find("all",array(
		
			"conditions"=>$conditions,
			"fields"     =>$fields
		));

		if(empty($data)) return array();
		$credit_values=Set::combine($data,"{n}.0.enter_date","{n}.0.price");
		return $credit_values;
	}

	function __setHeadGuetstName($guest){

		$cell="D7";
		$name=__("未設定");
		$sheet=$this->__getSheet();

		switch(true){
		
		case(!empty($guest["first_name"])):
			$name=$guest["first_name"];
			break;
		case(!empty($guest["last_name"])):
			$name=$guest["last_name"];
			break;
		case(!empty($guest["middle_name"])):
			$name=$guest["middle_name"];
			break;
		default:
			$name=__("未設定");
			break;
		}

		$sheet->setCellValue($cell,$name);
		$this->__setAlign($sheet->getStyle($cell),"left");
		$sheet->getStyle($cell)->getFont()->setSize(13);
	}

	function __getPrice($reserve_ids=array(),$params=array()){

		$controller=new K9PriceController();
		$res=$controller->__getPrice($reserve_ids,$params);
		return $res;
	}

	function __getReststayPrice($params=array()){

		$controller=new K9RestPriceController();
		$res=$controller->__getReststayPrice($params);
		return $res;
	}

	function __getSchedulePlans($reserve_ids=array()){

		$association["K9MasterRoomSituation"]["className"] ="K9MasterRoomSituation";
		$association["K9MasterRoomSituation"]["foreignKey"]="situation_id";
		$association["K9MasterRoomSituation"]["conditions"]=array("K9MasterRoomSituation.del_flg"=>'0');
		$this->K9MasterRoom->bindModel(array("belongsTo"=>$association));

		$controller=new K9SiteController();
		$res=$controller->__getSchedulePlans($reserve_ids);
		return $res;
	}

	function __getPainByYmd($data,$check_ymd){

		$controller=new K9SiteController();
		$res=$controller->__getPainByYmd($data,$check_ymd);
		return $res;
	}

	function __getPriceParYmd($ymd,$price_info,$params=array()){
	
		$controller=new K9SiteController();
		$res=$controller->__getPriceParYmd($ymd,$price_info,$params);
		return $res;
	}

	function __getRestPriceParYmd($ymd,$price_info,$params=array()){

		$controller=new K9SiteController();
		$res=$controller->__getRestPriceParYmd($ymd,$price_info,$params);
		return $res;
	}

	function __updateWithWeekdayOrWeekend(&$price,&$status,$k9_reservation,$ymd){

		$controller=new K9SiteController();
		$res=$controller->__updateWithWeekdayOrWeekend($price,$status,$k9_reservation,$ymd);
		return $res;
	}
}
