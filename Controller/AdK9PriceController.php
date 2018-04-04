<?php

App::uses('K9PriceBaseController','Controller');
class AdK9PriceController extends K9PriceBaseController{

	var $name = 'K9Price';

	public function beforeFilter() {

		parent::beforeFilter();
	}

	function __getRoomPriceHistory($room_ids=array()){

		$w=null;
		$w["and"]["K9DataHistoryPriceRoom.room_id"]=$room_ids;
		$this->K9MasterRoom->unbindModel(array("belongsTo"=>array("K9MasterRoomSituation")));
		$data=$this->K9DataHistoryPriceRoom->find("all",array(
		
			"conditions"=>$w,
			"order"=>array("K9DataHistoryPriceRoom.start ASC"),
			"recursive"=>2
		));

		return $data;
	}

	function __getRoomBasePrice($room_ids=array(),$params=array()){

		$data=$this->__getRoomPriceHistory($room_ids);
		if(empty($data)) throw new Exception("no base price ".__FUNCTION__);

		$__data=array();
		foreach($data as $k=>$v){

			if(!isset($__data[$v["K9DataHistoryPriceRoom"]["room_id"]])) $__data[$v["K9DataHistoryPriceRoom"]["room_id"]]=array();
			$count=count($__data[$v["K9DataHistoryPriceRoom"]["room_id"]]);
			$__data[$v["K9DataHistoryPriceRoom"]["room_id"]][$count]["K9DataHistoryPriceRoom"]=$v["K9DataHistoryPriceRoom"];
			$__data[$v["K9DataHistoryPriceRoom"]["room_id"]][$count]["K9MasterRoom"]=$v["K9MasterRoom"];
		} 

		$base_price_par_room=array();
		foreach($__data as $room_id=>$par_room_data){

			//v($par_room_data);
			foreach($par_room_data as $k=>$v){

				$price_history=$v["K9DataHistoryPriceRoom"];
				$master_room=$v["K9MasterRoom"];
				$master_room_type=$v["K9MasterRoom"]["K9MasterRoomType"];
			
				$id=$price_history["id"];
				$price=$price_history["price"];
				$start=date("Ymd",strtotime($price_history["start"]));

				if(!isset($par_room_data[$k+1])){

					$base_price_par_room[$room_id][$start]["room_type_id"]=$master_room_type["id"];
					$base_price_par_room[$room_id][$start]["price"]=$price;
					$base_price_par_room[$room_id][$start]["data_id"]=$id;
					$base_price_par_room[$room_id][$start]["status"]=self::$PRICE_BASE_ROOM;
					$base_price_par_room[$room_id][$start]["price_base_status"]=$base_price_par_room[$room_id][$start]["status"];
					$base_price_par_room[$room_id][$start]["base_price"]=$base_price_par_room[$room_id][$start]["price"];
					break;
				} 

				$next=$par_room_data[$k+1];
				//$next_start=date("Ymd",strtotime("-1 day",strtotime($next["start"])));
				$next_start=date("Ymd",strtotime("-1 day",strtotime($next["K9DataHistoryPriceRoom"]["start"])));
				$is_in=(($start>=$params["start_date"] AND $params["end_date"]>=$start) OR ($next_start>=$params["start_date"] AND $params["end_date"]>=$next_start));
				if(!$is_in) continue;

				if($start>$next_start) throw new Exception("start date is ahead,this is wrong ".__FUNCTION__);
				$period=makeDatePeriod($start,$next_start);
				foreach($period as $ymd=>$v){

					$base_price_par_room[$room_id][$ymd]["room_type_id"]=$master_room_type["id"];
					$base_price_par_room[$room_id][$ymd]["price"]=$price;
					$base_price_par_room[$room_id][$ymd]["data_id"]=$id;
					$base_price_par_room[$room_id][$ymd]["status"]=self::$PRICE_BASE_ROOM;
					$base_price_par_room[$room_id][$ymd]["price_base_status"]=$base_price_par_room[$room_id][$ymd]["status"];
					$base_price_par_room[$room_id][$ymd]["base_price"]=$base_price_par_room[$room_id][$ymd]["price"];
				} 
			}
		}

		return $base_price_par_room;
	}

