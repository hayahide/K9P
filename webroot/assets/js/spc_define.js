try {
	if(!Object.prototype.__defineGetter__ &&
	Object.defineProperty({}, "x", { get: function() { return true } }).x) {
		Object.defineProperty(Object.prototype, "__defineGetter__",
	 { enumerable: false, configurable: true,
		value: function(name, func) {
			Object.defineProperty(this, name,
			 { get: func, enumerable: true, configurable: true });
		} 
	 });
		Object.defineProperty(Object.prototype, "__defineSetter__",
	 { enumerable: false, configurable: true,
		value: function(name, func) {
			Object.defineProperty(this, name,
			 { set: func, enumerable: true, configurable: true });
		} 
	 });
	}
} catch(defPropException) { /*Do nothing if an exception occurs*/ };
