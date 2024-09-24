jQuery(document).ready(function(){
	
	//Get form fields
	jQuery('#cercf7_contact_form').on('change', function(){
		var $form = jQuery(this);
		var formID = $form.val();
		
		jQuery('#cercf7_form_fields').attr('disabled', true);
		jQuery('#cercf7_add_rule').addClass('cercf7-btn-disabled');
		
		jQuery.ajax({
			type: 'post',
			url: cercf7_vars.ajax_url,
			data: {
				action: 'cercf7_get_form_fields',
				form_ID: formID
			},
			success: function(response){
				jQuery('#cercf7_form_fields').html(response);
				
				if(formID != ''){
					jQuery('#cercf7_form_fields').attr('form-id',formID);
					jQuery('#cercf7_form_fields').attr('disabled', false);
				}else{
					jQuery('#cercf7_form_fields').attr('disabled', true);
				}
				
			}
		});
	});
	
	//Activate the 'Add rule' button
	jQuery('#cercf7_form_fields').on('change', function(){
		if(jQuery(this).val() != ''){
			jQuery('#cercf7_add_rule').removeClass('cercf7-btn-disabled');
		} else{
			jQuery('#cercf7_add_rule').addClass('cercf7-btn-disabled');
		}
	});
	
	//Set rule fields
	jQuery('#cercf7_add_rule').on('click', function(){
		var $this = jQuery(this);
		var formID = jQuery('#cercf7_contact_form').val();
		var formField = jQuery('#cercf7_form_fields').val();
		//var formID = jQuery(this).attr('form-id');
		if(formField != ''){
			jQuery.ajax({
				type: 'post',
				url: cercf7_vars.ajax_url,
				data: {
					action: 'cercf7_add_rule_html',
					form_ID: formID,
					form_field: formField,
				},
				success: function(response){
					jQuery('#cercf7_rule_rows').append(response);
				}
			});
		} else{
			alert('Please select a field');
		}
		
	});
	
	
});