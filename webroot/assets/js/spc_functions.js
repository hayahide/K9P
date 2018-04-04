var isNetworkError=function(){

		var network_unknown="NETWORK_UNKNOWN";
		var network_disconnected="NETWORK_DISCONNECTED";
		var network_connected="NETWORK_CONNECTED";
		window[network_unknown]=-1;
		window[network_disconnected]=0;
		window[network_connected]=1;

		var res={};
		switch(true){

				case(navigator["onLine"]==undefined):
				res["status"]=window[network_unknown];
		break;
				case(navigator["onLine"]==false):
				res["status"]=window[network_disconnected];
		break;
				default:
				res["status"]=window[network_connected];
		break;
		}

		return res;
}

var getPageX=function(e){

		if(e==undefined) return 0;
		if("pageX" in e) return e.pageX;
		if(!("originalEvent" in e)) return 0;
		if(("pageX" in e["originalEvent"]) && e["originalEvent"].pageX>0) return e["originalEvent"].pageX;
		if(("changedTouches" in e["originalEvent"]) && e["originalEvent"]["changedTouches"].length>0 && e["originalEvent"].changedTouches[0].pageX>0) 
			return e["originalEvent"].changedTouches[0].pageX;
		if(("touches" in e["originalEvent"]) && e["originalEvent"].touches.length>0 && e["originalEvent"].touches[0].pageX>0) return e["originalEvent"].touches[0].pageX;
        return 0;
}

var getPageY=function(e){

		if(e==undefined) return 0;
		if("pageY" in e) return e.pageY;
		if(!("originalEvent" in e)) return 0;
		if("pageY" in e["originalEvent"] && e["originalEvent"].pageY>0) return e["originalEvent"].pageY;
		if(("changedTouches" in e["originalEvent"]) && e["originalEvent"]["changedTouches"].length>0 && e["originalEvent"].changedTouches[0].pageY>0) 
				return e["originalEvent"].changedTouches[0].pageY;

		if(("touches" in e["originalEvent"]) && e["originalEvent"].touches.length>0 && e["originalEvent"].touches[0].pageY>0) return e["originalEvent"].touches[0].pageY;
        return 0;
}

//■タッチしてその位置まで戻されると以内になる
//■moveを使えば正常に判定可能
var isDiffPageX=function(start,end,point){

		point=(point==undefined?10:point);
		var start_point_x=getPageX(start);
		var current_point_x=getPageX(end);
		var diff_x=Math.abs(Math.abs(start_point_x)-Math.abs(current_point_x));
		if(diff_x>point) return true;
		return false;
}

//■タッチしてその位置まで戻されると以内になる
//■moveを使えば正常に判定可能
var isDiffPageY=function(start,end,point){

		point=(point==undefined?10:point);
		var start_point_y=getPageY(start);
		var current_point_y=getPageY(end);
		var diff_y=Math.abs(Math.abs(start_point_y)-Math.abs(current_point_y));
		if(diff_y>point) return true;
		return false;
}

//■タッチしてその位置まで戻されると以内になる
//■moveを使えば正常に判定可能
var isDiffPageXY=function(start,end,point){

		point=(point==undefined?10:point);

		var diff_x=isDiffPageX(start,end,point);
		if(diff_x) return true;

		var diff_y=isDiffPageY(start,end,point);
		if(diff_y) return true;

		return false;
}

var deviceClickEventEnd=function(){

		var event="click";
		if(isSmart()) event="touchend";
		if(!isSmart() && isSp()) event="touchend";
		return event;
}

var loadScript=function(path,callback){

		var self=this;
		var src_split=path.split(".");
		var ext     =src_split[src_split.length-1];
		var elements={"js":"script","css":"link"};
		var types   ={"js":"text/javascript","css":"text/css"};
		var src     ={"js":"src","css":"href"};
		var other   ={"js":[],"css":[{"property":"rel","value":"stylesheet"}]};

		var done=false;
		var head=document.getElementsByTagName('head')[0];
		var script=document.createElement(elements[ext]);
		if(!!src[ext])   script[src[ext]]=path;
		if(!!types[ext]) script.type=types[ext];

		if(other[ext].length>0){
		
				for(var i in other[ext]){
				
						if(!other[ext].hasOwnProperty(i)) continue;
						script[other[ext][i]["property"]]=other[ext][i]["value"];
				}
		}

		head.appendChild(script);
		script.onload=script.onreadystatechange=function(){

				if(!done && (!this.readyState || this.readyState==="loaded" || this.readyState==="complete")){

						done=true;
						if(callback!=undefined) callback();
						script.onload=script.onreadystatechange=null;
						if(head && script.parentNode) head.removeChild(script);
				}
		};
}

var getWeekDayFromNow=function(start_date,num){

                var n=Number(start_date).addMinutesZero();
                var date=new Date(n);
                var ymd=[];
                var res=[];
                ymd.push(date.getFullYear());
                ymd.push(Number(date.getMonth()+1).addZero());
                ymd.push(Number(date.getDate()).addZero());
                var origin=Number(ymd.join(""));
                res.push(origin+"");
                for(var i=1;i<=(num-1);i++) res.push(origin.addDate(i));
                return res;
}

