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
class AdK9DataCheckoutPayment extends AppModel{

    var $name = "K9DataCheckoutPayment";
    var $useTable = "k9_data_checkout_payment";
    var $primaryKey = "reserve_id";
	var $useDbConfig="default";

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
	);

	public function getCheckoutPaymentWithEnterTime($date,$params=array())
	{

		//キャッシュ受け取ったら purchase_flgはON
		//クレジットカードの場合、残高に含まれている事を確認されたら、purchase_flg はONになる
		//修正日以前を考慮する必要はなし
		//例えpurchase_flgが0だとしても
		$recursive=isset($params["recursive"])?$params["recursive"]:1;
		$date=date("YmdHis",strtotime($date));
		$conditions=array();
		$conditions["and"]["{$this->name}.purchase_flg"]=1;
		if(!empty($date)) $conditions["and"]["DATE_FORMAT({$this->name}.purchase_time,'%Y%m%d%H%i%s') > "]=$date;
		$conditions["and"]["{$this->name}.del_flg"]=0;

		return $this->find("all",array(
		
			"conditions"=>$conditions,
			"recursive" =>$recursive
		));
	}
}
