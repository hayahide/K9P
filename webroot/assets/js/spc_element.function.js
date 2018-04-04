//■キャッシュ
//
$.fn.propDisable=function(flg){

	var target=$(this);
	target.prop("disabled",(!!flg)?true:false).css("background-color",(!!flg?"#ccc":"#fff"));
	return target;
}

$.fn.setDocumentCache=function(list,name){

	var origin=this.find("input[type=hidden]"+((!!name)?"[name=\""+name+"\"]":""));
	var origin_length=origin.size();

	//■1つの場合は編集可能か評価
	if(list.length==1 && origin_length==1){
		origin.val(list[0]);
		return this;	
	}

	if(origin_length>0) origin.remove();

	var attr={"type":"hidden"};
	if(!!name) attr["name"]=name;

	var fragment=document.createDocumentFragment();
	var origin=$("<input>").attr(attr);
	var hidden;
	for(var i in list){

		hidden=origin.clone();
		hidden.val(list[i]);
		fragment.appendChild(hidden.get(0));
	}
	this.get(0).appendChild(fragment);
	return this;
}

//■キャッシュ取得
$.fn.getDocumentCache=function(name){

	var	elem=this.find("input[type=hidden]"+((!!name)?"[name=\""+name+"\"]":"")); 
	var list=[];
	for(var i=0;i<elem.size();i++) list.push(elem.eq(i).val());
	return list;
}

$.fn.isDisplay=function(){

	return this.css("display")!="none";
}

$.fn.hackZoomControlForSp=function(params){

	/*
	var styles={};
	styles["-webit-transform"]="scale(1.0)";
	styles["transform"]="scale(1.0)";
	styles["font-size"]="20px";
	$.extend(styles,((params==undefined)?{}:params));
	this.css(styles);
	*/
}

$.fn.hasScrollBar=function(){

    return this.get(0)?this.get(0).scrollHeight>this.innerHeight():false;
}

$.fn.insertDate=function(date){

	var target=this;
	var ymd=new Date(Number(date).addMinutesZero()).getYmdByMs();
	var m=ymd[1].ridFirstZero();
	var d=ymd[2].ridFirstZero();
	var md=m+"/"+d;
	var text=target.html();
	text=text.replace(/##date##/,md);
	target.html(text);
}

$.fn.inputError=function(){

	var target=this;
	target.css("background-color","red");
}

