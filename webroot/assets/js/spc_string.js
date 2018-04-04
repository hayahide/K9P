String.prototype.isOk=function(){

		return this.toLowerCase()=="yes";
}

String.prototype.__shift=function(){

		var s=this;
		var __s=s.split("");
		__s.shift();
		return __s.join("");
}

String.prototype.strCut=function(life,strs){

		if(!strs) strs=[];

		//■生き残り
		if(1>this.length) return strs.join("");	

		var str=this.substr(0,1);
		var sublife=2;
		if(/[a-zA-Z#_&\/@\(\)"'\?!\.,\$\^\[\]\s]/.test(str)) sublife=1.5;
		
		//■Life切れ(...を考慮)
		if(4>(life-sublife)) return strs.join("")+"...";	
		strs.push(str);
 
		life-=sublife;
		return this.__shift().strCut(life,strs);
}

String.prototype.replaceDateFormat=function(){

		return this.replace(/-/g,'/');
}

String.prototype.strpad = function(max) {
	var str = this;
	str = str.toString();
	return str.length < max ? ("0" + str).strpad(max) : str;
}

String.prototype.ridFirstZero=function(){

		var val=this.indexOf("0")==0?this.substr(1):this;
		return val;
}

