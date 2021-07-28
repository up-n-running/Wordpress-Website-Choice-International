<?php

//CONFIGURE FORMS ON INIT
function edit_tml_form_labels() {
    
    //LOGIN FORM
    $login_email_field = tml_get_form_field( 'login', 'log' );
    if( $login_email_field ) {
        $login_email_field->set_label( 'Email' );
    }
    
    $lostpass_email_field = tml_get_form_field( 'lostpassword', 'user_login' );
    if( $lostpass_email_field ) {
        $lostpass_email_field->set_label( 'Email' );
    }
    
    //LOGIN FORM: If no redirect hidden param set then it defailts to wp-admin which is not branded for users
    //so change the hidden to 'manage my bookings' front end page instead.
    $login_redirect_hidden = tml_get_form_field( 'login', 'redirect_to' );
    if( $login_redirect_hidden ) 
    {
        $redirect_url = $login_redirect_hidden->get_value();
        if( substr($redirect_url, -strlen("/wp-admin/")) === "/wp-admin/" ) {
            $redirect_url = substr( $redirect_url, 0, strlen($redirect_url)-strlen("/wp-admin/") ) . "/classes/my-bookings";
        }
        $login_redirect_hidden->set_value( $redirect_url );
    }
    
    //REGISTRATION FORM If no redirect hidden param set then it defailts to wp-admin which is not branded for users
    //so change the hidden to 'manage my bookings' frone tned page instead.
    /* DOESNT WORK BECAUSE IM USING AUTO LOGIN 
    $reg_redirect_hidden = tml_get_form_field( 'register', 'redirect_to' );
    if( $reg_redirect_hidden ) {
        $redirect_url = $reg_redirect_hidden->get_value();
        if( isset( $redirect_url ) && $redirect_url === '' ) {
            $redirect_url = get_site_url(null, "/classes/my-bookings");
        }
        $reg_redirect_hidden->set_value( $redirect_url );
    }
     * */
}
add_action( 'init', 'edit_tml_form_labels' );


//REGISTRATION FORM

/* ADD GPDR CHECKBOX */
function add_gdpr_checkbox_to_tml_register_form() {
    tml_add_form_field( 'register', 'first_name', array(
        'type' => 'text',
        'value' => tml_get_request_value( 'first_name', 'post' ),
        'label' => 'First Name',
        'description' => 'Enter your first name.',
        'priority' => 3,
    ) );   
    
    tml_add_form_field( 'register', 'last_name', array(
        'type' => 'text',
        'value' => tml_get_request_value( 'last_name', 'post' ),
        'label' => 'Surname',
        'description' => 'Enter your surname.',
        'priority' => 4,
    ) );   
    
    tml_add_form_field( 'register', 'dbem_phone', array(
        'type' => 'text',
        'value' => tml_get_request_value( 'dbem_phone', 'post' ),
        'label' => 'Phone',
        'description' => 'Enter your telephone or mobile number.',
        'priority' => 16,
    ) );   
    
    tml_add_form_field( 'register', 'dbem_country', array(
        'type' => 'dropdown',
        'value' => tml_get_request_value( 'dbem_country', 'post' ),
        'label' => 'Country',
        'description' => 'Enter your country of residence.',
        'options' => array(
            '' => 'Select country of residence...',
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BR' => 'Brazil',
            'VG' => 'British Virgin Islands',
            'BN' => 'Brunei',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'CI' => 'C&ocirc;te D\'Ivoire',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CR' => 'Costa Rica',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'KP' => 'Democratic People\'s Republic of Korea',
            'CD' => 'Democratic Republic of the Congo',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'XE' => 'England',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'PF' => 'French Polynesia',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GW' => 'Guinea Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KV' => 'Kosovo',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Laos',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MR' => 'Mauritania',
            'MQ' => 'Mauritania',
            'MU' => 'Mauritius',
            'MX' => 'Mexico',
            'FM' => 'Micronesia',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar(Burma)',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'AN' => 'Netherlands Antilles',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'XI' => 'Northern Ireland',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestine',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'CG' => 'Republic of the Congo',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'RW' => 'Rwanda',
            'RN' => 'Réunion',
            'ST' => 'S&agrave;o Tom&eacute; And Pr&iacute;ncipe',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'SA' => 'Saudi Arabia',
            'XS' => 'Scotland',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'KR' => 'South Korea',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syria',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TV' => 'Tuvalu',
            'VI' => 'US Virgin Islands',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VA' => 'Vatican',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'XW' => 'Wales',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe'
        ),
        'priority' => 17,
    ) ); 

    tml_add_form_field( 'register', 'accept_terms', array(
		'type' => 'checkbox',
		'label' => 'Yes, you can store my data as per the <a href="' . get_privacy_policy_url() . '" target="_blank">Privacy Policy</a>',
		'value' => '1',
		'checked' => tml_get_request_value( 'accept_terms', 'post' ),
		'priority' => 29,
	) );

    if( defined(T17UNR_CHECKBOX_NEWSLETTER) && T17UNR_CHECKBOX_NEWSLETTER )
    {
        tml_add_form_field( 'register', 'newsletter_signup', array(
            'type' => 'checkbox',
            'label' => 'Keep me posted occasionally about upcoming classes and news (optional)',
            'value' => '1',
            'checked' => tml_get_request_value( 'newsletter_signup', 'post' ),
            'priority' => 30,
        ) );
    }
}
add_action( 'init', 'add_gdpr_checkbox_to_tml_register_form' );


