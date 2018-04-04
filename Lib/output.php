<?php

require_once "Error/ScheduleError.php";

class AdOutput{

	public function __outputStatus($status,$params=array(),$changeCodes=array()){

		$error=Schedule\Errors::$errorStatus[$status];

		if(!empty($changeCodes)){

			$keys  =array_keys($changeCodes);
			$values=array_values($changeCodes);
			$error["message"]=str_replace($keys,$values,__($error["message"]));
		}

		$output["errorNo"]=$status;
		$output["title"]  =$error["title"];
		$output["message"]=$error["message"];
		$output=array_merge($output,$params);
		self::__outputNo($output);
	}

	static public function __outputNo($values = array()) {

		$trace=debug_backtrace();

		$option="";
		$controller=isset($trace[1]["object"])?$trace[1]["object"]->name:"";
		$action    =isset($trace[1]["function"])?$trace[1]["function"]:"";
		$line      =isset($trace[0]["line"])?$trace[0]["line"]:"";
		$option    ="{$controller}".DS."{$action} of {$line}";
		if(isset($values["message"])) $values["message"].="({$option})";
		$values["status"]="NO";
		self::__output($values);
	}

	static public function __outputYes($values = array()) {

		$values["status"]="YES";
		self::__output($values);
	}

	static public function __output($res=array()){

		//Configure::write("debug",0);
		//header("Access-Control-Allow-Origin: *");
		header("Content-type:application/json;charset=utf8");
		echo json_encode($res);
		exit;
	}

	static public function __outputMessage($message = NULL) {

		$values["status"]="YES";
		$values["message"] = ($message == NULL)?"Miss params":$message;
		self::__output($values);
	}

}

?>