	function __getRoomTypeBasePrice($room_type_ids=array(),$params=array()){

		$w=null;
		$w["and"]["K9DataHistoryPriceRoomType.room_type_id"]=$room_type_ids;
		//$w["and"]["DATE_FORMAT(K9DataRoomTypeHistory.start,'%Y%m%d') between ? AND ?"]=array($params["start_date"],$params["end_date"]);
		$this->K9DataHistoryPriceRoomType->unbindFully();
		$data=$this->K9DataHistoryPriceRoomType->find("all",array(
		
			"conditions"=>$w,
			"order"=>array("K9DataHistoryPriceRoomType.start ASC"),
		));

		if(empty($data)) throw new Exception("no base price ".__FUNCTION__);

		$__data=array();
		foreach($data as $k=>$v) $__data[$v["K9DataHistoryPriceRoomType"]["room_type_id"]][]=$v["K9DataHistoryPriceRoomType"];

		$base_price_par_room_type=array();
		foreach($__data as $room_type_id=>$par_room_type_data){

			foreach($par_room_type_data as $k=>$v){
			
				$id   =$v["id"];  
				$price=$v["price"];
				$start=date("Ymd",strtotime($v["start"]));

				if(!isset($par_room_type_data[$k+1])){
				
					$base_price_par_room_type[$room_type_id][$start]["price"]  =$price;
					$base_price_par_room_type[$room_type_id][$start]["data_id"]=$id;
					$base_price_par_room_type[$room_type_id][$start]["status"]=self::$PRICE_BASE_ROOM_TYPE;
					$base_price_par_room_type[$room_type_id][$start]["price_base_status"]=$base_price_par_room_type[$room_type_id][$start]["status"];
					$base_price_par_room_type[$room_type_id][$start]["base_price"]=$base_price_par_room_type[$room_type_id][$start]["price"];
					break;
				} 

				$next=$par_room_type_data[$k+1];
				$next_start=date("Ymd",strtotime("-1 day",strtotime($next["start"])));
				$is_in=(($start>=$params["start_date"] AND $params["end_date"]>=$start) OR ($next_start>=$params["start_date"] AND $params["end_date"]>=$next_start));
				if(!$is_in) continue;

				if($start>$next_start) throw new Exception("start date is ahead,this is wrong ".__FUNCTION__);
				$period=makeDatePeriod($start,$next_start);
				foreach($period as $ymd=>$v){

					$base_price_par_room_type[$room_type_id][$ymd]["price"]=$price;
					$base_price_par_room_type[$room_type_id][$ymd]["data_id"]=$id;
					$base_price_par_room_type[$room_type_id][$ymd]["status"]=self::$PRICE_BASE_ROOM_TYPE;
					$base_price_par_room_type[$room_type_id][$ymd]["price_base_status"]=$base_price_par_room_type[$room_type_id][$ymd]["status"];
					$base_price_par_room_type[$room_type_id][$ymd]["base_price"]=$base_price_par_room_type[$room_type_id][$ymd]["price"];
				} 
			}
		}

		return $base_price_par_room_type;
	}

