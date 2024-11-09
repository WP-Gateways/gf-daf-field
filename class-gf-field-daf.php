<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GF_Field_DAF extends GF_Field_CreditCard {

	public $type = 'daf';

    public function __construct( $data = array() ) {
        parent::__construct( $data );
        $this->_has_settings_renderer  = version_compare( GFCommon::$version, '2.5-beta', '>=' );
    }

	public function get_form_editor_field_title() {
		return esc_attr__( 'DAF Card', 'gf-daf-field' );
	}

    /**
     * Returns the field's form editor description.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_form_editor_field_description() {
        return esc_attr__( 'Allows users to enter Donor-Advised Fund (DAF) Card information.', 'gf-daf-field' );
    }

    /**
     * Returns the field's form editor icon.
     *
     * This could be an icon url or a gform-icon class.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_form_editor_field_icon() {
        return GF_DAF_PLUGIN_URL . '/images/icon.png';
    }

	public function get_form_editor_button() {
		return array(
			'group' => 'pricing_fields',
			'text'  => $this->get_form_editor_field_title()
		);
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'force_ssl_field_setting',
			'credit_card_style_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'sub_labels_setting',
			'sub_label_placement_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'description_setting',
			'css_class_setting',
			//'credit_card_setting',
			'input_placeholders_setting',
		);
	}

	public function validate( $value, $form ) {
		$card_number     = rgpost( 'input_' . $this->id . '_1' );
		$expiration_date = rgpost( 'input_' . $this->id . '_2' );
		$security_code   = rgpost( 'input_' . $this->id . '_3' );

		if ( $this->isRequired && ( empty( $card_number ) || ( apply_filters( 'gf_daf_cvv_required', false, $form ) && empty( $security_code ) ) || empty( $expiration_date[0] ) || empty( $expiration_date[1] ) ) ) {
			$this->failed_validation  = true;
			$this->validation_message = empty( $this->errorMessage ) ? esc_html__( 'Please enter your DAF card information.', 'gf-daf-field' ) : $this->errorMessage;
		} elseif ( ! empty( $card_number ) ) {

			if ( empty( $security_code ) && ( apply_filters( 'gf_daf_cvv_required', false, $form ) ) ) {
				$this->failed_validation  = true;
				$this->validation_message = esc_html__( "Please enter your card's security code.", 'gf-daf-field' );
			} elseif ( ! self::is_valid_card_checksum( $card_number ) ) {
				$this->failed_validation  = true;
				$this->validation_message = esc_html__( 'Invalid DAF card number.', 'gf-daf-field' );
			}
		}
	}

	private static function is_valid_card_checksum( $number ) {
		$checksum   = 0;
		$num        = 0;
		$multiplier = 1;

		// Process each character starting at the right
		for ( $i = strlen( $number ) - 1; $i >= 0; $i -- ) {

			//Multiply current digit by multiplier (1 or 2)
			$num = $number[ $i ] * $multiplier;

			// If the result is in greater than 9, add 1 to the checksum total
			if ( $num >= 10 ) {
				$checksum ++;
				$num -= 10;
			}

			//Update checksum
			$checksum += $num;

			//Update multiplier
			$multiplier = $multiplier == 1 ? 2 : 1;
		}

		return $checksum % 10 == 0;

	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$form_id  = ( $is_entry_detail || $is_form_editor ) && empty( $form_id ) ? rgget( 'id' ) : $form_id;

		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix  = $is_entry_detail ? '_admin' : '';

		$form_sub_label_placement = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$is_sub_label_above       = $field_sub_label_placement == 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement == 'above' );
		$sub_label_class          = $field_sub_label_placement == 'hidden_label' ? "hidden_sub_label screen-reader-text" : '';

		$card_number      = '';
		$card_name        = '';
		$expiration_month = '';
		$expiration_year  = '';
		$security_code    = '';
		$autocomplete     = "autocomplete='off'";

		if ( is_array( $value ) ) {
			$card_number     = esc_attr( rgget( $this->id . '.1', $value ) );
			$card_name       = esc_attr( rgget( $this->id . '.5', $value ) );
			$expiration_date = rgget( $this->id . '.2', $value );
			if ( ! is_array( $expiration_date ) && ! empty( $expiration_date ) ) {
				$expiration_date = explode( '/', $expiration_date );
			}

			if ( is_array( $expiration_date ) && count( $expiration_date ) == 2 ) {
				$expiration_month = $expiration_date[0];
				$expiration_year  = $expiration_date[1];
			}

			$security_code = esc_attr( rgget( $this->id . '.3', $value ) );
		}

		$onchange = "onchange=''";
		$onkeyup  = "onkeyup=''";

		// Aria attributes.
		$number_aria_attributes = $this->get_aria_attributes( $value, '1' );
		$expiration_month_aria_attributes = $this->get_aria_attributes( $value, '2_month' );
		$expiration_year_aria_attributes = $this->get_aria_attributes( $value, '2_year' );
		$security_aria_attributes = $this->get_aria_attributes( $value, '3' );
		$name_aria_attributes = $this->get_aria_attributes( $value, '5' );

		//card number fields
		$tabindex                = $this->get_tabindex();
		$card_number_field_input = GFFormsModel::get_input( $this, $this->id . '.1' );
		$validation_helper       = ! is_admin() ? "pattern='[0-9]*' title='" . esc_attr__( 'Only digits are allowed', 'gravityforms' ) . "'" : '';
		$card_number_label       = rgar( $card_number_field_input, 'customLabel' ) != '' ? $card_number_field_input['customLabel'] : esc_html__( 'Card Number', 'gravityforms' );
		$card_number_label       = gf_apply_filters( array( 'gform_card_number', $form_id ), $card_number_label, $form_id );

		$card_number_placeholder = $this->get_input_placeholder_attribute( $card_number_field_input );
		if ( $is_sub_label_above ) {
			$card_field = "<span class='ginput_full{$class_suffix} gform-grid-col' id='{$field_id}_1_container' >
                                    <label for='{$field_id}_1' id='{$field_id}_1_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$card_number_label}</label>
                                    <input type='text' name='input_{$id}.1' id='{$field_id}_1' value='{$card_number}' {$tabindex} {$disabled_text} {$onchange} {$onkeyup} {$autocomplete} {$validation_helper} {$card_number_placeholder} {$number_aria_attributes}/>
                                 </span>";
		} else {
			$card_field = "<span class='ginput_full{$class_suffix} gform-grid-col' id='{$field_id}_1_container' >
                                    <input type='text' name='input_{$id}.1' id='{$field_id}_1' value='{$card_number}' {$tabindex} {$disabled_text} {$onchange} {$onkeyup} {$autocomplete} {$validation_helper} {$card_number_placeholder} {$number_aria_attributes}/>
                                    <label for='{$field_id}_1' id='{$field_id}_1_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$card_number_label}</label>
                                 </span>";
		}

		// Expiration Date Inputs
		$expiration_wrapper_tag       = $this->get_field_container_tag( $form ) === 'fieldset' ? 'fieldset' : 'span';
		$expiration_label_tag         = $expiration_wrapper_tag === 'fieldset' ? 'legend' : 'label';
		$expiration_label_for         = $expiration_label_tag === 'label' ? " for='{$field_id}_2_month'" : '';
		$expiration_month_input       = GFFormsModel::get_input( $this, $this->id . '.2_month' );

		$expiration_label             = rgar( $expiration_month_input, 'customLabel' ) != '' ? esc_html( $expiration_month_input['customLabel'] ) : esc_html__( 'Expiration Date', 'gravityforms' );
		$expiration_label             = gf_apply_filters( array( 'gform_card_expiration', $form_id ), $expiration_label, $form_id );

		// Expiration Date: Month
		$expiration_month_label       = $expiration_wrapper_tag === 'fieldset' ? "<label for='{$field_id}_2_month' class='gform-field-label gform-field-label--type-sub screen-reader-text'>" . esc_html__( 'Month', 'gravityforms' ) . "</label>" : '';
		$expiration_month_tab_index   = $this->get_tabindex();
		$expiration_month_placeholder = $this->get_input_placeholder_value( $expiration_month_input );
		$expiration_months            = $this->get_expiration_months( $expiration_month, $expiration_month_placeholder );

		// Expiration Date: Year
		$expiration_year_input        = GFFormsModel::get_input( $this, $this->id . '.2_year' );
		$expiration_year_tab_index    = $this->get_tabindex();
		$expiration_year_placeholder  = $this->get_input_placeholder_value( $expiration_year_input );
		$expiration_years             = $this->get_expiration_years( $expiration_year, $expiration_year_placeholder );

		if ( $expiration_wrapper_tag === 'fieldset' ) {
			$expiration_year_label_for = $is_form_editor ? '' : $field_id . '_2_year';
			$expiration_year_label     = "<label for='{$expiration_year_label_for}' class='gform-field-label gform-field-label--type-sub screen-reader-text'>" . esc_html__( 'Year', 'gravityforms' ) . "</label>";
		} else {
			$expiration_year_label = '';
		}

		// legend tag should be the first child of fieldset, so we are putting out this markup for fieldset
		// even if the sub-label is below or hidden.
		if ( $expiration_wrapper_tag === 'fieldset' || $is_sub_label_above ) {
			$expiration_field = "<span class='ginput_full{$class_suffix} ginput_cardextras gform-grid-col gform-grid-row' id='{$field_id}_2_container'>
                                            <{$expiration_wrapper_tag} class='ginput_cardinfo_left{$class_suffix} gform-grid-col' id='{$field_id}_2_cardinfo_left'>
                                            <{$expiration_label_tag}{$expiration_label_for} class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$expiration_label}</{$expiration_label_tag}>
                                                <span class='ginput_card_expiration_container ginput_card_field gform-grid-row'>
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "<span class='ginput_card_expiration_month_container gform-grid-col'>" : "" ) . "
                                                       {$expiration_month_label}
                                                       <select name='input_{$id}.2[]' id='{$field_id}_2_month' {$expiration_month_tab_index} {$disabled_text} class='ginput_card_expiration ginput_card_expiration_month' {$expiration_month_aria_attributes}>
                                                           {$expiration_months}
                                                       </select>
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "</span>" : "" ) . "
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "<span class='ginput_card_expiration_year_container gform-grid-col'>" : "" ) . "
                                                       {$expiration_year_label}
                                                       <select name='input_{$id}.2[]' id='{$field_id}_2_year' {$expiration_year_tab_index} {$disabled_text} class='ginput_card_expiration ginput_card_expiration_year' {$expiration_year_aria_attributes}>
                                                           {$expiration_years}
                                                       </select>
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "</span>" : "" ) . "
                                                </span>
                                            </{$expiration_wrapper_tag}>";

		} else {
			$expiration_field = "<span class='ginput_full{$class_suffix} ginput_cardextras gform-grid-col gform-grid-row' id='{$field_id}_2_container'>
                                            <{$expiration_wrapper_tag} class='ginput_cardinfo_left{$class_suffix}' id='{$field_id}_2_cardinfo_left'>
                                                <span class='ginput_card_expiration_container ginput_card_field'>
                                                    " . ( $expiration_wrapper_tag === 'fieldset' ? "<span class='ginput_card_expiration_month_container'>" : "" ) . "
                                                       <select name='input_{$id}.2[]' id='{$field_id}_2_month' {$expiration_month_tab_index} {$disabled_text} class='ginput_card_expiration ginput_card_expiration_month' {$expiration_month_aria_attributes}>
                                                           {$expiration_months}
                                                       </select>
                                                       {$expiration_month_label}
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "</span>" : "" ) . "
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "<span class='ginput_card_expiration_year_container'>" : "" ) . "
                                                       <select name='input_{$id}.2[]' id='{$field_id}_2_year' {$expiration_year_tab_index} {$disabled_text} class='ginput_card_expiration ginput_card_expiration_year' {$expiration_year_aria_attributes}>
                                                           {$expiration_years}
                                                       </select>
                                                       {$expiration_year_label}
                                                       " . ( $expiration_wrapper_tag === 'fieldset' ? "</span>" : "" ) . "
                                                </span>
                                                 <{$expiration_label_tag}{$expiration_label_for} class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$expiration_label}</{$expiration_label_tag}>
                                            </{$expiration_wrapper_tag}>";
		}
		//security code field
		$tabindex                  = $this->get_tabindex();
		$security_code_field_input = GFFormsModel::get_input( $this, $this->id . '.3' );
		$security_code_label       = rgar( $security_code_field_input, 'customLabel' ) != '' ? $security_code_field_input['customLabel'] : esc_html__( 'Security Code', 'gravityforms' );
		$security_code_label       = gf_apply_filters( array( 'gform_card_security_code', $form_id ), $security_code_label, $form_id );
		$validation_helper         = "pattern='[0-9]*' title='" . esc_attr__( 'Only digits are allowed', 'gravityforms' ) . "'";
		$security_code_placeholder = $this->get_input_placeholder_attribute( $security_code_field_input );
		if ( $is_sub_label_above ) {
			$security_field = "<span class='ginput_cardinfo_right{$class_suffix} gform-grid-col' id='{$field_id}_2_cardinfo_right'>
                                                <label for='{$field_id}_3' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>$security_code_label</label>
                                                <input type='text' name='input_{$id}.3' id='{$field_id}_3' {$tabindex} {$disabled_text} class='ginput_card_security_code' value='{$security_code}' {$autocomplete} {$validation_helper} {$security_code_placeholder} {$security_aria_attributes}/>
                                                <span class='ginput_card_security_code_icon'>&nbsp;</span>
                                             </span>
                                        </span>";
		} else {
			$security_field = "<span class='ginput_cardinfo_right{$class_suffix} gform-grid-col' id='{$field_id}_2_cardinfo_right'>
                                                <input type='text' name='input_{$id}.3' id='{$field_id}_3' {$tabindex} {$disabled_text} class='ginput_card_security_code' value='{$security_code}' {$autocomplete} {$validation_helper} {$security_code_placeholder} {$security_aria_attributes}/>
                                                <span class='ginput_card_security_code_icon'>&nbsp;</span>
                                                <label for='{$field_id}_3' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>$security_code_label</label>
                                             </span>
                                        </span>";
		}

		$tabindex              = $this->get_tabindex();
		$card_name_field_input = GFFormsModel::get_input( $this, $this->id . '.5' );
		$card_name_label       = rgar( $card_name_field_input, 'customLabel' ) != '' ? $card_name_field_input['customLabel'] : esc_html__( 'Cardholder Name', 'gravityforms' );
		$card_name_label       = gf_apply_filters( array( 'gform_card_name', $form_id ), $card_name_label, $form_id );

		$card_name_placeholder = $this->get_input_placeholder_attribute( $card_name_field_input );
		if ( $is_sub_label_above ) {
			$card_name_field = "<span class='ginput_full{$class_suffix} gform-grid-col' id='{$field_id}_5_container'>
                                            <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$card_name_label}</label>
                                            <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$card_name}' {$tabindex} {$disabled_text} {$card_name_placeholder} {$name_aria_attributes}/>
                                        </span>";
		} else {
			$card_name_field = "<span class='ginput_full{$class_suffix} gform-grid-col' id='{$field_id}_5_container'>
                                            <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$card_name}' {$tabindex} {$disabled_text} {$card_name_placeholder} {$name_aria_attributes}/>
                                            <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$card_name_label}</label>
                                        </span>";
		}

		return "<div class='ginput_complex{$class_suffix} ginput_container ginput_container_creditcard gform-grid-row' id='{$field_id}'>" . $card_field . $expiration_field . $security_field . $card_name_field . ' </div>';

	}

	/**
	 * Get the options for the Expiration Date Month drop down.
	 *
	 * @since Unknown
	 * @since 2.5 Added $included_placeholder parameter.
	 *
	 * @param string $selected_month The currently selected month.
	 * @param string $placeholder    Placeholder text.
	 *
	 * @return string
	 */
	private function get_expiration_months( $selected_month, $placeholder ) {

		$str = '';

		if ( empty( $placeholder ) ) {
			$placeholder = esc_html__( 'Month', 'gravityforms' );
		}

		$str .= "<option value=''>{$placeholder}</option>";

		for ( $i = 1; $i < 13; $i ++ ) {
			$selected = intval( $selected_month ) == $i ? "selected='selected'" : '';
			$month    = str_pad( $i, 2, '0', STR_PAD_LEFT );
			$str .= "<option value='{$i}' {$selected}>{$month}</option>";
		}

		return $str;

	}

	/**
	 * Get the options for the Expiration Date Year drop down.
	 *
	 * @since Unknown
	 * @since 2.5 Added $included_placeholder parameter.
	 *
	 * @param string $selected_year The currently selected year.
	 * @param string $placeholder   Placeholder text.
	 *
	 * @return string
	 */
	private function get_expiration_years( $selected_year, $placeholder ) {

		$str = '';

		if ( empty( $placeholder ) ) {
			$placeholder = esc_html__( 'Year', 'gravityforms' );
		}

		$str .= "<option value=''>{$placeholder}</option>";

		$year = intval( date( 'Y' ) );

		for ( $i = $year; $i < ( $year + 20 ); $i ++ ) {
			$selected = intval( $selected_year ) == $i ? "selected='selected'" : '';
			$str .= "<option value='{$i}' {$selected}>{$i}</option>";
		}

		return $str;

	}

	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

		//saving last 4 digits of credit card
		list( $input_token, $field_id_token, $input_id ) = rgexplode( '_', $input_name, 3 );
		if ( $input_id == '1' ) {
			$value              = str_replace( ' ', '', $value );
			$card_number_length = strlen( $value );
			$value              = substr( $value, - 4, 4 );
			$value              = str_pad( $value, $card_number_length, 'X', STR_PAD_LEFT );
		} elseif ( $input_id == '4' ) {

			$value = rgpost( "input_{$field_id_token}_4" );

			if ( ! $value ) {
				$value       = __( 'DAF Card', 'gf-daf-field' );
			}
		} else {
			$value = '';
		}

		return $this->sanitize_entry_value( $value, $form['id'] );
	}

}

GF_Fields::register( new GF_Field_DAF() );