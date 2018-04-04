<?php

class AdK9GoogleHolidaysController extends AppController {

	var $name = 'K9GoogleHolidays';

	//https://gist.github.com/mattn/1438183
    //private $holiday_id="outid3el0qkcrsuf89fltf7a4qbacgt9@import.calendar.google.com";
	//private $holiday_id="ja.japanese#holiday@group.v.calendar.google.com";
	private $holiday_id="japanese__ja@holiday.calendar.google.com";
	//private $holiday_id="japanese@holiday.calendar.google.com";

    private $cacheDir;
    private $cacheSeparator="	";
    private $maps=array(

        "ganjitsu"=>"元日",
        "furikaekyujitsu"=>"振替休日",
        "seijinnohi"=>"成人の日",
        "kenkokukinennohi"=>"建国記念の日",
        "shunbunnohi"=>"春分の日",
        "showanohi"=>"昭和の日",
        "kenpokinenbi"=>"憲法記念日",
        "midorinohi"=>"みどりの日",
        "kodomonohi"=>"子供の日",
        "uminohi"=>"海の日",
        "yamanohi"=>"山の日",
        "keironohi"=>"敬老の日",
        "shubunnohi"=>"秋分の日",
        "taiikunohi"=>"体育の日",
        "bunkanohi"=>"文化の日",
        "kinrokanshanohi"=>"勤労感謝の日",
        "tennotanjobi"=>"天皇誕生日"
    );

    function beforeFilter(){

		$this->__init();
	}

    function getHolidays()
    {

        if(!$this->isPostRequest()) exit;
        $year=$_POST["year"];
        if(!is_numeric($year)) exit;
        $res["data"]["holidays"]=$this->__getHolidays($year);
        $res["data"]["year"]=$year;
		Output::__output($res);
    }

	function __init(){

		$this->cacheDir=dirname(ROOT).DS."html".DS."base".DS."tmp".DS."logs".DS."holidays".DS."ca".DS;
        //$this->cacheDir=LOGS."holidays".DS."ca".DS;
        $GLOBALS["googleApiKey"]=GOOGLE_API_KEY;
		$this->isClient=(PHP_SAPI=="cli");
	}

    function __makeUrl($year)
    {
        global $googleApiKey;

        $url=sprintf(

        	'https://www.googleapis.com/calendar/v3/calendars/%s/events?'.
        	'key=%s&timeMin=%s&timeMax=%s&maxResults=%d&orderBy=startTime&singleEvents=true',
        	$this->holiday_id,
        	$googleApiKey,
        	$year.'-01-01T00:00:00Z', // 取得開始日
        	$year.'-12-31T00:00:00Z', // 取得終了日
        	150 // 最大取得数
        );

        return $url;
    }

    function __isHolidayCache($year)
    {
       $file=$this->__cacheLogPath($year);
       if(is_file($file) AND filesize($file)>0) return true;
       return false;
    }

    function __cacheLogPath($year)
    {
        $file=$this->cacheDir.$year.".tsv";
        return $file;
    }

    function __getCacheLog($year)
    {
        $file=$this->__cacheLogPath($year);
        $data=trim(file_get_contents($file));
        $data=explode("\n",$data);
        $holidays=array();
        $separator=$this->cacheSeparator;
        foreach($data as $k=>$v){

            $v=explode($separator,$v);
            $holidays[$v[0]]=$v[1];
        }
        return $holidays;
    }

    function __saveCacheHolidays($year,$holidays)
    {

        $separator=$this->cacheSeparator;
        $log=array();
        foreach($holidays as $date=>$holiday){

            $holiday=mb_convert_encoding($holiday,"utf-8",mb_detect_encoding($holiday));
            $log[]="{$date}{$separator}{$holiday}";
        }

        $file=$this->__cacheLogPath($year);

        $file=$this->cacheDir.$year.".tsv";
        $log=implode("\n",$log);
        file_put_contents($file,$log);
    }

    function __getHolidaysGoogleApi($url)
    {
        $holidays=array();
        if(!$results=file_get_contents($url,true)) return false;

        $results=json_decode($results);
        foreach($results->items as $item){

        	$date=strtotime((string) $item->start->date);
        	$title=(string) $item->summary;
            $title=explode("/",$title);

			$key="";
			if(isset($title[1])){
				$key=str_replace(" ","",strtolower(trim($title[1])));
			}

            $jp_holiday=(isset($this->maps[$key])?$this->maps[$key]:trim($title[0]));
        	$holidays[date('Y-m-d', $date)]=$jp_holiday;
        }

        ksort($holidays);
        return $holidays;
    }

    function __getHolidays($year){

        if($this->__isHolidayCache($year)) return $this->__getCacheLog($year);
		return array();

		/*
        $url=$this->__makeUrl($year);
        $holidays=$this->__getHolidaysGoogleApi($url);
        $this->__saveCacheHolidays($year,$holidays);
        return $holidays;
		*/
    }

    function __getCambodiaHolidaysForYear($base_year)
    {

        $holidays=array();
        $before=$base_year-1;
        $holidays[$before]   =$this->__getHolidays($before);
        $holidays[$base_year]=$this->__getHolidays($base_year);
        $after=$base_year+1;
        $holidays[$after]    =$this->__getHolidays($after);
        return $holidays;
    }

	function __convertFormatHolidays($holidays) {

		$res = array();
		foreach ($holidays as $year => $days) {

			$_res=$this->__changeFormatDay($days);
			$res[$year]=$_res;
		}

		return $res;
	}

	function __changeFormatDay($days) {

		$res = array();
		foreach ($days as $k => $day) {

			$_day=str_replace("-","",$k);
			array_push($res,$_day);
		}

		return $res;
	}

}