	function __getPrice($reserve_ids=array(),$params=array()){

		$k9_plans=$this->__getPlansByReservations($reserve_ids);
		if(empty($k9_plans)) throw new Exception("no plans.");

		$start_date=$params["start_date"];
		$end_date  =$params["end_date"];
		$room_type_ids=array_unique(Set::extract($k9_plans,"{}.K9MasterRoom.room_type_id"));
		$room_ids     =array_unique(Set::extract($k9_plans,"{}.K9DataSchedulePlan.room_id"));

		//v($room_type_ids);
		$base_room_type_price=$this->__getRoomTypeBasePrice($room_type_ids,array(
		
			"start_date"=>$start_date,
			"end_date"  =>$end_date
		));
	//	v($base_room_type_price);

		//v($room_ids);
		$base_room_price=$this->__getRoomBasePrice($room_ids,array(
		
			"start_date"=>$start_date,
			"end_date"  =>$end_date
		));

		$room_type_price=$this->__roomTypeRealPrice($room_type_ids,array(
		
			"start_date"=>$start_date,
			"end_date"  =>$end_date,
			"base_room_type_price"=>$base_room_type_price,
			"base_room_price"=>$base_room_price
		));
		//v($room_type_price);

		$room_price_rate=$this->__roomRealPrice($room_ids,array(
		
			"start_date"=>$start_date,
			"end_date"  =>$end_date,
			"room_type_price"=>$room_type_price,           //RoomTypeの特定割引情報
			"base_room_type_price"=>$base_room_type_price, //RoomTypeの期間内の日付に漏れがない情報
			"base_room_price"     =>$base_room_price       //Roomの期間内の日付に漏れがない情報
		));

		//v($room_price_rate);
		$res["base_room_type_price"]=$base_room_type_price;
		$res["base_room_price"]=$base_room_price;
		$res["room_type"]=$room_type_price;
		$res["room"]=$room_price_rate;
		return $res;
	}

	function __roomRealPrice($room_ids=array(),$params=array()){

		//k9_data_price_room_typesに設定された特別割引情報
		$room_price_rate =$this->__roomPriceRate($room_ids,array(

			"start_date"=>$params["start_date"],
			"end_date"  =>$params["end_date"],
		));

		if(empty($room_price_rate)) return array();

		$res=array();
		$base_room_type_prices=$params["base_room_type_price"];
		$base_room_prices=$params["base_room_price"];

		$cache_room_dates=array();
		$cache_room_type_dates=array();
		foreach($room_price_rate as $room_id=>$v){
		
			foreach($v as $ymd=>$_v){

				if(!empty($_v["price"])){

					$res[$room_id][$ymd]["rate_par"]  =$_v["par"];
					$res[$room_id][$ymd]["rate_price"]=(Double)$_v["price"];
					$res[$room_id][$ymd]["price"] =(Double)$_v["price"];
					$res[$room_id][$ymd]["data_id"]=$_v["data_id"];
					$res[$room_id][$ymd]["status"]=$_v["status"];
					$res[$room_id][$ymd]["par"]   =0;
					$res[$room_id][$ymd]["price_base_status"]=$_v["status"];
					$res[$room_id][$ymd]["base_price"]=$_v["price"];
					continue;
				}

				$room_type_id=$_v["room_type_id"];
				$room_type_dates=(isset($cache_room_type_dates[$room_type_id]))?$cache_room_type_dates[$room_type_id]:array_keys($base_room_type_prices[$room_type_id]);
				$room_dates=(isset($cache_room_dates[$room_id]))?$cache_room_dates[$room_id]:array_keys($base_room_prices[$room_id]);

				$cache_room_dates[$room_id]=$room_dates;
				$cache_room_type_dates[$room_type_id]=$room_type_dates;

				$room_last_date=$room_dates[count($room_dates)-1];
				$room_type_last_date=$room_type_dates[count($room_type_dates)-1];

				switch(true){

					//Roomに対し期間内金額設定済み(0でない)
				case(isset($base_room_prices[$room_id][$ymd]) AND !empty($base_room_prices[$room_id][$ymd]["price"])):

					$base_room_type_price=$base_room_prices[$room_id][$ymd]["price"];
					$data_id=$base_room_prices[$room_id][$ymd]["data_id"];
					$status=$base_room_prices[$room_id][$ymd]["status"];
					break;

					//Roomに対し期間外で金額設定済み
				case(!empty($base_room_prices[$room_id][$room_last_date]["price"])):

					$base_room_type_price=$base_room_prices[$room_id][$room_last_date]["price"];
					$data_id=$base_room_prices[$room_id][$room_last_date]["data_id"];
					$status=$base_room_prices[$room_id][$room_last_date]["status"];
					break;

					//RoomTypeでの期間内で金額設定済み(0が設定されている事は無い)
				case(isset($base_room_type_prices[$room_type_id][$ymd])):

					$base_room_type_price=$base_room_type_prices[$room_type_id][$ymd]["price"];
					$data_id=$base_room_type_prices[$room_type_id][$ymd]["data_id"];
					$status=$base_room_type_prices[$room_type_id][$ymd]["status"];
					//$status=$base_room_type_prices[$room_id][$ymd]["status"];
					break;

					//RoomTypeでの期間外
				case(isset($base_room_type_prices[$room_type_id][$room_type_last_date])):

					$base_room_type_price=$base_room_type_prices[$room_type_id][$room_type_last_date]["price"];
					$data_id=$base_room_type_prices[$room_type_id][$room_type_last_date]["data_id"];
					$status=$base_room_type_prices[$room_type_id][$room_type_last_date]["status"];
					//$status=$base_room_type_prices[$room_id][$room_type_last_date]["status"];
					break;

				default:

					//有りえない
					throw new Exception(" could't get base price ".__FUNCTION__);
					break;
				}

				$res[$room_id][$ymd]["rate_par"]  =$_v["par"];
				$res[$room_id][$ymd]["rate_price"]=$_v["price"];
				$res[$room_id][$ymd]["price"]  =round($base_room_type_price*($_v["par"]/100),1);
				$res[$room_id][$ymd]["data_id"]=$data_id;
				$res[$room_id][$ymd]["par"]    =$_v["par"];
				$res[$room_id][$ymd]["status"] =self::$PRICE_EXCEPTION_ROOM;
				$res[$room_id][$ymd]["price_base_status"]=$status;
				$res[$room_id][$ymd]["base_price"]=$base_room_type_price;
			}
		}

		return $res;
	}

