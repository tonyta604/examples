/**
 * $.parseParams - parse query string paramaters into an object.
 */
(function($) {
var re = /([^&=]+)=?([^&]*)/g;
var decodeRE = /\+/g;  // Regex for replacing addition symbol with a space
var decode = function (str) {return decodeURIComponent( str.replace(decodeRE, " ") );};
$.parseParams = function(query) {
    var params = {}, e;
    while ( e = re.exec(query) ) { 
        var k = decode( e[1] ), v = decode( e[2] );
        if (k.substring(k.length - 2) === '[]') {
            k = k.substring(0, k.length - 2);
            (params[k] || (params[k] = [])).push(v);
        }
        else params[k] = v;
    }
    return params;
};
})(jQuery);


(function(){
	
	var $btnImport;

	function centerModal() {
	    $(this).css('display', 'block');
	    var $dialog = $(this).find(".modal-dialog");
	    var offset = ($(window).height() - $dialog.height()) / 2;
	    // Center modal vertically in window
	    $dialog.css("margin-top", offset);
	}

	//Sidebar Menu
	sidebarMenuBtn = $('#sidebar_menu_button a');
	sidebarMenuBtnIcon = $('#sidebar_menu_button .switch-icon');
	sidebarMenuPanel = $('#sidebar_menu_inner');

	sidebarMenuBtn.click(function(event){
		event.preventDefault();	
		if(sidebarMenuPanel.hasClass('open')) {
			sidebarMenuPanel.hide(300);
			sidebarMenuPanel.removeClass('open');
			sidebarMenuBtn.removeClass('open');
			sidebarMenuBtnIcon.removeClass('fa-times');
			if (sidebarMenuBtn.attr('name') === 'default') {
				sidebarMenuBtnIcon.addClass('fa-chevron-left');
			} else {
				sidebarMenuBtnIcon.addClass('fa-users');
			}
		} else {
			sidebarMenuPanel.show(300);
			sidebarMenuPanel.addClass('open');
			sidebarMenuBtn.addClass('open');
			sidebarMenuBtnIcon.addClass('fa-times');
			if (sidebarMenuBtn.attr('name') === 'default') {
				sidebarMenuBtnIcon.removeClass('fa-chevron-left');
			} else {
				sidebarMenuBtnIcon.removeClass('fa-users');
			}
			//sidebarMenuBtnIcon.removeClass('fa-users');
		}
	});


	$('.checkall').on('click', function () {
		$(this).closest('table').find(':checkbox').prop('checked', this.checked);
		$(this).closest('table').find('tr:not(":eq(0)")').toggleClass("tr-hightlight", this.checked);	
    });

	$(".checkbox").each(function() {
		
		$(this).click(function(event) {
			$(this).parent().parent().toggleClass("tr-hightlight", this.checked);	
		});
	});

	$("#ldap_import_user_close_button").click(function(event) {
		//event.preventDefault();			
		// reset form
		$('#ldap_import_user_settings_form').trigger("reset");
		$('#ldap-user-form-values').remove();		
		$('.modal-import-user').modal('toggle');
		$('#ldap_import_user_settings_form #user_extension').removeClass('error-input');
		$('.error-status').hide();
		$btnImport.removeClass('disabled');
	});

	$("#ldap_import_user_continue_button").click(function(event) {
		event.preventDefault();	
		var form_values = $('#ldap_import_user_settings_form').serialize();
		var data_values = $('#ldap-user-form-values').attr('data-user-form');		
		var param = $.parseParams(data_values);		
		var $user_extension = $('#ldap_import_user_settings_form #user_extension');
		var form_extension = $user_extension.val();		
		var is_form_validate = true;		
		var extension_arr = [];
		var username_arr = [];

		$('#sip_users_table tbody tr td.td-extension').each(function(idx, el) {
			extension_arr.push($(this).attr('data-extension'));
		});
		$('#sip_users_table tbody tr td.td-username').each(function(idx, el) {
			username_arr.push($(this).attr('data-username'));
		});
		
		values = data_values+"&"+form_values;

		$('#field_username').html(param['username']);
		$('#field_firstname').html(param['firstname']);
		$('#field_lastname').html(param['lastname']);
		$('#field_email').html(param['email']);

		if (form_extension == null || form_extension.length === 0) {
			$('.error-status').html('This is a required field.');
			$user_extension.addClass('error-input');
			$('.error-status').show();
			is_form_validate = false;
		} else if (form_extension.length != param['extension_length']) {
			$('.error-status').html('Extension length must be '+param['extension_length']+' digits.');
			$('.error-status').show();
			$user_extension.addClass('error-input');
			is_form_validate = false;
		} else if ($.inArray(form_extension,extension_arr) != -1) { 
			$('.error-status').html('Extension already exists.');
			$('.error-status').show();
			$user_extension.addClass('error-input');
			is_form_validate = false;
		}

		
		if (is_form_validate) {
			$user_extension.removeClass('error-input');
			$('.error-status').hide();
			//$btn = $('.ldap-import-selected');
			// close modal
			$('.modal-import-user').modal('toggle');

			//reset form field values
			$('#ldap_import_user_settings_form').trigger("reset");
			$('#ldap-user-form-values').remove();					
			$('#ldap_import_user_settings_form #user_extension').removeClass('error-input');
			$('.error-status').hide();

			// ajax call
			$.ajax({
				url: "/includes/ajax_ldap_import_user.php",
				type: "post",
				data: values,
				beforeSend: function(){				
					$btnImport.attr("disabled", "disabled").html("Loading...");
					Spocpbx.growl("Importing user", "warning", 0, 8000);
				},
				success: function(data){	
					if (data.success == 'success') {
						Spocpbx.growl(data.message, "success", 0, 5000);
						// success import message						
						$btnImport.parent().append('<span class="label label-success">Imported</span>');
						$btnImport.parent().parent().toggleClass("highlight-imported", this.checked);
						$btnImport.hide();
						$btnImport.removeClass('ldap-import-selected'); 
						
						$('#sip_users_table tbody').append(data.html_table_tr);

					} else {
						Spocpbx.growl(data.message, "danger", 0, 5000);
						$btnImport.parent().parent().toggleClass("highlight-danger", this.checked);
						$btnImport.parent().append('<i class="fa fa-exclamation"></i> '+data.message);
						$btnImport.hide();
					}
				},
				complete: function(data){					
					$btnImport.removeAttr("disabled").html("Import");	
					
				},
				error:function(data){
					alert(data.message);					
				}

			});				

		}
	});

	$("#ldap_import_form .ldap-import").each(function() {
		
		$(this).click(function(event) {
			event.preventDefault();
			var $btn = $(this);
			var data_values = $(this).attr('data-user');
			var form_values = $('#ldap_import_form').serialize();
			var param = $.parseParams(data_values);
			var username_arr = [];
		
			$('#sip_users_table tbody tr td.td-username').each(function(idx, el) {
				username_arr.push($(this).attr('data-username'));
			});

			values = data_values+"&"+form_values;

			var $inputDiv = $("<div>", {id: "ldap-user-form-values", "data-user-form": values});

			$btn.addClass('ldap-import-selected');

			// center modal
			$('.modal-import-user').on('show.bs.modal', centerModal);
			$(window).on("resize", function () {
				$('.modal-import-user:visible').each(centerModal);
			});
			$('.modal-import-user').modal({backdrop: 'static', keyboard: false});

			$inputDiv.appendTo(".modal-import-user .modal-body");

			$('#field_username').html(param['username']);
			$('#field_firstname').html(param['firstname']);
			$('#field_lastname').html(param['lastname']);
			$('#field_email').html(param['email']);

			$('.form-content').show();
			$('#username_exist').remove();
			$("#ldap_import_user_continue_button").show();
			if ($.inArray(param['username'],username_arr) != -1) {
				$('.form-content').hide();
				$("#ldap_import_user_continue_button").hide();
				$inputDiv = $("<div>", {id: "username_exist", class: "alert alert-danger"}).html('<i class="fa fa-exclamation"></i> Sip Username "'+param['username']+'" already exists.');
				$inputDiv.appendTo(".modal-import-user .modal-body");

			}
			$btnImport = $(this);
			$btnImport.addClass('disabled');
		});
	});
		
	

	$('#ldap_test_connection').click(function(event) {
		event.preventDefault();	
		var $btn = $(this);
		var values = $('#ldap_connection_settings_form').serialize();
		
		$.ajax({
			url: "/includes/ajax_ldap_connection.php",
			type: "post",
			data: values,
			beforeSend: function(){				
				$btn.attr("disabled", "disabled");
				$('i.fa-flask').addClass("hide");
				$('i.fa-spin').removeClass("hide");				
				$('.modal-ajax-loading-message').modal({backdrop: 'static', keyboard: false});

			},
			success: function(data){	
				//alert(data.message);
				if (data.status === 'failed') {					
					$('#ldap_connection_settings .label').removeClass('label-default');
					$('#ldap_connection_settings .label').removeClass('label-success');
					$('#ldap_connection_settings .label').addClass('label-danger');
					$('#ldap_connection_settings .label').html('Not Connected');
				} else {
					$('#ldap_connection_settings .label').removeClass('label-danger');
					$('#ldap_connection_settings .label').addClass('label-success');
					$('#ldap_connection_settings .label').html('Connected');
				}

				$('.bs-example-modal-sm .modal-body').html(data.message);
				$('.bs-example-modal-sm').modal();
			
			},
			complete: function(){
				$btn.removeAttr("disabled");
				$('i.fa-flask').removeClass("hide");
				$('i.fa-spin').addClass("hide");
				$btn.button('reset');
				$('.modal-ajax-loading-message').modal('hide');
			},
			error:function(data){
				alert(data.message);					
			}

		});	

	});

	$('#ldap_refresh_connection').click(function(event) {
		event.preventDefault();	
		var $btn = $(this);
		var values = "connection=reload";
		$.ajax({
			url: "/includes/ajax_ldap_connection.php",
			type: "post",
			data: values,
			beforeSend: function(){				
				$btn.attr("disabled", "disabled");
				$('#ldap_refresh_connection i.fa-refresh').addClass("hide");
				$('#ldap_refresh_connection i.fa-spin').removeClass("hide");				
				$('.modal-ajax-loading-message').modal({backdrop: 'static', keyboard: false});

			},
			success: function(data){	
				//alert(data.message);
				//$('.bs-example-modal-sm .modal-body').html(data.message);
				//$('.bs-example-modal-sm').modal();
				if (data.status === undefined  || data.status === 'success') {
					location.reload(true);
				} else {
					$('.bs-example-modal-sm .modal-body').html(data.message);
					$('.bs-example-modal-sm').modal();
					location.reload(true);
				}
			
			},
			complete: function(){
				$btn.removeAttr("disabled");
				$('#ldap_refresh_connection i.fa-refresh').removeClass("hide");
				$('#ldap_refresh_connection i.fa-spin').addClass("hide");
				$btn.button('reset');
				$('.modal-ajax-loading-message').modal('hide');
			},
			error:function(data){
				alert(data.message);
				//location.reload(true);					
			}

		});	

	});

})();
