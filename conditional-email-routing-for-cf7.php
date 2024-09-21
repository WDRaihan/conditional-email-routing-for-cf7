<?php
/**
 * Plugin Name: CF7 Conditional Email Routing
 * Description: Routes email to different recipients based on form field values.
 * Version: 1.0
 * Author: Your Name
 */
//prefix cercf7
// Hook into WordPress Admin Menu
add_action( 'admin_menu', 'cf7_conditional_email_routing_menu' );
add_action( 'admin_enqueue_scripts', 'enqueue_admin_scripts' );
function enqueue_admin_scripts(){
	wp_enqueue_script('cercf7-script', plugin_dir_url(__FILE__).'assets/scripts.js', array('jquery'), null, true);
	wp_localize_script('cercf7-script', 'cercf7_vars', array('ajax_url'=>admin_url('admin-ajax.php')));
}
function cf7_conditional_email_routing_menu() {
    add_menu_page(
        'CF7 Conditional Email Routing', // Page Title
        'CF7 Email Routing',             // Menu Title
        'manage_options',                // Capability
        'cf7-email-routing',             // Menu Slug
        'cf7_email_routing_settings_page'// Callback
    );
}

// Admin settings page
function cf7_email_routing_settings_page() {
    ?>
    <div class="wrap">
        <h1>CF7 Conditional Email Routing</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'cf7_email_routing_group' );
            do_settings_sections( 'cf7_email_routing' );
            submit_button();
            ?>
        </form>
        
        <?php //$form = WPCF7_ContactForm::get_instance(22); ?>
        <style>
			tbody th, thead tr, tbody > tr, tbody > tr > td {
			  border: 1px solid black;
			  border-collapse: collapse;
			  
			}
			tbody ul li {
				border-bottom: 1px solid black;
				padding: 5px;
				padding-bottom: 10px;
			}
			tbody ul li:last-child {
				border-bottom: 0;
			}
		</style>
       
       
       <?php
		$rules = [];
		$mailto_logics = [];
		if(isset($_POST['cercf7_selected_form_id'])){
			$selected_forms = $_POST['cercf7_selected_form_id'];
			if(is_array($selected_forms)){
				$selected_forms = array_unique($selected_forms);
				
				//Get forms
				foreach($selected_forms as $selected_form){
					
					$form_fields = $_POST['cercf7_selected_field_'.$selected_form.'_'];
					$form_fields = array_unique($form_fields);
					
					//Get fields
					foreach($form_fields as $form_field){
						
						//Get mail logics
						if( isset($_POST['cercf7_'.$selected_form.'_'.$form_field.'_value']) ){
							
							$selected_field_values = $_POST['cercf7_'.$selected_form.'_'.$form_field.'_value'];
							$selected_field_mailto = $_POST['cercf7_'.$selected_form.'_'.$form_field.'_mail'];
							
							if(is_array($selected_field_values) && is_array($selected_field_mailto)) :
							foreach($selected_field_values as $k=>$selected_field_value){
								
								if(!empty($selected_field_value) && !empty($selected_field_mailto[$k])){
									
									//$mailto_logics[$selected_field_value] = $selected_field_mailto[$k];
									
									$rules[$selected_form][$form_field][$selected_field_value] = $selected_field_mailto[$k];
								}
								
							}
							endif;
						}
						
						//Set rulse
						//$rules[$selected_form][$form_field] = array_unique($mailto_logics);
					}
					
				}
			}
			
			print_r($rules);
			echo '<br>';
			echo json_encode($rules[22]);
		}
		
		?>
       
        <select name="cercf7_contact_form" id="cercf7_contact_form">
			<option value="">--Select Form--</option><?php
			$dbValue = get_option('field-name'); //example!
			$posts = get_posts(array(
				'post_type'     => 'wpcf7_contact_form',
				'numberposts'   => -1
			));
			foreach ( $posts as $p ) {
				echo '<option value="'.$p->ID.'"'.selected($p->ID,$dbValue,false).'>'.$p->post_title.' ('.$p->ID.')</option>';
			} ?>
		</select>
		<select name="cercf7_form_fields" disabled form-id="" id="cercf7_form_fields">
			<option>-Select Field-</option>
		</select>
       <a href="#" id="cercf7_add_rule">Add Rule</a>
       <form action="" method="post">
		   <table style="border: 1px solid #000; border-collapse: collapse;">
				<thead>
					<tr>
						<th>
							Field Name
						</th>
						<th>
							Conditions
						</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<p>Form name: <b>Contact form 1</b></p>
							If <b>Department</b>
							<input type="hidden" value="22" name="cercf7_selected_form_id[]">
							<input type="hidden" value="department" name="cercf7_selected_field_22_[]">
						</td>
						<td>
							<ul>
								<li>Value == <input type="text" name="cercf7_22_department_value[]" value="HR"> Mail to <input type="text" name="cercf7_22_department_mail[]" value="hr@example.com"></li>
								<li>Value == <input type="text" name="cercf7_22_department_value[]" value="Sales"> Mail to <input type="text" name="cercf7_22_department_mail[]" value="sales@example.com"></li>
							</ul>
							<a href="#">+ Add Condition</a>
						</td>
						<td><a href="#">Delete</a></td>
					</tr>
					
					<tr>
						<td>
							<p>Form name: <b>Subscription form</b></p>
							If <b>Your name</b>
							<input type="hidden" value="27" name="cercf7_selected_form_id[]">
							<input type="hidden" value="your-name" name="cercf7_selected_field_27_[]">
						</td>
						<td>
							<ul>
								<li>Value == <input type="text" name="cercf7_27_your-name_value[]" value="Raihan"> Mail to <input type="text" name="cercf7_27_your-name_mail[]" value="raihan@example.com"></li>
								<li>Value == <input type="text" name="cercf7_27_your-name_value[]" value="Tahim"> Mail to <input type="text" name="cercf7_27_your-name_mail[]" value="tahim@example.com"></li>
							</ul>
						</td>
						<td><a href="#">Delete</a></td>
					</tr>
					
					<tr>
						<td>
							<p>Form name: <b>Contact form 1</b></p>
							If <b>Dropd</b>
							<input type="hidden" value="22" name="cercf7_selected_form_id[]">
							<input type="hidden" value="dropd" name="cercf7_selected_field_22_[]">
						</td>
						<td>
							<ul>
								<li>Value == <input type="text" name="cercf7_22_dropd_value[]" value="xx"> Mail to <input type="text" name="cercf7_22_dropd_mail[]" value="xx@example.com"></li>
								<li>Value == <input type="text" name="cercf7_22_dropd_value[]" value="yy"> Mail to <input type="text" name="cercf7_22_dropd_mail[]" value="yy@example.com"></li>
							</ul>
							<a href="#">+ Add Condition</a>
						</td>
						<td><a href="#">Delete</a></td>
					</tr>
					
				</tbody>
			</table>
       		<button>Save</button>
        </form>
    </div>
    <?php
}

