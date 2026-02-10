<?php
namespace describr;

/**
 * Fields class.
 * 
 * Creates HTML for fields not created
 * by JavaScript mainly in edit mode.
 *
 * @package Describr
 * @since 3.0
 */
class Fields {
	/**
	 * Form or tab ID
	 * 
	 * @since 3.0
	 * @var string
	 */
	public $id;
    
    /**
	 * Form fields
	 * 
	 * @since 3.0
	 * @var array
	 */
    public $fields = array();
    
    /**
	 * Stores the fields that have audits
	 * 
	 * @since 3.0
	 * @var array
	 */
    public $audits = array();

    /**
	 * Stores the current location: account, profile, etc
	 * 
	 * @since 3.0
	 * @var null|string
	 */
    public $mode = null;
    
    /**
	 * Whether field can be edited
	 * 
	 * @since 3.0
	 * @var bool
	 */
    public $is_editing = false;
    
    /**
	 * Whether field is being viewed
	 * 
	 * @since 3.0
	 * @var bool
	 */
    public $is_viewing = false;
    
    /**
	 * Field ID
	 * 
	 * @since 3.0
	 * @var string
	 */
    public $field_id;

    /**
	 * Retrieves form field settings
	 * 
	 * @since 3.0
	 * 
	 * @param string The field 
	 * @return array
	 */
	public function get_field( $field ) {
		$fields = $this->get_fields();

		if ( isset( $fields[ $field ] ) && is_array( $fields[ $field ] ) ) {
			$settings = $fields[ $field ];
		} else {
			$fields = describr_get_field();

			if ( isset( $fields[ $field ] ) ) {
				$settings = $fields[ $field ];
			}
		}

		if ( empty( $settings['type'] ) ) {
			return array();
		}
        
        if ( ! isset( $settings['required'] ) ) {
			$settings['required'] = false;
		}
		
		if ( ! isset( $settings['autocomplete'] ) ) {
			$settings['autocomplete'] = 'off';
		}
        
        if ( ! isset( $settings['spellcheck'] ) ) {
			$settings['spellcheck'] = false;
		}

		if ( ! isset( $settings['default'] ) ) {
			$settings['default'] = false;
		}
        
        $type = $settings['type'];

        $settings['classes'] = array( 
        	'describr-field', 
        	'describr-field-' . esc_attr( $field ), 
        	'describr-field-' . esc_attr( $type ),
        	'describr-field-type-' . esc_attr( $type ) 
        );
        
        if ( 'display_name' === $field ) {
        	$settings['description'] = _x( 'Displayed publicly', 'display name', 'describr' );
        }

        switch ( $type ) {
        	case 'text':
				$settings['input'] = 'text';
        		break;
        	case 'email':
        	    $settings['input'] = 'email';
        	    break;
        	case 'password':
        	    $settings['input'] = 'password';
        	    break;
        	case 'tel':
        	    $settings['input'] = 'tel';
        	    break;
        	case 'url':
        	    $settings['input'] = 'url';
        	    break;
        }
        
        /**
         * Filters the field settings
         * 
         * The dynamic part of the filter's name is the field's type
         *
         * @param array $settings Field settings
         */
        $settings = apply_filters( "describr_get_field_{$type}", $settings );

        /**
         * Filters the field settings
         * 
         * The dynamic part of the hook's name is the field's name
         *
         * @param array $settings Field settings
         */
        return apply_filters( "describr_get_field_{$field}", $settings );
	}
    
