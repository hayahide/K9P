<?php
/**
 * AppShell file
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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Application Shell
 *
 * Add your application-wide methods in the class below, your shells
 * will inherit them.
 *
 * @package       app.Console.Command
 */

App::uses('AppController','Controller');
App::uses('WeatherImportController','Controller');
App::uses('CheckSamePositioningsController','Controller');
App::uses('CheckDateSamePositioningsController','Controller');
App::uses('CheckWeatherInformationsController','Controller');
App::uses('DeletePostLogExpiredsController','Controller');
App::uses('DeleteLineInsertLogsController','Controller');
App::uses('DeleteExcelLogsController','Controller');
App::uses('SiteManagesFilesController','Controller');
App::uses('BackupLineActionsController','Controller');

class CronShell extends AppShell {

        #
        # @author Kiyosawa
        # @date
        public function startup(){

                parent::startup();
        }

		function _welcome() {
  		}

    /**
     * Cron import Yahoo weather to database
     * @date 2016-8-26
     * @author Edward <duc.nguyen@spc-vn.com>
     */
        public function importWeatherYahoo() {

	            $this->Controller=new WeatherImportController();
	            $res=$this->Controller->beforeFilter();
	            $res=$this->Controller->importWeatherYahoo();
	            exit;
        }

        public function samePositioningsConsole(){

	            $this->Controller=new CheckSamePositioningsController();
	            $res=$this->Controller->beforeFilter();
	            $res=$this->Controller->samePositioningConsole();
				echo $res;
	            exit;
        }

		public function getWeatherStatusByCurrentDate(){

                $this->Controller=new CheckWeatherInformationsController();
                $res=$this->Controller->beforeFilter();
                $res=$this->Controller->getWeatherStatusByCurrentDate();
                echo $res;
                exit;
        }

		 public function removeEmailContentsFilesNotInSevenDaysLatest(){

                $this->Controller=new SiteManagesFilesController();
				$this->Controller->beforeFilter();
                $res=$this->Controller->removeEmailContentsFilesNotInSevenDaysLatest();
				echo $res;
                exit;
        }

		 public function removeLog(){

                $this->Controller=new DeletePostLogExpiredsController();
                $res=$this->Controller->beforeFilter();
                $res=$this->Controller->removeLog();
                echo $res;
                exit;
        }

		 public function removeInsertLog(){

                $this->Controller=new DeleteLineInsertLogsController();
                $res=$this->Controller->beforeFilter();
                $res=$this->Controller->removeInsertLog();
                echo $res;
                exit;
        }

		 public function sameDatePositioningsConsole(){

                $this->Controller=new CheckDateSamePositioningsController();
                $res=$this->Controller->beforeFilter();
                $res=$this->Controller->samePositioningConsole();
                echo $res;
                exit;
        }

        public function removeExcelLogs()
        {
                $this->Controller=new DeleteExcelLogsController();
                $res=$this->Controller->beforeFilter();
                $res=$this->Controller->removeExcelLogs();
        }

        public function backupLineAction()
        {

                $args=$this->args;
                $client=$args[2];
                $this->Controller=new BackupLineActionsController();
                $res=$this->Controller->beforeFilter();
                $res=$this->Controller->backupLineAction();
        }
}

