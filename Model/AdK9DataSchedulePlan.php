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
class AdK9DataSchedulePlan extends AppModel{

    var $name = "K9DataSchedulePlan";
    var $useTable = "k9_data_schedule_plans";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9MasterRoom' => array(

			'className' => 'K9MasterRoom',
			'foreignKey' => 'room_id',
			'conditions' => array("K9MasterRoom.del_flg"=>'0')
		),
	);

	function deletePlanByIdOverToday($plan_ids=array()){

		$today=date("Y-m-d");
		$query ="update {$this->useTable} set del_flg=1";
		$query.=" where id IN(".implode(",",$plan_ids).")";
		$query.=" AND ";
		$query.="start>=\"{$today}\";";

		try{ $this->query($query);
		}catch(Exception $e){

			$res["message"]=$e->getMessage();
			$res["status"]=false;
			return $res;
		}

		$res["status"]=true;
		return $res;
	}



}
