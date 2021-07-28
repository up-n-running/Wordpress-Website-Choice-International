<?php
class UnrAdminSettingsUtils {

    /*
	URL: https://www.smashingmagazine.com/2016/04/three-approaches-to-adding-configurable-fields-to-your-plugin/
    Github: https://github.com/rayman813/smashing-custom-fields/blob/master/smashing-fields-approach-1/smashing-fields.php
	Description: Setting up custom fields for our plugin.
	Author: Matthew Ray
	Version: 1.0.0
    */
    
    /* This is how we simulate a static class in php like Java and C static classes */
    private function __construct() {}
    
    public static function render_field_callback( $arguments ) {

        $value = get_option( $arguments['uid'] );

        if( ! $value ) {
            $value = $arguments['default'];
        }
        
        //sanitize values to stop html in values causing rendering issues
        if( is_array($value) ) {
            $sanitised_key_value_pairs = array();
            array_walk($value, function(&$val, &$key) {
                $sanitised_key_value_pairs[esc_attr($key)] = esc_attr($val);
            });
            $value = $sanitised_key_value_pairs;
        }
        else {
            $value = esc_attr( $value );
        }
        
        $taginject = isset( $arguments['taginject'] ) ? ' '.$arguments['taginject'] : '';

        switch( $arguments['type'] ){
            case 'text':
            case 'password':
            case 'number':
                printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s"%5$s />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value, $taginject );
                break;
            case 'textarea':
                printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50"%3$s>%4$s</textarea>', $arguments['uid'], $arguments['placeholder'], $taginject, $value );
                break;
            case 'select':
            case 'multiselect':
                if( is_array( $arguments['options'] ) ){
                    if( isset($value) && !is_array($value) ) {
                        $value = [$value];
                    }
                    $attributes = '';
                    $elemNamePostfix='';
                    $options_markup = '';
                    foreach( $arguments['options'] as $key => $label ){
                        $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value[ array_search( $key, $value, true ) ], $key, false ), $label );
                    }
                    if( $arguments['type'] === 'multiselect' ){
                        $attributes = ' multiple="multiple" ';
                        $elemNamePostfix = '[]';
                    }
                    printf( '<select name="%1$s%2$s" id="%1$s" %3$s %4$s>%5$s</select>', $arguments['uid'], $elemNamePostfix ,$attributes, $taginject, $options_markup );
                }
                break;
            case 'radio':
            case 'checkbox':
                if( ! empty ( $arguments['options'] ) ){
                    if( !is_array( $arguments['options'] ) ) {
                        $arguments['options'] = [$arguments['options']=>$arguments['options']];
                    }
                    $options_markup = '';
                    $iterator = 0;
                    foreach( $arguments['options'] as $key => $label ){
                        $iterator++;
                        $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s%5$s /> %6$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked( $value[ array_search( $key, $value, true ) ], $key, false ), $label, $taginject, $iterator );
                    }
                    printf( '<fieldset>%s</fieldset>', $options_markup );
                }
                break;
        }

        if( $helper = $arguments['helper'] ){
            printf( '<span class="helper"> %s</span>', $helper );
        }

        if( $supplimental = $arguments['supplimental'] ){
            printf( '<p class="description">%s</p>', $supplimental );
        }
        print( "\r\n" );
    }
}