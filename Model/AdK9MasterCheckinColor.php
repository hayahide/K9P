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
class AdK9MasterCheckinColor extends AppModel{

    var $name = "K9MasterCheckinColor";
    var $useTable = "k9_master_checkin_colors";
    var $primaryKey = "id";
	var $useDbConfig="master";

	public static $UNCHECKIN="normal";
	public static $CHECKIN="checkin";
	public static $CHECKOUT="checkout";
    public static $UNAVAILABLE="unavailable";
	public static $DIPOSIT="diposit";

}
