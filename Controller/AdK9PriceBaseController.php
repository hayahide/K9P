<?php

class AdK9PriceBaseController extends AppController {

	var $uses = [

		"K9DataPriceParRoom",
		"K9DataPriceRoomType",
		"K9MasterRoomType",
		"K9DataReservation",
		"K9DataSchedulePlan",
		"K9MasterRoom",
		"K9DataHistoryPriceRoomType",
		"K9DataHistoryPriceRoom",
		"K9DataHistoryPriceReststay"
	];

	public static $PRICE_BASE_ROOM=1;
	public static $PRICE_BASE_ROOM_TYPE=2;
	public static $PRICE_EXCEPTION_ROOM=3;
	public static $PRICE_EXCEPTION_ROOM_TYPE=4;
	public static $PRICE_WEEKEND_FORCE=5;
	public static $PRICE_WEEKDAY_FORCE=6;
	public static $PRICE_RESTSTAY=7;

	public function beforeFilter() {

		parent::beforeFilter();
	}

}
