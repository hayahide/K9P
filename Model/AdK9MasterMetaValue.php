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
class AdK9MasterMetaValue extends AppModel{

    var $name = "K9MasterMetaValue";
    var $useTable = "k9_master_meta_values";
    var $primaryKey = "id";
	var $useDbConfig="default";
}
