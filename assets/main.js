jQuery(document).ready(function($) {
	$('.js-cjExport').on('submit', function(event) {
		event.preventDefault();
		/* Act on the event */
		var options = { 
			beforeSubmit:  function(){
				$('.js-cjExport-btn').removeClass('btn-success');
				$('.js-cjExport-btn').removeClass('btn-warning');
				$('.cj-spinner').show();
			},
			success: function(data){
				$('.cj-spinner ').toggle();
				if ( data.success == true ) {
					$('.js-cjExport-btn').addClass('btn-success');
					if ( data.data.type == 'file' ) {
						$('.js-save-file').attr( 'href', data.data.process );
						$('.js-save-file').addClass('show');
					}
				}else {
					$('.js-cjExport-btn').addClass('btn-warning');
				}
			},  
			// other available options: 
			url:       $(this).attr('action'),         
			type:      'POST',        
			clearForm: false,
			data: {
				action: 'plugin_cj_export'
			}

		};
		
	
	// bind form using 'ajaxForm' 
		$(this).ajaxSubmit(options); 
	});

	/** Show/Hide email field */
	$(document).on('change', '.js-transfer-select', function(event) {
		event.preventDefault();
		/* Act on the event */
		if ( $(this).val() == 'email' ) {
			$('.email-group').show();
		}else {
			$('.email-group').hide();
		}

		validateTransfer();

	});

	
	function validateTransfer() {
		var form = $('.js-transfer-validate');
		form.find('input[type="text"], input[type="password"]').each(function(index, el) {
			if( $(el).val() == '' && $('.js-transfer-select').val() == 'ftp' ) {
				$(el).parent('label').addClass('input-error');
				$('.js-cjExport-btn').prop('disabled', true);
			}else {
				$(el).parent('label').removeClass('input-error');
				$('.js-cjExport-btn').prop('disabled', false);
			}				
		});
	}
	validateTransfer();
});