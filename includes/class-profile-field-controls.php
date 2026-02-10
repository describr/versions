<?php
namespace describr;

/**
 * Profile_Field_Controls class
 * 
 * Creates the HTML for the Privacy, Availability, 
 * and More Options buttons for a field.
 *
 * @package Describr
 * @since 3.0
 */
class Profile_Field_Controls {
    /**
     * Stores the name of the field
     * 
     * @since 3.0
     * @var string
     */
    public $field;
    
    /**
     * Stores the field's label
     * 
     * @since 3.0
     * @var string
     */
    public $field_label;

    /**
     * Stores the name of the subfield
     * 
     * @since 3.0
     * @var string
     */
    public $sub_field;
    
    /**
     * Stores the fields's values
     * 
     * @since 3.0
     * @var array
     */
    public $field_values = array();
    
    /**
     * Stores the field's value
     * 
     * @since 3.0
     * @var string
     */
    public $field_val;
    
    /**
     * Whether the "which is" phrase should be added
     * to aria-label attributes
     * 
     * @since 3.0
     * @var bool
     */
    public $no_which_is;

    /**
     * The field's settings
     * 
     * @since 3.0
     * @var array
     */
    public $settings;

    /**
     * The field's asides
     * 
     * @since 3.0
     * @var array
     */
    public $asides;

    /**
     * The field's cached asides
     * 
     * @since 3.0
     * @var array
     */
    public $cache_asides = array();  

    /**
     * Adds field's controls
     * 
     * @since 3.0
     * 
     * @param string $field     Field
     * @param string $sub_field Subfield
     * @return string The field's controls
     */
    public function add( $field, $sub_field = '' ) {
    	$this->field = $field;
        $this->sub_field = $sub_field;

        $this->init();

        if ( isset( $this->settings['label'] ) ) {
            $this->field_label = $this->settings['label'];
        } elseif ( isset( $this->settings['title'] ) ) {
            $this->field_label = $this->settings['title'];
        } else {
            $this->field_label = '';
        }
        
        $output = '';

    	if ( $this->has_actions() ) {
            $this->field_val = trim( (string) $this->field_value(), '.' );
    		$this->get_saved_asides();
            $output .= $this->add_priv();
    		$output .= $this->add_status();
    		    
            $options = $this->add_options_btn();

    		if ( $output ) {
                $output_ = $output;

                $output = '<div class="describr-profile-field-states describr-flex-item';

                if ( $options ) {
                    $output .= ' options-exist';
                }
                
                $output .= '">';
                $output .= $output_;
    			$output .= '</div>';
    		}

            $output .= $options;
    	}
        
        $this->sub_field = '';

        return $output;
    }

    /**
     * Sets up the necessaries to add the field's controls
     * 
     * @since 3.0
     */
    public function init() {
    	$this->settings = describr_get_field( $this->field );
    	$this->asides = describr_get_aside( $this->field );
        $this->no_which_is = in_array( $this->field, array( 'lived_cities', 'work_history', 'high_school', 'college', 'relationship' ), true );
    }
    
    /**
     * Retrieves the field's value
     * 
     * @since 3.0
     * 
     * @return string The field's value
     */
    public function field_value() {
        if ( ! isset( $this->field_values[ $this->field ] ) ) {
            $this->field_values[ $this->field ] = describr_field_accessible_value( $this->field, $this->settings );
        }

        if ( $this->sub_field ) {
            if ( isset( $this->field_values[ $this->field ][ $this->sub_field ] ) ) {
                return $this->field_values[ $this->field ][ $this->sub_field ];
            }

            return '';
        }
        
        return $this->field_values[ $this->field ];
    }
        
    /**
     * Retrieves whether the field has status
     * 
     * @since 3.0
     * 
     * @return bool
     */
    public function has_status() {
    	return ! empty( $this->asides['status'] ) && describr_can_edit_profile();
    }
    
    /**
     * Retrieves whether the field has privacy
     * 
     * @since 3.0
     * 
     * @return bool
     */
    public function has_priv() {
    	return ! empty( $this->asides['privacy'] ) && describr_can_edit_profile();
    }

    /**
     * Retrieves whether the field has audit
     * 
     * @since 3.0
     * 
     * @return bool
     */
    public function has_audit() {
    	return ! empty( $this->asides['audit'] ) && ( empty( $this->asides['audit']['cap'] ) || current_user_can( $this->asides['audit']['cap'] ) );
    }

    /**
     * Retrieves whether the field is editable
     * 
     * @since 3.0
     * 
     * @return bool
     */        
    public function is_editable() {
        if ( 'user_nicename' === $this->field && describr_is_nicename_locked( describr_profile_id() ) ) {
            return false;
        } else {
            return describr_is_field_editable( $this->field ) && describr_can_edit_profile();
        }
    }
    
