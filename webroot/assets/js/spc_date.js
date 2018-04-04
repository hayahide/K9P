
Date.prototype.getLastDayInaMonth=function(){

		var dt=this;
		var d=new Date(dt.getFullYear(),dt.getMonth()+1,0)
		return d.getDate();
}

Date.prototype.getWeek=function(weekDayList){

		var date=this;
		//var weekDayList=["日","月","火","水","木","金","土"] ;
		var weekDay=weekDayList[date.getDay()];
		return weekDay;
}

Date.prototype.addDateArray=function(num){

		var dates=[];
		var d=this;
		d.setDate(d.getDate()+parseInt(num));
		dates.push(d.getFullYear()+"");
		dates.push(Number(d.getMonth()+1).addZero());
		dates.push(Number(d.getDate()).addZero());
		return dates;
}

Date.prototype.addDate=function(num){

		var dates=this.addDateArray(num);
		var next_date=dates.join("");
		return next_date;
}

Date.prototype.getHisByMs=function(){

		var date=this;
		var his=[];
		his.push(Number(date.getHours()).addZero());
		his.push(Number(date.getMinutes()).addZero());
		his.push(Number(date.getSeconds()).addZero());
		return his;
}

Date.prototype.diffDate=function(d2){

		var diff1=this.getTime();
		var diff2=d2.getTime();
		var msdiff=Math.abs(diff1-diff2);
		var daysDiff=Math.floor(msdiff/(1000*60*60*24));
		return daysDiff;
}

Date.prototype.getWeekDayFromNow=function(num){

		var date=this;
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

Date.prototype.getMonthDays=function(){

		var date=this;
		var current_ymd=new Date([date.getFullYear(),Number(date.getMonth()+1).addZero(),"01"].join("-"));
		var last_date  =current_ymd.getLastDayInaMonth();
		var days       =current_ymd.getWeekDayFromNow(last_date);
		return days;
}

Date.prototype.getYmdByMs=function(){

		var date=this;
		var ymd=[];
		ymd.push(Number(date.getFullYear()).addZero());
		ymd.push(Number(date.getMonth()+1).addZero());
		ymd.push(Number(date.getDate()).addZero());
		return ymd;
}

Date.prototype.addMonthSeparately=function(num){

		var date=this;
		date.setMonth(date.getMonth()+num);
		return date.getYmdByMs().concat(date.getHisByMs());
}

Date.prototype.addDateSeparately=function(num){

		var date=this;
		date.setDate(date.getDate()+num);
		return date.getYmdByMs().concat(date.getHisByMs());
}

Date.prototype.removeSecond=function(){

		var date=this;
		var start_ymd=date.getYmdByMs();
		var start_his=date.getHisByMs();
		start_his.pop();

		var ymd=start_ymd.join("-");
		var hi =start_his.join(":");
		return ymd+" "+hi;
}

Date.prototype.getLastDate=function(){

		return new Date(this.getFullYear(),this.getMonth()+1,0).getDate();
}

Date.prototype.getDateRange=function(end){

		var start=this;
		var schedule_dates=[];
		while(true){

				var ymd=start.getYmdByMs().join("");
				schedule_dates.push(parseInt(ymd));
				if(start.getTime()==end.getTime()) break;
				start.setDate(start.getDate()+1);
		}

		return schedule_dates;
}

Date.prototype.getMonday=function(){

		var d=this;
		var day = d.getDay(),
		diff = d.getDate() - day + (day == 0 ? -6:1); // adjust when day is sunday
		return new Date(d.setDate(diff));
}

//2016/11/23 Hien Nguyen add start
function addZero(i) {

	if (i < 10) {
		i = "0" + i;
	}

	return i;
}
// Date.prototype.setFormatCurrentDate=function(){
function setFormatCurrentDate(date) {

		var a = date.split(/[^0-9]/);
		var d = new Date(a[0],a[1]-1,a[2],a[3],a[4],a[5]);
		var dd = addZero(d.getDate());
		var mm = addZero(d.getMonth()+1); //January is 0!
		var yyyy = addZero(d.getFullYear());

		return yyyy+''+mm+''+dd;
}


function setDateSchedule(date) {

		var a = date.split(/[^0-9]/);
		var d = new Date(a[0],a[1]-1,a[2],a[3],a[4],a[5]);
		var dd = addZero(d.getDate());
		var mm = addZero(d.getMonth()+1);

		return (mm+"/"+dd );
}

function setFormatDateTime(date) {

		var hh = addZero(date.getHours());
		var mn = addZero(date.getMinutes());
		var sc = addZero(date.getSeconds());
		var dd = addZero(date.getDate());
		var mm = addZero(date.getMonth()+1);
		var yyyy = addZero(date.getFullYear());

		return (yyyy+"-"+mm+"-"+dd+" "+hh+":"+mn+":"+sc);
}

function setFormatDate(date) {

		var dd = addZero(date.getDate());
		var mm = addZero(date.getMonth()+1); //January is 0!
		var yyyy = addZero(date.getFullYear());
		return (yyyy+'/'+mm+'/'+dd);
}

function setDateScheduleReport(date) {

		var a = date.split(/[^0-9]/);
		var d = new Date(a[0],a[1]-1,a[2],a[3],a[4],a[5]);
		var dd = addZero(d.getDate());
		var mm = addZero(d.getMonth()+1);
		var yyyy = addZero(d.getFullYear());

		return (yyyy+"/"+mm+"/"+dd );
}

function convertStringToDate(str) {

		if(!/^(\d){8}$/.test(str)) return "invalid date";
		var y = str.substr(0,4),
			m = str.substr(4,2),
			d = str.substr(6,2);

		return new Date(y,m,d);
}

function formatFullDate(date) {

		var monthNames = [
			"January", "February", "March",
			"April", "May", "June", "July",
			"August", "September", "October",
			"November", "December"
		];

		var day = date.getDate();
		var monthIndex = date.getMonth();
		var year = date.getFullYear();
		var hh = addZero(date.getHours());
		var mn = addZero(date.getMinutes());
		var sc = addZero(date.getSeconds());

		return monthNames[monthIndex] + ' ' +day + ' ' + year + ' ' + hh + ':' + mn + ':' + sc;
}
//2016/11/23 Hien Nguyen add end
