<?php

class AdTimeoutInvestigationKeys{

	static function decBinKey($bin_key,$time_key){

		$dec=cipher_decrypt($bin_key,$time_key);
		$dec=explode("_",$dec);
		if(count($dec)!=2) return false;

		return array(

			"user_id" =>$dec[0],
			"time_key"=>$dec[1]
		);
	}

	static function makeBinKey($user_id,$time_key){

		$val="{$user_id}_{$time_key}";
		$bin=cipher_encrypt($val,$time_key);
		return $bin;
	}

	static function makeTimeSesKey($unique_key){

		return TIME_KEY."_".$unique_key;
	}
}

?>
