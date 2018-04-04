<?php

class ScheduleSamePosition{

		public $controller;
		public $useModels=array("TblMstepSiteSchedule");
		public $models;
		public $findValues=array();

		function __construct(Controller &$controller){

				$this->models=new SetModel($this,$controller);
				$this->controller=$controller;
		}

		/*
		function view($schedule_ids=array()){

				$__view=function($schedule_ids,$this){

						if(empty($schedule_ids)) return;
						$date=array_keys($schedule_ids)[0];
						$ids=array_shift($schedule_ids);
						$store=array();
						foreach($ids as $k=>$schedule_id){
						
								$data=$this->findValues[$schedule_id];
						}
				};

				$__view($schedule_ids,$this);
		}

		private function __view(){
		
		}
		*/

		function getNgScheduleIds(){

				$model=$this->models->getSettedModels()["TblMstepSiteSchedule"];
				$model->unbindFully();
				if(!$schedules=$model->findAllByDelFlg(0)) return array();

				$schedules=Set::combine($schedules,"{n}.TblMstepSiteSchedule.id","{n}.TblMstepSiteSchedule");
				$this->findValues=$schedules;

				$ng_schedule_ids=array();
				$schedule_positions=array();
				foreach($schedules as $schedule_id=>$v){

						$month=$v["start_month_prefix"];
						$day  =sprintf("%02d",$v["start_day"]);
						$start_date=$month.$day;
						if(!isset($values[$start_date])) $values[$start_date]=array();
						if(!in_array($v["position_num"],$values[$start_date])){ 

								$values[$start_date][]=$v["position_num"];
								$schedule_positions[$start_date][$v["position_num"]]=$v["id"];
								continue;
						}

						$ng_schedule_ids[$month.$day][]=$v["id"];
						$same_current_schedule_id=$schedule_positions[$start_date][$v["position_num"]];
						if(in_array($same_current_schedule_id,$ng_schedule_ids[$month.$day])) continue;
						$ng_schedule_ids[$month.$day][]=$same_current_schedule_id;
				}

				return $ng_schedule_ids;
		}

}

?>
