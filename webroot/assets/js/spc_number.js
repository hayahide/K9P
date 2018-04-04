Number.prototype.addZero=function(){

		var val=(this+"");
		val=((2>val.length)?("0"+val):val);
		return val;
}

Number.prototype.splitYmd=function(){

		var time=(this+"");
		var dates=[];
		dates.push(time.slice(0,4));
		dates.push(time.slice(4,6));
		dates.push(time.slice(6,8));
		return dates;
}

Number.prototype.getWeek=function(){

		var weekDayList=["日","月","火","水","木","金","土"] ;
		var date=new Date(this.dateFormatYmd());
		var weekDay=weekDayList[date.getDay()];
		return weekDay;
}

Number.prototype.addDate=function(num){

		var date=this;
		var dates=[];
		var d=new Date(Number(date).dateFormatYmd());
		d.setDate(d.getDate()+(num));
		dates.push(d.getFullYear());
		dates.push(Number(d.getMonth()+1).addZero());
		dates.push(Number(d.getDate()).addZero());
		var next_date=dates.join("");
		return next_date;
}

Number.prototype.diffDate=function(d2){

		var d1=this;
		var diff1=new Date(d1.dateFormatYmd()).getTime();
		var diff2=new Date(Number(d2).dateFormatYmd()).getTime();
		var msdiff=Math.abs(diff1-diff2);
		var daysDiff=Math.floor(msdiff/(1000*60*60*24));
		return daysDiff;
}

Number.prototype.changeToSeconds=function(){

		var ms=this;
		his=ms.getHisByMs();
		var h=parseInt(his[0])*60*60;
		var m=parseInt(his[1])*60;
		var s=parseInt(his[2]);
		var second=h+m+s;
		return second;
}

Number.prototype.getHisByMs=function(){

		var ms=this;
		var date=new Date(ms);
		return date.getHisByMs();
}

Number.prototype.addZero=function(){

		var val=(this+"");
		val=((2>val.length)?("0"+val):val);
		return val;
}

Number.prototype.splitYmd=function(){

		var time=(this+"");
		var dates=[];
		dates.push(time.slice(0,4));
		dates.push(time.slice(4,6));
		dates.push(time.slice(6,8));
		return dates;
}

Number.prototype.dateFormatYmd=function(){

		var dates=this.splitYmd();
		return dates.join("/");
}

Number.prototype.getWeek=function(){

		var weekDayList=["日","月","火","水","木","金","土"] ;
		var date=new Date(this.dateFormatYmd());
		return date.getWeek();
}

Number.prototype.addDate=function(num){

		var date=this;
		var dates=[];
		var d=new Date(Number(date).dateFormatYmd());
		return d.addDate(num);
}

Number.prototype.diffDate=function(d2){

		var _d1=this;
		var _d2=new Date(Number(d2).dateFormatYmd())
		return new Date(_d1.dateFormatYmd()).diffDate(_d2);
}

Number.prototype.changeToSeconds=function(){

		var ms=this;
		his=ms.getHisByMs();
		var h=parseInt(his[0])*60*60;
		var m=parseInt(his[1])*60;
		var s=parseInt(his[2]);
		var second=h+m+s;
		return second;
}

Number.prototype.getHisByMs=function(){

		var ms=this;
		var date=new Date(ms);
		return date.getHisByMs();
}

Number.prototype.addMinutesZero=function(){

		return this.splitYmd().join("/")+" 00:00:00";
}

Number.prototype.floatFormat=function(n){

		var _pow=Math.pow(10,n);
		return Math.round(this*_pow)/_pow;
}

Number.prototype.getDateRange=function(end){

		var start=this;
		var schedule_dates=[];

		while(true){

				schedule_dates.push(parseInt(start));
				if(start==end) break;
				var date=new Date(start.addMinutesZero());
				date.setDate(date.getDate()+1);
				start=Number(date.getYmdByMs().join(""));
		}

		return schedule_dates;
}


