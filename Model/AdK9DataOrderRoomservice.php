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
App::uses('K9DataOrderBase', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AdK9DataOrderRoomservice extends K9DataOrderBase{

    var $name = "K9DataOrderRoomservice";
    var $useTable = "k9_data_order_roomservice";

	//here shuld change to master_id...
	var $masterForeignKey="service_idb";

	public $belongsTo = array(

		'K9DataReservation' => array(

			'className' => 'K9DataReservation',
			'foreignKey'=> 'reserve_id',
			'conditions'=> array('K9DataReservation.del_flg' => '0'),
			'dependent' => false,
		),
		'K9MasterCard' => array(

			'className' => 'K9MasterCard',
			'foreignKey'=> 'cash_type_id',
			'dependent' => false,
		),
		'K9MasterRoomservice' => array(

			'className' => 'K9MasterRoomservice',
			'foreignKey'=> 'service_id',
			'dependent' => false,
		),
		'K9DataHistoryPriceRoomservice' => array(

			'className' => 'K9DataHistoryPriceRoomservice',
			'foreignKey'=> 'price_id',
			'dependent' => false,
		),
	);
}