	function __roomTypeRealPrice($room_type_ids=array(),$params=array()){

		//v($params);
		$room_type_rate =$this->__roomTypeRate($room_type_ids,array(

			"start_date"=>$params["start_date"],
			"end_date"  =>$params["end_date"],
		));

		//v($room_type_rate);

		if(empty($room_type_rate)) return array();

		$res=array();
		$base_prices=$params["base_room_type_price"];
		$base_room_prices=$params["base_room_price"];
		foreach($room_type_rate as $room_type_id=>$v){

			$dates=array_keys($base_prices[$room_type_id]);

			foreach($v as $ymd=>$_v){

				// forced.
				if(!empty($_v["price"])){

					$res[$room_type_id][$ymd]["rate_par"]  =0;
					$res[$room_type_id][$ymd]["rate_price"]=(Double)$_v["price"];
					$res[$room_type_id][$ymd]["price"]  =(Double)$_v["price"];
					$res[$room_type_id][$ymd]["data_id"]=$_v["data_id"];
					$res[$room_type_id][$ymd]["status"] =$_v["status"];
					$res[$room_type_id][$ymd]["par"]    =0;
					$res[$room_type_id][$ymd]["price_base_status"]=$_v["status"];
					$res[$room_type_id][$ymd]["base_price"]=$_v["price"];
					continue;
				}

				$last_date=$dates[count($dates)-1];

				switch(true){
				
				case(isset($base_prices[$room_type_id][$ymd]) AND !empty($base_prices[$room_type_id][$ymd]["price"])):

					$base_price=$base_prices[$room_type_id][$ymd]["price"];
					//v($base_price);
					$data_id=$base_prices[$room_type_id][$ymd]["data_id"];
					$status=$base_prices[$room_type_id][$ymd]["status"];
					break;

				case(isset($base_prices[$room_type_id][$last_date]) AND !empty($base_prices[$room_type_id][$last_date]["price"])):

					$base_price=$base_prices[$room_type_id][$last_date]["price"];
					$data_id=$base_prices[$room_type_id][$last_date]["data_id"];
					$status=$base_prices[$room_type_id][$last_date]["status"];
					break;

				default:

					throw new Exception("couldn't get base price ".__FUNCTION__);
					break;
				}

				//v($base_price);
				$res[$room_type_id][$ymd]["rate_par"]  =$_v["par"];
				$res[$room_type_id][$ymd]["rate_price"]=$_v["price"];
				$res[$room_type_id][$ymd]["price"]  =round($base_price*($_v["par"]/100),1);
				$res[$room_type_id][$ymd]["data_id"]=$data_id;
				$res[$room_type_id][$ymd]["par"]    =$_v["par"];
				$res[$room_type_id][$ymd]["status"] =self::$PRICE_EXCEPTION_ROOM_TYPE;
				$res[$room_type_id][$ymd]["price_base_status"]=$status;
				$res[$room_type_id][$ymd]["base_price"]=$base_price;
			}
		}

		return $res;
	}