    /**
     * Retrieves whether the field has actions
     * 
     * @since 3.0
     * 
     * @return bool
     */
    public function has_actions() {
        return ! empty( $this->settings ) && ( $this->has_audit() || $this->has_priv() || $this->has_status() || $this->is_editable() );
    }
        
    /**
     * Retrieves the field's privacy mark-up
     * 
     * @since 3.0
     * 
     * @return string The field's privacy mark-up
     */
    public function add_priv() {
    	if ( ! $this->has_priv() ) {
    		return '';
    	}

    	$saved_priv = $this->get( 'priv' );
        
        if ( ! isset( $this->asides['privacy']['options'][ $saved_priv ] ) ) {
            return '';
        }
            
        $audience = $this->asides['privacy']['options'][ $saved_priv ];
    	
        if ( $this->no_which_is ) {
            $label = sprintf(
                        /*translators: 1: Field value. 2: Field audience. For example, "Public".*/ 
                        __( 'Edit audience for %1$s. Currently sharing with %2$s', 'describr' ),
                        $this->field_val, 
                       $audience 
                   );
        } else {
            $label = sprintf(
                        /*translators: 1: Field label. 2: Field value. 3: Field audience. For example, "Public".*/ 
                        __( 'Edit audience for %1$s, which is %2$s. Currently sharing with %3$s', 'describr' ),
                        $this->field_label,
                        $this->field_val, 
                       $audience 
                   );
        }  

    	$output = '<div role="button" tabindex="0" aria-expanded="false" data-state="privacy" aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $audience ) . '" class="describr-profile-field-state"><span class="describr-profile-field-state-icon" aria-hidden="true"><span class="' . esc_attr( $this->asides['privacy']['icons'][ $saved_priv ] ) . '"></span></span></div>';

        return $output;
    }
        
    /**
     * Retrieves the field's status mark-up
     * 
     * @since 3.0
     * 
     * @return string The field's status mark-up
     */
    public function add_status() {
        if ( ! $this->has_status() ) {
            return '';
        }

        $saved_status = $this->get( 'status' );
        
        if ( ! isset( $this->asides['status']['options'][ $saved_status ] ) ) {
            return '';
        }

        $title = $this->asides['status']['options'][ $saved_status ];
        
        $can_edit = $this->asides['status']['editable'];

        if ( $this->no_which_is ) {
            if ( $can_edit ) {
                /*translators: 1: Field value. 2: Field availability. For example, "Available".*/
                $text = __( 'Edit availability for %1$s. Currently %2$s', 'describr' );
            } else {
                /*translators: 1: Field value. 2: Field availability. For example, "Available".*/
                $text = __( '%1$s is currently %2$s', 'describr' );
            }
        
            $label = sprintf( $text, $this->field_val, $title );
        } else {
            if ( $can_edit ) {
                /*translators: 1: Field label. 2: Field value. 3: Field availability. For example, "Available".*/
                $text = __( 'Edit availability for %1$s, which is %2$s. Currently %3$s', 'describr' );
            } else {
                /*translators: 1: Field label. 2: Field value. 3: Field availability. For example, "Available".*/
                $text = __( '%1$s, which is %2$s, is currently %3$s', 'describr' );
            }
        
            $label = sprintf( $text, $this->field_label, $this->field_val, $title );
        }
 
        $output = '';

        if ( ! $can_edit ) {
            $output .= describr_a11y_text( $label, '' );
        }

        $output .= '<div class="describr-profile-field-state" title="' . esc_attr( $title ) . '" ';

        if ( $can_edit ) {
            $output .= 'role="button" tabindex="0" aria-expanded="false" aria-label="' . esc_attr( $label ) . '" data-state="status"';
            $ariaHidden = ' aria-hidden="true"';
        } else {
            $output .= 'aria-hidden="true"';
            $ariaHidden = '';
        }

        $output .= '><span class="describr-profile-field-state-icon"' . $ariaHidden . '><span class="' . esc_attr( $this->asides['status']['icon'] ) . ' describr-profile-field-status-' . esc_attr( $saved_status ) . '"></span></span></div>';

        return $output;
    }
        
    /**
     * Retrieves the field's options button mark-up
     * 
     * @since 3.0
     * 
     * @return string The field's options button mark-up
     */        
    public function add_options_btn() {        
        $options = array();

        if ( $this->has_audit() && $this->get( 'audit' ) ) {
            $options[] = 'audit';
        }
        
        if ( $this->is_editable() ) {
            $options[] = 'edit';
        }   
        
        if ( empty( $this->settings['required'] ) ) {
            $options[] = 'delete';
        }

        if ( ! $options ) {
            return '';
        }
        
        if ( $this->no_which_is ) {
            $label = sprintf(
                        /*translators: %: Field value.*/ 
                        __( 'More options for %s', 'describr' ),
                        $this->field_val
                    );
        } else {
            $label = sprintf(
                        /*translators: 1: Field label. 2: Field value.*/
                        __( 'More options for %1$s, which is %2$s', 'describr', 'describr' ),
                        $this->field_label,
                        $this->field_val 
                    );
        }

        $output = '<div role="button" tabindex="0" aria-expanded="false" aria-label="' . esc_attr( $label ) . '" title="' . esc_attr_x( 'More Options', 'profile field', 'describr' ) . '" data-options="' . esc_attr( implode( ',', $options ) ) . '" data-action="showoptions" class="describr-profile-field-options"><i aria-hidden="true" class="describr-vertical-ellipsis"></i></div>';

        return $output;
    }

