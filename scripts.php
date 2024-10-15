<?php
function gf_daf_js_set_default_values() {
	?>
	case "daf" :
        if (!field.label)
        field.label = <?php echo json_encode( esc_html__( 'DAF Card', 'gravityforms' ) ); ?>;
        var ccNumber, ccExpirationMonth, ccExpirationYear, ccSecruityCode, ccCardType, ccName;

        dafNumber = new Input(field.id + ".1", <?php echo json_encode( gf_apply_filters( array( 'gform_daf_card_number', rgget( 'id' ) ), esc_html__( 'Card Number', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
        dafExpirationMonth = new Input(field.id + ".2_month", <?php echo json_encode( gf_apply_filters( array( 'gform_daf_card_expiration', rgget( 'id' ) ), esc_html__( 'Expiration Month', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
        dafExpirationMonth.defaultLabel = <?php echo json_encode( esc_html__( 'Expiration Date', 'gravityforms' ) ); ?>;
        dafExpirationYear = new Input(field.id + ".2_year", <?php echo json_encode( gf_apply_filters( array( 'gform_daf_card_expiration', rgget( 'id' ) ), esc_html__( 'Expiration Year', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
        dafSecruityCode = new Input(field.id + ".3", <?php echo json_encode( gf_apply_filters( array( 'gform_daf_card_security_code', rgget( 'id' ) ), esc_html__( 'Security Code', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
        dafCardType = new Input(field.id + ".4", <?php echo json_encode( gf_apply_filters( array( 'gform_daf_card_type', rgget( 'id' ) ), __( 'Card Type', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
        dafName = new Input(field.id + ".5", <?php echo json_encode( gf_apply_filters( array( 'gform_daf_card_name', rgget( 'id' ) ), esc_html__( 'Cardholder Name', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
        field.inputs = [dafNumber, dafExpirationMonth, dafExpirationYear, dafSecruityCode, dafCardType, dafName];
        break;
	<?php
}
add_action( 'gform_editor_js_set_default_values', 'gf_daf_js_set_default_values' );

function gf_daf_add_submission_data( $submission_data, $feed, $form, $entry ) {
	$fields = GFAPI::get_fields_by_type( $form, array( 'daf' ) );
	if( $fields ) {
		$daf_field = $fields[0];
		$submission_data['daf_card_number']          = str_replace( array( "\t", "\n", "\r", ' ' ), '', rgpost( "input_{$daf_field->id}_1" ) );
		$submission_data['daf_card_expiration_date'] = rgpost( "input_{$daf_field->id}_2" );
		$submission_data['daf_card_security_code']   = rgpost( "input_{$daf_field->id}_3" );
		$submission_data['daf_card_name']            = rgpost( "input_{$daf_field->id}_5" );
	}
	return $submission_data;
}
add_action( 'gform_submission_data_pre_process_payment', 'gf_daf_add_submission_data', 10, 4 );

function gf_daf_field_class( $css_class, $field, $form ) {
	if( GFFormsModel::get_input_type( $field ) == 'daf' && ! GFCommon::is_ssl() ) {
		$css_class .= ' gfield_creditcard_warning';
	}
	return $css_class;
}
add_filter( 'gform_field_css_class', 'gf_daf_field_class', 10, 3 );