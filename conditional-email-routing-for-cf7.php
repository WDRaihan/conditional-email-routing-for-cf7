<?php
/**
 * Plugin Name: CF7 Conditional Email Routing
 * Description: Routes email to different recipients based on form field values.
 * Version: 1.0
 * Author: Your Name
 */
//prefix cercf7
// Hook into WordPress Admin Menu
add_action( 'admin_enqueue_scripts', 'enqueue_admin_scripts' );
function enqueue_admin_scripts(){
	wp_enqueue_script('cercf7-script', plugin_dir_url(__FILE__).'assets/scripts.js', array('jquery'), null, true);
	wp_localize_script('cercf7-script', 'cercf7_vars', array('ajax_url'=>admin_url('admin-ajax.php')));
}



// Apply the custom filter to route emails
add_filter( 'wpcf7_mail_components', 'cercf7_conditional_email_routing', 10, 3 );

function cercf7_conditional_email_routing( $components, $contact_form, $that ) {
    // Get the form ID
    $form_id = $contact_form->id();

    // Get the posted form data
    $submission = WPCF7_Submission::get_instance();
    if ( $submission ) {
        $posted_data = $submission->get_posted_data();
    }
	

    // Get routing conditions from the admin settings
    $conditions = get_option( 'cf7_routing_conditions', '{}' );
    $conditions = json_decode( $conditions, true );

	
	ob_start();
	print_r($posted_data).'<br>';
	print_r($conditions);
	$data = ob_get_clean();
	
	$components['body'] = ''.$data.'';
	
	
	$recipient = [];
	
    if ( ! empty( $conditions ) && is_array( $posted_data ) ) {
        // Loop through the conditions and check the form data
        foreach ( $conditions as $field => $routing ) {
            if ( isset( $posted_data[$field] ) ) {
				
				$posted_field = $posted_data[$field];
				
                // If a condition is met, override the 'to' email address
				
				if(is_array($posted_field)){
					foreach($posted_field as $k=>$value){
						if ( isset( $routing[$value] ) ) {
							$recipient[] = $routing[$value];
							//break; //Disallow multiple
						}
					}
				}else{
					$recipient[] = $routing[$posted_field];
				}
                
                //break; //Disallow multiple
            }
        }
    }
	
	$recipient = implode(',', $recipient);
	
	if(!empty($recipient)){
		$components['recipient'] = $recipient;
	}

    return $components;
}

//Set form field
//add_action('wp_ajax_cercf7_get_form_fields', 'cercf7_get_form_fields_cb');
function cercf7_get_form_fields_cb(){
	$form_id = $_POST['form_ID'];

	echo '<option value="">-Select Field-</option>';
	
	if($form_id != ''){
		
		$form = WPCF7_ContactForm::get_instance($form_id);
		$tags = $form->scan_form_tags();

		foreach ( $tags as $k=>$value ) {
			if(!empty($value['name'])){
				echo '<option value="'.$value['name'].'">'.$value['name'].'</option>';
			}
		}
	}
	
	die();
}