    /**
     * Retrieves a saved aside's value
     * 
     * @since 3.0
     * 
     * @param string $data The index to locate the aside's value
     * @return mixed The saved aside's value or false
     */
    public function get( $data ) {
        switch ( $data ) {
            case 'status':
                $val = isset( $this->cache_asides[ $this->field ]['status'] ) ? $this->cache_asides[ $this->field ]['status'] : 'yes';
                break;
            case 'priv':
                $val = isset( $this->cache_asides[ $this->field ]['privacy'] ) ? $this->cache_asides[ $this->field ]['privacy'] : 'public';
                break;
            case 'audit':
                $val = isset( $this->cache_asides[ $this->field ]['audit'] ) ? $this->cache_asides[ $this->field ]['audit'] : false;
                break;
            default:
                $val = false;
                break;
        }
        
        return $val;
    }
        
    /**
     * Retrieves a field's saved asides' values
     * 
     * @since 3.0
     * 
     * @param bool $field The field whose asides are to be retrieved
     * @return array The field's saved asides' values
     */
    public function get_saved_asides( $field = false ) {
    	if ( ! $field ) {
    		$field = $this->field;
    	}
            
        if ( isset( $this->cache_asides[ $field ] ) ) {
            return $this->cache_asides[ $field ];
        }

        $output = array();
            
        //Bail if no field is set
        if ( ! $field ) {
            return $output;
        }

    	$asides = describr_get_aside( $field );
                
        if ( $asides ) {
            $meta_key_ = isset( $asides['_cat'] ) ? $asides['_cat'] : $field;

            unset($asides['_cat']);
            
            $has_audit = $this->has_audit();        
            $has_priv = $this->has_priv();
            
            foreach ( $asides as $aside => $settings_ ) {
                if ( isset( $settings_['name'] ) ) {
                    $meta_key = "{$meta_key_}_{$settings_['name']}";

                    if ( 'audit' === $aside && ! $has_audit ) {
                        continue;
                    }

                    if ( 'privacy' === $aside && ! $has_priv ) {
                        continue;
                    }
                    
                    /**
                     * Filters the value for the aside
                     * 
                     * @since 3.0
                     * 
                     * @param mixed  $val   The aside's value
                     * @param string $aside The aside
                     * @param string $field The field
                     */
                    $val = apply_filters( 'describr_profile_field_aside', describr_user( $meta_key ), $aside, $field );

                    /**
                     * Filters the value for the aside
                     * 
                     * The dynamic part of the filter's name is the aside
                     * 
                     * @since 3.0
                     * 
                     * @param mixed  $val   The aside's value
                     * @param string $field The field
                     */
                    $val = apply_filters( "describr_profile_field_aside_{$aside}", $val, $field );
                }               

                $val = map_deep( $val, 'trim' );   

                if ( $val ) {
                    if ( 'audit' === $aside ) {
                        if ( ! is_array( $val ) ) {
                            continue;
                        }
                        
                        usort( $val, function( $a, $b ) {
                            return (int) $b['time'] - (int) $a['time'];
                        });
                        
                        if ( ! isset( $timezone ) ) {
                            $timezone = wp_get_current_user()->timezone;
                        }

                        $val = array_map( function ( $log ) use( $timezone ) {
                            if ( ! isset( $log['user_id'], $log['time'] ) ) {
                                return array();
                            }

                            $user = get_userdata( $log['user_id'] );

                            if ( ! isset( $user->display_name ) ) {
                                $user->display_name = _x( 'Anonymous', 'user', 'describr' );
                            }
                                
                            $date = describr_date( describr_locale_date_format(), (int) $log['time'], $timezone );
                                    
                            if ( empty( $date ) ) {
                                $date = '<span aria-hidden="true">&mdash;</span><span class="describr-a11y-text">';
                                /*translators: Hidden accessibility text.*/
                                $date .= _x( 'Unknown', 'date', 'describr' );
                                $date .= '</span>';
                            }
                                    
                            return array(
                                'displayName' => $user->display_name,
                                'date'        => $date,
                            );
                        }, $val );

                        $val = array_filter( $val );

                        if ( ! $val ) {
                            continue;
                        }
                    }

                    $output[ $aside ] = $val;
                }
            }
        }

        $this->cache_asides[ $field ] = $output;

        return $this->cache_asides[ $field ];
    }
}