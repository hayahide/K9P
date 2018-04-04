<?php

namespace Schedule;

class Errors {

	static $errorStatus = array(

		"0" => array("title" => "実行エラー", "message" => "【##USER##】が編集中です"),
		"1" => array("title" => "実行エラー", "message" => "正常に処理が終了しませんでした"),
		"2" => array("title" => "実行エラー", "message" => "【##USER##】によりスケジュールの内容は既に更新されております。※画面をリロード致します"),
	);

	public static function changeCode($number,$changeCodes=array()){

		$keys  =array_keys($changeCodes);
        $values=array_values($changeCodes);
		$error=Errors::$errorStatus[$number];
        $error["message"]=str_replace($keys,$values,$error["message"]);
		return $error;
	}
}

?>
