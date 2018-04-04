<?php
/*
 * Copyright 2015 SPCVN Co., Ltd.
 * All right reserved.
 */

/**
 * @Author: Nguyen Chat Hien
 * @Date:   2016-11-15 16:27:40
 * @Last Modified by:   Nguyen Chat Hien
 * @Last Modified time: 2016-11-16 10:10:49
 */

App::uses('ExceptionRenderer', 'Error');

class AppExceptionRenderer extends ExceptionRenderer {

	public function notFound($error) {

		$this->controller->redirect(array('controller' => 'Errors', 'action' => 'error404'));
	}

	public function unAuthorized($error) {

		$this->controller->redirect(array('controller' => 'Errors', 'action' => 'accessdenied'));
	}
}
