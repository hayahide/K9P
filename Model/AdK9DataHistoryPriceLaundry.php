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
class AdK9DataHistoryPriceLaundry extends AppModel{

    var $name       ="K9DataHistoryPriceLaundry";
    var $useTable   ="k9_data_history_price_laundry_price";
    var $primaryKey ="id";
	var $useDbConfig="default";
	var $foreignKey ="laundry_id";

	public $belongsTo = array(

		'K9MasterLaundry' => array(

			'className' => 'K9MasterLaundry',
			'foreignKey' => 'laundry_id',
			//'conditions' => array("K9MasterLaundry.del_flg"=>'0'),
		),
		'K9MasterEmployee' => array(

			'className' => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered',
			'conditions' => array("K9MasterEmployee.del_flg"=>'0'),
		),
	);

}
