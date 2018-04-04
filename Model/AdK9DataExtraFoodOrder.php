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
class AdK9DataExtraFoodOrder extends AppModel{

    var $name = "K9DataExtraFoodOrder";
    var $useTable = "k9_data_extra_food_orders";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9MasterFood' => array(

			'className' => 'K9MasterFood',
			'foreignKey' => 'master_id'
		),
		'K9MasterEmployee' => array(

			'className' => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered'
		),
		'K9MasterCard' => array(

			'className' => 'K9MasterCard',
			'foreignKey' => 'cash_type_id'
		),
		'K9DataExtraOrder' => array(

			'className' => 'K9DataExtraOrder',
			'foreignKey' => 'group_id'
		),
		'K9DataHistoryPriceFood' => array(

			'className' => 'K9DataHistoryPriceFood',
			'foreignKey' => 'price_id'
		),
	);

}
