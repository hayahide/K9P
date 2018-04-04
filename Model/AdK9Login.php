<?php

App::uses('AuthComponent', 'Controller/Component');

class AdK9Login extends AppModel {

    var $name = "K9Login";
    var $useTable = 'k9_master_employee_accounts';
    var $primaryKey = "employee_id";

    public $validate = array(

        'username' => array(
            'nonEmpty' => array(
                'rule' => array('notEmpty'),
                'message' => 'A username is required',
                'allowEmpty' => false
            ) ),
        'password' => array(
                'required' => array(
                'rule' => array('notEmpty'),
                'message' => 'A password is required'
            ) )
    );

}

?>
