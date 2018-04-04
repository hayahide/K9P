var BASE_URL   ="//spc-mstep.local/";
// var BASE_URL   ="//mstep.localhost/web/";

Number.prototype.addZero=function(){

		var val=(this+"");
		val=((2>val.length)?("0"+val):val);
		return val;
}

Number.prototype.dateFormat=function(){

		var time=(this+"");
		var dates=[];
		dates.push(time.slice(0,4));
		dates.push(time.slice(4,6));
		dates.push(time.slice(6,8));
		return dates.join("/");
}

Number.prototype.addDate=function(num){

		var date=this;
		var dates=[];
		var d=new Date(Number(date).dateFormat());
		d.setDate(d.getDate()+(num));
		dates.push(d.getFullYear());
		dates.push(Number(d.getMonth()+1).addZero());
		dates.push(Number(d.getDate()).addZero());
		var next_date=dates.join("");
		return next_date;
}

Number.prototype.diffDate=function(d2){

		var d1=this;
		var diff1=new Date(d1.dateFormat()).getTime();
		var diff2=new Date(Number(d2).dateFormat()).getTime();
		var msdiff=Math.abs(diff1-diff2);
		var daysDiff=Math.floor(msdiff/(1000*60*60*24));
		return daysDiff;
}

//■オプションの検索項目
apiGeoRequests.getAddDateApi=function(data,callback){

		var obj= new this();
		obj.setBaseURL(BASE_URL);
		obj.setURL(this.getAddDateApi.makeURL());
		obj.request(data,{"type":"POST"},function(status,res){
				if(!status || !res["status"]=="YES"){
						callback.call(obj,false,res["errors"]);
						return;
				}
				callback.call(obj,true,res);
		})
}

apiGeoRequests.getAddDateApi.makeURL=function(){

		var base_url='sample/getAddDateApi';
		return base_url;
}

apiGeoRequests.getSubDateApi=function(data,callback){

		var obj= new this();
		obj.setBaseURL(BASE_URL);
		obj.setURL(this.getSubDateApi.makeURL());
		obj.request(data,{"type":"POST"},function(status,res){
				if(!status || !res["status"]=="YES"){
						callback.call(obj,false,res["errors"]);
						return;
				}
				callback.call(obj,true,res);
		})
}

apiGeoRequests.getSubDateApi.makeURL=function(){

		var base_url='sample/getSubDateApi';
		return base_url;
}

