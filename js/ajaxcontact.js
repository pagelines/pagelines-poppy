function ajaxformsendmail(name,email,subject,contents,captcha)
{
jQuery.ajax({
type: 'POST',
url: ajaxcontactajax.ajaxurl,
data: {
action: 'ajaxcontact_send_mail',
poppyname: name,
poppyemail: email,
poppysubject:subject,
poppycontents:contents,
poppycaptcha:captcha
},

success: function(data, textStatus, XMLHttpRequest){
	var id = '#ajaxcontact-response'
	,	ans = data

	if (ans == "ok") {
		jQuery(id)
			.html('')
			.append('Message Sent!')
			.removeClass('alert alert-error')
			.addClass('alert alert-success')

		setTimeout(function() {
			jQuery('#poppy-modal').modal('hide')
		}, 2000);

	} else {
		jQuery(id)
			.html('')
			.append(data)
			.addClass('alert alert-error')
	}
},

error: function(MLHttpRequest, textStatus, errorThrown){
alert(errorThrown);
}

});
}