    /**
	 * Retrieves the field in edit mode
	 * 
	 * @since 3.0
	 * 
	 * @param string $field    The field 
	 * @param string $settings The field settings
	 * @return string
	 */
    public function edit_field( $field, $settings ) {
    	if ( ! empty( $settings['disabled_by_admin'] ) || describr()->is_blacklisted_field( $field ) ) {
    		return '';
    	}

    	$mode = $this->mode;
        
        if ( 'account' === $mode ) {
    		$user_id = describr()->account()->user_id;

    		if ( ! $user_id ) {
    			return '';
    		}
    		describr_set_user_id( $user_id );
    	} elseif ( 'profile' === $mode ) {
    		$user_id = describr_profile_id();

    		if ( ! $user_id ) {
    			return '';
    		}
    		describr_set_user_id( $user_id );
    	} else {
    		$user_id = describr_get_user_id();
    	}

    	if ( 'register' === $mode && is_user_logged_in() && ! describr()->is_admin() ) {
    		return '';
    	}
        
    	if ( ! empty( $settings ) && is_array( $settings ) ) {
    		$settings_ = $this->get_field( $field );
			
			if ( is_array( $settings_ ) && $settings_ ) {
				$settings = array_merge( $settings, $settings_ );
			}
    	}

		if ( 'register' !== $mode && is_user_logged_in() && ! current_user_can( 'edit_user', $user_id ) ) {
			return '';
		}
        
        if ( ( empty( $settings['editable'] ) && 'user_login' !== $field ) || empty( $settings['type'] ) ) {
        	return '';
        }

    	$type = $settings['type'];

    	$disabled = '';
		
		if ( isset( $settings['disabled'] ) ) {			
			$disabled = $settings['disabled'];
		}
        
        $classes = '';

        if ( array_key_exists( 'classes', $settings ) ) {
			$classes = implode( ' ', $settings['classes'] );
		}
        
        $wrapper_args = array( 'classes' => array( $classes ) );

        $input   = array_key_exists( 'input', $settings ) ? $settings['input'] : 'text';
		$default = array_key_exists( 'default', $settings ) ? $settings['default'] : false;
		$autocomplete = array_key_exists( 'autocomplete', $settings ) ? $settings['autocomplete'] : 'off';
        
        if ( true === $autocomplete ) {
        	$autocomplete = 'on';
        } elseif ( false === $autocomplete ) {
        	$autocomplete = 'off';
        }

		$spellcheck  = array_key_exists( 'spellcheck', $settings ) ? ( ! $settings['spellcheck'] ? 'false' : 'true' ) : 'false';
        
        $required = ! empty( $settings['required'] ) ? ' required="required" aria-required="true"' : '';
        
        describr_fetch_user( $user_id );

        $output = '';
                
        $value = $this->value( $field, $settings, $default );

        $prefix = $this->beautify( describr()->prefix );
       
		switch ( $type ) {
			case 'email':
			case 'text':
			case 'url':
			case 'tel':
			    if ( $is_fieldset = ! empty( $settings['legend'] ) ) {
                    $output .= '<fieldset class="describr-fieldset"><legend>' . esc_html( $settings['legend'] ) . '</legend>';
                }
                
                $this->field_id = $prefix . $this->beautify( $field );
				
				$output .= '<div ' . $this->wrapper_attrs( $field, $wrapper_args, $settings ) . '>';

				if ( isset( $settings['label'] ) ) {
					$output .= $this->label( $settings['label'], $field, $settings );
				}
                
                $has_error = false;

                if ( $this->has_error( $field ) ) {
                	$has_error = true;
					$output .= $this->error( $field, $settings );
				}

				$output .= '<div class="describr-field-wrap' . ( $has_error ? ' describr-field-wrap-error' : '' ) . '">';

				$describedby = ! empty( $settings['description'] ) ? ' aria-describedby="' . esc_attr( $this->field_id ) . '-description"' : '';
				
				if ( '' !== $value ) {
                	$value = ' value="' . esc_attr( $value ) . '"';
                }
                
                $invalid = $this->aria_invalid_attrs( $has_error, $field );

				$output .= '<input ' . $disabled . $required . $describedby . $invalid . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $field, $settings ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field ) . '"' . $value . ' id="' . esc_attr( $this->field_id ) . '" data-field="' . esc_attr( $field ) . '" /></div>';

				if ( ! empty( $disabled ) ) {
                	$hidden_val = $this->hidden_value( $field, $default, $settings );

                	if ( ! is_bool( $hidden_val ) ) {
                		$output .= $this->hidden( $field, $hidden_val );
                	}
                }

				if ( $describedby ) {
					$output .= $this->description( $field, $settings['description'], $settings );
				}	
                
                if ( 'user_email' === $field && is_user_logged_in() && $user_id === get_current_user_id() ) {
                	$output .= describr_cancel_pending_email_change_notice();
                }

				$output .= $this->asides( $field, $settings );		

				$output .= '</div>';
                
				if ( $is_fieldset ) {
                    $output .= '</fieldset>';
                }

                describr_set_form_field( $field );
				break;
			case 'password':
			    $toggle = ! empty( $settings['toggle'] );
                
                $required = ' required="required" aria-required="true"';
                
                if ( $is_fieldset = ! empty( $settings['legend'] ) ) {
                    $output .= '<fieldset class="describr-fieldset"><legend>' . esc_html( $settings['legend'] ) . '</legend>';
                }

			    if ( 'single_user_pass' === $field ) {
			    	$this->field_id = $prefix . $this->beautify( $field );
					
					$output .= '<div ' . $this->wrapper_attrs( $field, $wrapper_args, $settings ) . '>';

				    if ( isset( $settings['label'] ) ) {
					    $output .= $this->label( $settings['label'], $field, $settings );
				    }
                
                    if ( $has_error = $this->has_error( $field ) ) {
                	    $output .= $this->error( $field, $settings );
				    }

				    $output .= '<div class="describr-field-wrap';
                    
                    if ( $has_error ) {
                    	$output .= ' describr-field-wrap-error';
                    }
                    
                    $output .= '">';

				    $describedby = ! empty( $settings['description'] ) ? ' aria-describedby="' . esc_attr( $this->field_id ) . '-description"' : '';

                    if ( 'on' === $autocomplete ) {
                    	$autocomplete = 'current-password';
                    }

				    $output .= '<input ' . $required . $describedby . $this->aria_invalid_attrs( $has_error, $field ) . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $field, $settings ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field ) . '" id="' . esc_attr( $this->field_id ) . '" data-field="' . esc_attr( $field ) . '" /></div>';
                    
                    if ( $toggle ) {
                    	$toggle_pwd_id = $this->field_id . '-toggle';

                    	$output .= '<p><input type="checkbox" id="' . esc_attr( $toggle_pwd_id ) . '" class="describr-toggle-pass" data-toggle="' . esc_attr( $this->field_id ) . '" /> <label for="' . esc_attr( $toggle_pwd_id ) . '">' . esc_html__( 'Show password', 'describr' ) . '</label></p>';
                    }

                    if ( $describedby ) {
					    $output .= $this->description( $field, $settings['description'], $settings );
				    }			
                    
				    $output .= '</div>';

				    describr_set_form_field( $field );
				} else {
					$toggle_pwds = array();

                    if ( ( 'account' === $mode || describr_is_page( 'account' ) ) && describr()->account()->is_password_required( 'password' ) ) {
						$field_ = 'current_' . $field;
                        $this->field_id = $prefix . $this->beautify( $field_ );
						$output .= '<div ' . $this->wrapper_attrs( $field_, $wrapper_args, $settings ) . '>';

				        if ( isset( $settings['label'] ) ) {
					        $output .= $this->label( __( 'Current Password', 'describr' ), $field_, $settings );
				        }
                
                        if ( $has_error = $this->has_error( $field_ ) ) {
                	        $output .= $this->error( $field_, $settings );
				        }

				        $output .= '<div class="describr-field-wrap';
                    
                        if ( $has_error ) {
                    	    $output .= ' describr-field-wrap-error';
                        }
                        
                        $output .= '">';

				        $toggle_pwds[] = $this->field_id;

                        $autocomplete_ = $autocomplete;

                        if ( 'on' === $autocomplete_ ) {
                    	    $autocomplete_ = 'current-password';
                        }

	                    $output .= '<input ' . $required . $this->aria_invalid_attrs( $has_error, $field_ ) . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete_ ) . '" class="' . esc_attr( $this->get_class( $field_, $settings ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_ ) . '" id="' . esc_attr($this->field_id ) . '" data-field="' . esc_attr( $field_ ) . '" /></div>';
                        $output .= '</div>';
                        describr_set_form_field( $field_ );
					}

                    $this->field_id = $prefix . $this->beautify( $field );
                    
                    $output .= '<div ' . $this->wrapper_attrs( $field, $wrapper_args, $settings ) . '>';
                    
                    if ( 'account' === $mode || describr_is_page( 'account' ) ) {
					    if ( isset( $settings['label'] ) ) {
					    	$output .= $this->label( __( 'New Password', 'describr' ), $field, $settings );
					    }  
					} elseif ( isset( $settings['label'] ) ) {
					    $output .= $this->label( $settings['label'], $field, $settings );
				    }
                
                    if ( $has_error = $this->has_error( $field ) ) {
                	    $output .= $this->error( $field, $settings );
				    }

				    $output .= '<div class="describr-field-wrap';
                    
                    if ( $has_error ) {
                    	$output .= ' describr-field-wrap-error';
                    }
                    
                    $output .= '">';

				    $toggle_pwds[] = $this->field_id;
                    
                    $describedby_ids = array();

                    if ( ! empty( $settings['description'] ) ) {
                    	$describedby_ids[] = $this->field_id . '-description';
                    }

                    if ( ! empty( $settings['description'] ) ) {
                    	$describedby_ids[] = 'pass-strength-result';
                    }
                    
                    $describedby_ids = array_map( 'esc_attr', $describedby_ids );
                    
                    if ( $describedby_ids ) {
                    	$describedby = ' aria-describedby="' . implode( ',', $describedby_ids ) . '"';
                    } else {
                    	$describedby = '';
                    }
                    
                    if ( 'on' === $autocomplete ) {
                    	$autocomplete = 'new-password';
                    }

                    $output .= '<input ' . $required . $describedby . $this->aria_invalid_attrs( $has_error, $field ) . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $field, $settings ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field ) . '" id="' . esc_attr( $this->field_id ) . '" data-field="' . esc_attr( $field ) . '" />';

