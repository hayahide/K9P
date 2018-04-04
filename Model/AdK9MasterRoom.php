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
class AdK9MasterRoom extends AppModel{

    var $name = "K9MasterRoom";
    var $useTable = "k9_master_rooms";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public static $CLIEAN=1;
	public static $DIRTY=2;
	public static $UNAVAILABLE=3;
	public static $INSPECTED=4;
	public static $HOTEL="room";
	public static $APART="apart";

	public static $CATEGORY_ROOM="room";

	public $belongsTo = array(

		'K9MasterRoomType' => array(

			'className' => 'K9MasterRoomType',
			'foreignKey' => 'room_type_id',
			//'conditions' => array("K9MasterRoomType.del_flg"=>'0')
		),
		'K9MasterRoomSituation' => array(

			'className' => 'K9MasterRoomSituation',
			'foreignKey' => 'situation_id',
			'conditions' => array("K9MasterRoomSituation.del_flg"=>'0')
		),
		'K9MasterCategory' => array(

			'className' => 'K9MasterCategory',
			'foreignKey' => 'category_id'
		),
	);

	var $association=array(

		"hasMany"=>array(

			'K9DataUnavailableRoom' => array(

				'className' => 'K9DataUnavailableRoom',
				'foreignKey' => 'room_id'
			),
		),
	);


	function getEffectRooms($is_only_effect=true){
	
		$conditions=null;
		if($is_only_effect) $conditions["and"]["K9MasterRoomSituation.id"]=array(self::$CLIEAN,self::$DIRTY);
		return $this->find("all",array( "conditions"=>$conditions ));
	}

	public function getScheduleBlockNum()
	{
		$count=$this->findCount();	
		return $count;
	}

	function getRoomIdByType($room_type_id,$type=null){

		$this->unbindFully();

		$conditions=array();
		$conditions["and"]["room_type_id"]=$room_type_id;
		if(!empty($type)) $conditions["and"]["type"]=$type;
		$data=$this->find("all",array( "conditions"=>$conditions ));
		return $data;
	}

	public function updateRoomSituation($room_id,$situation_id)
	{

		$save["id"]=$room_id;
		$save["situation_id"]=$situation_id;
		return $this->save($save);
	}

}
