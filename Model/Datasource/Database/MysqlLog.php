<?php

		App::uses('Mysql','Model/Datasource/Database');
		class MysqlLog extends Mysql {

		   		 function logQuery($sql,$params=array()){

		   		         parent::logQuery($sql);
		   		         if(Configure::read("Cake.logQuery")){

								 //v($this->_queriesLog,1);

		   		 				//■SQLの実行詳細
		   		            	//$this->log($this->_queriesLog,SQL_QUERY_LOG);

		   		 				//■SQLクエリーのみ
		   		 				$this->log($sql,SQL_QUERY_LOG);
		   		         }
		   		 }
		}
