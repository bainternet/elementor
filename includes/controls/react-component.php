<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor React Component control.
 *
 * A base control for creating text control. Displays a simple text input.
 *
 */
class Control_React_Component extends Control_Base_Multiple {

	/**
	 * Get React Component control type.
	 *
	 * Retrieve the control type, in this case `react_component`.
	 *
	 * @access public
	 *
	 * @return string Control type.
	 */
	public function get_type() {
		return 'react_component';
	}

	/**
	 * Render React Component control output in the editor.
	 *
	 * Used to generate the control HTML in the editor using Underscore JS
	 * template. The variables for the class are available using `data` JS
	 * object.
	 *
	 * @access public
	 */
	public function content_template() {
		$control_uid = $this->get_control_uid();
		?>
		<div class="elementor-control-field">
			<# if ( data.label ) {#>
				<label for="<?php echo $control_uid; ?>" class="elementor-control-title">{{{ data.label }}}</label>
			<# } #>
			<div class="elementor-control-input-wrapper">
				<div class="elementor-react-component-placeholder"></div>
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}

	/**
	 * Get React Component control default settings.
	 *
	 * Retrieve the default settings of the text control. Used to return the
	 * default settings while initializing the React Component control.
	 *
	 * @access protected
	 *
	 * @return array Control default settings.
	 */
	protected function get_default_settings() {
		return [
			'props' => [],
			'component' => '',
		];
	}
}
