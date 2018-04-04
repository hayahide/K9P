<?php

App::uses('K9BaseDailyReportController','Controller');
App::uses('K9DailyReportOutputOfCreditRateController','Controller');
App::uses('K9DailyReportAttendanceController','Controller');
App::uses('K9DailyReportRestAmountsController','Controller');
App::uses('K9DailyReportOrderHistoriesController','Controller');
App::uses('K9DailyReportExtraOrderHistoriesController','Controller');
App::uses('K9DailyReportHotelPricesController','Controller');

class AdK9DailyReportController extends K9BaseDailyReportController{

	var $name = "K9DailyReport";

	var $models=array();
	private $the_day="";

	const maximumOutputPaymentRow=36;
	const maximumOutputEmployeeRow=13;

	function beforeFilter(){
	
		parent::beforeFilter();

		ini_set("memory_limit",-1);
		set_time_limit(0);
	}

	private function __useModel()
	{
		$this->loadModel("K9DataHistoryPriceCard");
	}

	public function __init()
	{
		$post=$this->data;
		$prev_lang=Configure::read('Config.language');
		$lang=isset($post["lang"])?$post["lang"]:$prev_lang;
		Configure::write('Config.language',$lang);
		parent::__init();
		Configure::write('Config.language',$prev_lang);
	}

	public function report()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();
		
		$this->__init();
		$this->__useModel();

		$post=$this->data;
		$day =isset($post["day"])?$post["day"]:date("Ymd");
		if(!isYmd($day)) exit;

		$lang=isset($post["lang"])?$post["lang"]:Configure::read('Config.language');;
		$path=$this->__getReportByDates($day,$lang);

