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
class AdK9DataHistoryPriceFood extends AppModel{

    var $name = "K9DataHistoryPriceFood";
    var $useTable = "k9_data_history_price_food";
    var $primaryKey = "id";
	var $useDbConfig="default";
	var $foreignKey ="food_id";

	public $belongsTo = array(

		'K9MasterFood' => array(

			'className' => 'K9MasterFood',
			'foreignKey' => 'food_id',
			//'conditions' => array("K9MasterFood.del_flg"=>'0'),
		),
		'K9MasterEmployee' => array(

			'className' => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered',
			'conditions' => array("K9MasterEmployee.del_flg"=>'0'),
		),
	);

}
