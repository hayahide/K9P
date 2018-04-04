<?php

App::uses('CakeEmail', 'Network/Email');
class AdMstepCakeEmail extends CakeEmail{

	protected function _getContentTransferEncoding() {

		return '7bit';
		//return "Quoted-Printable";

		/*
		$charset = strtoupper($this->charset);
		if (in_array($charset, $this->_charset8bit)) {

				return '8bit';
		}
		return '7bit';
		 */
	}
}

?>
