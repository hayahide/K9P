/***********API送信************/
var apiGeoRequests=function(){

	this.basePhpExecFile='';
}

apiGeoRequests.prototype.setBaseURL=function(url){

	this.basePhpExecFile=url;
}

apiGeoRequests.prototype.setURL=function(url){

		this.requestURL=(this.basePhpExecFile+url);
}

apiGeoRequests.prototype.request=function(data,params,callback){

		var __params = {

				url     :this.requestURL,
				async   :true,
				data    :data,
				type    :"POST",
				cache   :false,
				dataType:"json",
				error   :function(){

						var ary = Array.prototype.slice.call(arguments);
						ary.unshift(false);
						if($.isFunction(callback)) callback.apply(this,ary);
				},
				success :function(){

						var ary = Array.prototype.slice.call(arguments);
						ary.unshift(true);
						if($.isFunction(callback)) callback.apply(this, ary);
						return false;
				},
		};

		//var ajaxParams=$.extend({},__params,params);
		var ajaxParams=$.extend(true,__params,params);
		//$.extend(__params,(!params?{}:params));
		return $.ajax(ajaxParams);
}
