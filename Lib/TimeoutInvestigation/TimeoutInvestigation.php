<?php

class AdTimeoutInvestigation{

	static function effectiveTime(){

		return EDIT_EFFECTIVE_SECOND*1000;
	}

	static function currentMsTime(){

		return time()*1000;
	}
}

?>
