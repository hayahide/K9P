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
class AdK9MasterEmployeeAccount extends AppModel{

    var $name = "K9MasterEmployeeAccount";
    var $useTable = "k9_master_employee_accounts";
    var $primaryKey = "employee_id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9MasterEmployee' => array(
			'className' => 'K9MasterEmployee',
			'foreignKey' => 'employee_id',
			'conditions' => array('K9MasterEmployee.del_flg'=>'0'),
		),
	);


}
