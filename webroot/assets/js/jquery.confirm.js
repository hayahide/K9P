(function($){

	$.confirm = function(params){

		if($('#confirmOverlay').length){
			// A confirm is already shown on the page:
			return false;
		}

		var buttonHTML = '';
		$.each(params.buttons,function(name,obj){

			// Generating the markup for the buttons:

			buttonHTML += '<a href="#" class="button '+obj['class']+'">'+name+'<span></span></a>';

			if(!obj.action){
				obj.action = function(){};
			}
		});

		var results = '';
		if(params.message.trim() != 0) {
			results +='<p>'+params.message+'</p>';
		}

		var markup = [
			'<div id="confirmOverlay">',
			'<div id="confirmBox">',
			'<div class="warning">',params.main_title,'<span class="glyphicon glyphicon-remove pull-right"></span></div>',
			'<div class="conf-info">',
				'<h1>',params.title,'</h1>',//change this text to schedule/site/staff...
				results,
			'</div>',
			'<div id="confirmButtons">',
			buttonHTML,
			'</div></div></div>'
		].join('');

		$(markup).hide().appendTo('body').fadeIn();

		var buttons = $('#confirmBox .button'),
			i = 0;

		$.each(params.buttons,function(name,obj){
			buttons.eq(i++).click(function(){

				// Calling the action attribute when a
				// click occurs, and hiding the confirm.
				$.confirm.hide(function(){

						obj.action();
				});

				return false;
			});
		});

		if(isSp()){
			$('body').addClass('mobi-confirm');
			$(window).resize(function(event) {
				$('#confirmBox').css('top', ( $(window).height() - $('#confirmBox').height() ) / 2 + 'px' );
			});
			$('#confirmBox').css('top', ( $(window).height() - $('#confirmBox').height() ) / 2 + 'px' );
		}

		$( "#confirmOverlay span" ).on( "click", function() {
		  $('#confirmOverlay').fadeOut('1000', function() {

				$(this).remove();
				if($.isFunction(params["close"])){ 

						params["close"]();
						return;
				}
		  });
		});
	}

	$.confirm.hide = function(callback){

			$('#confirmOverlay').fadeOut(function(){

					$(this).remove();
					setTimeout(function(){

							callback();
					},100);
			});
	}

})(jQuery);