//Set rule html
//add_action('wp_ajax_cercf7_add_rule_html', 'cercf7_add_rule_html_cb');
function cercf7_add_rule_html_cb(){
	$form_id = $_POST['form_ID'];
	$form_field = $_POST['form_field'];
	
	if($form_id != '' && $form_field != ''):
	
	$form = WPCF7_ContactForm::get_instance($form_id);

	$tags = $form->scan_form_tags();
	
	$field_type = '';
	if($form_field != ''){
		$tag_index = array_search($form_field, array_column($tags, 'name'));
		$field_type = $tags[$tag_index]['type'];
	}
	?>
	<tr class="cercf7_form_<?php echo esc_attr($form_id); ?>_row">
		<td>
			<p>Form: <b><?php echo esc_html($form->title()); ?></b></p>
			Field: <b><?php echo esc_html($form_field); ?></b>
			<input type="hidden" value="<?php echo esc_attr($form_id); ?>" name="cercf7_selected_form_id[]">
			<input type="hidden" value="<?php echo esc_attr($form_field); ?>" name="cercf7_selected_field_<?php echo esc_attr($form_id); ?>_[]">
		</td>
		<td>
			<ul>
			<?php 
				$value_field_html = '';
				if( $field_type == 'select' || $field_type == 'checkbox' || $field_type == 'radio' ){

					$value_field_html = '<select name="cercf7_'.esc_attr($form_id).'_'.esc_attr($form_field).'_value[]">';
						$value_field_html .= '<option value="">-Select value-</option>';

						$select_field_values = $tags[$tag_index]['values'];
						if(is_array($select_field_values)){
							foreach($select_field_values as $select_field_value){
								$value_field_html .= '<option value="'.esc_html($select_field_value).'">'.esc_html($select_field_value).'</option>';
							}
						}

					$value_field_html .= '</select>';

				}else{
					$value_field_html = '<input type="text" name="cercf7_'.esc_attr($form_id).'_'.esc_attr($form_field).'_value[]" value="">';
				}
					
				?>
				<!--Hidden rule start-->
				<li class="duplicate_field" style="display:none">Value == <?php echo $value_field_html; ?> <!--<input type="text" name="cercf7_<?php echo esc_attr($form_id); ?>_<?php echo esc_attr($form_field); ?>_value[]" value="">--> Mail to <input type="text" name="cercf7_<?php echo esc_attr($form_id); ?>_<?php echo esc_attr($form_field); ?>_mail[]" value="" placeholder="xx@example.com"></li>
				<!--Hidden rule end-->
				
				<li>Value == <?php echo $value_field_html; ?> <!--<input type="text" name="cercf7_<?php //echo esc_attr($form_id); ?>_<?php echo esc_attr($form_field); ?>_value[]" value="">--> Mail to <input type="text" name="cercf7_<?php echo esc_attr($form_id); ?>_<?php echo esc_attr($form_field); ?>_mail[]" value="" placeholder="xx@example.com"></li>
				
			</ul>
			<a href="#">+ Add Condition</a>
		</td>
		<td><a href="#">Delete</a></td>
	</tr>
	<?php
	endif;
	
	die();
}


// Add a custom tab to the CF7 editor
add_filter( 'wpcf7_editor_panels', 'cf7_add_conditional_routing_tab' );

function cf7_add_conditional_routing_tab( $panels ) {
    $panels['conditional-email-routing'] = array(
        'title'    => __( 'Conditional Email Routing', 'cf7-conditional-email-routing' ),
        'callback' => 'cf7_conditional_routing_tab_content',
    );
    return $panels;
}

