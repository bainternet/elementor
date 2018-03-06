<?php
namespace Elementor\Core\RoleManager;

use Elementor\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Role_Manager extends Settings {

	const PAGE_ID = 'elementor-role-manager';

	/**
	 * @since ??
	 * @access public
	 */
	public function register_admin_menu() {
		add_submenu_page(
			Settings::PAGE_ID,
			$this->get_page_title(),
			$this->get_page_title(),
			'manage_options',
			self::PAGE_ID,
			[ $this, 'display_settings_page' ]
		);
	}

	/**
	 * @since ??
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'admin_menu', [ $this, 'register_admin_menu' ], 205 );
	}

	/**
	 * @since ??
	 * @access protected
	 */
	protected function create_tabs() {
		$validations_class_name = __NAMESPACE__ . '\Settings_Validations';
		return [
			'general' => [
				'label' => __( 'General', 'elementor' ),
				'sections' => [
					'tools' => [
						'fields' => [
							'exclude_user_roles' => [
								'label' => __( 'Exclude Roles', 'elementor' ),
								'field_args' => [
									'type' => 'checkbox_list_roles',
									'exclude' => [ 'administrator' ],
								],
								'setting_args' => [ $validations_class_name, 'checkbox_list' ],
							],
						],
					],
					'advance_role_manager' => [
						'fields' => [
							'advanced_role_manager_label' => [
								'field_args' => [
									'type' => 'raw_html',
									'html' => '<h2>' . esc_html__( 'Advanced Role Manager', 'elementor-pro' ) . '</h2><p> Go Pro</p>',
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * @since 1.5.0
	 * @access public
	 */
	public function display_settings_page() {
		$tabs = $this->get_tabs();
		?>
		<div class="wrap">
			<h1><?php echo $this->get_page_title(); ?></h1>
			<form id="elementor-settings-form" method="post" action="options.php">
				<?php
				settings_fields( static::PAGE_ID );

				foreach ( $tabs as $tab_id => $tab ) {
					if ( empty( $tab['sections'] ) ) {
						continue;
					}

					$active_class = '';

					if ( 'general' === $tab_id ) {
						$active_class = ' elementor-active';
					}

					echo '<div id="tab-' . esc_attr( $tab_id ) . '" class="elementor-settings-form-page' . esc_attr( $active_class ) . '">';

					foreach ( $tab['sections'] as $section_id => $section ) {
						$full_section_id = 'elementor_' . $section_id . '_section';

						if ( ! empty( $section['callback'] ) ) {
							$section['callback']();
						}

						echo '<div class="settings-form-wrapper" id="' . esc_attr( $full_section_id ). '">';
						if ( ! empty( $section['label'] ) ) {
							echo '<h2>' . esc_html( $section['label'] ) . '</h2>';
						}
						$this->do_settings_fields( static::PAGE_ID, $full_section_id );

						echo '</div>';
					}

					echo '</div>';
				}

				submit_button();
				?>
			</form>
		</div><!-- /.wrap -->
		<?php
	}

	private function do_settings_fields( $page, $section ) {
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
			$class = '';

			if ( ! empty( $field['args']['class'] ) ) {
				$class = $field['args']['class'];
			}

			echo '<div class="settings-form-row ' . esc_attr( $class ) . '">';
			if ( $field['args']['type'] === 'raw_html') {
				call_user_func( $field['callback'], $field['args'] );
				echo '</div>';
				continue;
			}

			if ( ! empty( $field['args']['label_for'] ) ) {
				echo '<p><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label>';
			} else {
				echo '<p>' . $field['title'];
			}

			echo '<br>';
			call_user_func( $field['callback'], $field['args'] );
			echo '</p>';
			echo '</div>';
		}
	}

	/**
	 * @since ??
	 * @access protected
	 */
	protected function get_page_title() {
		return __( 'Role Manager', 'elementor' );
	}

	private function get_user_restrictions() {
		static $restrictions = false;
		if ( ! $restrictions ) {
			$restrictions = apply_filters( 'elementor/editor/user/restrictions', [] );
		}
		return $restrictions;
	}

	public function get_user_restrictions_array() {
		$user  = wp_get_current_user();
		$user_roles = $user->roles;
		$options = $this->get_user_restrictions();
		$restrictions = [];

		if ( empty( $options ) ) {
			return $restrictions;
		}

		foreach ( $user_roles as $role ) {
			if ( ! isset( $options[ $role ] ) ) {
				continue;
			}
			$restrictions = array_merge( $restrictions, $options[ $role ] );
		}
		return array_unique( $restrictions );
	}

	public function user_can( $capability ) {
		$options = $this->get_user_restrictions_array();

		if ( in_array( $capability, $options ) ) {
			return false;
		}
		return true;
	}
}
