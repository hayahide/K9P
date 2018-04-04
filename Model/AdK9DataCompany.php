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
class AdK9DataCompany extends AppModel{

    var $name = "K9DataCompany";
    var $useTable = "k9_data_companies";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public static $TYPE_AGENCY   ="agency";
	public static $TYPE_BANK     ="bank";
	public static $TYPE_GAVERMENT="gaverment";
	public static $TYPE_NGO      ="ngo";
	public static $TYPE_EMBASSY  ="embassy";
	public static $TYPE_TOUR     ="tour";

	public static $STATUS_FIX   ="fix";
	public static $STATUS_PUBLIC="public";

	public $belongsTo = array(

		'K9MasterAgencyType' => array(

			'className'  => 'K9MasterAgencyType',
			'foreignKey' => 'agency_id',
		),
		'K9MasterEmployee' => array(

			'className'  => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered',
		),
		'K9MasterReservationSalesource' => array(

			'className'  => 'K9MasterReservationSalesource',
			'foreignKey' => 'salesource_id',
		),
	);

	public function getAgentsByIds($ids=array(),$status=array(),$del_flg=0)
	{

		$conditions=array();
		if(!empty($ids))         $conditions["and"]["{$this->name}.id"]=$ids;
		if(is_numeric($del_flg)) $conditions["and"]["{$this->name}.del_flg"]=$del_flg;
		if(!empty($status))      $conditions["and"]["{$this->name}.status"]=$status;
		return $this->find("all",array(
		
			"conditions"=>$conditions
		));
	}

}
