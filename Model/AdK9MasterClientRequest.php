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
class AdK9MasterClientRequest extends AppModel{

    var $name = "K9MasterClientRequest";
    var $useTable = "k9_master_client_requests";
    var $primaryKey = "id";
	var $useDbConfig="master";

}