$(function(){

		var TYPE_SCHEDULE      ="schedule";
		var TYPE_SCHEDULE_BLOCK="schedule-block";
		var TYPE_DAYTOP        ="day-top";
		var TYPE_SCHEDULE_TOP_HEAD ="schedule-top-head";
		var TYPE_SCHEDULE_INNER="schedule-top-inner";
		var TYPE_SCHEDULE_HEAD ="schedule-head";
		var BTN_SCHEDULE_LEFT  ="schedule-btn-left";
		var BTN_SCHEDULE_RIGHT ="schedule-btn-right";
		var BLOCK_NUM=10;

		var html=$("html");
		var schedule_top=html.find("#schedule_top");
		var schedule_head=schedule_top.children("div[data-type=\""+TYPE_SCHEDULE_TOP_HEAD+"\"]");
		var schedule_top_inner=schedule_top.children("div[data-type=\""+TYPE_SCHEDULE_INNER+"\"]");

		//■前後１ヶ月間のデータ
		var information_data =JSON.parse('{"20160709":[],"20160710":[],"20160711":[],"20160712":[],"20160713":[],"20160714":[],"20160715":[],"20160716":[],"20160717":[],"20160718":[],"20160719":[],"20160720":[],"20160721":[],"20160722":[],"20160723":[],"20160724":[],"20160725":[],"20160726":[],"20160727":[],"20160728":[],"20160729":[],"20160730":[],"20160731":[],"20160801":[],"20160802":[],"20160803":[],"20160804":[],"20160805":[],"20160806":[{"title":"\u307b\u3052\u307b\u3052","color":"green","place":"\u6a2a\u9808\u8cc0\u5e02","users":["\u6e05\u6ca2","\u7530\u4e2d","\u5c71\u5d0e"]},{"title":"\u307b\u3052\u307b\u3052","color":"orange","place":"\u6a2a\u9808\u8cc0\u5e02","users":["\u6e05\u6ca2","\u7530\u4e2d","\u5c71\u5d0e"]}],"20160807":[{"title":"\u307b\u3052\u307b\u3052","color":"red","place":"\u6a2a\u9808\u8cc0\u5e02","users":["\u6e05\u6ca2","\u7530\u4e2d","\u5c71\u5d0e"]},{"title":"\u307b\u3052\u307b\u3052","color":"blue","place":"\u6a2a\u9808\u8cc0\u5e02","users":["\u6e05\u6ca2","\u7530\u4e2d","\u5c71\u5d0e"]}],"20160808":[{"title":"\u307b\u3052\u307b\u3052","color":"green","place":"\u6a2a\u9808\u8cc0\u5e02","users":["\u6e05\u6ca2","\u7530\u4e2d","\u5c71\u5d0e"]},{"title":"\u307b\u3052\u307b\u3052","color":"orange","place":"\u6a2a\u9808\u8cc0\u5e02","users":["\u6e05\u6ca2","\u7530\u4e2d","\u5c71\u5d0e"]}],"20160809":[],"20160810":[{"title":"\u307b\u3052\u307b\u3052","color":"green","place":"\u6a2a\u9808\u8cc0\u5e02","users":["\u6e05\u6ca2","\u7530\u4e2d","\u5c71\u5d0e"]},{"title":"\u307b\u3052\u307b\u3052","color":"orange","place":"\u6a2a\u9808\u8cc0\u5e02","users":["\u6e05\u6ca2","\u7530\u4e2d","\u5c71\u5d0e"]}],"20160811":[],"20160812":[],"20160813":[],"20160814":[],"20160815":[],"20160816":[],"20160817":[],"20160818":[],"20160819":[],"20160820":[],"20160821":[],"20160822":[],"20160823":[],"20160824":[],"20160825":[],"20160826":[],"20160827":[],"20160828":[],"20160829":[],"20160830":[],"20160831":[],"20160901":[],"20160902":[],"20160903":[],"20160904":[],"20160905":[],"20160906":[],"20160907":[],"20160908":[],"20160909":[]}');
		var times=JSON.parse('[20160809,20160810,20160811,20160812,20160813,20160814,20160815]');

		//■データ整形処理
		var makeInformationAry=function(times,information_data){

				var informations=[];
				for(var i in times){

						var obj={};
						if(obj[times[i]]==undefined) obj[times[i]]=[];
						if(1>information_data[times[i]].length) continue;
						for(var r=0;r<information_data[times[i]].length;r++){

								obj[times[i]].push({

										"title":information_data[times[i]][r]["title"],
										"place":information_data[times[i]][r]["place"],
										"color":information_data[times[i]][r]["color"],
										"users":information_data[times[i]][r]["users"]
								});
						}

						informations.push(obj);
				}

				return informations;
		}

		//■整形
		var informations=makeInformationAry(times,information_data);

		//■スケジュール要素か
		var checkScheduleTargetElem=function(type){

				return  type==TYPE_SCHEDULE;
		}

		//■マップ要素か
		var checkScheduleBlockElem=function(type){

				return  type==TYPE_SCHEDULE_BLOCK;
		}

		//■日付ヘッダ
		var addScheduleHead=function(times,method,callback){
		
				var elem    =this;
				if(1>times.length){
				
						callback.call(elem);
						return;
				}

				var fn=arguments.callee;
				var instance=new TPL("schedule_head");
				var tpl =instance.get_schedule_head;
				var time=times.shift();
				var html=instance.replaceTPL(tpl,{
				
						"time":time,
						"day" :time
				});

				html=$(html);
				elem[method](html);
				html.ready(function(){
				
						fn.call(elem,times,method,callback);
				});
		}

		var addScheduleTimeElements=function(times,method,callback){
		
				var elem=this;
				if(1>times.length){
				
						callback.call(elem);
						return;
				}
		
				var fn      =arguments.callee;
				var instance=new TPL("schedule_day");
				var tpl     =instance.get_schedule_day;
				var time    =times.shift();
				var html=instance.replaceTPL(tpl,{
				
						"time":time
				});
		
				html=$(html);
				elem[method](html);
				html.ready(function(){
		
						addScheduleBlockElements.call(html,time,function(){
						
								fn.call(elem,times,method,callback);
						});
				});
		}
		
		var addScheduleBlockElements=function(prefix,callback){
		
				var elem    =this;
				var instance=new TPL("schedule_block");
				var tpl     =instance.get_schedule_block;
				var fragment=document.createDocumentFragment();
				var block_num=BLOCK_NUM;
				var html;
		
				for(var i=0;i<block_num;i++){
		
			  			html=$(instance.replaceTPL(tpl,{
			  			
			  					"id":(prefix+"_"+i)
			  			}));
		
			  			fragment.appendChild(html.get(0));
			  	}
			  	
			  	elem.append(fragment);
			  	html.ready(function(){
			  			
			  			callback.call(elem);
			  	});
		}

		var addSchuduleElements=function(informations,callback){

				var elem=this;

				if(1>informations.length){
				
						callback.call(elem);
						return;
				}

				var fn=arguments.callee;
				var instance=new TPL("schedule");
				var tpl     =instance.get_schedule;
				var information=informations.shift();
				for(var time in information) break;

				var day_top=elem.children("div[data-time=\""+time+"\"]");
				if(1>day_top.size() || 1>information[time].length){
				
						fn.call(elem,informations,callback);
						return;
				}

				var schedule_maps=day_top.children("div[data-type=\""+TYPE_SCHEDULE_BLOCK+"\"]");
				var __addScheduleElements=function(informations,callback,index){
				
						index=(index==undefined)?0:index;
						if(1>informations.length){
						
								callback();
								return;
						}

						var fn=arguments.callee;
						var information=informations.shift();
						var html=$(instance.replaceTPL(tpl,{

								"color":information["color"]
						}));

						schedule_maps.eq(index).append(html);
						html.ready(function(){
						
								fn(informations,callback,(index+1));
						});
				}

				__addScheduleElements(information[time],function(){
				
						fn.call(elem,informations,callback);
				});
		}

		//■ヘッダ
		addScheduleHead.call(schedule_head,times.slice(0),"append",function(){
		
				//■マップ要素
				addScheduleTimeElements.call(schedule_top_inner,times,"append",function(){
		
						var schedule_day=this.find("div[data-type=\""+TYPE_DAYTOP+"\"]")
						var schedule_btn_left =schedule_top.find("div[data-type=\""+BTN_SCHEDULE_LEFT+"\"]");
						var schedule_btn_right=schedule_top.find("div[data-type=\""+BTN_SCHEDULE_RIGHT+"\"]");

						//■スケジュール設定
						addSchuduleElements.call(schedule_top_inner,informations.slice(0),function(){
						
								var schedule_blocks=schedule_day.find("div[data-type=\""+TYPE_SCHEDULE_BLOCK+"\"]");
								var schedule=schedule_day.find("div[data-type=\""+TYPE_SCHEDULE+"\"]");
								var timer="";

								var slideToNextSide=function(num){

										var touch_element=this;
										if(!(schedule instanceof jQuery)) return;
										if(!checkScheduleTargetElem(touch_element.attr("data-type"))) return;
										var map=touch_element.parent();
										var data_id=map.attr("data-id");
										var split=data_id.split("_");
										var next_day=Number(split[0]).addDate(num);
										var next_day_top=schedule_top_inner.children("div[data-time=\""+next_day+"\"]");
										var next_id=(next_day+"_"+split[1]);
										var map=next_day_top.children("div[data-id=\""+next_id+"\"]");
										moveToElemnet.call(map);
								}

								//■カーソルが大枠外での対応
								var moveToLayoutWhenOutsideLeft=function(){

										var fn=arguments.callee;
										timer=setTimeout(function(){

												if(!manageTouch().isOn()){
												
														clearTimeout(timer);
														return;
												}
			
												scheduleBtnLeftEvent(null,1,function(){

														//■横にスライド
														var touch_element=manageTouch().touchElement;
														slideToNextSide.call(touch_element,-1);

														fn();
												});

										},300);
								}

								//■カーソルが大枠外での対応
								var moveToLayoutWhenOutsideRight=function(){

										var fn=arguments.callee;
										timer=setTimeout(function(){

												if(!manageTouch().isOn()){
												
														clearTimeout(timer);
														return;
												}
								
												scheduleBtnRightEvent(null,1,function(){

														//■横にスライド
														var touch_element=manageTouch().touchElement;
														slideToNextSide.call(touch_element,1);

														fn();
												});

										},300);
								}

								var moveToElemnet=function(){

										var map=this;
										var target_map_id=map.attr("data-id");
										var touch_element=manageTouch().touchElement;
		
										//■移動元、先位置確認
										if(!manageMoveJudge().isChange(target_map_id)) return;
										manageMoveJudge().clear().register(target_map_id);
		
										//■移動処理(z-index考慮)
										touch_element.prependTo(map);
										touch_element.css("z-index",map.children("div[data-type=\""+TYPE_SCHEDULE+"\"]").size());
								}

								var mouseMoveEvent=function(e){

										if(!manageTouch().isOn()) return false;

										//■左端外
										var is_left_over =manageRangeOverFlow(schedule_top_inner).isLeftOver(e.pageX);
										if(is_left_over){

												clearTimeout(timer);
												moveToLayoutWhenOutsideLeft();
												return;
										}

										//■右端外
										var is_right_over=manageRangeOverFlow(schedule_top_inner).isRightOver(e.pageX);
										if(is_right_over){

												clearTimeout(timer);
												moveToLayoutWhenOutsideRight();
												return;
										}

										var target=$(e.target);
										var type=target.attr("data-type");
										var map=((type==TYPE_SCHEDULE_BLOCK)?target:(type==TYPE_SCHEDULE?target.parent("div[data-type=\""+TYPE_SCHEDULE_BLOCK+"\"]"):""))
										if(!map) return;

										moveToElemnet.call(map);
								}

								var mouseUpEvent=function(e){

										var target=$(e.target);
										var type=target.attr("data-type");
										var map=((type==TYPE_SCHEDULE_BLOCK)?target:(type==TYPE_SCHEDULE?target.parent("div[data-type=\""+TYPE_SCHEDULE_BLOCK+"\"]"):""))

										var touch_element=manageTouch().touchElement;
										manageTouch().clear();
	
										if(!touch_element) return false;
		
										//■同位置判定
										var target_map_id=(!map?manageMoveJudge().currentId:map.attr("data-id"));
										var start_block_map_id=manageCurrentTouchElem().startBlockId;
										if(start_block_map_id==target_map_id) return;

										/*
										//■保存処理(ステータス次第で以下実行、それか戻す)
										var current_day_top=touch_element.parents("div[data-type=\""+TYPE_DAYTOP+"\"]");
										resetMapElement(current_day_top.attr("data-time"),function(){
										
												resetElement.call(touch_element,start_block_map_id);
										});
										*/

										//■移動先要素並び調整
										var day_top=touch_element.parents("div[data-type=\""+TYPE_DAYTOP+"\"]")
										var clone_touch_elem=touch_element.clone(true);
										touch_element.remove();
		
										//■追加子要素
										var children=day_top.find("div[data-type=\""+TYPE_SCHEDULE+"\"]").toArray();
										children.push(touch_element.get(0));
										arrangeElement.call(day_top,children,function(){
		
												//■z-index初期化
												day_top.find("div[data-type=\""+TYPE_SCHEDULE+"\"]").css("z-index",0);
		
												//■移動元要素並び調整
												var previous_map=schedule_top_inner.find("div[data-id=\""+start_block_map_id+"\"]");
												var previous_day_top=previous_map.parent();
												var children=previous_day_top.find("div[data-type=\""+TYPE_SCHEDULE+"\"]").toArray();
												arrangeElement.call(previous_day_top,children);
										});
								};

								var mouseDownEvent=function(e){

										var touch_element=$(e.target);
										var type=touch_element.attr("data-type");
										if(!checkScheduleTargetElem(type)) return false;
		
										//■タッチした要素
										manageTouch().register(touch_element);
		
										//■移動ブロック設定
										var current_map_id=manageCurrentTouchElem().register(touch_element).getScheduleBaseBlockId();
										manageMoveJudge().register(current_map_id);
								};

								var allScheduleEvents=function(){

										var targets=this;
										targets.on("mousemove",function(e){
		
												mouseMoveEvent(e);
												return false;

										}).on("mouseup",function(e){

												mouseUpEvent(e);
												return false;

										}).on("mousedown",function(e){

												mouseDownEvent(e);
												return false;
										});
								};

								var scheduleBtnLeftEvent=function(e,life,callback){

										if(life==0){
										
												callback();
												return;
										}

										var dates=[];
										var prev_date=manageScheduleDate(schedule_head).getPreviousDate();
										var before_prev_date=Number(prev_date).addDate(-1);
										var data=information_data[before_prev_date];
										var fn=arguments.callee;

										//■新規データ取得
										if(data==undefined){

												var params={}
												params["date"]=before_prev_date;
												apiGeoRequests.getSubDateApi(params,function(status,res){

														var informations=res["data"]["informations"];
														var week        =res["data"]["week"];
														$.extend(information_data,informations);
														fn(e,life,callback);
												});
												return;
										}

										//■消す日
										var latest_date=manageScheduleDate(schedule_head).getLatestDate();

										//■前日データ設定
										schedule_head.children("div[data-time=\""+latest_date+"\"]").hide();
										schedule_top_inner.children("div[data-time=\""+latest_date+"\"]").hide();

										//■表示確認
										var current_head_top=schedule_head.children("div[data-time=\""+before_prev_date+"\"]");
										var current_day_top =schedule_top_inner.children("div[data-time=\""+before_prev_date+"\"]");
										if(current_head_top.size()>0){
										
												current_head_top.show();
												current_day_top.show();
												fn(e,(life-1),callback);
												return;
										}

										addScheduleHead.call(schedule_head,[before_prev_date],"prepend",function(){

												addScheduleTimeElements.call(schedule_top_inner,[before_prev_date],"prepend",function(){

														var informations=makeInformationAry([before_prev_date],information_data)
														addSchuduleElements.call(schedule_top_inner,informations,function(){

																//■イベント設定
																var day_top=schedule_top_inner.children("div[data-time=\""+before_prev_date+"\"]");
																var schedule_blocks=day_top.children("div[data-type=\""+TYPE_SCHEDULE_BLOCK+"\"]");
																allScheduleEvents.call(html);

																fn(e,(life-1),callback);
														})
												});
										});
								};

								var scheduleBtnRightEvent=function(e,life,callback){

										if(life==0){
										
												callback();
												return;
										}

										//■最新の日付
										var dates=[];
										var latest_date=manageScheduleDate(schedule_head).getLatestDate();
										var after_latest_date=Number(latest_date).addDate(1);
										var data=information_data[after_latest_date];
										var fn=arguments.callee;

										//■新規データ取得
										if(data==undefined){
										
												var params={}
												params["date"]=after_latest_date;
												apiGeoRequests.getAddDateApi(params,function(status,res){

														var informations=res["data"]["informations"];
														var week        =res["data"]["week"];
														$.extend(information_data,informations);
														fn(e,life,callback);
												});
												return;
										}

										//■消す日
										var prev_date=manageScheduleDate(schedule_head).getPreviousDate();

										//■削除処理
										schedule_head.children("div[data-time=\""+prev_date+"\"]").hide();
										schedule_top_inner.children("div[data-time=\""+prev_date+"\"]").hide();

										//■表示確認
										var current_head_top=schedule_head.children("div[data-time=\""+after_latest_date+"\"]");
										var current_day_top =schedule_top_inner.children("div[data-time=\""+after_latest_date+"\"]");
										if(current_head_top.size()>0){
										
												current_head_top.show();
												current_day_top.show();
												fn(e,(life-1),callback);
												return;
										}

										//■翌日データ設定
										addScheduleHead.call(schedule_head,[after_latest_date],"append",function(){

												addScheduleTimeElements.call(schedule_top_inner,[after_latest_date],"append",function(){

														var informations=makeInformationAry([after_latest_date],information_data)
														addSchuduleElements.call(schedule_top_inner,informations,function(){

																//■イベント設定
																var day_top=schedule_top_inner.children("div[data-time=\""+after_latest_date+"\"]");
																var schedule_blocks=day_top.children("div[data-type=\""+TYPE_SCHEDULE_BLOCK+"\"]");
																allScheduleEvents.call(html);

																fn(e,(life-1),callback);
														})
												});
										});
								};

								//■要素の差し戻し
								var resetElement=function(start_map_id,callback){

										var touch_element=this;
										schedule_top_inner.find("div[data-id=\""+start_map_id+"\"]").append(touch_element);
										if(!$.isFunction(callback)) return;
										touch_element.ready(function(){
										
												callback();
										});
								}

								//■元の頁の状態
								var resetMapElement=function(current_date,callback){

										var start_left_side_id =manageCurrentTouchElem().startLeftSideId;
										var current_left_side_id=manageScheduleDate(schedule_head).getPreviousDate();
										var current_day_num=schedule_head.find("div[data-type=\""+TYPE_SCHEDULE_HEAD+"\"]").size();

										//■同位置
										if(current_left_side_id==start_left_side_id){
										
												callback(0);
												return;
										}

										//■日付差分
										var diff=Number(current_left_side_id).diffDate(start_left_side_id);

										//■加算
										if(current_left_side_id>start_left_side_id){

												scheduleBtnLeftEvent(null,diff,function(){
												
														callback(1);
												});
												return;
										}

										scheduleBtnRightEvent(null,diff,function(){

												callback(-1);
										});
								}

								//■日付進む
								schedule_btn_right.one("click",function(e){

										var fn=arguments.callee;
										scheduleBtnRightEvent(e,1,function(){

												schedule_btn_right.one("click",fn);	
										});
								});

								//■日付戻る
								schedule_btn_left.one("click",function(e){

										var fn=arguments.callee;
										scheduleBtnLeftEvent(e,1,function(){

												schedule_btn_left.one("click",fn);
										});
								});

								allScheduleEvents.call(html);

						});
				});
		});

		//■枠の範囲
		var manageRangeOverFlow=(function(){

				var fn=function(params){

						this.params=params
						this.params["offset_bottom"]=params["offset_top"]+params["height"];
						this.params["offset_right"] =params["offset_left"]+params["width"];
						console.log(this.params);
				}

				fn.prototype.isLeftOver=function(left){

						if(this.params["offset_left"]>=left) return true;
						return false;
				}

				fn.prototype.isRightOver=function(left){

						if(left>this.params["offset_right"]) return true;
						return false;
				}

				fn.prototype.isTopOver=function(top){

						if(this.params["offset_top"]>=top) return true;
						return false;
				}

				fn.prototype.isBottomUnder=function(top){

						if(top>this.params["offset_bottom"]) return true;
						return false;
				}

				fn.prototype.isOver=function(left,top){

						if(this.isTopOver(top)   || this.isBottomUnder(top)) return true;
						if(this.isLeftOver(left) || this.isRightOver(left)) return true;
						return false;
				}

				return function(selector){

						if(fn.instance instanceof fn) return fn.instance;

						var instance=new fn({
						
								"offset_top" :selector.offset().top,
								"offset_left":selector.offset().left,
								"width"      :selector.width(),
								"height"     :selector.height()
						});

						fn.instance=instance;
						return instance;
				}

		}());

		//■ボタンの日付
		var manageScheduleDate=(function(){

				var fn=function(selector){

						if(selector!=undefined) this.selector=selector;
				}


				fn.prototype.getHeaders=function(){

						var children=this.selector.children("div[data-type=\""+TYPE_SCHEDULE_HEAD+"\"]");
						return children;
				}

				fn.prototype.getLatestDate=function(){

						var children=this.getHeaders().filter(":visible");
						var last=children.eq(children.size()-1);
						var time=last.attr("data-time");
						return time;
				}

				fn.prototype.getPreviousDate=function(){

						var children=this.getHeaders().filter(":visible");
						var last=children.eq(0);
						var time=last.attr("data-time");
						return time;
				}

				return function(selector){

						if(fn.instance instanceof fn){
						
								return fn.instance;
						}

						var instance=new fn(selector);
						fn.instance=instance;
						return instance;
				}
		}());

		//■タッチ要素
		var manageTouch=(function(){

				var fn=function(selector){

						if(selector instanceof jQuery){

								this.touchElement=selector;
						} 
				}

				fn.prototype.isOn=function(){

						return (this["touchElement"] instanceof jQuery);
				}

				fn.prototype.register=function(selector){
				
						this.touchElement=selector;
						return this;
				}

				fn.prototype.clear=function(){

						if(!this.isOn()) return;
						delete this["touchElement"];
						return this;
				}

				return function(selector){

						if(fn.instance instanceof fn){
						
								return fn.instance;
						}

						var instance=new fn(selector);
						fn.instance=instance;
						return instance;
				}
		}());

		//■領域の変更確認
		var manageMoveJudge=(function(){

				var fn=function(){}

				fn.prototype.register=function(id){

						this.currentId=id;
						return this;
				}

				fn.prototype.isChange=function(id){

						if(id==undefined) return false;
						if(this.currentId==id) return false;
						return true;
				}

				fn.prototype.clear=function(){

						this.currentId="";
						return this;
				}

				return function(id){

						if(fn.instance instanceof fn) return fn.instance;

						var instance=new fn(id);
						fn.instance=instance;
						return instance;
				}
		}())

		//■タッチElem
		var manageCurrentTouchElem=(function(){

				var fn=function(selector){

						this.touchElement=null;
						this.startBlockId=null;
						this.startLeftSideId=null;
						this.startRightSideId=null;
				}

				fn.prototype.getScheduleBaseBlockId=function(){

						var target=this.scheduleBaseBlock();
						return target.attr("data-id");
				}

				fn.prototype.scheduleBaseBlock=function(){

						return this.touchElement.parents("div[data-type=\""+TYPE_SCHEDULE_BLOCK+"\"]");
				}

				fn.prototype.register=function(selector){

						this.touchElement=selector;
						this.startBlockId=this.getScheduleBaseBlockId();
						this.startLeftSideId=manageScheduleDate(schedule_head).getPreviousDate();
						this.startRightSideId=manageScheduleDate(schedule_head).getLatestDate();
						return this;
				}

				fn.prototype.clear=function(){

						fn.instance=null;
						this.touchElement=null;
						this.startBlockId=null;
						return this;
				}

				return function(){

						if(fn.instance instanceof fn) return fn.instance;
						var instance=new fn();
						fn.instance=instance;
						return instance;
				}
		}())

		//■要素の移動
		var arrangeElement=function(add_elements,callback){

				var day_top=this;
				var schedule_blocks=day_top.children("div[data-type=\""+TYPE_SCHEDULE_BLOCK+"\"]");
				var child_size=add_elements.length;

				var __arrange=function(callback,index){

						if(index==undefined) index=child_size;

						if(1>index){

								if($.isFunction(callback)) callback();
								return;
						}

						var fn=arguments.callee;
						var __index=(child_size-index);
						var __child=$(add_elements[__index])
						schedule_blocks.eq(__index).empty().prepend(__child);
						__child.ready(function(){
						
								fn(callback,(index-1));
						});
				}

				__arrange(callback);
		}

});
