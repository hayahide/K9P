Number.prototype.currencyFormat=function(fixed){
	var fixed=fixed||0;
	var str=this.toFixed(fixed);
	return str.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
}

$(document).ready(function() {

    $(document).on("keyup",'.data-numeric',function(event){

        var val=$(this).val();
        var target_char="-";
        var index=val.indexOf(target_char);
        if(0>index) return;
        if(index==0) return;
        val=val.replace("-","");
        $(this).val(val);
    });

	$(document).on('keypress', '.data-numeric', function (event) { 
		
		// Check the '-' mark position.
		var reg = new RegExp('^-\\d+$');
		var lastValue = event.target.value;
		// check if signed digital
		if(reg.test(lastValue)) {
			// Prevent inter '-' mark
			if(event.which == 45) {
				event.preventDefault(); //stop character from entering input
			}
		} else {
			if(lastValue == '0' || lastValue == '') {
				if(event.target.selectionStart) {
					if(event.which == 45 && event.target.selectionStart == 1) {
						event.preventDefault();
					}
				}
			} else {
				if(event.which == 45) {
					event.preventDefault();
				}
			}
		}

		// maintain 
		if(event.which != 8 
			&& event.which != 45 // Exclude the '-' mark
			&& isNaN(String.fromCharCode(event.which))){
	    	event.preventDefault(); //stop character from entering input
	    }
	    
	});

	$(document).scroll(function(event) {
		$('.navbar-inverse').css('left', '-'+$(document).scrollLeft()+'px');
	});
});
