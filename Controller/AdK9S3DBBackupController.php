<?php

require_once('vendor/autoload.php');

class S3_base{

	protected static $bucketName="k9dbbackup";
}

class S3_factory extends S3_base{

	private static $_instance;

	public static function getInstance()
	{
	    if(isset(self::$_instance)) return self::$_instance;

		$s3=Aws\S3\S3Client::factory([
		
			"credentials"=>["key"=>getenv("AWS_S3_ACCESS_KEY_ID"),"secret"=>getenv("AWS_S3_SECRET_ACCESS_KEY")],
   			"region" => getenv("AWS_S3_DEFAULT_REGION"),
			"version"=> getenv("AWS_S3_VERSION")
		]);

	    self::$_instance=$s3;
		return self::$_instance;
	}
}

class S3_upload extends S3_base{

	private $dirname;

	public function __construct($dirname)
	{
		$this->dirname=$dirname;
	}

	public function upload()
	{
		$s3_dirinfo=new S3_dirInformation($this->dirname);
		$s3_dir=$s3_dirinfo->getDir();
		return $this->__upload($s3_dir);
	}

	private function __upload(S3_localdir $s3_dir)
	{
		$keynames=$s3_dir->getKeynames();
		$res=$this->__uploadIndividuals($s3_dir,$keynames);
		return $res;
	}

	private function __uploadIndividuals(S3_localdir $s3_dir,$keynames=array(),$res=array())
	{
		if(empty($keynames)) return $res;

		$keyname=array_shift($keynames);
		$dir_separate=explode("_",$s3_dir->getDirectory());
		$day =$dir_separate[0];
		$hour=$dir_separate[1];
		$keypath="{$day}/{$hour}/{$keyname}";

		$data=array();
		$data["Bucket"]=parent::$bucketName;
		$data["Key"]   =$keypath;
		$data["Body"]  =fopen(S3_DB_CLIENT_BACKUP_DIR.$s3_dir->getDirectory().DS.$keyname,"r");
		$data["StorageClass"]="STANDARD";
		//$data["ACL"]="";
		//$data["ContentType"]="";
		$res[]=S3_factory::getInstance()->putObject($data);	
		return $this->__uploadIndividuals($s3_dir,$keynames,$res);
	}
}

class S3_listObjects extends S3_base{

	protected static $keySeparator=DS;

	public static function getListObjects($bucketName="")
	{
		//https://docs.aws.amazon.com/ja_jp/AmazonS3/latest/dev/ListingObjectKeysUsingPHP.html
		$s3=S3_factory::getInstance();
		$res=$s3->getIterator("ListObjects",array("Bucket"=>(empty($bucketName)?self::$bucketName:$bucketName)));
		return $res;
	}

	public static function getKeyinformation($key)
	{
		$res=array();	
		$key_separator=explode(self::$keySeparator,$key);
		if(3>count($key_separator)) throw new Exception("wrong the file of keyname {$key}");

		$keyname=new class extends S3_listObjects{
		
			public $ymd;
			public $h;
			public $keyname;
		};

		$keyname->ymd=$key_separator[0];
		$keyname->h  =$key_separator[1];
		$keyname->keyname=$key_separator[2];
		return $keyname;
	}
}

class S3_deleteObjects extends S3_base{

	private static function keyConduct($keynames=array(),$res=array())
	{
		if(empty($keynames)) return $res;	
		$keyname=array_shift($keynames);
		$res[]["Key"]=$keyname;
		return self::keyConduct($keynames,$res);
	}

	//https://docs.aws.amazon.com/ja_jp/AmazonS3/latest/dev/DeletingMultipleObjectsUsingPHPSDK.html
	//https://docs.aws.amazon.com/AWSJavaScriptSDK/latest/AWS/S3.html
	public static function deleteObjects($keynames=array())
	{
		$t=array();
		$t["Bucket"]=parent::$bucketName;
		$t["Delete"]["Objects"]=self::keyConduct($keynames);
		$res=S3_factory::getInstance()->deleteObjects($t);
		return $res;
	}
}

class S3_localdir{

	private $dirpath;

	public function __construct($dirpath)
	{
		$this->dirpath=$dirpath;
	}