var isSmart=function(){

		var ua=navigator.userAgent.toLowerCase();
		if(ua.indexOf('iphone')>0 || ua.indexOf('ipod')>0 || ua.indexOf('android')>0 && ua.indexOf('mobile')>0) return true;
		return false;
}

var isIos=function(){

		var ua=navigator.userAgent.toLowerCase();
		if(ua.indexOf('iphone')>0 || ua.indexOf('ipod')>0 || ua.indexOf("ipad")) return true;
		return false;
}

var isTablet=function(){

		var ua=navigator.userAgent.toLowerCase();
		if(isSmart()) return false;
		if(ua.indexOf('ipad')>0 || ua.indexOf('android')>0) return true;
		return false;
}

var isSp=function(){

		if(isSmart() || isTablet()) return true;
		return false;
}

var remodalAlert=function(id,main_title,message,params){

	var elem        =$('[data-remodal-id='+id+']');
	var title_html  =elem.find("h1[data-type=\"remodal-title\"]");
	var message_html=elem.find("div[data-type=\"remodal-message\"]");
	var remodal_params=params["params"]==undefined?{}:params["params"];
	title_html.html(main_title);
	message_html.empty().append(message);
	var remodal=elem.remodal(remodal_params);
	remodal.open();

	elem.off("opened").one("opened",function(e){

		if(params["opened"]!=undefined && $.isFunction(params["opened"])) params["opened"].call(remodal,e);

		var start="mousedown";
		var move ="mousemove";
		var end  ="mouseup";

		if(isSp()){

			var start="touchstart";
			var move ="touchmove";
			var end  ="touchend";
		}

		elem.off(start).one(start,function(e){

			var self=$(this);
			var target=$(e.target);
			var type=target.attr("data-type");
			var fn=arguments.callee;

			self.data("e",e);

			if(params[type]!=undefined && $.isFunction(params[type])){
			
				params[type].call(remodal,e,"start");
				var tagname=target.prop("tagName").toLowerCase();
				if(["button"].indexOf(tagname)>-1){

					setTimeout(function(){ self.one(e.type,fn); },1000);
					return;
				}
			}

			self.one(e.type,fn);
		});

		elem.off(end).one(end,function(e){

			var self=$(this);
			var target=$(e.target);
			var type=target.attr("data-type");
			var fn=arguments.callee;
			var tagname=target.prop("tagName").toLowerCase();

			if(isSp()){

				var start_event=self.data("e");
				if(isDiffPageXY(start_event,e,ALLOW_MOVE_TOUCH_POINT)){

					self.one(e.type,fn);
					return;
				}
			}

			if(params[type]!=undefined && $.isFunction(params[type])){

				params[type].call(remodal,e,"end");
				var tagname=target.prop("tagName").toLowerCase();
				if(["button"].indexOf(tagname)>-1){

					setTimeout(function(){ self.one(e.type,fn); },1000);
					return;
				}
			}

			self.one(e.type,fn);
		});
	});

	elem.off("closed").one("closed",function(e){

		if(e.reason && e.reason=="confirmation"){

			if(params["yes"]!=undefined && $.isFunction(params["yes"])) params["yes"].call(remodal,e);
			return;
		}

		if(e.reason && e.reason=="cancellation"){

			if(params["no"]!=undefined && $.isFunction(params["no"])) params["no"].call(remodal,e);
			return;
		}

		if(params["close"]!=undefined && $.isFunction(params["close"])) params["close"].call(remodal,e);
		return;
	});

	return remodal;
}

var nl2br=function(str, is_xhtml) {   

    	var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';    
    	return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}

var modalWindow=function(callback){

        var elem=this;
        var instance=TPL.getInstance("schedule_site_modal_window");
        var tpl =instance.get_schedule_site_modal_window;
        var html=$(tpl);
        elem.prepend(html);

        html.ready(function(){

              	  var win_scroll_top=$(window).scrollTop();
                  var before_position=elem.css("position");
                  var before_width=elem.css("width");
                  var before_height=elem.css("height");
                  elem.css({"position":"fixed","width":"100%","height":"100%","overflow":"hidden"});
                  elem.css("top",-win_scroll_top);
				  var ua=navigator.userAgent.toLowerCase();
				  var is_ios=(ua.indexOf("iphone")>-1 || ua.indexOf("ipad")>-1);

				  if(is_ios){

                            html.one("touchstart",function(e){

                                    var self=$(this);
                                    var target=$(e.target);
                                    var tagName=target.prop("tagName").toLowerCase();
                                    var type=target.attr("data-type");
                                    var touchnum=e.originalEvent.touches.length;
                                    var allowTypes=["schedule-bg-color",
                                                    "schedule-add-worker",
                                                    "schedule-worker-all-label",
                                                    "schedule-truck-all-label",
                                                    "schedule-add-truck",
                                                    "schedule-report-send",
                                                    "schedule-report-cancel"];

                                    self.data("e",e);
                                    self.one(e.type,arguments.callee);

                                    if(type=="schedule-site-modal"){

                                            e.preventDefault();
                                            return;
                                    }

                                    if(touchnum>1){

                                            //e.preventDefault();
                                            return;
                                    }

                                    var scroll_target=target.parents("div[data-sp-scroll=\"YES\"]");
                                    if(scroll_target.size()>0 && !scroll_target.hasScrollBar()){

                                            e.preventDefault();
                                            return;
                                    }

                                    if(allowTypes.indexOf(type)>-1){

                                            e.preventDefault();
                                            return;
                                    }
                            });

                        	html.on("touchmove",function(e){

                                    var self=$(this);
                                    var target=$(e.target);
                                    var type=target.attr("data-type");
                                    var start_event=self.data("e");
                                    if(type=="schedule-site-modal") e.preventDefault();
                                    return true;
                            });
				  }

                  var event="modalclose";
                  html.bind(event,function(){

						if(is_ios){
						
								html.off("touchstart");
								html.off("touchmove");
						}

                        elem.css("position","");
                        elem.css({"width":before_width,"height":before_height,"overflow":"auto"});
                        $(window).scrollTop(0,win_scroll_top);
                        html.off(event).remove();
                  });

                  html.show();
                  callback.call(html,{

                        "cw":html.outerWidth(),
                        "ch":html.outerHeight()
                  });
        });
}

