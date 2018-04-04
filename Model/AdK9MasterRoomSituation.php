<?php

/*
 * Copyright 2017 SPCVN Co., Ltd.
 * All right reserved.
*/

/**
 * @Author: Naoki Kiyosawa
 * @Date:   2017-10-31 17:38:35
 */

App::uses('AppModel', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AdK9MasterRoomSituation extends AppModel{

    var $name = "K9MasterRoomSituation";
    var $useTable = "k9_master_room_situations";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public static $SITUATION_CLEAN      =1;
	public static $SITUATION_DIRTY      =2;
	public static $SITUATION_UNAVAILABLE=3;
	public static $SITUATION_INSPECTED  =4;
	public static $TYPE_AVAILABLE       ="available";
	public static $TYPE_UNAVAILABLE     ="unavailable";


}
