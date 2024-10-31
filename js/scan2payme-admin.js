jQuery(document).ready(function($) { 	   //wrapper
	$("#scan2payme_option_account").change(function() {

        // dont call when selection was (none)
        if(this.value == 'OPTION_ACCOUNT_NONE'){
            return ;
        }

        // clear and disable account fields
        $('.scan2payme_account_setting').prop( "disabled", true );
        $("#scan2payme_option_BIC").val("");
        $("#scan2payme_option_Name").val("");
        $("#scan2payme_option_IBAN").val("");

		var this2 = this;
		$.post(my_ajax_obj.ajax_url, {
	       _ajax_nonce: my_ajax_obj.nonce,
			action: "scan2payme_option_account_changed",
	  		changed_to: this.value
  		}, function(data) {
            // enable and populate account fields or display error
            $('.scan2payme_account_setting').prop( "disabled", false );
            if(data.error){
                alert(data.message);
            } else {
                $("#scan2payme_option_BIC").val(data.bic);
                $("#scan2payme_option_Name").val(data.name);
                $("#scan2payme_option_IBAN").val(data.iban);
            }
		});
        
	});
});