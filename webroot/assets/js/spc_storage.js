var spcStorage;
var Storage=function(type){
	
		if(spcStorage instanceof Storage) return;
    	
    	this.storageName={
    	    
    	   		"local"    :"localStorage", 
    	   		"session"  :"sessionStorage"
    	    
    	}[type];
    	
    	this.instanceName={
    	    
    	    	"local"   :"local",
    	    	"session" :"session"
    	    
    	}[type];
    	
    	this.__cache={};
    	this._set=function(key,val,opt){
    	    
    	    	opt=opt||{};
    	    	var maxSize=(opt && (opt.maxSize || -1) || -1);
    	    	var nameSpace=(opt.nameSpace||"");
    	    	opt["expirationAbsolute"]=opt.expire||false;
    	    	var cache = new window[this.storageName](maxSize,false,new window[this.storageName]["storageCacheStorage"](nameSpace));
    	    	this.__cache[nameSpace]=cache;       
    	    	cache.setItem(key,val,opt);
    	}

    	this.get=function(key,nameSpace){
    	    
    	    	var cache=this.__cache[nameSpace||""];  
    	    	if(!cache) cache = new window[this.storageName]("",false,new window[this.storageName]["storageCacheStorage"](nameSpace));
    	    	return cache.getItem(key); 
    	}

    	this.remove=function(key,nameSpace){
    	    
    	    	var cache=this.cache[nameSpace||""]; 
    	    	if(!cache) cache=new window[this.storageName]("",false,new window[this.storageName]["storageCacheStorage"](nameSpace));
    	    	cache.removeItem(key);
    	}

		this._isNull=function(val){

				return val===null;	
		}
};

