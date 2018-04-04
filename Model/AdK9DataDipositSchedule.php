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
class AdK9DataDipositSchedule extends AppModel{

    var $name = "K9DataDipositSchedule";
    var $useTable = "k9_data_diposit_schedules";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9DataSchedule' => array(

			'className' => 'K9DataSchedule',
			'foreignKey' => 'schedule_id',
		),
		'K9MasterCard' => array(

			'className' => 'K9MasterCard',
			'foreignKey' => 'cash_type_id',
		),
		'K9MasterDipositReason' => array(

			'className' => 'K9MasterDipositReason',
			'foreignKey' => 'reason_id',
		),
		'K9MasterEmployee' => array(

			'className' => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered',
		),
	);
}