// Register Settings
add_action( 'admin_init', 'cf7_email_routing_settings' );

function cf7_email_routing_settings() {
    register_setting( 'cf7_email_routing_group', 'cf7_routing_conditions' );

    add_settings_section(
        'cf7_routing_conditions_section',
        'Routing Conditions',
        'cf7_routing_conditions_section_callback',
        'cf7_email_routing'
    );

    add_settings_field(
        'cf7_routing_conditions_field',
        'Conditional Routing Rules (JSON format)',
        'cf7_routing_conditions_field_callback',
        'cf7_email_routing',
        'cf7_routing_conditions_section'
    );

    add_settings_field(
        'cf7_routing_conditions_multiple',
        'Conditional Routing Rules (JSON format) 2',
        'cf7_routing_conditions_multiple_callback',
        'cf7_email_routing',
        'cf7_routing_conditions_section'
    );
}

function cf7_routing_conditions_section_callback() {
    echo 'Specify the conditions in JSON format. Example:<br><code>{"department": {"Sales": "sales@example.com", "Support": "support@example.com"}}</code>';
}

function cf7_routing_conditions_field_callback() {
    $conditions = get_option( 'cf7_routing_conditions', '{}' );
    echo '<textarea name="cf7_routing_conditions" rows="10" cols="50" class="large-text">' . esc_attr( $conditions ) . '</textarea>';
}

function cf7_routing_conditions_multiple_callback() {
	
	//print_r( get_option( 'cf7_routing_conditions_dd', '{}' ));
	
	
	?>
	
	<?php
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
							break;
						}
					}
				}else{
					$recipient[] = $routing[$posted_field];
				}
                
                break;
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
add_action('wp_ajax_cercf7_get_form_fields', 'cercf7_get_form_fields_cb');
function cercf7_get_form_fields_cb(){
	$form_id = $_POST['form_ID'];

	echo '<option>-Select Field-</option>';
	
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
add_action('wp_ajax_cercf7_add_rule_html', 'cercf7_add_rule_html_cb');
function cercf7_add_rule_html_cb(){
	$form_id = $_POST['form_ID'];
	$form_field = $_POST['form_field'];
	
	if($form_id != ''):
	
	$form = WPCF7_ContactForm::get_instance($form_id);

	$tags = $form->scan_form_tags();
	
	$field_type = '';
	if($form_field != ''){
		$tag_index = array_search($form_field, array_column($tags, 'name'));
		$field_type = $tags[$tag_index]['type'];
	}
	?>
	<tr>
		<td>
			<p>Form name: <b><?php echo esc_html($form->title()); ?></b></p>
			Field Name <b><?php echo esc_html($form_field); ?></b>
			<input type="hidden" value="<?php echo esc_attr($form_id); ?>" name="cercf7_selected_form_id[]">
			<input type="hidden" value="<?php echo esc_attr($form_field); ?>" name="cercf7_selected_field_<?php echo esc_attr($form_id); ?>_[]">
		</td>
		<td>
			<ul>
			<?php 
				function cercf7_field_type(){
					$value_field_html = '';
					if( $field_type == 'select' ){

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
					
					echo $value_field_html;
				}
				?>
				<!--Hidden rule start-->
				<li class="duplicate_field" style="display:none">Value == <?php echo  ?> <!--<input type="text" name="cercf7_<?php echo esc_attr($form_id); ?>_<?php echo esc_attr($form_field); ?>_value[]" value="">--> Mail to <input type="text" name="cercf7_<?php echo esc_attr($form_id); ?>_<?php echo esc_attr($form_field); ?>_mail[]" value="" placeholder="xx@example.com"></li>
				<!--Hidden rule end-->
				
				<li>Value == <input type="text" name="cercf7_<?php echo esc_attr($form_id); ?>_<?php echo esc_attr($form_field); ?>_value[]" value=""> Mail to <input type="text" name="cercf7_<?php echo esc_attr($form_id); ?>_<?php echo esc_attr($form_field); ?>_mail[]" value="" placeholder="xx@example.com"></li>
				
			</ul>
			<a href="#">+ Add Condition</a>
		</td>
		<td><a href="#">Delete</a></td>
	</tr>
	<?php
	endif;
	
	die();
}
