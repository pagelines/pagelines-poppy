!function ($) {

	$(document).ready(function() {

		$('.send-poppy').on('click', function(){

			plSendMail()
		})

	})

	function plSendMail() {

		var name = $('.poppy-name').val()
		,	email = $('.poppy-email').val()
		,	msg = $('.poppy-msg').val()
		,	captcha = $('.poppy-captcha').val()
		
		jQuery.ajax({
			type: 'POST'
			, url: ajaxurl
			, data: {
				action: 'ajaxcontact_send_mail'
				,	name: name
				,	email: email
				,	msg: msg
				,	cap: captcha
			}

			,	success: function(response){
				
					var responseElement = jQuery('.poppy-response')

					responseElement
						.hide()
						.removeClass('alert alert-error alert-success')
				

					if (response == "ok") {
						responseElement
							.fadeIn()
							.html('Great work! Your message was sent.')
							.addClass('alert alert-success')

						setTimeout(function() {
							jQuery('.poppy').modal('hide')
						}, 2000);

					} else {
						responseElement
							.fadeIn()
							.html(response)
							.addClass('alert alert-error')
					}
			}

			, error: function(MLHttpRequest, textStatus, errorThrown){
				console.log(errorThrown);
			}

		});

	}

}(window.jQuery);