                    if ( in_array( 'pass-strength-result', $describedby_ids, true ) ) {
                    	$output .= '<div id="describr-pass-strength-result" class="describr-hide-if-no-js ' . esc_attr( $mode ) . '" aria-live="polite">';
                    	$output .= esc_html_x( 'Strength indicator', 'password', 'describr' );
                    	$output .= '</div>';
                    }
                    
                    $output .= '</div>';

                    if ( ! empty( $settings['description'] ) ) {
					    $output .= $this->description( $field, $settings['description'], $settings );
				    }			
                    
                    describr_set_form_field( $field );

				    if ( ! empty( $settings['confirm'] ) ) {
                    	$field_ = 'confirm_' . $field;
                        
                        $this->field_id = $prefix . $this->beautify( $field_ );

						$output .= '</div><div ' . $this->wrapper_attrs( $field_, $wrapper_args, $settings ) . '>';

				        if ( isset( $settings['label'] ) ) {
					        $output .= $this->label( __( 'Confirm Password', 'describr' ), $field_, $settings );
				        }
                
                        if ( $has_error = $this->has_error( $field_ ) ) {
                	        $output .= $this->error( $field_, $settings );
				        }

				        $output .= '<div class="describr-field-wrap';
                    
                        if ( $has_error ) {
                    	    $output .= ' describr-field-wrap-error';
                        }
                        
                        $output .= '">';

				        $toggle_pwds[] = $this->field_id;

				        $output .= '<input ' . $required . $this->aria_invalid_attrs( $has_error, $field_ ) . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $field_, $settings ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_ ) . '" id="' . esc_attr( $this->field_id ) . '" data-field="' . esc_attr( $field_ ) . '" /></div>';

				        if ( $toggle ) {
                    	    $toggle_pwd_id = 'describr-password-toggle';

                    	    $output .= '<p><input type="checkbox" id="' . esc_attr( $toggle_pwd_id ) . '" class="describr-toggle-pass" data-toggle="' . esc_attr( implode( ',', $toggle_pwds ) ) . '" /> <label for="' . esc_attr( $toggle_pwd_id ) . '">' . esc_html__( 'Show password', 'describr' ) . '</label></p>';
                        }
                        
                        $output .= $this->asides( $field, $settings );

                        $output .= '</div>';

