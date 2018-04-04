<?php

App::uses('AppModel', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AdK9DataEmployeeAttendance extends AppModel{

    var $name = "K9DataEmployeeAttendance";
    var $useTable = "k9_data_employee_attendance";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9MasterEmployee' => array(

			'className' => 'K9MasterEmployee',
			'foreignKey'=> 'employee_id',
			'dependent' => false,
		),
	);

	public function getMembersAttendanceByThedayRecored($day,$employee_ids=array())
	{

		$conditions=array();
		if(!empty($employee_ids)) $conditions["and"]["{$this->name}.employee_id"]=$employee_ids;
		$conditions["and"]["DATE_FORMAT({$this->name}.enter_date,'%Y%m%d')"]=$day;
		$conditions["and"]["{$this->name}.del_flg"]=0;
		$data=$this->find("all",array(
		
			"conditions"=>$conditions
		));

		return $data;
	}

}
