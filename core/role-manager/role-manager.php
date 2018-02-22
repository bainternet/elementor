<?php
namespace Elementor\Core\RoleManager;

use Elementor\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Role_Manager extends Settings {

	const PAGE_ID = 'elementor-role-manager';
	const ROLE_MANAGER_OPTION_NAME = 'elementor-role-manager';

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

	public function get_role_manager_options() {
		return get_option( self::ROLE_MANAGER_OPTION_NAME, [] );
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
				],
			],
		];
	}

	/**
	 * @since ??
	 * @access protected
	 */
	protected function get_page_title() {
		return __( 'Role Manager', 'elementor' );
	}

	public function get_user_restrictions() {
		static $restrictions = false;
		if ( ! $restrictions ) {
			$restrictions = apply_filters( 'elementor/editor/user/restrictions', [] );
		}
		return $restrictions;
	}

	public function user_can( $capability ) {
		$user  = wp_get_current_user();
		$user_roles = $user->roles;
		$options = $this->get_user_restrictions();

		if ( empty( $options ) ) {
			return true;
		}

		foreach ( $user_roles as $role ) {
			if ( ! isset( $options[ $role ] ) ) {
				continue;
			}
			if ( in_array( $capability, $options[ $role ] ) ) {
				return false;
			}
		}

		return true;
	}
}
