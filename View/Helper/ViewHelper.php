<?php
/**
 * Application level View Helper
 *
 * This file is application-wide helper file. You can put all
 * application-wide helper-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Helper
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppHelper', 'View/Helper');  

/**
 * Application helper
 *
 * Add your application-wide methods in the class below, your helpers
 * will inherit them.
 *
 * @package       app.View.Helper
 */
class ViewHelper extends AppHelper {

		private $greetings=array("l"=>"お疲れ様です","m"=>"おはようございます","a"=>"御苦労様です","e"=>"こんばんは");

		function getGreeting(){

				$hour=date("G");
				if(in_array($hour,range(0,8)))  return  $this->greetings["l"];
				if(in_array($hour,range(9,12))) return  $this->greetings["m"];
				if(in_array($hour,range(13,18))) return $this->greetings["a"];
				return $this->greetings["e"];
		}

		function employeeColors($level){

				$map["normal"]   ="rgba(255,255,255,1.000)";
				$map["develop"]  ="rgba(227,247,255,1.000)";
				$map["master"]="rgba(255,208,211,1.000)";
				return $map[$level];
		}

		function strCut($str,$length=30){
		
		}

		function toYmd($date){

				$d=date("Y/m/d",strtotime($date));
				return $d;
		}
}

