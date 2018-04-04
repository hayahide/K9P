<?php

App::uses("K9PriceController","Controller");
App::uses("K9RestPriceController","Controller");
App::uses("K9BasePricesController","Controller");
App::uses("K9SiteController","Controller");

class AdK9DipositReportsController extends AppController {

	var $name = "K9DipositReports";
	var $uses = [

		"K9DataSchedule",
		"K9DataReststaySchedule",
		"K9DataDipositReststaySchedule",
		"K9DataDipositSchedule",
		"K9DataReservation",
		"K9DataCompany",
		"K9DataHistoryPriceRoomType",
		"K9MasterRoom"
	];

	var $startCell="B2";
	var $sheet=0;

	function beforeFilter(){
	
		parent::beforeFilter();

		$this->__init();
		$this->__useModel();
	}

	function __init(){

		Configure::write('Config.language','eng');
	    $this->book=PHPExcel_IOFactory::load(MASTER_DATA."k9_diposit.xlsx");
        $this->book->setActiveSheetIndex(0);

		$object=$this->__getSheet();
		$style=&$object;
		$style->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$style->getPageSetup()->setHorizontalCentered(false);
		$style->getPageSetup()->setVerticalCentered(false);
	}

	public function __useModel()
	{
		$this->loadModel("K9DataSchedule");
		$this->loadModel("K9DataReststaySchedule");
		$this->loadModel("K9MasterDipositReason");
	}

	function invoice(){

		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		//$post["reservation_hash"]="8f1fde4ed401d52102a08a90d5dfc6792046791ca9d4a6651e1b77000e75509e";
		$hash=$post["reservation_hash"];

		$data=$this->__getReservationByHash($hash);
		$data_reservation  =$data["K9DataReservation"];
		$data_schedule     =$data[$this->__stayTypeModel($data_reservation["staytype"])->name];
		$data_guest        =$data["K9DataGuest"];
		$data_schedule_plan=$data["K9DataSchedulePlan"];
		$staytype=$data_reservation["staytype"];

		//for room num.
		$this->__setRoomNum($data_schedule_plan);

		//guest name
		$this->__setGuestName($data_guest);

		//arrival
		$this->__setArrival($data_reservation);

		//departure
		$this->__setDeparture($data_reservation);

		//nights
		$this->__setNightCount($data_schedule);

		//booking num.
		$this->__setBookingNum($data_reservation);

		//weekend,weekday
		$this->__setWeekdayWeekendPrices($data_reservation,$data_schedule,$data_schedule_plan);

		//including price,remarks...
		$last_positions=$this->__setRowValues($data_schedule);
		$last_row_position=$last_positions["row_position"];
		$last_row_position+=4;

		// range of print outl
		/*==================================================================*/
		$this->__setPrintRange($last_row_position);
		/*==================================================================*/

        $writer=PHPExcel_IOFactory::createWriter($this->book,'Excel2007');

		$file=date("YmdHis").".xlsx";
        $dir=WWW_ROOT."excel_tmp".DS;
        if(!is_dir($dir)) mkdir($dir,0777);
        $path=$dir.$file;
        $writer->save($path);

		$res=array();
		$res["data"]["file"]=pathinfo($path)["basename"];
		$res["data"]["url"] =ROOT_DOMAIN.DS."webroot".DS."excel_tmp".DS.$res["data"]["file"];
		Output::__outputYes($res);
	}

	private function __putDataForSheet($value,$params=array())
	{
		$position=$params["position"];
		$alpha   =$params["alpha"];
		$cell    ="{$alpha}{$position}";

		$sheet=$this->__getSheet();
		$sheet->setCellValue($cell,$value);
	}

	private function __setRoomNum($data_schedule_plan)
	{
		$cell="E8";
		$separated=$this->__getPositionByCell($cell);
		$first_plan=array_shift($data_schedule_plan);
		$value=$first_plan["K9MasterRoom"]["room_num"];
		$this->__putDataForSheet($value,array(
		
			"position"=>$separated["position"],
			"alpha"   =>$separated["alpha"],
		));
	}

	private function __setGuestName($data_guest)
	{
		$cell="C6";
		$separated=$this->__getPositionByCell($cell);
		$title=ucfirst($data_guest["title"]);
		$firstname=$data_guest["first_name"];
		$lastname =$data_guest["last_name"];
		$fullname=trim("{$firstname} {$lastname}");
		$value="{$title}.";
		if(!empty($fullname)) $value.=" {$fullname}.";
		$this->__putDataForSheet($value,array(
		
			"position"=>$separated["position"],
			"alpha"   =>$separated["alpha"],
		));
	}

	private function __setArrival($data_reservation)
	{
		$cell="E9";
		$separated=$this->__getPositionByCell($cell);
		$checkin=$data_reservation["checkin_time"];
		if(0>strtotime($checkin)) return;

		$value=localDatetime($checkin);
		$this->__putDataForSheet($value,array(
		
			"position"=>$separated["position"],
			"alpha"   =>$separated["alpha"],
		));
	}

