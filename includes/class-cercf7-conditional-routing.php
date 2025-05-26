<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CERCF7_Conditional_Email_Routing {
    private static $instance = null;

    private function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_filter( 'wpcf7_mail_components', [ $this, 'apply_conditional_routing' ], 10, 3 );
        add_filter( 'wpcf7_editor_panels', [ $this, 'add_custom_tab' ] );
        add_action( 'wpcf7_save_contact_form', [ $this, 'save_custom_tab_settings' ] );
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function enqueue_admin_scripts() {
		wp_enqueue_style(
            'cercf7-styles',
            CERCF7_PLUGIN_URL . 'assets/styles.css'
        );
		
        wp_enqueue_script(
            'cercf7-script',
            CERCF7_PLUGIN_URL . 'assets/scripts.js',
            [ 'jquery' ],
            '1.0',
            true
        );
		
		// Load translations for the script
    	wp_set_script_translations('cercf7-script', 'conditional-email-routing-for-contact-form-7');
    }

    public function apply_conditional_routing( $components, $contact_form, $submission ) {
        $form_id = $contact_form->id();
		
		$routing_enabled = get_post_meta( $form_id, '_cercf7_routing_enabled', true );
    	$use_default_email = get_post_meta( $form_id, '_cercf7_use_default_email', true );
		
		// If conditional routing is disabled, return the original mail
		if ( $routing_enabled !== '1' ) {
			return $components;
		}
		
        // Get the posted form data
		$submission = WPCF7_Submission::get_instance();
		if ( $submission ) {
			$posted_data = $submission->get_posted_data();
		}

		// Get routing conditions from the admin settings
		$conditions = !empty(get_post_meta( $form_id, '_cercf7_routing_conditions', true )) ? get_post_meta( $form_id, '_cercf7_routing_conditions', true ) : '';

		$recipient = [];
		
		if( $use_default_email == '1' ){
			$recipient[] = $components['recipient'];
		}
		
		if ( ! empty( $conditions ) && is_array( $conditions ) && is_array( $posted_data ) ) {
			// Loop through the conditions and check the form data
			foreach ( $conditions as $field => $routing ) {
				if ( isset( $posted_data[$field] ) ) {

					$posted_field = $posted_data[$field];

					// If a condition is met, override the 'to' email address

					if(is_array($posted_field)){
						foreach($posted_field as $k=>$value){
							$value = strtolower($value);
							if ( isset( $routing[$value] ) ) {
								$recipient[] = $routing[$value];
								break; //Do not allow multiple
							}
						}
					}else{
						$recipient[] = $routing[$posted_field];
					}

					break; //Do not allow multiple
				}
			}
		}

		$recipient = implode(',', $recipient);

		if(!empty($recipient)){
			$components['recipient'] = $recipient;
		}

		return $components;
    }

    public function add_custom_tab( $panels ) {
        $panels['conditional-email-routing'] = [
            'title'    => __( 'Conditional Email Routing', 'conditional-email-routing-for-contact-form-7' ),
            'callback' => [ $this, 'render_custom_tab_content' ],
        ];
        return $panels;
    }

    public function render_custom_tab_content( $post ) {
        
		$form_id = $post->id();
		
		$routing_enabled = !empty(get_post_meta( $form_id, '_cercf7_routing_enabled', true )) ? get_post_meta( $form_id, '_cercf7_routing_enabled', true ) : '';
		$use_default_email = !empty(get_post_meta( $form_id, '_cercf7_use_default_email', true )) ? get_post_meta( $form_id, '_cercf7_use_default_email', true ) : '';
		$routing_conditions = !empty(get_post_meta( $form_id, '_cercf7_routing_conditions', true )) ? get_post_meta( $form_id, '_cercf7_routing_conditions', true ) : '';
        ?>
        <h2><?php echo esc_html__( 'Conditional Email Routing', 'conditional-email-routing-for-contact-form-7' ); ?></h2>
        
        <!-- Enable/Disable Conditional Email Routing -->
		<div class="cercf7-field-checkbox">
			<input type="checkbox" name="cercf7_routing_enabled" id="cercf7_routing_enabled" value="1" <?php checked( $routing_enabled, '1' ); ?>>
			<label for="cercf7_routing_enabled"><?php echo esc_html__( 'Enable Conditional Email Routing', 'conditional-email-routing-for-contact-form-7' ); ?></label>
		</div>

		<!-- Use Default Email Recipient -->
		<div class="cercf7-field-checkbox">
			<input type="checkbox" name="cercf7_use_default_email" id="cercf7_use_default_email" value="1" <?php checked( $use_default_email, '1' ); ?>>
			<label for="cercf7_use_default_email"><?php echo esc_html__( 'Send Email To The Default Recipient', 'conditional-email-routing-for-contact-form-7' ); ?></label>
		</div>
        
        <p>
            <?php echo esc_html__( 'Define email routing rules based on form field values.', 'conditional-email-routing-for-contact-form-7' ); ?>
        </p>
        <p>
        	<strong><?php echo esc_html__( 'Example:', 'conditional-email-routing-for-contact-form-7' ); ?></strong><br>
        	<code><?php echo esc_html__("If 'department' == 'Sales' Mail To 'sales@example.com'", "conditional-email-routing-for-contact-form-7"); ?></code>
        </p>
        
        <!--Routing conditions-->

        <div class="cercf7-rountings">
			<div id="cercf7_roles">
				<div class="cercf7-roles-header">
					<div class="cercf7-header-field"><?php echo esc_html__( 'Form Field', 'conditional-email-routing-for-contact-form-7' ); ?></div>
					<div class="cercf7-header-conditions"><?php echo esc_html__( 'Conditions', 'conditional-email-routing-for-contact-form-7' ); ?></div>
					<div class="cercf7-header-action"><?php echo esc_html__( 'Remove Role', 'conditional-email-routing-for-contact-form-7' ); ?></div>
				</div>
				<!--To duplicate-->
				<select style="display:none" class="cercf7_selected_field_options">
					<?php
					$tags = $this->get_form_tags( $form_id );
					foreach ( $tags as $tag ) {
						if(!empty($tag)){
							echo '<option value="' . esc_attr( $tag ) . '">' . esc_html( $tag ) . '</option>';
						}
					}
					?>
				</select>
				<!--To duplicate-->
				
				<?php 
				if( !empty($routing_conditions) && is_array($routing_conditions) ) {
				foreach( $routing_conditions as $field => $routings ) : 
				?>
				<div class="cercf7-role">
					<div class="cercf7-field">
						<label for=""><?php echo esc_html__( 'If', 'conditional-email-routing-for-contact-form-7' ); ?></label>
						<select name="cercf7_selected_field[]" class="cercf7_selected_field">
							<option value=""><?php echo esc_html__( '-Select form field-', 'conditional-email-routing-for-contact-form-7' ); ?></option>
							<?php
							$tags = $this->get_form_tags( $form_id );
							foreach ( $tags as $tag ) {
								if(!empty($tag)){
									echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $field, $tag, false ) . '>' . esc_html( $tag ) . '</option>';
								}
							}
							?>
						</select>
					</div>
					<div class="cercf7-conditions">
						<ul class="cercf7_conditions_list">
							<?php 
							if( is_array($routings) ) :
							$index = 0;
							foreach( $routings as $value => $email ) :  
							?>
							<li>
								<span><?php echo esc_html__( 'Value ==', 'conditional-email-routing-for-contact-form-7' ); ?></span> <input type="text" name="cercf7_<?php echo esc_attr( $field ); ?>_value[<?php echo esc_attr($index); ?>]" value="<?php echo esc_html( $value ); ?>" placeholder="<?php echo esc_html__( 'Enter a value', 'conditional-email-routing-for-contact-form-7' ); ?>" required> 
								<span><?php echo esc_html__( 'Mail to', 'conditional-email-routing-for-contact-form-7' ); ?></span> <input type="text" name="cercf7_<?php echo esc_attr( $field ); ?>_mail[<?php echo esc_attr($index); ?>]" value="<?php echo esc_attr( $email ); ?>" placeholder="<?php echo esc_html__( 'Recipient email', 'conditional-email-routing-for-contact-form-7' ); ?>" required> <span class="remove_condition" title="<?php echo esc_html__( 'Remove Condition', 'conditional-email-routing-for-contact-form-7' ); ?>">✕</span>
							</li>
							<?php 
							$index++;
							endforeach; 
							endif;
							?>
						</ul>
						<a href="#" class="cercf7_add_condition button"><?php echo esc_html__( '+ Add Condition', 'conditional-email-routing-for-contact-form-7' ); ?></a>
					</div>
					<div class="remove-role-wrapper">
						<a class="cercf7_remove_role disabled button"><?php echo esc_html__( 'Remove Role', 'conditional-email-routing-for-contact-form-7' ); ?></a><a class="cercf7-pro-link" target="_blank" href="https://codecanyon.net/item/conditional-email-routing-for-contact-form-7-pro/55815361">Pro Feature</a>
					</div>
				</div>
				<?php 
				endforeach; 
				}else{
				?>
				<!-- Default empty role -->
				<div class="cercf7-role">
					<div class="cercf7-field">
						<label for=""><?php echo esc_html__( 'If', 'conditional-email-routing-for-contact-form-7' ); ?></label>
						<select name="cercf7_selected_field[]" class="cercf7_selected_field">
							<option value=""><?php echo esc_html__( '-Select form field-', 'conditional-email-routing-for-contact-form-7' ); ?></option>
							<?php
							foreach ( $tags as $tag ) {
								if ( ! empty( $tag ) ) {
									echo '<option value="' . esc_attr( $tag ) . '">' . esc_html( $tag ) . '</option>';
								}
							}
							?>
						</select>
					</div>
					<div class="cercf7-conditions">
						<ul class="cercf7_conditions_list">
							
						</ul>
						<a href="#" class="cercf7_add_condition button"><?php echo esc_html__( '+ Add Condition', 'conditional-email-routing-for-contact-form-7' ); ?></a>
					</div>
					<div class="remove-role-wrapper">
						<a class="cercf7_remove_role disabled button"><?php echo esc_html__( 'Remove Role', 'conditional-email-routing-for-contact-form-7' ); ?></a><a class="cercf7-pro-link" target="_blank" href="https://codecanyon.net/item/conditional-email-routing-for-contact-form-7-pro/55815361">Pro Feature</a>
					</div>
				</div>
				<?php
				}
				?>
			</div>
			<a id="cercf7_add_role" class="cercf7_add_role disabled button"><?php echo esc_html__( '+ Add Role', 'conditional-email-routing-for-contact-form-7' ); ?></a><a class="cercf7-pro-link" target="_blank" href="https://codecanyon.net/item/conditional-email-routing-for-contact-form-7-pro/55815361">Pro Feature</a>
			<div class="cercf7-pro-box">
				<p>The Pro version offers advanced features like multiple conditions, allowing you to create complex logic for email routing. With this, you can send emails to multiple addresses when several conditions are met, making it ideal for handling intricate workflows and ensuring emails reach the right recipients efficiently.</p>
				<a target="_blank" class="button" href="https://codecanyon.net/item/conditional-email-routing-for-contact-form-7-pro/55815361">Buy Pro</a>
			</div>
		</div>
       <?php wp_nonce_field( 'cercf7_meta_box_nonce', 'cercf7_meta_box_noncename' ); ?>
        <?php
    }

    public function save_custom_tab_settings( $contact_form ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		
		$postdata = wp_unslash( $_POST );
		
		if ( ! isset( $postdata[ 'cercf7_meta_box_noncename' ] ) || ! wp_verify_nonce( $postdata['cercf7_meta_box_noncename'], 'cercf7_meta_box_nonce' ) )
			return;

		if ( ! current_user_can( 'edit_posts' ) )
			return;
		
		
        $form_id = $contact_form->id();
		
		// Save checkbox values for enabling routing and using default email
		if ( isset( $_POST['cercf7_routing_enabled'] ) ) {
			update_post_meta( $form_id, '_cercf7_routing_enabled', '1' );
		} else {
			delete_post_meta( $form_id, '_cercf7_routing_enabled' );
		}

		if ( isset( $_POST['cercf7_use_default_email'] ) ) {
			update_post_meta( $form_id, '_cercf7_use_default_email', '1' );
		} else {
			delete_post_meta( $form_id, '_cercf7_use_default_email' );
		}
		
        if ( isset( $_POST['cercf7_selected_field'] ) ) {
            $form_fields = array_map( 'sanitize_text_field', wp_unslash($_POST['cercf7_selected_field']) );
            $rules = [];

            foreach ( $form_fields as $form_field ) {
                if ( isset( $_POST[ "cercf7_{$form_field}_value" ] ) && isset( $_POST[ "cercf7_{$form_field}_mail" ] ) ) {
                    $values = array_map( 'sanitize_text_field', wp_unslash($_POST[ "cercf7_{$form_field}_value" ]) );
                    $mails  = array_map( 'sanitize_email', wp_unslash($_POST[ "cercf7_{$form_field}_mail" ]) );

                    foreach ( $values as $index => $value ) {
                        if ( ! empty( $value ) && ! empty( $mails[ $index ] ) ) {
							$value = strtolower($value);
                            $rules[ $form_field ][ $value ] = $mails[ $index ];
                        }
                    }
                }
            }

            update_post_meta( $form_id, '_cercf7_routing_conditions', $rules );
        }
    }

    private function get_form_tags( $form_id ) {
        $form = WPCF7_ContactForm::get_instance( $form_id );
        if ( $form ) {
            $tags = $form->scan_form_tags();
            return array_map( function ( $tag ) {
                return ! empty( $tag['name'] ) ? $tag['name'] : '';
            }, $tags );
        }
        return [];
    }
}
