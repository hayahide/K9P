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
class AdK9DataMetaValue extends AppModel{

    var $name = "K9DataMetaValue";
    var $useTable = "k9_data_meta_values";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public static $INVOICE_COMPANYNAME     ="invoice_companyname";
	public static $INVOICE_COMPANYVATNUMBER="invoice_vatnumber";
	public static $INVOICE_COMPANYADRESS   ="invoice_address";
	public static $INVOICE_COMPANYPHONE    ="invoice_phone";

}