// Content for the custom tab
function cf7_conditional_routing_tab_content_( $post ) {
    // Retrieve existing settings for the current form
    $form_id = $post->id();
    $routing_conditions = get_post_meta( $form_id, '_cf7_routing_conditions', true );
    ?>
    <h2><?php _e( 'Conditional Email Routing', 'cf7-conditional-email-routing' ); ?></h2>
    <p>
        <?php _e( 'Define email routing rules based on form field values in JSON format.', 'cf7-conditional-email-routing' ); ?><br>
        <strong><?php _e( 'Example:', 'cf7-conditional-email-routing' ); ?></strong><br>
        <code>{"department": {"Sales": "sales@example.com", "Support": "support@example.com"}}</code>
    </p>
    <style>
		.cercf7-field {
			width: 20%;
		}

		.cercf7-conditions {
			width: 80%;
		}

		.cercf7-rountings {
			display: flex;
			background: #fff;
			padding: 10px
		}

		.cercf7-conditions ul {
			margin-top: 0;
		}
	</style>
   
   
<!--
    <div class="cercf7-rountings">
    	<div class="cercf7-field">
    		<label for="">If</label>
    		<select name="cercf7_selected_field[]" id="">
    			<option value="">-Select field-</option>
    			<?php
				$form_id = $_GET['post'];

				if($form_id != ''){

					$form = WPCF7_ContactForm::get_instance($form_id);
					$tags = $form->scan_form_tags();

					foreach ( $tags as $k=>$value ) {
						if(!empty($value['name'])){
							echo '<option value="'.$value['name'].'">'.$value['name'].'</option>';
						}
					}
				}
				?>
    		</select>
    	</div>
    	<div class="cercf7-conditions">
    		<ul>
				<li>Value == <input type="text" name="cercf7_department_value[]" value="HR"> Mail to <input type="text" name="cercf7_department_mail[]" value="hr@example.com"></li>
				<li>Value == <input type="text" name="cercf7_department_value[]" value="Sales"> Mail to <input type="text" name="cercf7_department_mail[]" value="sales@example.com"></li>
			</ul>
			<a href="#">+ Add Condition</a>
    	</div>
    </div>
-->
   
   <div class="cercf7-rountings">
    <div class="cercf7-field">
        <label for="">If</label>
        <select name="cercf7_selected_field[]" id="cercf7_selected_field">
            <option value="">-Select field-</option>
            <?php
            $form_id = $_GET['post'];

            if ($form_id != '') {
                $form = WPCF7_ContactForm::get_instance($form_id);
                $tags = $form->scan_form_tags();

                foreach ($tags as $k => $value) {
                    if (!empty($value['name'])) {
                        echo '<option value="' . esc_attr($value['name']) . '">' . esc_html($value['name']) . '</option>';
                    }
                }
            }
            ?>
        </select>
    </div>
    <div class="cercf7-conditions">
        <ul id="cercf7_conditions_list">
            <li>
                Value == <input type="text" name="cercf7_initial_value[0]" value="HR"> 
                Mail to <input type="text" name="cercf7_initial_mail[0]" value="hr@example.com">
            </li>
        </ul>
        <a href="#" id="cercf7_add_condition">+ Add Condition</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addConditionButton = document.getElementById('cercf7_add_condition');
    const conditionsList = document.getElementById('cercf7_conditions_list');
    const fieldSelect = document.getElementById('cercf7_selected_field');

    // Function to update all existing condition names based on selected field
    function updateConditionNames() {
        const selectedField = fieldSelect.value;
        if (!selectedField) {
            return;
        }

        // Update the name attributes of all inputs
        const conditions = conditionsList.querySelectorAll('li');
        conditions.forEach((condition, index) => {
            const inputs = condition.querySelectorAll('input');
            if (inputs.length === 2) {
                inputs[0].name = `cercf7_${selectedField}_value[${index}]`;
                inputs[1].name = `cercf7_${selectedField}_mail[${index}]`;
            }
        });
    }

    // Event listener for adding a new condition
    addConditionButton.addEventListener('click', function (e) {
        e.preventDefault();

        const selectedField = fieldSelect.value;
        if (!selectedField) {
            alert('Please select a field first.');
            return;
        }

        // Get the current number of conditions for this field
        const conditionIndex = conditionsList.querySelectorAll('li').length;

        // Create a new list item
        const newCondition = document.createElement('li');
        newCondition.innerHTML = `
            Value == <input type="text" name="cercf7_${selectedField}_value[${conditionIndex}]" value=""> 
            Mail to <input type="text" name="cercf7_${selectedField}_mail[${conditionIndex}]" value="">
        `;

        // Append the new condition to the list
        conditionsList.appendChild(newCondition);
    });

    // Event listener for changing the selected field
    fieldSelect.addEventListener('change', function () {
        updateConditionNames();
    });
});
</script>

   
    <?php print_r(get_post_meta( 22, '_cf7_routing_conditions', true )); ?>
	
				
    <?php
}


