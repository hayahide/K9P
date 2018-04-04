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
class AdK9DataOutputPayment extends AppModel{

    var $name = "K9DataOutputPayment";
    var $useTable = "k9_data_output_payments";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9MasterEmployee' => array(

			'className' => 'K9MasterEmployee',
			'foreignKey'=> 'final_employee_entered',
			'conditions'=> array('K9MasterEmployee.del_flg' => '0'),
		),
	);

	public function getOutputPaymentByDate($day,$params=array())
	{

		$conditions["and"]["DATE_FORMAT({$this->name}.day,'%Y%m%d')"]=$day;
		$conditions["and"]["{$this->name}.del_flg"]=0;

		$options=array();
		if(isset($params["recursive"])) $options["recursive"]=$params["recursive"];
		$options["conditions"]=$conditions;
		$data=$this->find("all",$options);
		return $data;
	}

}
