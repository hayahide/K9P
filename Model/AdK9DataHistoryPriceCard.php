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
class AdK9DataHistoryPriceCard extends AppModel{

    var $name = "K9DataHistoryPriceCard";
    var $useTable = "k9_data_history_price_card";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public static $TYPE_INCOME_DAY="income_day";
	public static $TYPE_RATE="rate";

	var $association=array(

		'K9MasterEmployee' => array(
	
			'className' => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered',
			'conditions' => array("K9MasterEmployee.del_flg"=>'0'),
		),
		'K9MasterCard' => array(
	
			'className'  => 'K9MasterCard',
			'foreignKey' => 'card_id',
		),
	);
}