function cf7_conditional_routing_tab_content( $post ){
	// Retrieve existing settings for the current form
    $form_id = $post->id();
    $routing_conditions = get_post_meta( $form_id, '_cf7_routing_conditions', true );
    ?>
    <h2><?php _e( 'Conditional Email Routing', 'cf7-conditional-email-routing' ); ?></h2>
    <p>
        <?php _e( 'Define email routing rules based on form field values in JSON format.', 'cf7-conditional-email-routing' ); ?><br>
        <strong><?php _e( 'Example:', 'cf7-conditional-email-routing' ); ?></strong><br>
        <code>{"department": {"Sales": "sales@example.com", "Support": "support@example.com"}}</code>
    </p>
    <style>
		.cercf7-field {
			width: 20%;
		}

		.cercf7-conditions {
			width: 80%;
		}

		.cercf7-rountings {
			display: flex;
			background: #fff;
			padding: 10px
		}

		.cercf7-conditions ul {
			margin-top: 0;
		}
	</style>
	
	<div class="cercf7-rountings">
    <div id="cercf7_roles">
        <div class="cercf7-role">
            <h3>Condition Group</h3>
            <div class="cercf7-field">
                <label for="">If</label>
                <select name="cercf7_selected_field[]" class="cercf7_selected_field">
                    <option value="">-Select field-</option>
                    <?php
                    $form_id = $_GET['post'];

                    if ($form_id != '') {
                        $form = WPCF7_ContactForm::get_instance($form_id);
                        $tags = $form->scan_form_tags();

                        foreach ($tags as $k => $value) {
                            if (!empty($value['name'])) {
                                echo '<option value="' . esc_attr($value['name']) . '">' . esc_html($value['name']) . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="cercf7-conditions">
                <ul class="cercf7_conditions_list">
                    <li>
                        Value == <input type="text" name="cercf7_value[0]" value=""> 
                        Mail to <input type="text" name="cercf7_mail[0]" value="">
                    </li>
                </ul>
                <a href="#" class="cercf7_add_condition">+ Add Condition</a>
            </div>
        </div>
    </div>
    <a href="#" id="cercf7_add_role">+ Add Role</a>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const addRoleButton = document.getElementById('cercf7_add_role');
    const rolesContainer = document.getElementById('cercf7_roles');

    // Add a new role
    addRoleButton.addEventListener('click', function (e) {
        e.preventDefault();

        const newRole = document.createElement('div');
        newRole.classList.add('cercf7-role');
        newRole.innerHTML = `
            <h3>Condition Group</h3>
            <div class="cercf7-field">
                <label for="">If</label>
                <select name="cercf7_selected_field[]" class="cercf7_selected_field">
                    <option value="">-Select field-</option>
                    ${rolesContainer.querySelector('.cercf7_selected_field').innerHTML}
                </select>
            </div>
            <div class="cercf7-conditions">
                <ul class="cercf7_conditions_list">
                    <li>
                        Value == <input type="text" name="" value=""> 
                        Mail to <input type="text" name="" value="">
                    </li>
                </ul>
                <a href="#" class="cercf7_add_condition">+ Add Condition</a>
            </div>
        `;
        rolesContainer.appendChild(newRole);

        // Initialize dynamic behavior for the new role
        initializeRoleLogic(newRole);
    });

    // Initialize dynamic behavior for a role
    function initializeRoleLogic(role) {
        const fieldSelect = role.querySelector('.cercf7_selected_field');
        const conditionsList = role.querySelector('.cercf7_conditions_list');
        const addConditionButton = role.querySelector('.cercf7_add_condition');

        // Function to update names dynamically
        function updateConditionNames() {
            const selectedField = fieldSelect.value;
            const conditions = conditionsList.querySelectorAll('li');
            conditions.forEach((condition, index) => {
                const inputs = condition.querySelectorAll('input');
                if (inputs.length === 2) {
                    inputs[0].name = `cercf7_${selectedField}_value[${index}]`;
                    inputs[1].name = `cercf7_${selectedField}_mail[${index}]`;
                }
            });
        }

        // Add condition dynamically
        addConditionButton.addEventListener('click', function (e) {
            e.preventDefault();

            const selectedField = fieldSelect.value;
            if (!selectedField) {
                alert('Please select a field first.');
                return;
            }

            const conditionIndex = conditionsList.querySelectorAll('li').length;

            const newCondition = document.createElement('li');
            newCondition.innerHTML = `
                Value == <input type="text" name="cercf7_${selectedField}_value[${conditionIndex}]" value=""> 
                Mail to <input type="text" name="cercf7_${selectedField}_mail[${conditionIndex}]" value="">
            `;
            conditionsList.appendChild(newCondition);
        });

        // Update names on field change
        fieldSelect.addEventListener('change', function () {
            updateConditionNames();
        });

        // Initialize names for existing conditions
        updateConditionNames();
    }

    // Initialize existing roles
    const roles = document.querySelectorAll('.cercf7-role');
    roles.forEach(role => initializeRoleLogic(role));
});

</script>

<?php print_r(get_post_meta( 22, '_cf7_routing_conditions', true )); ?>
<?php
}

// Save the custom tab data when the form is saved
add_action( 'wpcf7_save_contact_form', 'cf7_save_conditional_routing_settings' );

function cf7_save_conditional_routing_settings( $contact_form ) {
    $form_id = $contact_form->id();
    if(isset($_POST['cercf7_selected_field'])){
		$form_fields = $_POST['cercf7_selected_field'];
		$form_fields = array_unique($form_fields);
		$rules = [];
		//Get fields
		foreach($form_fields as $form_field){

			//Get mail logics
			if( isset($_POST['cercf7_'.$form_field.'_value']) ){

				$selected_field_values = $_POST['cercf7_'.$form_field.'_value'];
				$selected_field_mailto = $_POST['cercf7_'.$form_field.'_mail'];

				if(is_array($selected_field_values) && is_array($selected_field_mailto)) :
				foreach($selected_field_values as $k=>$selected_field_value){

					if(!empty($selected_field_value) && !empty($selected_field_mailto[$k])){

						$rules[$form_field][$selected_field_value] = $selected_field_mailto[$k];
					}

				}
				endif;
			}

			//print_r($rules);
		}
		
		update_post_meta( 22, '_cf7_routing_conditions', $rules );
	}
}