/*Validate GPDR Checkbox */
function validate_registration_form_extra_fields( $errors ) {

    if ( ! tml_get_request_value( 'first_name', 'post' ) ) {
		$errors->add( 'first_name', 'Your first name is required.' );
	}
    if ( ! tml_get_request_value( 'last_name', 'post' ) ) {
		$errors->add( 'last_name', 'Your surname is required.' );
	}
    if ( ! tml_get_request_value( 'dbem_phone', 'post' ) ) {
		$errors->add( 'last_name', 'Your phone number is required.' );
	}
        if ( ! tml_get_request_value( 'dbem_country', 'post' ) ) {
		$errors->add( 'last_name', 'Your country of residence is required.' );
	}
    if ( tml_get_request_value( 'user_pass1', 'post' ) && strlen( tml_get_request_value( 'user_pass1', 'post' ) ) < 6 ) {
		$errors->add( 'user_pass1', 'Your password must be at least 6 characters long' );
	}
    if ( ! tml_get_request_value( 'accept_terms', 'post' ) ) {
		$errors->add( 'accept_terms', 'You must consent to us storing your data in accordance with our Privacy Policy.' );
	}

	return $errors;
}
add_filter( 'registration_errors', 'validate_registration_form_extra_fields' );


/*Save GPDR Checkbox */
function save_extra_fields_on_registration( $user_id ) {
    
	if ( !empty( tml_get_request_value('first_name') ) ) {
		update_user_meta( $user_id, 'first_name', tml_get_request_value('first_name') );
        //nickname and display as name are set in themes set_default_display_name function in functions.php
    }
    if ( !empty( tml_get_request_value('last_name') ) ) {
		update_user_meta( $user_id, 'last_name', tml_get_request_value('last_name') );  
    }
    if ( !empty( tml_get_request_value('dbem_phone') ) ) {
		update_user_meta( $user_id, 'dbem_phone', tml_get_request_value('dbem_phone') );  
    }
    if ( !empty( tml_get_request_value('dbem_country') ) ) {
		update_user_meta( $user_id, 'dbem_country', tml_get_request_value('dbem_country') );  
    }    
    
	if ( tml_get_request_value( 'accept_terms', 'post' ) ) {
		update_user_meta( $user_id, 'em_data_privacy_consent', current_time('mysql') );
	}

    if( defined(T17UNR_CHECKBOX_NEWSLETTER) && T17UNR_CHECKBOX_NEWSLETTER )
    {
        if ( tml_get_request_value( 'newsletter_signup', 'post' ) ) {
            update_user_meta( $user_id, 'newsletter_signedup', 1 );
        }
        else {
            update_user_meta( $user_id, 'newsletter_signedup', 0 );
        }
    }
}
add_action( 'user_register', 'save_extra_fields_on_registration' );