		$res=array();
		$res["data"]["file"]=pathinfo($path)["basename"];
		$res["data"]["url"] =ROOT_DOMAIN.DS."webroot".DS."excel_tmp".DS.$res["data"]["file"];
		Output::__outputYes($res);
	}

	public function reportByMonth()
	{
		if(!$this->isPostRequest()) exit;
		//$post=$this->__getTestPostData();

		$post=$this->data;
		$year =isset($post["year"])?$post["year"]:date("Y");
		$month=isset($post["month"])?sprintf("%02d",$post["month"]):date("m");
		$lang=isset($post["lang"])?$post["lang"]:"eng";
		$ym=$year.$month;

		//file list.
		$files=$this->__getReportsWithConsole($ym,$lang);

		//excel book.
		$this->__getMainBook($files,$main_book,$lang);

		//tmp file remove.
		$this->__tmpReportRemove($ym);

        $file=CLIENT."_{$ym}.xlsx";
        $writer=PHPExcel_IOFactory::createWriter($main_book,'Excel2007');
        $dir=WWW_ROOT."excel_tmp".DS;
        if(!is_dir($dir)) mkdir($dir,0777);
        $path=$dir.$file;
        $writer->save($path);

		$res=array();
		$res["data"]["file"]=pathinfo($path)["basename"];
		$res["data"]["url"] =ROOT_DOMAIN.DS."webroot".DS."excel_tmp".DS.$res["data"]["file"];
		Output::__outputYes($res);
	}

	private function __getReportsWithConsole($ym,$lang)
	{
		$client=CLIENT;
		$command="php ".dirname(ROOT).DS."php".DS."dailyReportYm.php {$ym} {$lang} {$client}";

		ob_start();
		system($command);
		$buffer=ob_get_contents(); 
		ob_end_clean();
		$buffer=trim($buffer);
		$files=explode(",",$buffer);
		return $files;
	}

	private function __tmpReportRemove($ym)
	{
		$command="rm -rf ".WWW_ROOT."excel_tmp".DS."k9_dailyreport_{$ym}*";
		system($command);
	}

	private function __getMainBook($files=array(),&$main_book,$lang)
	{
		$main_book=new PHPExcel();
		$main_book->removeSheetByIndex(0);
		foreach($files as $k=>$path){

			$path=trim($path);
			if(!file_exists($path)) continue;

			$this->__getIntitalBool($path,$book,$lang);
			$sheet=$book->getActiveSheet();
			$sheet_copy=$sheet->copy();
			$main_book->addExternalSheet($sheet_copy);
		}
	}

	public function __getReportByConsole($day,$lang,$client)
	{
		Configure::write('Config.language',$lang);
		parent::__init();

		$this->__useModel();

		$path=$this->__getReportByDates($day,$lang,$client);
		return $path;
	}

	private function __putdataTargetDateForSheet($day,$lang)
	{

		if(empty($lang)) $lang=Configure::read('Config.language');

		$year =substr($day,0,4);
		$month=substr($day,4,2);
		$date =substr($day,6,2);
	
		switch($lang){
		case("eng"):

			$month=(Int)$month;
			$date=(Int)$date;
			$value="{$month}/{$date}/{$year}";
			break;

		default:

			$value="{$year}/{$month}/{$date}";
			break;
		}

		$position=2;
		$this->__putDataForSheet($value,array(
		
			"position"=>$position,
			"alpha"   =>"B",
			"align"   =>"center"
		));
	}

	private function __orderExtraHistory($day)
	{
		$controller=new K9DailyReportExtraOrderHistoriesController();
		$controller->__setOrderModels();
		$data=$controller->__orderHistory($day);
		return $data;
	}

	private function __getReportByDates($day,$lang="",$client="")
	{
		$order_history =$this->__orderHistory($day);
		$order_extra_history=$this->__orderExtraHistory($day);

		$order_history =isset($order_history[$day])?$order_history[$day]:array();
		$order_extra_history =isset($order_extra_history[$day])?$order_extra_history[$day]:array();

		$output_payment=$this->__outputPayment($day);
		$output_payment=isset($output_payment[$day])?$output_payment[$day]:array();

		/*==================================================================*/
		$this->__putdataTitleForSheet($lang);
		/*==================================================================*/

		/*==================================================================*/
		$this->__putdataTargetDateForSheet($day,$lang);
		/*==================================================================*/

		/*==================================================================*/
		$this->__putdataSheetTitleForSheet($day);
		/*==================================================================*/

		/*==================================================================*/
		//注文側、残金修正後の金額(クレジットカード考慮)
		//宿泊費、残金修正後の金額(クレジットカード考慮)
		$this->__putdataBalanceAmount($day);
		/*==================================================================*/
  
		/*==================================================================*/
		$this->__putdataStayInformationForSheet($day);
		/*==================================================================*/

		//売上の部
		/*==================================================================*/
		$this->__putdataOrdersForSheet($order_history,$order_extra_history);
		/*==================================================================*/

		//支出
		/*==================================================================*/
		$last_position=$this->__putdataOutputpaymentForSheet($output_payment);
		$this->__outputCreditRateForSheet($day,$last_position);
		/*==================================================================*/

		/*==================================================================*/
		$this->__putdataOrdersEachCashTypesForSheet($order_history,$order_extra_history);
		/*==================================================================*/

		/*==================================================================*/
		$this->__putdataAttendanceForSheet($day);
		/*==================================================================*/

		$client=!empty($client)?$client:CLIENT;
        $file=$client."_dailyreport_{$day}.xlsx";
        $writer=PHPExcel_IOFactory::createWriter($this->book,'Excel2007');

        $dir=WWW_ROOT."excel_tmp".DS;
        if(!is_dir($dir)) mkdir($dir,0777);
        $path=$dir.$file;
        $writer->save($path);
		return $path;
	}

	private function __putdataSheetTitleForSheet($day)
	{
		
		$title=date("n-j",strtotime($day));
		$sheet=$this->__getSheet();
		$sheet->setTitle($title);
	}

	private function __getSheetTitle($lang)
	{
		$prev_lang=Configure::read('Config.language');
		Configure::write('Config.language',$lang);
		$title=__("Ｋ９ Ｒｉｖｅｒｓｉｄｅ Ｈｏｔｅｌ 　業　務　日　報");
		Configure::write('Config.language',$prev_lang);
		return $title;
	}

	private function __putdataTitleForSheet($lang)
	{

		$title=$this->__getSheetTitle($lang);
		$year=date("Y");
		$title.="  {$year}";

		$position=1;
		$this->__putDataForSheet($title,array(
		
			"position"=>$position,
			"alpha"   =>"B",
			"align"   =>"center"
		));
	}

	private function __putdataBalanceAmount($day)
	{

		$amounts=$this->__culcRestAmount($day);

		$position=50;
		$this->__putDataForSheet($amounts[K9MasterRoom::$HOTEL],array(
		
			"position"=>$position,
			"alpha"   =>"J",
			"align"   =>"right"
		));

		$position=51;
		$this->__putDataForSheet($amounts["order"],array(
		
			"position"=>$position,
			"alpha"   =>"J",
			"align"   =>"right"
		));

		$position=52;
		$this->__putDataForSheet($amounts["cash"],array(
		
			"position"=>$position,
			"alpha"   =>"J",
			"align"   =>"right"
		));

		$position=53;
		$this->__putDataForSheet($amounts["bank"],array(
		
			"position"=>$position,
			"alpha"   =>"J",
			"align"   =>"right"
		));
	}

	/*==================================================================*/

	private function __outputCreditRateByCardForSheet($data,$card_titles,$position=27)
	{
		if(empty($data)) return;
		foreach($data as $card_id=>$orders) break;
		unset($data[$card_id]);

		$value=0;
		$count=0;
		$title=$card_titles[$card_id];
		foreach($orders as $order_type=>$order){

			if(empty($order["value"])) continue;
			$value+=$order["value"];
			$count+=$order["count"];
		}

		if(empty($value)) return $this->__outputCreditRateByCardForSheet($data,$card_titles,$position);

		$this->__putDataForSheet($title,array(
		
			"position"=>$position,
			"alpha"   =>"C",
			"align"   =>"right"
		));

		$description=__("決済手数料");
		$description.="({$count})";
		$this->__putDataForSheet($description,array(
		
			"position"=>$position,
			"alpha"   =>"D",
			"align"   =>"right"
		));

		$this->__putDataForSheet($value,array(
		
			"position"=>$position,
			"alpha"   =>"F",
			"align"   =>"right"
		));

		return $this->__outputCreditRateByCardForSheet($data,$card_titles,++$position);
	}

	private function __outputCreditRateForSheet($day,$position)
	{
		$controller=new K9DailyReportOutputOfCreditRateController();
		$controller->__setOrderModels();
		$data=$controller->__outputCreditRateForSheet($day,$position);

		$card_titles=$data["card_titles"];
		$order_data=$data["data"];
		$this->__outputCreditRateByCardForSheet($order_data,$card_titles,$position);
	}

	/*==================================================================*/

	private function __putdataAttendanceForSheet($day)
	{
		
		$start_position=28;
		$controller=new K9DailyReportAttendanceController();
		$attendance_records=$controller->__getAttendanceRecords($day);
		if(empty($attendance_records)) return;

		$position=$start_position;

		//エクセルの空欄数従業員分足りない、テンプレートを修正して空欄を増やして下さい.
		if(count($attendance_records)>self::maximumOutputEmployeeRow) throw new Exception(__("no space for staffs on daily sheet,check here master_data/k9_report.xls"));

		foreach($attendance_records as $k=>$v){

			$this->__putdataAttendanceNameForSheet($position,$v["K9MasterEmployee"]["first_name"]);

			//include del_flg=1.
			if(empty($v["K9DataEmployeeAttendance"]["id"])){
				
				$enter="-  :  -";
				$this->__putdataAttendanceEnterForSheet($position,$enter);

				$leave=$enter;
				$this->__putdataAttendanceLeaveForSheet($position++,$leave);
				continue;
			}

			if(!empty($v["K9DataEmployeeAttendance"]["is_dayoff"])){

				$enter="off";
				$this->__putdataAttendanceEnterForSheet($position,$enter);

				$leave=$enter;
				$this->__putdataAttendanceLeaveForSheet($position++,$leave);
				continue;
			}

			$enter_hour=sprintf("%02d",$v["K9DataEmployeeAttendance"]["enter_hour"]);
			$enter_min =sprintf("%02d",$v["K9DataEmployeeAttendance"]["enter_min"]);
			$leave_hour=sprintf("%02d",$v["K9DataEmployeeAttendance"]["leave_hour"]);
			$leave_min =sprintf("%02d",$v["K9DataEmployeeAttendance"]["leave_min"]);
			$start="{$day}{$enter_hour}{$enter_min}00";
			$end  ="{$day}{$leave_hour}{$leave_min}00";
			$hm=timeDiffWith_H_M($day,$start,$end);

			$enter="{$enter_hour}:{$enter_min}";
			$this->__putdataAttendanceEnterForSheet($position,$enter);

			$leave="{$leave_hour}:{$leave_min}";
			$this->__putdataAttendanceLeaveForSheet($position,$leave);

			$start="{$day}{$enter_hour}{$enter_min}00";
			$end  ="{$day}{$leave_hour}{$leave_min}00";
			$hm=timeDiffWith_H_M($day,$start,$end);
			$wk_time="{$hm["hour"]}H{$hm["min"]}M";
			$this->__putdataAttendanceWkTimeForSheet($position++,$wk_time);
		}
	}

	private function __culcRestAmount($day)
	{
		$controller=new K9DailyReportRestAmountsController();
		$controller->__setOrderModels();
		$amounts=$controller->__culcRestAmount($day);
		return $amounts;
	}

	private function __putdataAttendanceWkTimeForSheet($position,$value)
	{

		$this->__putDataForSheet($value,array(
		
			"position"=>$position,
			"alpha"   =>"K",
			"align"   =>"right"
		));
	}
	
	private function __putdataAttendanceLeaveForSheet($position,$value)
	{

		$this->__putDataForSheet($value,array(
		
			"position"=>$position,
			"alpha"   =>"J",
			"align"   =>"center"
		));
	}
	
	private function __putdataAttendanceEnterForSheet($position,$value)
	{

		$this->__putDataForSheet($value,array(
		
			"position"=>$position,
			"alpha"   =>"I",
			"align"   =>"center"
		));
	}

	private function __putdataAttendanceNameForSheet($position,$value)
	{

		$this->__putDataForSheet($value,array(
		
			"position"=>$position,
			"alpha"   =>"H",
			"align"   =>"left"
		));
	}

	private function __getEmployeeList($day)
	{
		
		$conditions["and"]["K9MasterEmployee.del_flg"]=0;
		$conditions["and"]["DATE_FORMAT(created,'%Y%m%d') <= "]=$day;
		$data=$this->K9MasterEmployee->find("all",array(
		
			"conditions"=>$conditions
		));

		return $data;
	}

	private function __putdataOrdersFoodByCashForSheet($data,$data_extra)
	{
		$breakfast=isset($data["breakfast"])?$data["breakfast"]:array();
		$lunch    =isset($data["lunch"])?$data["lunch"]:array();
		$dinner   =isset($data["dinner"])?$data["dinner"]:array();

		$breakfast_extra=isset($data_extra["breakfast"])?$data_extra["breakfast"]:array();
		$lunch_extra    =isset($data_extra["lunch"])?$data_extra["lunch"]:array();
		$dinner_extra   =isset($data_extra["dinner"])?$data_extra["dinner"]:array();

		$price=0;
		if(isset($breakfast["cash"])) $price+=$this->__getPriceForOrders($breakfast["cash"]);
		if(isset($lunch["cash"]))     $price+=$this->__getPriceForOrders($lunch["cash"]);
		if(isset($dinner["cash"]))    $price+=$this->__getPriceForOrders($dinner["cash"]);

		if(isset($breakfast_extra["cash"])) $price+=$this->__getPriceForOrders($breakfast_extra["cash"]);
		if(isset($lunch_extra["cash"]))     $price+=$this->__getPriceForOrders($lunch_extra["cash"]);
		if(isset($dinner_extra["cash"]))    $price+=$this->__getPriceForOrders($dinner_extra["cash"]);

		$position=7;
		$this->__putDataForSheet($price,array(
		
			"position"=>$position,
			"alpha"   =>"K",
			"align"   =>"right"
		));
	}

	private function __putdataOrdersFoodByCardForSheet($data,$data_extra)
	{

		$breakfast=isset($data["breakfast"])?$data["breakfast"]:array();
		$lunch    =isset($data["lunch"])?$data["lunch"]:array();
		$dinner   =isset($data["dinner"])?$data["dinner"]:array();

		$breakfast_extra=isset($data_extra["breakfast"])?$data_extra["breakfast"]:array();
		$lunch_extra    =isset($data_extra["lunch"])?$data_extra["lunch"]:array();
		$dinner_extra   =isset($data_extra["dinner"])?$data_extra["dinner"]:array();

		$price=0;
		if(isset($breakfast["card"])) $price+=$this->__getPriceForOrders($breakfast["card"]);
		if(isset($lunch["card"]))     $price+=$this->__getPriceForOrders($lunch["card"]);
		if(isset($dinner["card"]))    $price+=$this->__getPriceForOrders($dinner["card"]);

		if(isset($breakfast_extra["card"])) $price+=$this->__getPriceForOrders($breakfast_extra["card"]);
		if(isset($lunch_extra["card"]))     $price+=$this->__getPriceForOrders($lunch_extra["card"]);
		if(isset($dinner_extra["card"]))    $price+=$this->__getPriceForOrders($dinner_extra["card"]);

		$position=16;
		$this->__putDataForSheet($price,array(
		
			"position"=>$position,
			"alpha"   =>"K",
			"align"   =>"right"
		));
	}

	private function __putdataOrdersByCashForSheet($data,$data_extra,$params=array())
	{

		$position=$params["position"];
		$alpha   =$params["alpha"];

		$price=0;
		if(isset($data["cash"])) $price+=$this->__getPriceForOrders($data["cash"]);
		if(isset($data_extra["cash"])) $price+=$this->__getPriceForOrders($data_extra["cash"]);

		$this->__putDataForSheet($price,array(
		
			"position"=>$position,
			"alpha"   =>$alpha,
			"align"   =>"right"
		));
	}

	private function __putdataOrdersByCardForSheet($data,$data_extra,$params=array())
	{

		$position=$params["position"];
		$alpha   =$params["alpha"];

		$price=0;
		if(isset($data["card"])) $price+=$this->__getPriceForOrders($data["card"]);
		if(isset($data_extra["card"])) $price+=$this->__getPriceForOrders($data_extra["card"]);

		$this->__putDataForSheet($price,array(
		
			"position"=>$position,
			"alpha"   =>$alpha,
			"align"   =>"right"
		));
	}

	private function __putdataOrdersEachCashTypesForSheet($order_history,$order_extra_history)
	{

		if(!empty($order_history[K9MasterTobacco::$CATEGORY_TOBACCO]) OR !empty($order_extra_history[K9MasterTobacco::$CATEGORY_TOBACCO])){

			$type      =K9MasterTobacco::$CATEGORY_TOBACCO;
			$data      =isset($order_history[$type])?$order_history[$type]:array();
			$data_extra=isset($order_extra_history[$type])?$order_extra_history[$type]:array();

			$value=array();
			$cash_tobacco=isset($data["tobacco"]["cash"])?$data["tobacco"]["cash"]:array();
			$cash_lighter=isset($data["lighter"]["cash"])?$data["lighter"]["cash"]:array();
			$extra_cash_tobacco=isset($data_extra["tobacco"]["cash"])?$data_extra["tobacco"]["cash"]:array();
			$extra_cash_lighter=isset($data_extra["lighter"]["cash"])?$data_extra["lighter"]["cash"]:array();

			$value["cash"]=array_merge($cash_tobacco,$cash_lighter);
			$extra_value["cash"]=array_merge($extra_cash_tobacco,$extra_cash_lighter);
			$this->__putdataOrdersByCashForSheet($value,$extra_value,array( "position"=>13,"alpha"=>"K" ));

			$value=array();
			$card_tobacco=isset($data["tobacco"]["card"])?$data["tobacco"]["card"]:array();
			$card_lighter=isset($data["lighter"]["card"])?$data["lighter"]["card"]:array();
			$extra_card_tobacco=isset($data_extra["tobacco"]["card"])?$data_extra["tobacco"]["card"]:array();
			$extra_card_lighter=isset($data_extra["lighter"]["card"])?$data_extra["lighter"]["card"]:array();

			$value["card"]=array_merge($card_tobacco,$card_lighter);
			$extra_value["card"]=array_merge($extra_card_tobacco,$extra_card_lighter);
			$this->__putdataOrdersByCardForSheet($value,$extra_value,array( "position"=>22,"alpha"=>"K" ));
		}

		if(!empty($order_history[K9MasterFood::$CATEGORY_FOOD]) OR !empty($order_extra_history[K9MasterFood::$CATEGORY_FOOD])){

			$type      =K9MasterFood::$CATEGORY_FOOD;
			$data      =isset($order_history[$type])?$order_history[$type]:array();
			$data_extra=isset($order_extra_history[$type])?$order_extra_history[$type]:array();
			$this->__putdataOrdersFoodByCashForSheet($data,$data_extra);
			$this->__putdataOrdersFoodByCardForSheet($data,$data_extra);
		}

		if(!empty($order_history[K9MasterBeverage::$CATEGORY_DRINK]) OR !empty($order_extra_history[K9MasterBeverage::$CATEGORY_DRINK])){

			$type      =K9MasterBeverage::$CATEGORY_DRINK;
			$data      =isset($order_history[$type])?$order_history[$type]:array();
			$data_extra=isset($order_extra_history[$type])?$order_extra_history[$type]:array();
			$this->__putdataOrdersByCashForSheet($data,$data_extra,array( "position"=>12,"alpha"=>"K" ));
			$this->__putdataOrdersByCardForSheet($data,$data_extra,array( "position"=>21,"alpha"=>"K" ));
		}

		if(!empty($order_history[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]) OR !empty($order_extra_history[K9MasterRoomservice::$CATEGORY_ROOMSERVICE])){

			$type      =K9MasterRoomservice::$CATEGORY_ROOMSERVICE;
			$data      =isset($order_history[$type])?$order_history[$type]:array();
			$data_extra=isset($order_extra_history[$type])?$order_extra_history[$type]:array();
			$this->__putdataOrdersByCashForSheet($data,$data_extra,array( "position"=>8,"alpha"=>"K" ));
			$this->__putdataOrdersByCardForSheet($data,$data_extra,array( "position"=>17,"alpha"=>"K" ));
		}

		if(!empty($order_history[K9MasterSpa::$CATEGORY_SPA])){

			$data=$order_history[K9MasterSpa::$CATEGORY_SPA];
			$this->__putdataOrdersByCashForSheet($data,null,array( "position"=>9,"alpha"=>"K" ));
			$this->__putdataOrdersByCardForSheet($data,null,array( "position"=>18,"alpha"=>"K" ));
		}

		if(!empty($order_history[K9MasterLimousine::$CATEGORY_LIMOUSINE])){

			$data=$order_history[K9MasterLimousine::$CATEGORY_LIMOUSINE];
			$this->__putdataOrdersByCashForSheet($data,null,array( "position"=>10,"alpha"=>"K" ));
			$this->__putdataOrdersByCardForSheet($data,null,array( "position"=>19,"alpha"=>"K" ));
		}

		if(!empty($order_history[K9MasterLaundry::$CATEGORY_LAUNDRY])){

			$data=$order_history[K9MasterLaundry::$CATEGORY_LAUNDRY];
			$this->__putdataOrdersByCashForSheet($data,null,array( "position"=>11,"alpha"=>"K" ));
			$this->__putdataOrdersByCardForSheet($data,null,array( "position"=>20,"alpha"=>"K" ));
		}
	}

	private function __putdataOrdersForSheet($order_history,$order_extra_history)
	{

		if(!empty($order_history[K9MasterTobacco::$CATEGORY_TOBACCO]) OR !empty($order_extra_history[K9MasterTobacco::$CATEGORY_TOBACCO])){

			$data=$order_history[K9MasterTobacco::$CATEGORY_TOBACCO];
			$data_extra=$order_extra_history[K9MasterTobacco::$CATEGORY_TOBACCO];

			$tobacco=isset($data["tobacco"])?$data["tobacco"]:array();
			$lighter=isset($data["lighter"])?$data["lighter"]:array();
			$extra_tobacco=isset($data_extra["tobacco"])?$data_extra["tobacco"]:array();
			$extra_lighter=isset($data_extra["lighter"])?$data_extra["lighter"]:array();

			$this->__putdataOrdersTobaccoForSheet($tobacco,$extra_tobacco);
			$this->__putdataOrdersLighterForSheet($lighter,$extra_lighter);
		}
	
		if(!empty($order_history[K9MasterFood::$CATEGORY_FOOD]) OR !empty($order_extra_history[K9MasterFood::$CATEGORY_FOOD])){

			$type      =K9MasterFood::$CATEGORY_FOOD;
			$data      =isset($order_history[$type])      ?$order_history[$type]:array();
			$data_extra=isset($order_extra_history[$type])?$order_extra_history[$type]:array();

			$breakfast=isset($data["breakfast"])?$data["breakfast"]:array();
			$lunch    =isset($data["lunch"])?$data["lunch"]:array();
			$dinner   =isset($data["dinner"])?$data["dinner"]:array();
			$breakfast_extra=isset($data_extra["breakfast"])?$data_extra["breakfast"]:array();
			$lunch_extra    =isset($data_extra["lunch"])?$data_extra["lunch"]:array();
			$dinner_extra   =isset($data_extra["dinner"])?$data_extra["dinner"]:array();

			$this->__putdataOrdersFoodBreakfastForSheet($breakfast,$breakfast_extra);
			$this->__putdataOrdersFoodLunchForSheet($lunch,$lunch_extra);
			$this->__putdataOrdersFoodDinnerForSheet($dinner,$dinner_extra);
		}

		if(!empty($order_history[K9MasterBeverage::$CATEGORY_DRINK]) OR !empty($order_extra_history[K9MasterBeverage::$CATEGORY_DRINK])){

			$type      =K9MasterBeverage::$CATEGORY_DRINK;
			$data      =isset($order_history[$type])?$order_history[$type]:array();
			$data_extra=isset($order_extra_history[$type])?$order_extra_history[$type]:array();
			$this->__putdataOrdersDrinkForSheet($data,$data_extra);
		}

		if(!empty($order_history[K9MasterRoomservice::$CATEGORY_ROOMSERVICE]) OR !empty($order_extra_history[K9MasterRoomservice::$CATEGORY_ROOMSERVICE])){

			$type=K9MasterRoomservice::$CATEGORY_ROOMSERVICE;
			$data=isset($order_history[$type])?$order_history[$type]:array();
			$data_extra=isset($order_extra_history[$type])?$order_extra_history[$type]:array();
			$this->__putdataOrdersRoomserviceForSheet($data,$data_extra);
		}

		if(!empty($order_history[K9MasterSpa::$CATEGORY_SPA])){

			$data=$order_history[K9MasterSpa::$CATEGORY_SPA];
			$this->__putdataOrdersSpaForSheet($data);
		}

		if(!empty($order_history[K9MasterLimousine::$CATEGORY_LIMOUSINE])){

			$data=$order_history[K9MasterLimousine::$CATEGORY_LIMOUSINE];
			$this->__putdataOrdersLimousineForSheet($data);
		}

		if(!empty($order_history[K9MasterLaundry::$CATEGORY_LAUNDRY])){

			$data=$order_history[K9MasterLaundry::$CATEGORY_LAUNDRY];
			$this->__putdataOrdersLaundryForSheet($data);
		}
	}

	//part of sales for Lighter.(left side)
	private function __putdataOrdersLighterForSheet($data,$data_extra)
	{
		$position=20;
		$this->__putdataOrdersCountWithPriceForSheet($data,$data_extra,$position);
	}

	//part of sales for Tobacco.(left side)
	private function __putdataOrdersTobaccoForSheet($data,$data_extra)
	{
		$position=19;
		$this->__putdataOrdersCountWithPriceForSheet($data,$data_extra,$position);
	}

	//part of sales for Laundry.(left side)
	private function __putdataOrdersLaundryForSheet($data)
	{
		$position=18;
		$this->__putdataOrdersCountWithPriceForSheet($data,null,$position);
	}

	//part of sales for Limousine.(left side)
	private function __putdataOrdersLimousineForSheet($data)
	{
		$position=17;
		$this->__putdataOrdersCountWithPriceForSheet($data,null,$position);
	}

	//part of sales for Spa.(left side)
	private function __putdataOrdersSpaForSheet($data)
	{
		$position=16;
		$this->__putdataOrdersCountWithPriceForSheet($data,null,$position);
	}

	//part of sales for Drink.(left side)
	private function __putdataOrdersRoomserviceForSheet($data,$data_extra)
	{
		$position=14;
		$this->__putdataOrdersCountWithPriceForSheet($data,$data_extra,$position);
	}

	//part of sales for Drink.(left side)
	private function __putdataOrdersDrinkForSheet($data,$data_extra)
	{
		$position=13;
		$this->__putdataOrdersCountWithPriceForSheet($data,$data_extra,$position);
	}

	//part of sales for Dinner.(left side)
	private function __putdataOrdersFoodDinnerForSheet($data,$data_extra)
	{
		$position=12;
		$this->__putdataOrdersCountWithPriceForSheet($data,$data_extra,$position);
	}

	//part of sales for Lunch.(left side)
	private function __putdataOrdersFoodLunchForSheet($data,$data_extra)
	{
		$position=11;
		$this->__putdataOrdersCountWithPriceForSheet($data,$data_extra,$position);
	}

	//part of sales for Breakfast.(left side)
	private function __putdataOrdersFoodBreakfastForSheet($data,$data_extra)
	{
		$position=10;
		$this->__putdataOrdersCountWithPriceForSheet($data,$data_extra,$position);
	}

	private function __getPriceForOrders($data)
	{
		if(empty($data)) return 0;
		$price=array_sum(Set::extract($data,"{}.price"));
		return $price;
	}

	private function __getCountForOrders($data)
	{
		if(empty($data)) return 0;
		$count=array_sum(Set::extract($data,"{}.count"));
		return $count;
	}

	private function __putdataOrdersCountWithPriceForSheet($data,$data_extra,$position)
	{
		$price=0;
		if(isset($data["cash"])) $price+=$this->__getPriceForOrders($data["cash"]);
		if(isset($data["card"])) $price+=$this->__getPriceForOrders($data["card"]);

		$count=0;
		if(isset($data["cash"])) $count+=$this->__getCountForOrders($data["cash"]);
		if(isset($data["card"])) $count+=$this->__getCountForOrders($data["card"]);

		if(!empty($data_extra)){

			if(isset($data_extra["cash"])) $price+=$this->__getPriceForOrders($data_extra["cash"]);
			if(isset($data_extra["card"])) $price+=$this->__getPriceForOrders($data_extra["card"]);
			if(isset($data_extra["cash"])) $count+=$this->__getCountForOrders($data_extra["cash"]);
			if(isset($data_extra["card"])) $count+=$this->__getCountForOrders($data_extra["card"]);
		}

		$this->__putDataForSheet($count,array(
		
			"position"=>$position,
			"alpha"   =>"E",
			"align"   =>"right"
		));

		$this->__putDataForSheet($price,array(
		
			"position"=>$position,
			"alpha"   =>"F",
			"align"   =>"right"
		));
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

	/*==================================================================*/

	private function __putdataOutputpaymentForSheet($data,$position=27,$counter=0,$stock=array())
	{

		if(empty($data)){

			if(!empty($stock)){

				$price=$this->__getPriceForOrders($stock);
				$this->__putdataOutputpaymentThreethingsForSheet(array(
				
					"store"=>__("その他"),
					"item" =>__("その他"),
					"price"=>$price
	
				),$position);
			}
		
			return $position;
		};

		$_data=array_shift($data);

		if($counter>=self::maximumOutputPaymentRow){
		
			$stock[]=$_data;
			return $this->__putdataOutputpaymentForSheet($data,$position,++$counter,$stock);
		}

		$this->__putdataOutputpaymentThreethingsForSheet(array(
		
			"store"=>$_data["store"],
			"item" =>$_data["item"],
			"price"=>$_data["price"]

		),$position);

		return $this->__putdataOutputpaymentForSheet($data,++$position,++$counter,$stock);
	}

	private function __putdataOutputpaymentThreethingsForSheet($data,$position)
	{

		$store=$data["store"];
		$item =$data["item"];
		$price=$data["price"];

		$this->__putDataForSheet($store,array(
		
			"position"=>$position,
			"alpha"   =>"C",
			"align"   =>"right"
		));

		$this->__putDataForSheet($item,array(
		
			"position"=>$position,
			"alpha"   =>"D",
			"align"   =>"right"
		));

		$this->__putDataForSheet($price,array(
		
			"position"=>$position,
			"alpha"   =>"F",
			"align"   =>"right"
		));
	}

	/*==================================================================*/

	private function __orderHistory($day)
	{
		$controller=new K9DailyReportOrderHistoriesController();
		$controller->__setOrderModels();
		$data=$controller->__orderHistory($day);
		return $data;
	}

	private function __outputPayment($day)
	{

		$this->K9DataOutputPayment->unbindModel(array("belongsTo"=>array("K9MasterEmployee")));
		$data=$this->K9DataOutputPayment->getOutputPaymentByDate($day);
		if(empty($data)) return array();

		$list=array();
		foreach($data as $k=>$v){

			$ymd=date("Ymd",strtotime($v["K9DataOutputPayment"]["day"]));
			if(!isset($list[$ymd])) $list[$ymd]=array();

			$count=count($list[$ymd]);	
			$list[$ymd][$count]["store"]=$v["K9DataOutputPayment"]["store"];
			$list[$ymd][$count]["item"] =$v["K9DataOutputPayment"]["item"];
			$list[$ymd][$count]["price"]=$v["K9DataOutputPayment"]["price"];
		}

		return $list;
	}

	private function __putdataStayInformationForSheet($day)
	{

		$hotel=$this->__hotelStayNumberForHotel($day);
		$rest=$this->__hotelStayNumberForRest($day);
		$this->__putdataStaynumberForSheet($hotel,$rest);

		$hotel_reserve_ids=array();
		$apart_reserve_ids=array();
		if(!empty($hotel)) $hotel_reserve_ids=Set::extract($hotel,"{}.K9DataReservation.id");
		if(!empty($apart)) $apart_reserve_ids=Set::extract($apart,"{}.K9DataReservation.id");

		$reserve_ids=array_merge($hotel_reserve_ids,$apart_reserve_ids);
		$prices=$this->__hotelPrices($reserve_ids,$day);

		$cash=isset($prices["cash"])?$prices["cash"]:array();
		$card=isset($prices["card"])?$prices["card"]:array();
		$this->__putdataStaypriceCashForSheet($cash);
		$this->__putdataStaypriceCardForSheet($card);
	}

	private function __putdataStaypriceCashForSheet($data)
	{
		
		$price=isset($data["price"])?$data["price"]:0;
		$count=isset($data["reserve_ids"])?count($data["reserve_ids"]):0;

		$position=6;
		$this->__putDataForSheet($count,array(
		
			"position"=>$position,
			"alpha"   =>"E",
			"align"   =>"right"
		));

		$this->__putDataForSheet($price,array(
		
			"position"=>$position,
			"alpha"   =>"F",
			"align"   =>"right"
		));
	}

	private function __putdataStaypriceCardForSheet($data)
	{

		$price=isset($data["price"])?$data["price"]:0;
		$count=isset($data["reserve_ids"])?count($data["reserve_ids"]):0;

		$position=7;
		$this->__putDataForSheet($count,array(
		
			"position"=>$position,
			"alpha"   =>"E",
			"align"   =>"right"
		));

		$this->__putDataForSheet($price,array(
		
			"position"=>$position,
			"alpha"   =>"F",
			"align"   =>"right"
		));
	}

	private function __separateForHotelAndApartWithSchedules(&$list=array(),$data=array()){

		foreach($data as $k=>$v){
		
			$schedule_model=$this->__stayTypeModel($v["K9DataReservation"]["staytype"]);
			$schedule=$v[$schedule_model->name];
			$schedule_plans=$v["K9DataReservation"]["K9DataSchedulePlan"];
			$ymd=makeYmdByYmAndD($schedule["start_month_prefix"],$schedule["start_day"]);

			foreach($schedule_plans as $_k=>$_v){
			
				$__ymd=implode("",explode("-",$_v["start"]));
				if($__ymd>$ymd) continue;

				$staytype=$v["K9DataReservation"]["staytype"];
				$type=$_v["K9MasterRoom"]["type"];
				$list[$type][$staytype][]=$v;
				break;
			}
		}
	}

	private function __putdataStaynumberForSheet($hotel=array(),$rest=array())
	{

		$list=array();
		$list[K9MasterRoom::$HOTEL]=array();
		$list[K9MasterRoom::$APART]=array();
		$this->__separateForHotelAndApartWithSchedules($list,$hotel);
		$this->__separateForHotelAndApartWithSchedules($list,$rest);

		$hotel_child_num=0;
		$hotel_adult_num=0;
		$hotel_count=isset($list[K9MasterRoom::$HOTEL]["stay"])?count($list[K9MasterRoom::$HOTEL]["stay"]):0;
		if(!empty($list[K9MasterRoom::$HOTEL]["stay"])){

			$hotel_adult_num=array_sum(Set::extract($list[K9MasterRoom::$HOTEL]["stay"],"{}.K9DataReservation.adults_num"));
			$hotel_child_num=array_sum(Set::extract($list[K9MasterRoom::$HOTEL]["stay"],"{}.K9DataReservation.child_num"));
		}

		$hotel_rest_child_num=0;
		$hotel_rest_adult_num=0;
		$hotel_rest_count=isset($list[K9MasterRoom::$HOTEL]["rest"])?count($list[K9MasterRoom::$HOTEL]["rest"]):0;
		if(!empty($list[K9MasterRoom::$HOTEL]["rest"])){

			$hotel_rest_adult_num=array_sum(Set::extract($list[K9MasterRoom::$HOTEL]["rest"],"{}.K9DataReservation.adults_num"));
			$hotel_rest_child_num=array_sum(Set::extract($list[K9MasterRoom::$HOTEL]["rest"],"{}.K9DataReservation.child_num"));
		}

		$apart_child_num=0;
		$apart_adult_num=0;
		$apart_count     =isset($list[K9MasterRoom::$APART]["stay"])?count($list[K9MasterRoom::$APART]["stay"]):0;
		if(!empty($list[K9MasterRoom::$APART]["stay"])){

			$apart_adult_num=array_sum(Set::extract($list[K9MasterRoom::$APART]["stay"],"{}.K9DataReservation.adults_num"));
			$apart_child_num=array_sum(Set::extract($list[K9MasterRoom::$APART]["stay"],"{}.K9DataReservation.child_num"));
		}
		
		//basically reststay of apartment is nothing.
		$apart_rest_child_num=0;
		$apart_rest_adult_num=0;
		$apart_rest_count=isset($list[K9MasterRoom::$APART]["rest"])?count($list[K9MasterRoom::$APART]["rest"]):0;
		if(!empty($list[K9MasterRoom::$APART]["rest"])){

			$apart_rest_adult_num=array_sum(Set::extract($list[K9MasterRoom::$APART]["rest"],"{}.K9DataReservation.adults_num"));
			$apart_rest_child_num=array_sum(Set::extract($list[K9MasterRoom::$APART]["rest"],"{}.K9DataReservation.child_num"));
		}

		$this->__putdataStaynumberHotelStayForSheet(array(
		
			"adult_num" =>$hotel_adult_num,
			"child_num" =>$hotel_child_num,
		));

		$this->__putdataStaynumberHotelRestForSheet(array(
		
			"adult_num" =>$hotel_rest_adult_num,
			"child_num" =>$hotel_rest_child_num,
		));

		$this->__putdataStaynumberApartForSheet(array(
		
			"adult_num" =>$apart_adult_num,
			"child_num" =>$apart_child_num,
		));

		$this->__putdataRoomcountHotelStayForSheet(array( "room_count"=>$hotel_count ));
		$this->__putdataRoomcountHotelRestForSheet(array( "room_count"=>$hotel_rest_count ));
		$this->__putdataRoomcountApartForSheet(array( "room_count"=>$apart_count ));

		$data=isset($list[K9MasterRoom::$HOTEL]["stay"])?$list[K9MasterRoom::$HOTEL]["stay"]:array();
		$this->__putdataPaymentSituationHotelStayForSheet($data);

		$data=isset($list[K9MasterRoom::$HOTEL]["rest"])?$list[K9MasterRoom::$HOTEL]["rest"]:array();
		$this->__putdataPaymentSituationHotelRestForSheet($data);

		$data=isset($list[K9MasterRoom::$APART]["stay"])?$list[K9MasterRoom::$APART]["stay"]:array();
		$this->__putdataPaymentSituationApartForSheet($data);
	}

	private function __putdataPaymentSituationApartForSheet($apart)
	{

		$payment_situation["0"]=0;
		$payment_situation["1"]=0;
		foreach($apart as $k=>$v){

			$is_payment=$v["K9DataReservation"]["K9DataCheckoutPayment"]["purchase_flg"]?1:0;
			$payment_situation[$is_payment]++;
		}

		$position=4;
		$this->__putDataForSheet($payment_situation[1],array(
		
			"position"=>$position,
			"alpha"   =>"I",
			"align"   =>"right"
		));

		$this->__putDataForSheet($payment_situation[0],array(
		
			"position"=>$position,
			"alpha"   =>"K",
			"align"   =>"right"
		));
	}

	private function __putdataPaymentSituationHotelRestForSheet($hotel)
	{

		$payment_situation["0"]=0;
		$payment_situation["1"]=0;
		foreach($hotel as $k=>$v){

			$staytype=$v["K9DataReservation"]["staytype"];
			if($staytype!="rest") continue;
			$is_payment=(empty($v["K9DataReservation"]["K9DataCheckoutPayment"]) OR !$v["K9DataReservation"]["K9DataCheckoutPayment"]["purchase_flg"])?0:1;
			$payment_situation[$is_payment]++;
		}

		$position=3;
		$this->__putDataForSheet($payment_situation[1],array(
		
			"position"=>$position,
			"alpha"   =>"I",
			"align"   =>"right"
		));

		$this->__putDataForSheet($payment_situation[0],array(
		
			"position"=>$position,
			"alpha"   =>"K",
			"align"   =>"right"
		));
	}

	private function __putdataPaymentSituationHotelStayForSheet($hotel)
	{

		$payment_situation["0"]=0;
		$payment_situation["1"]=0;
		foreach($hotel as $k=>$v){

			$staytype=$v["K9DataReservation"]["staytype"];
			if($staytype!="stay") continue;
			$is_payment=(empty($v["K9DataReservation"]["K9DataCheckoutPayment"]) OR !$v["K9DataReservation"]["K9DataCheckoutPayment"]["purchase_flg"])?0:1;
			$payment_situation[$is_payment]++;
		}

		$position=2;
		$this->__putDataForSheet($payment_situation[1],array(
		
			"position"=>$position,
			"alpha"   =>"I",
			"align"   =>"right"
		));

		$this->__putDataForSheet($payment_situation[0],array(
		
			"position"=>$position,
			"alpha"   =>"K",
			"align"   =>"right"
		));
	}

	private function __putdataRoomcountHotelRestForSheet($data)
	{

		$position=3;
		$room_count =$data["room_count"];
		$total=$room_count;

		$this->__putDataForSheet($total,array(
		
			"position"=>$position,
			"alpha"   =>"E",
			"align"   =>"right"
		));
	}

	private function __putdataRoomcountHotelStayForSheet($data)
	{

		$position=2;
		$room_count =$data["room_count"];
		$total=$room_count;

		$this->__putDataForSheet($total,array(
		
			"position"=>$position,
			"alpha"   =>"E",
			"align"   =>"right"
		));
	}

	private function __putdataRoomcountApartForSheet($data)
	{

		$position=4;
		$room_count =$data["room_count"];
		$total=$room_count;

		$this->__putDataForSheet($total,array(
		
			"position"=>$position,
			"alpha"   =>"E",
			"align"   =>"right"
		));
	}

	private function __putdataStaynumberHotelRestForSheet($data)
	{

		$position=3;
		$adult_num =$data["adult_num"];
		$child_num =$data["child_num"];
		$total=$adult_num+$child_num;

		$this->__putDataForSheet($total,array(
		
			"position"=>$position,
			"alpha"   =>"F",
			"align"   =>"right"
		));
	}
	
	private function __putdataStaynumberHotelStayForSheet($data)
	{

		$position=2;
		$adult_num =$data["adult_num"];
		$child_num =$data["child_num"];
		$total=$adult_num+$child_num;

		$this->__putDataForSheet($total,array(
		
			"position"=>$position,
			"alpha"   =>"F",
			"align"   =>"right"
		));
	}

	private function __putdataStaynumberApartForSheet($data)
	{

		$position=4;
		$adult_num =$data["adult_num"];
		$child_num =$data["child_num"];
		$total=$adult_num+$child_num;

		$this->__putDataForSheet($total,array(
		
			"position"=>$position,
			"alpha"   =>"F",
			"align"   =>"right"
		));
	}

	private function __hotelStayNumberForHotel($day)
	{
		$hotel=$this->__getScheduleByDate($this->K9DataSchedule,$day);
		return $hotel;
	}

	private function __hotelStayNumberForRest($day)
	{
		$rest=$this->__getScheduleByDate($this->K9DataReststaySchedule,$day);
		return $rest;
	}

	private function __getScheduleByDate(Model $schedule_model,$ymd)
	{

		$association=$this->K9DataReservation->association;
		$association_outputpayment=$association["hasOne"];

		$association_scheduleplan=$association["hasMany"]["K9DataSchedulePlan"];
		$association_scheduleplan["conditions"]["and"]["K9DataSchedulePlan.del_flg"]=0;
		$association_scheduleplan["order"]=array("K9DataSchedulePlan.start DESC");

		$this->K9DataCheckoutPayment->unbindModel(array("belongsTo"=>array("K9DataReservation")));
		$this->K9DataReservation->bindModel(array("hasOne" =>$association_outputpayment));
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedulePlan"=>$association_scheduleplan)));

		$this->K9DataReservation->unbindModel(array("belongsTo"=>array("K9DataGuest")));
		$hotel=$schedule_model->getScheduleByDate($ymd,array(
		
			"recursive"=>3
		));

		return $hotel;
	}

	private function __hotelPrices($reserve_ids,$day)
	{
		$controller=new K9DailyReportHotelPricesController();
		$data=$controller->__hotelPrices($reserve_ids,$day);
		return $data;
	}

}//END class

?>
