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
        wp_enqueue_script(
            'cercf7-script',
            CERCF7_PLUGIN_URL . 'assets/scripts.js',
            [ 'jquery' ],
            '1.0',
            true
        );
        wp_localize_script( 'cercf7-script', 'cercf7_vars', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
    }

    public function apply_conditional_routing( $components, $contact_form, $submission ) {
        $form_id = $contact_form->id();
		
		$routing_enabled = get_post_meta( $form_id, '_cf7_routing_enabled', true );
    	$use_default_email = get_post_meta( $form_id, '_cf7_use_default_email', true );
		
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
		$conditions = !empty(get_post_meta( $form_id, '_cf7_routing_conditions', true )) ? get_post_meta( $form_id, '_cf7_routing_conditions', true ) : '';

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

    public function add_custom_tab( $panels ) {
        $panels['conditional-email-routing'] = [
            'title'    => __( 'Conditional Email Routing', 'conditional-email-routing' ),
            'callback' => [ $this, 'render_custom_tab_content' ],
        ];
        return $panels;
    }

    public function render_custom_tab_content( $post ) {
        $form_id = $post->id();
		
		$routing_enabled = get_post_meta( $form_id, '_cf7_routing_enabled', true );
		$use_default_email = get_post_meta( $form_id, '_cf7_use_default_email', true );
		$routing_conditions = !empty(get_post_meta( $form_id, '_cf7_routing_conditions', true )) ? get_post_meta( $form_id, '_cf7_routing_conditions', true ) : '';
        ?>
        <h2><?php esc_html_e( 'Conditional Email Routing', 'conditional-email-routing' ); ?></h2>
        
        <!-- Enable/Disable Conditional Email Routing -->
		<div class="cercf7-field">
			<label for="cercf7_routing_enabled"><?php _e( 'Enable Conditional Email Routing', 'conditional-email-routing' ); ?></label>
			<input type="checkbox" name="cercf7_routing_enabled" id="cercf7_routing_enabled" value="1" <?php checked( $routing_enabled, '1' ); ?>>
		</div>

		<!-- Use Default Email Recipient -->
		<div class="cercf7-field">
			<label for="cercf7_use_default_email"><?php _e( 'Use Default Email as Recipient', 'conditional-email-routing' ); ?></label>
			<input type="checkbox" name="cercf7_use_default_email" id="cercf7_use_default_email" value="1" <?php checked( $use_default_email, '1' ); ?>>
		</div>
        
        <p>
            <?php esc_html_e( 'Define email routing rules based on form field values.', 'conditional-email-routing' ); ?>
        </p>
        <p>
        	<strong><?php _e( 'Example:', 'conditional-email-routing' ); ?></strong><br>
        	<code>If 'department' == 'Sales' Mail To 'sales@example.com'</code>
        </p>
        
        <!--Routing conditions-->
        <?php 
		if( !empty($routing_conditions) && is_array($routing_conditions) ): 
		foreach( $routing_conditions as $field => $routings ) : 
		
		?>
        <div class="cercf7-rountings">
            <div class="cercf7-field">
                <label for="cercf7_selected_field"><?php esc_html_e( 'If', 'conditional-email-routing' ); ?></label>
                <select name="cercf7_selected_field[]" id="cercf7_selected_field">
                    <option value=""><?php esc_html_e( '-Select field-', 'conditional-email-routing' ); ?></option>
                    <?php
                    $tags = $this->get_form_tags( $form_id );
                    foreach ( $tags as $tag ) {
                        echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $field, $tag, false ) . '>' . esc_html( $tag ) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="cercf7-conditions">
                <ul id="cercf7_conditions_list">
                   <?php 
					if( is_array($routings) ) :
					$index = 0;
					foreach( $routings as $value => $email ) :  
					?>
                    <li>
                        Value == <input type="text" name="cercf7_<?php echo esc_attr( $field ); ?>_value[<?php echo esc_attr($index); ?>]" value="<?php echo esc_html( $value ); ?>"> 
                        Mail to <input type="text" name="cercf7_<?php echo esc_attr( $field ); ?>_mail[<?php echo esc_attr($index); ?>]" value="<?php echo esc_attr( $email ); ?>">
                    </li>
                    <?php 
					$index++;
					endforeach; 
					endif; 
					?>
                </ul>
                <a href="#" id="cercf7_add_condition">+ Add Condition</a>
            </div>
       
        </div>
        <?php 
		endforeach; 
		endif; 
		?>
        
        <?php
    }

    public function save_custom_tab_settings( $contact_form ) {
        $form_id = $contact_form->id();
		
		// Save checkbox values for enabling routing and using default email
		if ( isset( $_POST['cercf7_routing_enabled'] ) ) {
			update_post_meta( $form_id, '_cf7_routing_enabled', '1' );
		} else {
			delete_post_meta( $form_id, '_cf7_routing_enabled' );
		}

		if ( isset( $_POST['cercf7_use_default_email'] ) ) {
			update_post_meta( $form_id, '_cf7_use_default_email', '1' );
		} else {
			delete_post_meta( $form_id, '_cf7_use_default_email' );
		}
		
        if ( isset( $_POST['cercf7_selected_field'] ) ) {
            $form_fields = array_map( 'sanitize_text_field', $_POST['cercf7_selected_field'] );
            $rules = [];

            foreach ( $form_fields as $form_field ) {
                if ( isset( $_POST[ "cercf7_{$form_field}_value" ] ) && isset( $_POST[ "cercf7_{$form_field}_mail" ] ) ) {
                    $values = array_map( 'sanitize_text_field', $_POST[ "cercf7_{$form_field}_value" ] );
                    $mails  = array_map( 'sanitize_email', $_POST[ "cercf7_{$form_field}_mail" ] );

                    foreach ( $values as $index => $value ) {
                        if ( ! empty( $value ) && ! empty( $mails[ $index ] ) ) {
							$value = strtolower($value);
                            $rules[ $form_field ][ $value ] = $mails[ $index ];
                        }
                    }
                }
            }

            update_post_meta( $form_id, '_cf7_routing_conditions', $rules );
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