	public static function deleteLocalDir(Aws\Result $s3_result){

		$statuscode  =$s3_result["@metadata"]["statusCode"];
		$effectiveurl=$s3_result["@metadata"]["effectiveUri"];
		if($statuscode!=200) return false;

		$parse_url=explode(DS,$effectiveurl);
		$ymd=$parse_url[count($parse_url)-3];
		$h  =$parse_url[count($parse_url)-2];
		$keyname=$parse_url[count($parse_url)-1];
		$path=S3_DB_CLIENT_BACKUP_DIR."{$ymd}_{$h}".DS;
		if(!is_dir($path)) return false;
		system("rm -rf {$path}");
		if(!is_dir($path)) return true;
		return false;
	}

	public function getDirectory()
	{
		$dirname=pathinfo($this->dirpath)["basename"];
		return $dirname;
	}

	public function getKeynames()
	{
		$files=$this->__getFiles();
		if(empty($files)) return array();
		return $this->__getKeynames($files);
	}

	private function __getKeynames($files,$res=array())
	{
		if(empty($files)) return $res;
		$file=array_shift($files);
		$res[]=pathinfo($file)["basename"];
		return $this->__getKeynames($files,$res);
	}

	private function __getFiles()
	{
		$files=glob($this->dirpath."*");
		if(empty($files)) return array();
		return $files;
	}
}

class S3_dirInformation{

	private $dirpath;

	public function __construct($dirname)
	{
		$this->dirpath=S3_DB_CLIENT_BACKUP_DIR.$dirname.DS;
		if(!is_dir($this->dirpath)) throw new Exception(__("Could not find target backup dir."));
	}

	public function getDir()
	{
		$s3_dir=new S3_localdir($this->dirpath);	
		return $s3_dir;
	}
}

class AdK9S3DBBackupController extends AppController {

	var $name = 'K9S3DBBackup';
	var $uses = [];

	//2days.
	const expiredSeconds=172800;
	const NG=0;
	const OK=1;

	private function __deleteObjects()
	{
		$iterator=S3_listObjects::getListObjects();
		$keynames=$this->__checkExpired($iterator);
		if(!isset($keynames[self::NG])) return true;
		$object=S3_deleteObjects::deleteObjects($keynames[self::NG]);
		return true;
	}

	private function __checkExpired(Generator $generator,$res=array())
	{
		$current=$generator->current();
		if(empty($current)) return $res;

		$key=$current["Key"];
		$generator->next();

		try{

			$is_expired=$this->__expiredJudge($key,self::expiredSeconds);
			$res[(!empty($is_expired))?self::NG:self::OK][]=$key;

		}catch(Exception $e){ 

			$res[self::NG][]=$key; 
		}

		return $this->__checkExpired($generator,$res);
	}

	private function __expiredJudge($key,$expired_second=86400)
	{
		$keyname=S3_listObjects::getKeyinformation($key);
		if(empty($keyname)) return false;
		if(strtotime("- {$expired_second} second",time()) > strtotime($keyname->ymd.sprintf("%02d",$keyname->h)."0000")) return true;
		return false;
	}

	public function s3DbBackup($client)
	{
		if(!defined("S3_DB_CLIENT_BACKUP_DIR")) define("S3_DB_CLIENT_BACKUP_DIR",S3_DB_BACKUP_DIR.$client.DS);

		$this->__deleteObjects();

		$directories=glob(S3_DB_CLIENT_BACKUP_DIR."*");
		if(empty($directories)) return;
		$dir_info=$this->__s3DbBackupEachDirectories($directories);
		$this->__deleteLocalMultiDirs($dir_info);
		return;
	}

	private function __deleteLocalMultiDirs($dir_info=array())
	{
		if(empty($dir_info)) return;
		foreach($dir_info as $dirname=>$values) break;
		$this->__deleteLocalDir($dirname,$values);
		unset($dir_info[$dirname]);
		return $this->__deleteLocalMultiDirs($dir_info);
	}

	private function __deleteLocalDir($dirname,$values=array())
	{
		if(empty($values)) return;
		$value=array_shift($values);
		$res=S3_localdir::deleteLocalDir($value);
		return $this->__deleteLocalDir($value);
	}

	private function __s3DbBackupEachDirectories($directories=array(),$res=array())
	{
		if(empty($directories)) return $res;

		$directory=array_shift($directories);
		$dirname=pathinfo($directory)["basename"];
		$s3_upload=new S3_upload($dirname);
		$__res=$s3_upload->upload();
		$res[$dirname]=$__res;
		return $this->__s3DbBackupEachDirectories($directories,$res);
	}

}