                        describr_set_form_field( $field_ );
                    } else {
                    	if ( $toggle ) {
                    	    $toggle_pwd_id = 'describr-password-toggle';

                    	    $output .= '<p><input type="checkbox" id="' . esc_attr( $toggle_pwd_id ) . '" class="describr-toggle-pass" data-toggle="' . esc_attr( implode( ',', $toggle_pwds ) ) . '" /> <label for="' . esc_attr( $toggle_pwd_id ) . '">' . esc_html__( 'Show password', 'describr' ) . '</label></p>';
                        }

                        $output .= $this->asides( $field, $settings );

                        $output .= '</div>';
                    }
				}

				if ( $is_fieldset ) {
					$output .= '</fieldset>';
				}
			    break;
			case 'checkbox':
			    if ( $is_fieldset = ! empty( $settings['legend'] ) ) {
                    $output .= '<fieldset class="describr-fieldset"><legend>' . esc_html( $settings['legend'] ) . '</legend>';
                }
                
                $this->field_id = $prefix . $this->beautify( $field );

			    $output .= '<div' . $this->wrapper_attrs( $field, $wrapper_args, $settings );

			    $has_error = $this->has_error( $field );
                
                $invalid = $this->aria_invalid_attrs( $has_error, $field );

                $has_options = isset( $settings['options'] );

                if ( $has_options ) {
                	$output .= $invalid;
                }

			    $output .= '>';

                if ( $has_options && isset( $settings['label'] ) ) {
                	$output .= $this->label( $settings['label'], $field, $settings );
                }
                 
                if ( $has_error ) {
                	$output .= $this->error( $field, $settings );
                }

                $output .= '<div class="describr-field-wrap">';

				$describedby = ! empty( $settings['description'] ) ? ' aria-describedby="' . esc_attr( $this->field_id ) . '-description"' : '';

                if ( ! $has_options ) {				
				    /**
                     * Filters the input value for a field with a single checkbox
                     * 
                     * @since 3.0
                     * 
                     * @param int    $value    The checkbox value
                     * @param string $field    The field
                     * @param array  $settings The field settings
                     */
                    $value_ = apply_filters( 'describr_edit_field_checkbox_single_value', 1, $field, $settings );
				    
				    $output .= '<input ' . $disabled . $required . $describedby . $invalid . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $field, $settings ) ) . '" type="checkbox" name="' . esc_attr( $field ) . '" value="' . esc_attr( $value_ ) . '" id="' . esc_attr( $this->field_id ) . '" data-field="' . esc_attr( $field ) . '"' . checked( $value_, $value, false ) . ' />';
                    
                    if ( isset( $settings['label'] ) ) {
					    $output .= ' <label class="describr-field-checkbox-label" for="' . esc_attr( $this->field_id ) . '">' . esc_html( $settings['label'] ) . '</label>';
				    }

                    if ( ! empty( $disabled ) ) {
                	    $hidden_val = $this->hidden_value( $field, $default, $settings );

                	    if ( ! is_bool( $hidden_val ) ) {
                		    $output .= $this->hidden( $field, $hidden_val );
                	    }
                    }
			    } else {
				
				    $options = $this->options( $field, $settings );
                
                    if ( ! empty( $options ) ) {
					    $is_numeric_array = wp_is_numeric_array( $options );

					    if ( ! is_array( $value ) ) {
					    	$value = array( $value );
					    }

                        foreach ( $options as $option_value => $option_label ) {
						    if ( $is_numeric_array ) {
							    $option_value = $option_label;
						    };

						    $output .= '<div class="describr-field-checkbox">';

						    $checkbox_id = wp_unique_id( $this->field_id . '-' );
                            
                            $output .= '<input ' . $disabled . $describedby . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete ) . '" type="checkbox" name="' . esc_attr( $field ) . '[]" value="' . esc_attr( $option_value ) . '" id="' . esc_attr( $checkbox_id ) . '" data-field="' . esc_attr( $field ) . '"';

						    $output .= checked( in_array( $option_value, $value, true ), true, false );

						    $output .= ' /> <label class="describr-field-checkbox-label" for="' . esc_attr( $checkbox_id ) . '">' . esc_html( $option_label ) . '</label>';
						    $output .= '</div>';

						    if ( ! empty( $disabled ) ) {
						    	$output .= $this->hidden( "{$field}[]", $option_value );
						    }
					    }
				    }
			    }

			    $output .= '</div>';

				if ( $describedby ) {
					$output .= $this->description( $field, $settings['description'], $settings );
				}
                
                $output .= $this->asides( $field, $settings );

				$output .= '</div>';

				if ( $is_fieldset ) {
					$output .= '</fieldset>';
				}
                
				describr_set_form_field( $field );
			    break;
			case 'radio':
			    if ( $is_fieldset = ! empty( $settings['legend'] ) ) {
                    $output .= '<fieldset class="describr-fieldset"><legend>' . esc_html( $settings['legend'] ) . '</legend>';
                }
                
                $this->field_id = $prefix . $this->beautify( $field );

                $has_error = $this->has_error( $field );

			    $output .= '<div' . $this->wrapper_attrs( $field, $wrapper_args, $settings ) . $this->aria_invalid_attrs( $has_error, $field ) . '>';

				if ( isset( $settings['label'] ) ) {
					$output .= $this->label( $settings['label'], $field, $settings );
				}                

                if ( $has_error ) {
					$output .= $this->error( $field, $settings );
				}

				$describedby = ! empty( $settings['description'] ) ? ' aria-describedby="' . esc_attr( $this->field_id ) . '-description"' : '';
                
				$output .= '<div class="describr-field-wrap">';
				
				$options = $this->options( $field, $settings );
                
                //Add options
				if ( ! empty( $options ) ) {
					$is_numeric_array = wp_is_numeric_array( $options );

					foreach ( $options as $option_value => $option_label ) {
						if ( $is_numeric_array ) {
							$option_value = $option_label;
						};

						$output .= '<div class="describr-field-radio">';

						$radio_id = wp_unique_id( $this->field_id . '-' );
                        
						$output .= '<input ' . $disabled . $describedby . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete ) . '" type="radio" name="' . esc_attr( $field ) . '" value="' . esc_attr( $option_value ) . '" id="' . esc_attr( $radio_id ) . '" data-field="' . esc_attr( $field ) . '"';

						$output .= checked( $value, $option_value, false );

						$output .= ' /> <label class="describr-field-radio-label" for="' . esc_attr( $radio_id ) . '">' . esc_html( $option_label ) . '</label>';
						$output .= '</div>';
					}
				}
                
                if ( ! empty( $disabled ) ) {
                	$hidden_val = $this->hidden_value( $field, $default, $settings );

                	if ( ! is_bool( $hidden_val ) ) {
                		$output .= $this->hidden( $field, $hidden_val );
                	}
                }

				$output .= '</div>';

				if ( $describedby ) {
					$output .= $this->description( $field, $settings['description'], $settings );
				}
                
                $output .= $this->asides( $field, $settings );

				$output .= '</div>';

				if ( $is_fieldset ) {
					$output .= '</fieldset>';
				}

				describr_set_form_field( $field );
			    break;
			case 'select':
			    if ( $is_fieldset = ! empty( $settings['legend'] ) ) {
                    $output .= '<fieldset class="describr-fieldset"><legend>' . esc_html( $settings['legend'] ) . '</legend>';
                }
                
                $this->field_id = $prefix . $this->beautify( $field );

			    $output .= '<div ' . $this->wrapper_attrs( $field, $wrapper_args, $settings ) . '>';

				if ( isset( $settings['label'] ) ) {
					$output .= $this->label( $settings['label'], $field, $settings );
				}

                if ( $has_error = $this->has_error( $field ) ) {
					$output .= $this->error( $field, $settings );
				}

                $describedby = ! empty( $settings['description'] ) ? ' aria-describedby="' . esc_attr( $this->field_id ) . '-description"' : '';
                
				$output .= '<div class="describr-field-wrap">';
				
				$options = $this->options( $field, $settings );

	            $output .= '<select ' . $disabled . $required . $describedby . $this->aria_invalid_attrs( $has_error, $field ) . ' spellcheck="' . $spellcheck . '" autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $field, $settings ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field ) . '" id="' . esc_attr( $this->field_id ) . '" data-field="' . esc_attr( $field ) . '">';
				
				//Add options
				if ( ! empty( $options ) ) {
					$is_numeric_array = wp_is_numeric_array( $options );
                    
                    foreach ( $options as $option_value => $option_label ) {
						if ( $is_numeric_array ) {
							$option_value = $option_label;
						};

						$output .= '<option value="' . esc_attr( $option_value ) . '"';

                        $selected = selected( $value, $option_value, false );

                        if ( $selected ) {
                        	$output .= $selected;
                        }

                        $output .= '>' . esc_html( $option_label ) . '</option>';
					}
				}

				$output .= '</select>';
                
                if ( ! empty( $disabled ) ) {
                	$hidden_val = $this->hidden_value( $field, $default, $settings );

                	if ( ! is_bool( $hidden_val ) ) {
                		$output .= $this->hidden( $field, $hidden_val );
                	}
                }

				$output .= '</div>';

				if ( $describedby ) {
					$output .= $this->description( $field, $settings['description'], $settings );
				}
                
                $output .= $this->asides( $field, $settings );

				$output .= '</div>';

				if ( $is_fieldset ) {
                    $output .= '</fieldset>';
                }

                describr_set_form_field( $field );
			    break;
			default:
				$mode = (string) $this->mode;//account, profile, register, etc

				/**
				 * Filters the edit field output
			     * 
			     * Each return field key must be passed the {@see 'describr_set_form_field'} function.
			     * The fields are used to create a nonce that is used to validate submitted form fields
			     * 
			     * Remove a set field by passing the field key to the {@see 'describr_remove_form_field'} function
				 *
				 * @since 3.0
				 *
				 * @param string $output   Field HTML
				 * @param array  $settings Field settings
				 * @param string $mode     Field mode
				 */
				$output = apply_filters( 'describr_edit_field', $output, $settings, $mode );

				/**
				 * Filters the edit field output by mode
			     * 
			     * Each return field key must be passed the {@see 'describr_set_form_field'} function.
			     * The fields are used to create a nonce that is used to validate submitted form fields
			     * 
			     * Remove a set field by passing the field key to the {@see 'describr_remove_form_field'} function
				 *
				 * @since 3.0
				 *
				 * @param string $output   Field HTML
				 * @param array  $settings Field settings
				 * @param string $type     Field type
				 */
				$output = apply_filters( "describr_edit_field_{$mode}", $output, $settings, $type );

				/**
				 * Filters the edit field output by type
			     * 
			     * Each return field key must be passed the {@see 'describr_set_form_field'} function.
			     * The fields are used to create a nonce that is used to validate submitted form fields
			     * 
			     * Remove a set field by passing the field key to the {@see 'describr_remove_form_field'} function
				 *
				 * @since 3.0
				 *
				 * @param string $output   Field HTML
				 * @param array  $settings Field settings
				 * @param string $type     Field mode
				 */
                $output = apply_filters( "describr_edit_field_{$type}", $output, $settings, $mode );
				break;
		}

		if ( $this->mode ) {
			/**
			 * Filters the edit field output by field
			 * 
			 * Each return field key must be passed to the {@see 'describr_set_form_field'} function.
			 * The fields are used to create a nonce that is used to validate submitted form fields
			 * 
			 * Remove a set field by passing the field key to the {@see 'describr_remove_form_field'} function
			 *
			 * @since 3.0
			 *
			 * @param string $output   Field HTML
			 * @param array  $settings Field settings
			 * @param string $type     Field mode
			 */
            $output = apply_filters( "describr_edit_field_{$field}_output", $output, $settings, $this->mode );
		}

		return $output;
    }

    /**
	 * Retrieves form fields
	 * 
	 * @since 3.0
	 * 
	 * @return array
	 */
	public function get_fields() {
		if ( empty( $this->id ) ) {
			return array();
		}

		if ( empty( $this->fields[ $this->id ] ) ) {
		    /**
			 * Filters the form fields
			 * 
			 * @since 3.0
			 *
			 * @param array $fields  Form fields
			 * @param int   $form_id Form ID
			 */
			$this->fields[ $this->id ] = apply_filters( 'describr_form_fields', array(), $this->id );
		}

		return $this->fields[ $this->id ];
	}
    
    /**
	 * Retrieves field value
	 * 
	 * @since 3.0
	 *
	 * @param  string $field    The field
	 * @param  array  $settings The field settings
	 * @param  mixed  $default  The field's default value
	 * @return mixed
	 */
	public function value( $field, $settings, $default = false ) {
		$type = isset( $settings['type'] ) ? $settings['type'] : '';
		
		if ( isset( describr()->form()->post_form[ $field ] ) ) {
			if ( str_contains( $field, 'user_pass' ) ) {
				return '';
			}

			$value = describr()->form()->post_form[ $field ];
		} elseif ( $this->is_editing ) {
			if ( 'password' === $type || str_contains( $field, 'user_pass' ) ) {
				return '';
			}

			$value = describr_user( $field );

			/**
			 * Filters the editing field value
			 * 
			 * The dynamic part of the hook's name is the name of the field
			 * 
			 * @since 3.0
			 * 
			 * @param mixed $value    Field value
			 * @param array $settings Field settings
			 */
			$value = apply_filters( "describr_edit_field_{$field}_value", $value, $settings );

			/**
			 * Filters the editing field value
			 * 
			 * The dynamic part of the hook's name is the name of the field
			 * type
			 * 
			 * @since 3.0
			 * 
			 * @param mixed  $value    Field value
			 * @param string $field    Field
			 * @param array  $settings Field settings
			 */
			$value = apply_filters( "describr_edit_field_{$type}_value", $value, $field, $settings );
		} elseif ( isset( describr()->user()->cur_user->$field ) ) {
			$value = describr()->user()->cur_user->$field;

			/**
			 * Filters the field saved value
			 * 
			 * The dynamic part of the hook's name is the name of the field
			 * 
			 * @since 3.0
			 * 
			 * @param mixed $value    Field saved value
			 * @param array $settings Field settings
			 */
			$value = apply_filters( "describr_field_{$field}_value", $value, $settings );
			
			/**
			 * Filters the field saved value
			 * 
			 * The dynamic part of the hook's name is the name of the field
			 * type
			 * 
			 * @since 3.0
			 * 
			 * @param mixed  $value    Field saved value
			 * @param string $field    Field
			 * @param array  $settings Field settings
			 */
			$value = apply_filters( "describr_field_{$type}_value", $value, $field, $settings );
		}
        
        if ( ! isset( $value ) ) {
        	$value = '';

        	if ( 'register' === $this->mode || $this->is_editing ) {
        		/**
				 * Filters the fields default value
				 * 
				 * @since 3.0
				 * 
				 * @param mixed  $default  The field default value
			     * @param string $field    The field
			     * @param array  $settings The field settings
			     * @param string $type     The field type 
				 */
				$default = apply_filters( 'describr_field_default_value', $default, $field, $settings, $type );

				/**
				 * Filters the fields default value
				 * 
				 * The dynamic part of the hook's name is the name of the field
				 * 
				 * @since 3.0
				 * 
				 * @param mixed  $default  The field default value
			     * @param array  $settings The field settings
			     * @param string $type     The field type 
				 */
				$default = apply_filters( "describr_field_{$field}_default_value", $default, $settings, $type );
				
				/**
				 * Filters the fields default value
				 * 
				 * The dynamic part of the hook's name is the name of the field
			     * type
				 * 
				 * @since 3.0
				 * 
				 * @param mixed  $default  The field default value
			     * @param string $field    The field
			     * @param array  $settings The field settings 
				 */
				$value = apply_filters( "describr_field_{$type}_default_value", $default, $field, $settings );

				/*This filter is documented in wp-content/plugins/describr/includes/class-fields.php*/
			    $value = apply_filters( "describr_field_{$field}_value", $value, $settings );
			    
			    /*This filter is documented in wp-content/plugins/describr/includes/class-fields.php*/
			    $value = apply_filters( "describr_field_{$type}_value", $value, $field, $settings );
        	}
        } elseif ( is_array( $value ) && ! count( $value ) ) {
        	$value = '';
        }
        
        /**
		 * Filters the field value
		 * 
		 * @since 3.0
		 * 
		 * @param mixed  $value    Field value
		 * @param mixed  $default  Field default value
	     * @param string $field    Field
	     * @param string $type     Field type
	     * @param array  $settings Field settings 
		 */
        $value = apply_filters( 'describr_field_value', $value, $default, $field, $type, $settings );

		return  map_deep( $value, 'describr_falsey_to_empty_str' );
	}
    
    /**
	 * Retrieves the field wrapper attributes
	 * 
	 * @since 3.0
	 *
	 * @param  string $field    The field
	 * @param  array  $attrs    The field attributes
	 * @param  mixed  $settings The field settings
	 * @return string The field wrapper attributes
	 */
	public function wrapper_attrs( $field, $attrs, $settings ) {
		if ( ! is_array( $attrs ) ) {
			$attrs = array( $attrs );
		}
        
        $type = $settings['type'];
        
        $attrs_ = array(
        	'id'         => array( 'describr-field-' . $this->id . '-' . $this->beautify( $field ) ),
        	'data-field' => array( $field ), 
        );
        
        if ( isset( $attrs['classes'] ) ) {
        	$attrs_['class'] = (array) $attrs['classes'];
        }

        if ( isset( $attrs['style'] ) ) {
            $style = $attrs['style'];
        	$style_ = '';

        	foreach ( $style as $prop => $val ) {
        		$style_ .= esc_attr( $prop ) . ':' . esc_attr( $val ) . ';';
        	}
        }

        unset( $attrs['style'], $attrs['classes'], $attrs['class'] );

        $attrs = wp_parse_args( $attrs, $attrs_ );

        $attrs = map_deep( $attrs, 'esc_attr' );

        if ( isset( $style_ ) ) {
        	$attrs['style'] = array( $style_ );
        }
        
        /**
		 * Filters the field wrapper HTML attributes
		 * 
		 * @since 3.0
		 * 
		 * @param string $attrs    The field wrapper attributes
		 * @param string $field    The field
		 * @param array  $settings The field settings
		 */
		$attrs = apply_filters( 'describr_field_wrapper_attrs', $attrs, $field, $settings );
        $attrs = array_filter( $attrs );

        $html_attrs = '';

        foreach ( $attrs as $attr_name => $attr_val ) {
        	$attr_val = implode( ' ', $attr_val );

        	$html_attrs .= ' ' . esc_attr( $attr_name ) . '="' . $attr_val . '"';
        }

        return $html_attrs;
    }

    /**
	 * Retrieves the field HTML classes
	 * 
	 * @since 3.0
	 *
	 * @param  string $field    Field
	 * @param  array  $settings Field settings
	 * @return string The HTML classes
	 */
	public function get_class( $field, $settings ) {
		$classes = 'describr-form-field';

		if ( describr_is_page( 'account' ) && ! empty( $settings['type'] ) && in_array( $settings['type'], array( 'text', 'email', 'url', 'password' ), true ) ) {

			$classes .= ' describr-regular-text';
		}

		/**
		 * Filters the field classes
		 * 
		 * @since 3.0
		 * 
		 * @param string $classes  Field classes
		 * @param string $field    Field
		 * @param array  $settings Field settings
		 */
		return apply_filters( 'describr_field_classes', $classes, $field, $settings );
	}
    
    /**
	 * Retrieves the field's aria-invalid accessibility attribute and its value
	 *
	 * @param bool   $has_error Whether the field has errors
	 * @param string $field     The field
	 * @return string
	 */
	public function aria_invalid_attrs( $has_error, $field ) {
		$attr = ' aria-invalid="false" ';
		
		if ( $has_error ) {
			$attr = ' aria-invalid="true" aria-errormessage="' . esc_attr( $this->field_id ) . '-error" ';
		}

		return $attr;
	}

    /**
	 * Retrieves whether the field has errors
	 *
	 * @since 3.0
	 * 
	 * @param string $field Field
	 * @return bool True if field has errors, otherwise false
	 */
	public function has_error( $field ) {
		return isset( describr()->error()->errors[ $field ] );
	}
    
    /**
	 * Retrieves the field errors
	 *
	 * @since 3.0
	 * 
	 * @param string $field    Field
	 * @param array  $settings Field settings
	 * @return string
	 */
	public function error( $field, $settings ) {
		if ( empty( describr()->error()->errors[ $field ] ) ) {
			return '';
		}
        
        $errors = array_map( 'wp_kses_post', describr()->error()->get_error_messages( $field ) );

        $output = '<p class="describr-field-error" id="' . esc_attr( $this->field_id ) . '-error">' . implode( '<br />', $errors ) . '</p>';
		
		/**
		 * Filters the field errors HTML
		 * 
		 * @since 3.0
		 * @param string $output   Error output
		 * @param array  $label    Field errors
		 * @param string $field    Field
		 * @param array  $settings Field settings
		 */
        return apply_filters( 'describr_edit_field_error', $output, $errors, $field, $settings );
	}
    
    /**
	 * Retrieves field label
	 *
	 * @since 3.0
	 * 
	 * @param string $label    Field label
	 * @param string $field    Field
	 * @param array  $settings Field settings
	 * @return string
	 */
	public function label( $label, $field, $settings ) {
	    /**
		 * Filters the field label by field
		 * 
		 * @since 3.0
		 * 
		 * @param string $label    Field label
		 * @param array  $settings Field settings
		 */
        $label = apply_filters( "describr_edit_field_label_{$field}", $label, $settings );
        
        if ( $label ) {
        	$output = '<div class="describr-field-label">';

		    $output .= '<label for="' . esc_attr( $this->field_id ) . '">' . esc_html( $label );

		    if ( $this->is_editing && ! empty( $settings['required'] ) ) {
			    $output .= '<span class="describr-req" title="' . esc_attr_x( 'Required', 'form field', 'describr' ) . '">';
			    $output .= esc_html_x( '*', 'required symbol', 'describr' );
			    $output .= '</span>';
		    }

		    $output .= '</label></div>';
        } else {
        	$output = '';
        }
        
		/**
		 * Filters the field label HTML
		 * 
		 * @since 3.0
		 * @param string $output   Label output
		 * @param string $label    Field label
		 * @param string $field    Field
		 * @param array  $settings Field settings
		 */
        return apply_filters( 'describr_edit_field_label', $output, $label, $field, $settings );
	}
    
    /**
	 * Retrieves the field description
	 *
	 * @since 3.0
	 * 
	 * @param string $field    Field
	 * @param string $field    Field description
	 * @param array  $settings Field settings
	 * @return string
	 */
	public function description( $field, $description, $settings ) {
		/**
		 * Filters the field description by field
		 * 
		 * @since 3.0
		 * 
		 * @param string $description Field description
		 * @param array  $settings    Field settings
		 */
        $description = apply_filters( "describr_edit_field_description_{$field}", $description, $settings );
        
        if ( $description ) {
        	$output = '<';
            $output .= 'account' === $this->mode ? 'p' : 'div';
        
            $output .= ' class="describr-field-description describr-description" id="' . esc_attr( $this->field_id ) . '-description">' . wp_kses_post( $description ) . '</';

            $output .= 'account' === $this->mode ? 'p' : 'div';
            $output .= '>';
        } else {
        	$output = '';
        }
        
        /**
		 * Filters the field description
		 * 
		 * @since 3.0
		 * 
		 * @param string $output   Field description
		 * @param string $field    Field
		 * @param array  $settings Field settings
		 * @param string $mode     Field mode
		 */
        return apply_filters( 'describr_edit_field_description', $output, $field, $settings, $this->mode );
	}

	/**
	 * Retrieves whether a function has been disallowed
	 * 
	 * PHP functions are use as callbacks
	 * 
	 * @since 3.0
	 *
	 * @param string $func Function name
	 * @return bool True if the function is disallowed, otherwise false 
	 */
	public function is_func_disallowed( $func ) {
		$funcs = get_defined_functions();
		$disallowed_funcs = isset( $funcs['internal'] ) ? $funcs['internal'] : array();

		/**
		 * Filters the disallowed functions
		 * 
		 * @since 3.0
		 * 
		 * @param array  $disallowed_funcs Disallowed functions
		 */
		$disallowed_funcs = apply_filters( 'describr_disallowed_functions', $disallowed_funcs );
        
        $func = strtolower( trim( $func ) );        
        $func = trim( $func, '\\' );
			
		return in_array( $func, $disallowed_functs, true );
	}
    
    /**
	 * Retrieves options for a field or field aside
	 * 
	 * @since 3.0
	 *
	 * @param  string $key      The field or aside key
	 * @param  array  $settings The field or aside settings
	 * @return array The options
	 */
	public function options( $key, $settings ) {
		$options = array();

		if ( isset( $settings['options'] ) && is_array( $settings['options'] ) ) {
			$options = $settings['options'];
		}

		if ( ! empty( $settings['options_func'] ) && function_exists( $settings['options_func'] ) ) {
			if ( ! $this->is_func_disallowed( $settings['options_func'] ) ) {
				$options_func = $settings['options_func'];

				if ( empty( $settings['options_func_args'] ) || empty( $settings['options_func_num_args'] ) ) {
					$options = call_user_func( $settings['options_func'] );
				} else {
					$options = call_user_func_array( $settings['options_func'], array_slice( $settings['options_func_args'], 0, $settings['options_func_num_args'] ) );
				}
                        
                if ( ! is_array( $options ) ) {
                    $options = array();
                }
            }
		}

		/**
		 * Filters the field options
		 * 
		 * The dynamic part of the hook's name is
		 * the name of the field
		 * 
		 * @since 3.0
		 * 
		 * @param string $options  Field or aside options
		 * @param array  $settings Field or aside settings
		 */
        $options = apply_filters( "describr_edit_field_{$key}_options", $options, $settings );

        /**
		 * Filters the field options
		 * 
		 * @since 3.0
		 * 
		 * @param string $options  Field or aside options
		 * @param string $key      Field or aside key
		 * @param array  $settings Field or aside settings
		 */
        return (array) apply_filters( 'describr_edit_field_options', $options, $key, $settings );
	}

	/**
	 * Retrieves HTML input element of type hidden
	 * 
	 * @since 3.0
	 *
	 * @param string $field The field
	 * @param string $value The field value
	 * @return string
	 */
	public function hidden( $field, $value ) {
		return '<input type="hidden" name="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '" />';
	}
    
    /**
	 * Retrieves the value for HTML input element of type hidden
	 * 
	 * @since 3.0
	 *
	 * @param string $field    The field
	 * @param string $default  The field default value
	 * @param array  $settings The field settings
	 * @return mixed
	 */
	public function hidden_value( $field, $default, $settings ) {
		$value = describr_user( $field );

		if ( ! is_scalar( $value ) ) {
			$value = false;
		}
        
        /**
		 * Filters the field value for HTML input element of type hidden
		 * 
		 * @since 3.0
		 * 
		 * @param mixed  $value    The field value
		 * @param mixed  $default  The defualt value
		 * @param string $field    The field
	     * @param array  $settings The field settings
		 */
		return apply_filters( 'describr_edit_field_hidden_value', $value, $default, $field, $settings );
	}

	/**
	 * Retrives a field's asides output
	 *  
	 * @since 3.0
	 *
	 * @param string $field    The field
	 * @param array  $settings The field settings
	 * @return string
	 */
    public function asides( $field, $settings ) {
    	if ( ! is_user_logged_in() ) {
    		return '';
    	}

		$asides = describr_get_aside( $field );
        
        if ( isset( $asides['_cat'] ) ) {
        	$category = $asides['_cat'];
        	unset( $asides['_cat'] );
        }

		if ( ! $asides ) {
			return '';
		}
        
        $meta_key = isset( $category ) ? $category : $field;
        
        $name = esc_attr( $field ) . '_asides';
        
        $output = '';        
        
        if ( isset( $settings['label'] ) ) {
        	$field_label = $settings['label']  ;
        } elseif ( isset( $settings['title'] ) ) {
        	$field_label = $settings['title'];
        } else {
        	$field_label = '';
        }
        
        $audit_label = esc_attr(sprintf(
            	    	/*translators: %s Field label.*/
            	    	__( 'View %s Administration', 'describr' ), 
            	    	$field_label
            	    ));

        $audit_html = esc_html__( 'View Administration', 'describr' );

        if ( $field_label ) {
        	$field_label = esc_attr( $field_label ) . ' ';
        }

        foreach ( $asides as $aside => $data ) {
        	$has_cap = empty( $data['cap'] ) || current_user_can( $data['cap'] );
            
            $aside_output = '';

            $meta_key_ = $meta_key . '_' . $data['name'];

        	if ( ! empty( $data['editable'] ) ) {
            	$options = $this->options( "{$aside}_aside", $data );
                    
            	if ( ! empty( $options ) && is_array( $options ) ) {
            		$name_ = $name . '[' . esc_attr( $data['name'] ) . ']';
            		$label = '';
                    $title = '';
                    $disabled = '';
                    $required = ! empty( $data['required'] ) ? ' required="required" aria-required="true"' : '';

                    if ( ! $has_cap ) {
                    	$disabled = ' disabled="disabled"';
                    }
            		
            		if ( isset( $data['label'] ) ) {
            			$label = $data['label'];
            		}
                       
            		if ( $label ) {
            		    $label = esc_attr( $label );
            		    $title = ' title="' . $label . '"';
            		    $label = ' label="' . $field_label . $label . '"';
            		}
                        
                    $default = isset( $data['default'] ) ? $data['default'] : '';
            			
                    $value = $this->aside_value( $field, $meta_key_, $default, $data );
                        
                    $aside_output .= '<div class="describr-field-aside describr-field-aside-' . esc_attr( $aside ) . '"><select name="' .$name_ . '"' . $title . $label . $disabled . $required . '>';
            			
                    $is_numeric_arr = wp_is_numeric_array( $options );
            			
                    foreach ( $options as $option_value => $option_label ) {
                        if ( $is_numeric_arr ) {
            	            $option_value = $option_label;
                        }

                        $aside_output .= '<option value="' . esc_attr( $option_value ) . '"' . selected( $value, $option_value, false ) .'>' . esc_html( $option_label ) . '</option>';
                    }

            		$aside_output .= '</select>';
                        
                    if ( $disabled ) {
            			$aside_output .= '<input type="hidden" name="' . $name_ . '" value="' . esc_attr( $value ) . '" />';
            		}

                    $aside_output .= '</div>';
            	}
            } elseif ( $has_cap && 'audit' === $aside ) {
            	$value = $this->aside_value( $field, $meta_key_, array(), $data );
                
                if ( is_array( $value ) && $value ) {
                	$icon = isset( $data['icon'] ) ? $data['icon'] : '';
                    $aside_output .= '<div role="button" tabindex="0" title="' . esc_attr( $data['title'] ). '" aria-expanded="false" label="' . $audit_label . '" class="describr-field-aside describr-field-aside-audit describr-hide-if-no-js" data-field="' . esc_attr( $field ) . '">';
            	    $aside_output .= '<span aria-hidden="true" class="' . esc_attr( $icon ) . '"></span> <span class="describr-field-aside-audit-label" aria-hidden="true">' . $audit_html . '</span></div>';

            	    if ( isset( $settings['label'] ) ) {
            	    	$field_label = $settings['label'];
            	    } elseif ( isset( $settings['title'] ) ) {
            	    	$field_label = $settings['title'];
            	    } else {
            	    	$field_label = '';
            	    }
                    
            	    $this->audits[ $field ] = array(
            	    	'label' => $field_label,
            	    	'log'   => $value,
            	    );
                }
            }

            /**
             * Filters the field aside output
             * 
             * The dynamic part of the hook's name is the aside's name:
             * for example, "privacy"
             * 
             * @since 3.0
             * 
             * @param string $aside_output Aside output
             * @param string $field        Field
             * @param array  $data         Aside settings
             * @param array  $settings     Field settings
             */
            $output .= apply_filters( "describr_edit_field_aside_{$aside}", $aside_output, $field, $data, $settings );
        }
        
        /**
         * Filters the field asides output
         * 
         * @since 3.0
         * 
         * @param string $output   The field asides output
         * @param string $field    Field
         * @param array  $asides   Asides settings
         * @param array  $settings Field settings
         */
        return apply_filters( 'describr_edit_field_aside', $output, $field, $asides, $settings );
	}
    
    /**
	 * Retrieves a field's aside value
	 * 
	 * @since 3.0
	 *
	 * @param string $field    Field
	 * @param string $meta_key Aside meta key
	 * @param mixed  $default  Aside default value
	 * @param array  $settings Aside settings
	 * @return string The aside value
	 */
	public function aside_value( $field, $meta_key, $default, $settings ) {
		if ( isset( describr()->form()->asides[ $field ][ $meta_key ] ) ) {
			return describr()->form()->asides[ $field ][ $meta_key ];
		} elseif ( $this->is_editing ) {
			$value = describr_user( $meta_key );

			/**
			 * Filters the field's aside value
			 * 
			 * @since 3.0
			 * 
			 * @param mixed $value    The field value
			 * @param array $settings The field settings
			 */
			$value = apply_filters( "describr_edit_field_{$field}_aside_value", $value, $settings );

			/**
			 * Filters the edit field value by type
			 * 
			 * @since 3.0
			 * 
			 * @param mixed  $value    The field value
			 * @param string $field    The field
			 * @param array  $settings The field settings
			 */
			$value = apply_filters( "describr_edit_field_aside_{$meta_key}_value", $value, $field, $settings );
		} elseif ( isset( describr()->user()->cur_user->$meta_key ) ) {
			$value = describr()->user()->cur_user->$meta_key;

			/**
			 * Filters the field aside value
			 * 
			 * The dynamic part of the hook's name is the name of the field
			 * 
			 * @since 3.0
			 * 
			 * @param mixed $value    Field aside value
			 * @param array $settings Aside settings
			 */
			$value = apply_filters( "describr_field_{$field}_aside_value", $value, $settings );
			    
			/**
			 * Filters the field aside value
			 * 
			 * The dynamic part of the hook's name is the name of the field
			 * aside meta key
			 * 
			 * @since 3.0
			 * 
			 * @param mixed  $value    Field aside value
			 * @param string $field    The field
			 * @param array  $settings Aside settings
			 */
			$value = apply_filters( "describr_field_aside_{$meta_key}_value", $value, $field, $settings );
		}
        
        if ( ! isset( $value ) ) {
        	$value = '';

        	if ( ( 'register' === $this->mode && describr()->is_admin() ) || $this->is_editing ) {
        		/**
				 * Filters the field aside default value
				 * 
				 * @since 3.0
				 * 
				 * @param mixed  $default  Field aside default value
			     * @param string $field    Field
			     * @param array  $settings Aside settings
			     * @param string $meta_key Aside meta key 
				 */
				$default = apply_filters( 'describr_field_aside_default_value', $default, $field, $settings, $meta_key );

				/**
				 * Filters the field aside default value
				 * 
				 * The dynamic part of the hook's name is the name of the field
				 * 
				 * @since 3.0
				 * 
				 * @param mixed  $default  Field aside default value
			     * @param array  $settings Aside settings
			     * @param string $meta_key Aside meta keys 
				 */
				$default = apply_filters( "describr_field_{$field}_aside_default_value", $default, $settings, $meta_key );
				
				/**
				 * Filters the fields default value
				 * 
				 * The dynamic part of the hook's name is the name of the field
			     * aside meta key
				 * 
				 * @since 3.0
				 * 
				 * @param mixed  $default  Field aside default value
			     * @param string $field    Field
			     * @param array  $settings Field settings 
				 */
				$value = apply_filters( "describr_field_aside_{$meta_key}_default_value", $default, $field, $settings );

				/*This filter is documented in wp-content/plugins/describr/includes/class-fields.php*/
				$value = apply_filters( "describr_field_{$field}_aside_value", $value, $settings );

				/*This filter is documented in wp-content/plugins/describr/includes/class-fields.php*/
				$value = apply_filters( "describr_field_aside_{$meta_key}_value", $value, $field, $settings );
        	}
        }
        
        /**
		 * Filters the field aside value
		 * 
		 * @since 3.0
		 * 
		 * @param mixed  $value    Field value
		 * @param mixed  $default  Field aside default value
	     * @param string $field    Field
	     * @param string $meta_key Aside meta key
	     * @param array  $settings Aside settings 
		 */
        return apply_filters( 'describr_field_aside_value', $value, $default, $field, $meta_key, $settings );
	}

	/**
	 * Replaces "_" with "-" in field identifier
	 * 
	 * @since 3.0
	 *
	 * @param string $str Field identifier
	 * @return string
	 */
	public function beautify( $str ) {
		return str_replace( '_', '-', $str );
	}
}