	function __roomPriceRate($room_ids=array(),$params=array()){

		$start_date=$params["start_date"];
		$end_date  =$params["end_date"];

		$data=$this->K9DataPriceParRoom->getDataByRoomIdWithRelationDays($room_ids,$start_date,$end_date);
		if(empty($data)) return array();

		$res=array();
		foreach($data as $k=>$v){

			$id      =$v["K9DataPriceParRoom"]["id"];
			$room_id =$v["K9DataPriceParRoom"]["room_id"];
			$price   =$v["K9DataPriceParRoom"]["price"];
			$par     =$v["K9DataPriceParRoom"]["par"];
			$room_type_id=$v["K9MasterRoom"]["room_type_id"];

			$__start=$v["K9DataPriceParRoom"]["start"];
			$__end  =$v["K9DataPriceParRoom"]["end"];
			$period=makeDatePeriod($__start,$__end);
			foreach($period as $ymd=>$_v){

				if(!empty($price) AND !empty($par)) throw new Exception(__FUNCTION__." rate confrict on {$ymd}.");
				$res[$room_id][$ymd]["price"]=$price;
				$res[$room_id][$ymd]["par"]  =$par;
				$res[$room_id][$ymd]["room_type_id"]=$room_type_id;
				$res[$room_id][$ymd]["data_id"]=$id;
				$res[$room_id][$ymd]["status"]=self::$PRICE_EXCEPTION_ROOM;
			}
		}

		return $res;
	}

	function __roomTypeRate($room_type_ids=array(),$params=array()){

		$start_date=$params["start_date"];
		$end_date  =$params["end_date"];

		if(!$data=$this->K9DataPriceRoomType->getDataByRoomIdWithRelationDays($room_type_ids,$start_date,$end_date)) return array();

		$res=array();
		foreach($data as $k=>$v){

			$id          =$v["K9DataPriceRoomType"]["id"];
			$room_type_id=$v["K9DataPriceRoomType"]["room_type_id"];
			$price       =$v["K9DataPriceRoomType"]["price"];
			$par         =$v["K9DataPriceRoomType"]["par"];

			$__start=$v["K9DataPriceRoomType"]["start"];
			$__end  =$v["K9DataPriceRoomType"]["end"];
			//v($__end);
			$period=makeDatePeriod($__start,$__end);
			//v($period);
			foreach($period as $ymd=>$_v){

				if(!empty($price) AND !empty($par)) throw new Exception(__FUNCTION__." rate confrict on {$ymd}.");
				$res[$room_type_id][$ymd]["price"]=$price;
				$res[$room_type_id][$ymd]["par"]  =$par;
				$res[$room_type_id][$ymd]["data_id"]=$id;
				$res[$room_type_id][$ymd]["status"]=self::$PRICE_EXCEPTION_ROOM_TYPE;
			}
		}

		return $res;
	}

	function __getPlansByReservations($reserve_ids=array(),$del_flg=0){

		$w=null;
		$w["and"]["K9DataSchedulePlan.reserve_id"]=$reserve_ids;
		if(is_numeric($del_flg)) $w["and"]["K9DataSchedulePlan.del_flg"]=$del_flg;
		$k9_plans=$this->K9DataSchedulePlan->find("all",array(

			"conditions"=>$w,
			"recursive"=>2
		));

		return $k9_plans;
	}

}