	private function __setDeparture($data_reservation)
	{
		$cell="E10";
		$separated=$this->__getPositionByCell($cell);
		$checkout=$data_reservation["checkout_time"];
		if(0>strtotime($checkout)) return;

		$value=localDatetime($checkout);
		$this->__putDataForSheet($value,array(
		
			"position"=>$separated["position"],
			"alpha"   =>$separated["alpha"],
		));
	}

	private function __setNightCount($data_schedule)
	{
		$cell="E11";
		$separated=$this->__getPositionByCell($cell);
		$value=count($data_schedule);
		$this->__putDataForSheet($value,array(
		
			"position"=>$separated["position"],
			"alpha"   =>$separated["alpha"],
		));
	}

	private function __setBookingNum($data_reservation)
	{
		$cell="J6";
		$separated=$this->__getPositionByCell($cell);
		$value=$data_reservation["booking_id"];
		$this->__putDataForSheet($value,array(
		
			"position"=>$separated["position"],
			"alpha"   =>$separated["alpha"],
		));
	}

	private function __setWeekdayWeekendPrices($data_reservation,$data_schedule,$data_schedule_plan)
	{
		$res=array();
		$weekday=$data_reservation["weekday_price"];
		$weekend=$data_reservation["weekend_price"];
		//$price=$this->__getPriceParDay($data_reservation,$data_schedule,$data_schedule_plan);

		$cell="J8";
		$separated=$this->__getPositionByCell($cell);
		$value=$weekday;
		$this->__putDataForSheet($value,array(
		
			"position"=>$separated["position"],
			"alpha"   =>$separated["alpha"],
		));

		$cell="J9";
		$separated=$this->__getPositionByCell($cell);
		$value=$weekend;
		$this->__putDataForSheet($value,array(
		
			"position"=>$separated["position"],
			"alpha"   =>$separated["alpha"],
		));
	}

	private function __setRowValues($data_schedule,$start_position=16)
	{
		$row=2;
		$row_position =$start_position+4;
		$data_position=$start_position;
		foreach($data_schedule as $k=>$v){
		
			$diposits=isset($v[$this->K9DataDipositSchedule->name])?$v[$this->K9DataDipositSchedule->name]:$v[$this->K9DataDipositReststaySchedule->name];
			$row_position=$this->__setRowEachItems($diposits,$row_position,$row);

			foreach($diposits as $k=>$diposit){
			
				$date=localDatetime($diposit["regist_date"]);
				$reason_id=$diposit["K9MasterDipositReason"]["id"];
				$remarks=$diposit["remarks"];
				$credit=$diposit["value"];
				$debit=0;

				$separated=$this->__getPositionByCell("C{$data_position}");
				$this->__putDataForSheet($date,array(
				
					"position"=>$separated["position"],
					"alpha"   =>$separated["alpha"],
				));

				$separated=$this->__getPositionByCell("E{$data_position}");
				$this->__putDataForSheet($remarks,array(
				
					"position"=>$separated["position"],
					"alpha"   =>$separated["alpha"],
				));

				$separated=$this->__getPositionByCell("H{$data_position}");
				$this->__putDataForSheet($credit,array(
				
					"position"=>$separated["position"],
					"alpha"   =>$separated["alpha"],
				));

				$separated=$this->__getPositionByCell("I{$data_position}");
				$this->__putDataForSheet($debit,array(
				
					"position"=>$separated["position"],
					"alpha"   =>$separated["alpha"],
				));

				$data_position+=$row;
			}
		}

		$res=array();
		$res["data_position"]=$data_position;
		$res["row_position"] =$row_position;
		return $res;
	}

	private function __setRowEachItems($diposits,$position=20,$row=2)
	{
		if(empty($diposits)) return $position;
		$data=array_shift($diposits);
		$position=$this->__addRow($position,$row);
		$next_position=$position+1;
		return $this->__setRowEachItems($diposits,$next_position,$row);
	}

	private function __addRow($position,$row=2)
	{
		$sheet=$this->book->getActiveSheet();
		$next_position=$position+$row-1;
		$sheet->insertNewRowBefore($position,$row);

		$cells=["C:D","E:F:G","H","I","J:K"];
		for($i=0;$i<count($cells);$i++){

			$cell=$cells[$i];
			$split_cell=explode(":",$cell);
			$first_alpha=$split_cell[0];
			$comp_cell="{$first_alpha}{$position}";

			$before_position=$position-$row;
			$sheet->duplicateStyle($sheet->getStyle("{$first_alpha}{$before_position}"),$comp_cell);

			if(count($split_cell)>1){
			
				$merge_cell="{$split_cell[0]}{$position}:{$split_cell[count($split_cell)-1]}{$next_position}";
				$sheet->mergeCells($merge_cell);
				continue;
			} 

			$merge_cell="{$first_alpha}{$position}:{$first_alpha}{$next_position}";
			$sheet->mergeCells($merge_cell);
		}

		$this->__putDataForSheet(0,array(
		
			"position"=>$position,
			"alpha"   =>"H",
		));

		$this->__putDataForSheet(0,array(
		
			"position"=>$position,
			"alpha"   =>"I",
		));
	
		$formula="=H{$position}-I{$position}";
		$this->__putDataForSheet($formula,array(
		
			"position"=>$position,
			"alpha"   =>"J",
		));
	
		return $next_position;
	}

