if(Object.values==undefined){

		Object.values=function(obj){ 

				if(!obj) return [];
		
				if(!obj) return [];
				return Object.keys(obj).map(function(key){
		
						return obj[key];
				});
		};
}

if(Object.is==undefined){

		Object.is=function(type,obj){

		    	var clas=Object.prototype.toString.call(obj).slice(8,-1);
				console.log(clas);
		    	return obj!==undefined&&obj!==null&&clas===type;
		}
}

