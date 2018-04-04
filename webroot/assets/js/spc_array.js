Array.prototype.unique=function(){

		return this.filter(function (x,i,self){

				return self.indexOf(x)===i;
		});
}
