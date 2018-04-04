<?php

App::uses('K9PriceBaseController','Controller');
class AdK9RestPriceController extends K9PriceBaseController{

	var $name = 'K9RestPrice';

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function __getPriceHistory($reststay_ids=array()){

		$w=null;
		if(!empty($reststay_ids)) $w["and"]["K9DataHistoryPriceReststay.reststay_id"]=$reststay_ids;
		$data=$this->K9DataHistoryPriceReststay->find("all",array(
		
			"conditions"=>$w,
			"order"=>array("K9DataHistoryPriceReststay.start ASC"),
			"recursive"=>2
		));

		return $data;
	}

	function __getReststayPrice($params=array()){

		$start=$params["start_date"];
		$end  =$params["end_date"];

		$data=$this->__getPriceHistory();

		$list=array();
		foreach($data as $k=>$v){

			$count=count($list);
			$start=$v["K9DataHistoryPriceReststay"]["start"];
			$list[$count]["date"]["start"]=date("Ymd",strtotime($start));
			$list[$count]["price"]=$v["K9DataHistoryPriceReststay"]["price"];
			$list[$count]["data"]["history_id"]=$v["K9DataHistoryPriceReststay"]["id"];
			$list[$count]["data"]["data_id"]=$v["K9MasterReststay"]["id"];

			//次なし
			if(!isset($data[$k+1])){
			
				break;
			}

			$next=$data[$k+1];
			$end=$next["K9DataHistoryPriceReststay"]["start"];
			$list[$count]["date"]["end"]=date("Ymd",strtotime("-1 day",strtotime($end)));
		}

		return $list;
	}

}