	private function __getPriceParDay($data_reservation,$data_schedule,$data_schedule_plan)
	{
		$dates=array();
		foreach($data_schedule as $k=>$v) $dates[]=makeYmdByYmAndD($v["start_month_prefix"],$v["start_day"]);
		$schedule_plans=$this->__getSchedulePlans(array($data_reservation["id"]));
		$staytype=$data_reservation["staytype"];

		switch($staytype){
		
		case($this->K9DataSchedule->stayType):
			$price_info=$this->__getPrice(array($data_reservation["id"]),array("start_date"=>$dates[0],"end_date"=>$dates[count($dates)-1]));
			break;
		case($this->K9DataReststaySchedule->stayType):
			$price_info=$this->__getReststayPrice(array("start_date"=>$dates[0],"end_date"=>$dates[count($dates)-1]));
			break;
		}

		$list=array();
		foreach($dates as $k=>$ymd){

			$k9_plans=$this->__getPainByYmd($data_schedule_plan,$ymd);
			$room_id     =$k9_plans["room_id"];
			$room_type_id=$k9_plans["room_type_id"];
			$room_type   =$k9_plans["room_type"];
			$room_floor  =$k9_plans["room_floor"];

			switch($staytype){
			
			case($this->K9DataSchedule->stayType):

				$priceinfo=$this->__getPriceParYmd($ymd,$price_info,array("room_id"=>$room_id,"room_type_id"=>$room_type_id));
				$price=$priceinfo["price"];
				break;

			case($this->K9DataReststaySchedule->stayType):

				$priceinfo=$this->__getRestPriceParYmd($ymd,$price_reststay_info);
				$price=$priceinfo["price"];
				break;
			}

			$list[$ymd]=$price;
		}

		return $list;
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

		$this->K9DataDipositSchedule->unbindModel(array("belongsTo"=>array("K9DataSchedule","K9MasterEmployee")));
		$this->K9DataDipositReststaySchedule->unbindModel(array("belongsTo"=>array("K9DataReststaySchedule","K9MasterEmployee")));

		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedule"=>$schedule,"K9DataReststaySchedule"=>$schedule_rest,"K9DataSchedulePlan"=>$schedule_plan)));
		$this->K9DataSchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataReststaySchedule->unbindModel(array("belongsTo"=>array("K9DataReservation")));

		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataReservation","K9DataCompany")));
		$data=$this->K9DataReservation->getReservationByHash($hash,0,array( "recursive"=>3 ));
		return $data;
	}
	
	function __setPrintRange($last_row_position){

		$print_start=$this->startCell;
		$print_end  ="M".($last_row_position);
		$print_range="{$print_start}:{$print_end}";
		$this->__getSheet()->getPageSetup()->setPrintArea($print_range);
	}

	function __getPositionByCell($cell){

		$res=preg_match("#([0-9]+)#",$cell,$match);
		if(empty($res)) throw new Exception("Wrong Cell Value.");
		$position=$match[1];

		$res=array();
		$res["position"]=$position;
		$res["alpha"]=rtrim($cell,$position);
		return $res;
	}

    function __getSheet()
    {
        return $this->book->getActiveSheet($this->sheet);
    }

	private function __getSchedulePlans($reserve_ids=array()){

		$association["K9MasterRoomSituation"]["className"] ="K9MasterRoomSituation";
		$association["K9MasterRoomSituation"]["foreignKey"]="situation_id";
		$association["K9MasterRoomSituation"]["conditions"]=array("K9MasterRoomSituation.del_flg"=>'0');
		$this->K9MasterRoom->bindModel(array("belongsTo"=>$association));

		$controller=new K9SiteController();
		$res=$controller->__getSchedulePlans($reserve_ids);
		return $res;
	}

	private function __getPrice($reserve_ids=array(),$params=array()){

		$controller=new K9PriceController();
		$res=$controller->__getPrice($reserve_ids,$params);
		return $res;
	}

	private function __getReststayPrice($params=array()){

		$controller=new K9RestPriceController();
		$res=$controller->__getReststayPrice($params);
		return $res;
	}

	private function __getPainByYmd($data,$check_ymd){

		$controller=new K9SiteController();
		$res=$controller->__getPainByYmd($data,$check_ymd);
		return $res;
	}

	private function __getPriceParYmd($ymd,$price_info,$params=array()){
	
		$controller=new K9SiteController();
		$res=$controller->__getPriceParYmd($ymd,$price_info,$params);
		return $res;
	}

	private function __getRestPriceParYmd($ymd,$price_info,$params=array()){

		$controller=new K9SiteController();
		$res=$controller->__getRestPriceParYmd($ymd,$price_info,$params);
		return $res;
	}

}
