<?php

class AdSetModel{

	private $models=array();

	function __construct($__this,Controller &$controller){

		foreach($__this->useModels as $k=>$model){

			$controller->loadModel($model);
			$this->models[$model]=$controller->$model;
		}
	}

	public function getSettedModels(){
	
		return $this->models;
	}
}

?>
