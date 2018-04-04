<?php

App::uses('K9SettingCardValuesController','Controller');
App::uses('K9SettingTaxController','Controller');
class AdK9SettingsController extends AppController{

	var $name = "K9Settings";
	var $langs=array();
    var $uses = [];

	function beforeFilter(){
	
		parent::beforeFilter();
		$this->__setUseModel();
	}

	public function __setUseModel()
	{
		$this->loadModel("K9MasterCard");
		$this->loadModel("K9DataHistoryPriceCard");
		$this->loadModel("K9DataMetaValue");
	}

	function getSettings(){

		if(!$this->isPostRequest()) exit;

		$res["data"]["lang"] =$this->__getLanguage();
		$res["data"]["langs"]=$this->__getLangDefined();
		$res["data"]["tax"]  =$this->__getTaxSettingWithRange();
		$res["data"]["cards"]["rate"] =$this->__getCardSettingsWithRate();
		$res["data"]["cards"]["range"]=$this->__getCardSettingsWithRange();
		$res["data"]["invoice"]=$this->__getInvoiceCompanyInformations();
		Output::__outputYes($res);
	}

	function saveSetting(){

		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();

		$meta_key=$post["meta_key"];
		$meta_value=$post["meta_value"];

		$cml=ucwords($meta_key,'-');
		$cml=str_replace('-','',$cml);
		$method="__{$cml}";
		if(method_exists($this,$method)){

			try{

				$res=$this->{$method}($meta_value);
			}catch(Exception $e){

				$res["status"]=false;
				$res["message"]=$e->getMessage();
				Output::__outputNo($res);
			}

			Output::__outputYes($res);
			return;
		}

		if(!$current_value=$this->K9DataMetaValue->findByMetaKey($meta_key)){

			$res["status"]=false;
			$res["message"]=__("正常に処理が終了しませんでした");
			Output::__outputNo($res);
		}

		$save["id"]        =$current_value["K9DataMetaValue"]["id"];
		$save["meta_value"]=$meta_value;
		if(!$this->K9DataMetaValue->save($save)){
		
			$res["message"]=__("正常に処理が終了しませんでした");
			Output::__outputNo($res);
		}

		$res["message"]=__("設定した内容が正常に登録されました");
		Output::__outputYes($res);
	}

	function __SelectLanguage($meta_value){

		$langs=array_keys(LANG);
		if(!in_array($meta_value,$langs)) throw new Exception(__("言語情報が不正です"));
		$this->Session->write("lang",$meta_value);

		$res["status"]=true;
		$res["message"]=__("設定が登録されました、画面を更新すると反映されます");
		return $res;
	}

	public function __getTaxSettingWithRange()
	{
		$controller=new K9SettingTaxController();
		return $controller->__getTaxSettingWithRange();
	}

	public function __getCardSettingsWithRate()
	{
		$controller=new K9SettingCardValuesController();
		$controller->__setUseModel();
		return $controller->__getCardSettingsWithRate();
	}

	public function __getCardSettingsWithRange()
	{
		$controller=new K9SettingCardValuesController();
		$controller->__setUseModel();
		return $controller->__getCardSettingsWithRange();
	}

	private function __getLangDefined(){

		$res=array();
		foreach(LANG as $lang=>$v) $res[$lang]=__($v);
		return $res;
	}

	private function __getLanguage()
	{
		$langs=array_keys(LANG);
		$lang=$this->Session->read("lang");
		if(!empty($lang) AND in_array($lang,$langs)) return $lang;
		$this->Session->write("lang",DEF_LANG);
		return DEF_LANG;
	}

	private function __getInvoiceCompanyInformations()
	{
		$conditions=array();
		$values=array(K9DataMetaValue::$INVOICE_COMPANYPHONE,
		        	  K9DataMetaValue::$INVOICE_COMPANYADRESS,
					  K9DataMetaValue::$INVOICE_COMPANYVATNUMBER,
					  K9DataMetaValue::$INVOICE_COMPANYNAME);

		$conditions["and"]["K9DataMetaValue.meta_key"]=$values;
		$titles=getTSVInvoiceCompany();
		$res=$this->K9DataMetaValue->find("all",array( "conditions"=>$conditions ));

		$list=array();
		foreach($res as $k=>$v){

			$meta_key=$v["K9DataMetaValue"]["meta_key"];
			$list[$meta_key]["title"]=__($titles[$meta_key]);
			$list[$meta_key]["value"]=$v["K9DataMetaValue"]["meta_value"];
		}

		return $list;
	}

}//END class

?>