var printManagerModalWindow=function(w,h,p,callback){

		var body=this;
		modalWindow.call(body,function(data){
	
				var overlay=this;
				var js=document.location.protocol+BASE_URL+"assets/js/printThis.js";
				var load=function(callback){
				
						var overlay=this;
						var instance=TPL.getInstance("print_base_layout");
						var tpl =instance.replaceTPL(instance.get_print_base_layout,{});
		
						var p=0.9;
						var win   =$(window);
						var win_w =win.width();
						var win_h =win.height();
						var win_base_w=win_w*p;
						var win_base_h=win_h*p;
		
						var a=10;
						//var baseA4w=21.6;
						//var baseA4h=30.3;
						var baseA4w=w;
						var baseA4h=h;
						var c_baseA4w=baseA4w;
						var c_baseA4h=baseA4h;
		
						while(true){
		
								if(!(win_base_h>=c_baseA4h)) break;
								if(!(win_base_w>=c_baseA4w)) break;
								var x=(c_baseA4w+a)/c_baseA4w;
								c_baseA4w+=a;
								c_baseA4h*=x;
						}
		
						var ch=data["ch"];
						var cw=data["cw"];
						var html=$(tpl);
						html.css("min-width" ,baseA4w+"px");
						html.css("max-height",win_h+"px");
						html.width(floatFormat(c_baseA4w,1));
						html.height(floatFormat(c_baseA4h,1));
						overlay.append(html);
						html.ready(function(){
		
								var inner_h=html.height();
								var mh=(ch/2-(inner_h/2));
								html.css("marginTop",mh+"px");
								callback(html);
						});
				}
	
				if(overlay["printThis"]==undefined){
	
						loadScript(js,function(){
	
								load.call(overlay,function(html){
	
										callback.call(overlay,html);
								});
						});
						return;
				}
	
				load.call(overlay,function(html){
	
						callback.call(overlay,html);
				});
		});
}

var floatFormat=function(number,n){

		var _pow=Math.pow(10,n);
		return Math.round(number*_pow )/_pow;
}

var loading=(function(){

		var self=this;
		var targetVenders=["-o-","-moz-","-webkit-","-ms",""];
		var fn=function(body){

				self.body=body;
		}

		fn.prototype.loadingParent=function(){
		
				return self.body.children("div[data-type=\"loading-img\"]");
		}

		fn.prototype.show=function(){
		
			var loading=this.loadingParent();
			if(loading.size()>0) return;
			if(loading.size()>0) loading.remove();
			var instance=TPL.getInstance("loading_img");
			var tpl=instance.get_loading_img;
			var html=$(tpl);
			self.body.append(html);
			html.css("opacity",1);
			html.css("z-index",99999);
		}

		fn.prototype.hide=function(){

			var self=this;
			var styles={"opacity":0};
			var loading=self.loadingParent();
			var duration="0.3";
			for(var i in targetVenders){
			
				var vendor=targetVenders[i];
				if(!targetVenders.hasOwnProperty(i)) continue;
				styles[vendor+"transition-property"]=vendor+"transform,opacity";
				styles[vendor+"transition-duration"]=duration+"s";
				styles[vendor+"transition-delay"]   ="0s";
			}

			loading.css(styles);
			loading.one("oTransitionEnd mozTransitionEnd webkitTransitionEnd transitionend",function(){

				loading.remove();
			});

			//support.
			var delay=1.1;
			var timer=(duration*1000)*delay;
			setTimeout(function(){

				var loading=self.loadingParent();
				if(1>loading.size()) return;
				loading.remove();
			
			},timer);
		}

		return function(body){

				if(!!fn["instance"] && fn.instance instanceof fn) return fn.instance;
				fn.instance=new fn(body);
				return fn.instance;
		}

}(this));

