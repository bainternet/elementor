<?php
namespace Elementor\Core\RoleManager;

use Elementor\Settings;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RoleManager extends Settings {

	const OPTION_NAME = 'elementor-role-capabilities';
	const NONCE_ACTION = 'role-manager-nonce';
	const BASIC_CAP = 'elementor_editor_access';

	/**
	 * @var array
	 * holds base capabilities
	 */
	private $capabilities = [
		'elementor_editor_access_level' => [
			'default' => [ 'editor', 'author', 'contributor' ],
			'description' => 'Allow Access to Elementor',
			'sub_caps' => [
				'elementor_editor_content' => [
					'default' => [ 'editor', 'author' ],
					'description' => 'Allows editing existing elements content.',
				],
				'elementor_editor_style' => [
					'default' => [ 'editor', 'author', 'contributor' ],
					'description' => 'Allows styling and designing of existing elements.',
					'includes' => [ 'elementor_editor_content' ],
				],
				'elementor_editor_all' => [
					'default'     => [ 'editor', 'author', 'contributor' ],
					'description' => 'Allows all editor capabilities.',
					'includes'    => [ 'elementor_editor_content', 'elementor_editor_style' ],
				],
			],
		],
	];

	/**
	 * @param $name
	 * @param array $args
	 * @param bool $is_sub_cap
	 *
	 * @return array
	 */
	public function add_capability( $name, $args = [], $is_sub_cap = false ) {
		$base = [
			'default' => [],
			'description' => '',
			'sub_caps' => [],
			'includes' => [],
		];

		$new_cap = array_merge( $base, $args );

		if ( $is_sub_cap ) {
			return $new_cap;
		}

		//validate sub capabilities structure
		if ( count( $new_cap['sub_caps'] ) > 0 ) {
			foreach ( $new_cap['sub_cap'] as $sub_cap_name => $sub_cap_args ) {
				$new_cap['sub_caps'][ $sub_cap_name ] = $this->add_capability( $sub_cap_name, $sub_cap_args, true );
			}
		}
		$this->capabilities[ $name ] = $new_cap;
	}

	/**
	 * @return bool|null
	 */
	public function is_role_manager_enabled() {
		static $enabled = null;
		if ( null === $enabled ) {
			$enabled = ( count( $this->get_capabilities( 'names' ) ) > 0 );
		}
		return $enabled;
	}

	/**
	 * @param string $capability
	 *
	 * @return bool
	 */
	public function current_user_can( $capability = '' ) {
		//admin is never limited
		if ( $this->is_role_manager_enabled() && ! current_user_can( 'manage_options' ) ) {
			return $this->user_can( $capability );
		} else {
			return true;
		}
	}

	/**
	 * @param string $capability
	 * @param null $user
	 *
	 * @return bool
	 */
	public function user_can( $capability = '', $user = null ) {
		if ( $this->is_role_manager_enabled() ) {
			if ( null === $user ) {
				$user = wp_get_current_user();
			}
			//admin is never limited
			if ( user_can( $user, 'manage_options' ) ) {
				return true;
			} else {
				if ( user_can( $user, self::BASIC_CAP ) ) {
					return user_can( $user, $capability );
				} else {
					return false;
				}
			}
		} else {
			return true;
		}
	}

	public function get_user_capabilities( $user = null ) {
		$capabilities = $this->get_capabilities( 'names' );
		$user_caps = [];
		foreach ( $capabilities as $capability_name ) {
			$user_caps[ $capability_name ] = $this->user_can( $capability_name, $user );
		}
		return $user_caps;
	}

	/**
	 * @param string $return_type
	 *
	 * @return array|mixed
	 */
	public function get_capabilities( $return_type = 'default' ) {
		static $capabilities = null;
		if ( null === $capabilities ) {
			$capabilities = apply_filters( 'elementor/role_manager/capabilities', $this->capabilities );
		}
		$return = [];
		switch ( $return_type ) {
			case 'names':
				foreach ( $capabilities as $capability => $args ) {
					$return[] = $capability;
					foreach ( $args['sub_caps'] as $sub_cap => $sub_args ) {
						$return[] = $sub_cap;
					}
				}
				return $return;
				break;
			case 'flat':
				foreach ( $capabilities as $capability => $args ) {
					$return[ $capability ] = $args;
					foreach ( $args['sub_caps'] as $sub_cap => $sub_args ) {
						$return[ $sub_cap ] = $sub_args;
					}
				}
				return $return;
				break;
		}
		return $capabilities;
	}

	/**
	 * @param bool $remove_admin
	 *
	 * @return array
	 */
	public function get_role_names( $remove_admin = true ) {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}

		if ( $remove_admin ) {
			$names = $wp_roles->get_names();
			unset( $names['administrator'] );
			return $names;
		}
		return $wp_roles->get_names();
	}

	/**
	 * @param $role_name
	 *
	 * @return array
	 */
	public function get_role_capabilities( $role_name ) {
		$role_capabilities = [];
		$role = get_role( $role_name );
		foreach ( $this->get_capabilities() as $capability => $cap_args ) {
			if ( $role->has_cap( $capability ) ) {
				$role_capabilities[ $capability ] = $cap_args['description'];
			}
			foreach ( $cap_args['sub_caps'] as $sub_cap => $sub_cap_args ) {
				if ( $role->has_cap( $sub_cap ) ) {
					$role_capabilities[ $sub_cap ] = $sub_cap_args['description'];
				}
			}
		}
		return $role_capabilities;
	}

	/**
	 * @param string $name
	 * @param array $options
	 * @param string $selected
	 * @param array $attr
	 *
	 * @return string
	 */
	private function build_select( $name = '', $options = [], $selected = '', $attr = [] ) {
		$select = '<select name="' . $name . '"';
		foreach ( $attr as $name => $value ) {
			$select .= ' ' . $name . '="' . $value . '"';
		}
		$select .= '>';
		foreach ( $options as $value => $label ) {
			$select .= '<option value="' . $value . '" ' . selected( $selected, $value, false ) . '>' . $label . '</option>';
		}
		$select .= '</select>';
		return $select;
	}

	/**
	 * @param string $capability
	 *
	 * @return string
	 */
	private function cap_to_label( $capability = '' ) {
		return ucwords( str_replace( '_', ' ', $capability ) );
	}

	/**
	 * @param string $td
	 * @param string $role_name
	 * @param array $capabilities
	 *
	 * @return string
	 */
	public function get_edit_caps_column( $td = '', $role_name = '', $capabilities = [] ) {
		$role = get_role( $role_name );
		$td .= '<table class="form-table" id="' . $role_name . '_extended_caps" style="SHOW_HIDE"><tbody>';
		foreach ( $capabilities as $capability => $capability_args ) {
			$descriptions = [];
			$td .= '<tr><th><label>' . $this->cap_to_label( $capability ) . '</label></th><td>';
			$select_options = [
				'disable' => __( 'Disable', 'elementor' ),
			];
			$select_value = '';
			$select_name = 'role_caps[' . $role_name . '][' . $capability . ']';
			foreach ( (array) $capability_args['sub_caps'] as $sub_cap => $sub_cap_args ) {
				if ( $role->has_cap( $sub_cap ) ) {
					$select_value = $sub_cap;
				}
				$select_options[ $sub_cap ] = ucwords( str_replace( '_', ' ', $sub_cap ) );
				$descriptions[ $sub_cap ] = $sub_cap_args['description'];
			}
			if ( 1 === count( $select_options ) ) {
				$select_options['enable'] = __( 'Enable', 'elementor' );
				$select_value = ( $role->has_cap( $capability ) ) ? 'enable' : 'disable';
			}
			$td .= $this->build_select( $select_name, $select_options, $select_value );
			$td .= '<span class="description">';
			foreach ( (array) $descriptions as $cap => $desc ) {
				$td .= '<span class="cap_description ' . $cap . '" style="display:none">   ' . $desc . '</span>';
			}
			$td .= '</span></td></tr>';
		}
		$show_hide = '';
		if ( ! $role->has_cap( self::BASIC_CAP ) ) {
			$show_hide = 'display:none;';
		}
		return str_replace( 'SHOW_HIDE', $show_hide, $td ) . '</tbody></table>';
	}

	/**
	 * @param string $role_name
	 *
	 * @return string
	 */
	private function get_basic_capabilities( $role_name = '' ) {
		$user_config = $this->get_user_config();
		$basic_td = '';
		$select_options = [
			'enable' => __( 'Enable', 'elementor' ),
			'disable' => __( 'Disable', 'elementor' ),
		];

		$select_value = ( $user_config[ $role_name ][ self::BASIC_CAP ] ) ? 'enable' : 'disable';
		$select_name = 'role_caps[' . $role_name . '][' . self::BASIC_CAP . ']';
		$basic_td .= $this->build_select( $select_name, $select_options, $select_value, [ 'data-role' => $role_name ] );
		return '<td style="vertical-align: middle;">' . $basic_td . '</td>';
	}

	/**
	 * @return string
	 */
	private function get_role_table_headers() {
		$table_heading = '';
		$table_heading .= '<th id="role-name" class="manage-column column-role" scope="col">' . __( 'Role', 'elementor' ) . ' </th>';
		$table_heading .= '<th id="main_cap" class="manage-column column-basic" scope="col" style="width: 15%;">' . __( 'Elementor Access', 'elementor' ) . ' </th>';
		$table_heading .= '<th id="main_cap" class="manage-column column-advanced" scope="col">' . __( 'Advanced Capabilities', 'elementor' ) . ' </th>';
		return '<tr>' . $table_heading . '</tr>';
	}

	/**
	 * @return string
	 */
	public function build_role_table_html() {
		$capabilities = $this->get_capabilities();
		$main_table_rows = [];
		foreach ( $this->get_role_names() as $role_name => $role_label ) {
			$tr = '<tr><th id="' . $role_name . '" class="manage-column column-' . $role_name . '" scope="col">' . $role_label . '</th>';
			$tr .= $this->get_basic_capabilities( $role_name );
			$more_caps = '';
			$more_caps = apply_filters( 'elementor/role_manager/role/capabilities/' . $role_name, $more_caps, $role_name, $capabilities );
			$tr .= '<td>' . $more_caps . '</td></tr>';
			$main_table_rows[] = $tr;
		}
		$table_heading = $this->get_role_table_headers();
		$nonce_field = wp_nonce_field( self::NONCE_ACTION, self::NONCE_ACTION, false, false );
		return $nonce_field . '<table class="widefat fixed striped" id="role-cap-table" cellspacing="0"><thead>' . $table_heading . '</thead><tfoot>' . $table_heading . '</tfoot><tbody>' . implode( "\n", $main_table_rows ) . '</tbody>';
	}

	private function backwards_compatability() {
		$exclude_roles = get_option( 'elementor_exclude_user_roles', [] );
		if ( false === $exclude_roles ) {
			return;
		}
		$has_changes = false;
		$user_config = $this->get_user_config();
		foreach ( $this->get_role_names() as $role_name => $role_label ) {
			if ( isset( $user_config[ $role_name ][ self::BASIC_CAP ] ) ) {
				continue;
			}
			if ( in_array( $role_name, $exclude_roles ) ) {
				$user_config[ $role_name ][ self::BASIC_CAP ] = false;
				$has_changes = true;
			} else {
				$user_config[ $role_name ][ self::BASIC_CAP ] = true;
				$has_changes = true;
			}
		}
		if ( $has_changes ) {
			$this->save_user_config( $user_config );
		}
	}

	/**
	 * @param Settings $settings
	 */
	public function register_role_manager_fields( Settings $settings ) {
		$this->backwards_compatability();
		$settings->add_tab( 'role_manager',
			[
				'label' => __( 'Role Manager', 'elementor' ),
				'sections' => [
					'roles_capabilities' => [
						'callback' => function() {
							echo $this->build_role_table_html();
						},
						'fields' => [
							'enable' => [
								'label' => '',
								'field_args' => [
									'type' => 'raw_html',
								],
								'setting_args' => [
									'sanitize_callback' => [ $this, 'process_role_manager_table' ],
								],
							],
						],
					],
				],
			]
		);
	}



	/**
	 * @param \WP_Role $role
	 * @param $capability
	 * @param array $capabilities_args
	 * @param string $level
	 */
	public function set_sub_capability( \WP_Role $role, $capability, $capabilities_args = [], $level = '' ) {
		if ( isset( $capabilities_args ) && isset( $capabilities_args['sub_caps'] ) && is_array( $capabilities_args['sub_caps'] ) && ! empty( $capabilities_args['sub_caps'] ) ) {
			//first remove all in case of downgrading a role
			foreach ( $capabilities_args['sub_caps'] as $sub_cap => $sub_args ) {
				$role->remove_cap( $sub_cap );
			}

			foreach ( $capabilities_args['sub_caps'] as $sub_cap => $sub_args ) {
				if ( $level === $sub_cap ) {
					$role->add_cap( $capability );
					$role->add_cap( $sub_cap );
					if ( isset( $sub_args['includes'] ) && is_array( $sub_args['includes'] ) && ! empty( $sub_args['includes'] ) ) {
						foreach ( $sub_args['includes'] as $sub_capability ) {
							$role->add_cap( $sub_capability );
						}
					}
				}
			}
		}
	}

	/**
	 * @param \WP_Role $role
	 * @param string $capability_name
	 * @param array $args
	 */
	private function remove_role_capabilities( \WP_Role $role, $capability_name = '', $args = [] ) {
		$role->remove_cap( $capability_name );
		if ( isset( $args['sub_caps'] ) ) {
			foreach ( array_keys( $args['sub_caps'] ) as $sub_cap ) {
				$role->remove_cap( $sub_cap );
			}
		}
	}

	/**
	 * @param array $user_config
	 * @param string $role_name
	 * @param array $capabilities
	 *
	 * @return array
	 */
	private function reset_caps( $user_config = [], $role_name = '', $capabilities = [] ) {
		foreach ( $capabilities as $capability_to_remove ) {
			$user_config[ $role_name ][ $capability_to_remove ] = false;
		}
		return $user_config;
	}

	/**
	 * @param array $user_config
	 * @param string $role_name
	 * @param array $capabilities
	 *
	 * @return array
	 */
	private function set_included_caps( $user_config = [], $role_name = '', $capabilities = [] ) {
		foreach ( $capabilities as $sub_cap ) {
			$user_config[ $role_name ][ $sub_cap ] = true;
		}
		return $user_config;
	}

	/**
	 * @param $input
	 *
	 * @return mixed
	 */
	public function process_role_manager_table( $input ) {
		if ( ! isset( $_POST[ self::NONCE_ACTION ] ) || ! wp_verify_nonce( $_POST[ self::NONCE_ACTION ], self::NONCE_ACTION ) || ! isset( $_POST['role_caps'] ) ) {
			return $input;
		}

		$posted_data = $_POST['role_caps'];
		$user_config = $this->get_user_config();
		$role_names = $this->get_role_names();
		$elementor_config = $this->get_capabilities( 'flat' );

		foreach ( $role_names as $role_name => $role_label ) {
			$basic = $posted_data[ $role_name ][ self::BASIC_CAP ];
			if ( in_array( $basic, array( 'enable', 'disable' ) ) ) {
				$user_config[ $role_name ][ self::BASIC_CAP ] = ( 'enable' === $basic );
			}
			//if disabled basic skip
			if ( 'disable' === $basic ) {
				continue;
			}

			foreach ( $elementor_config as $capability => $capability_args ) {
				if ( ! isset( $posted_data[ $role_name ][ $capability ] ) ) {
					continue;
				}
				//reset sub caps to false
				$user_config = $this->reset_caps( $user_config, $role_name, array_keys( $capability_args['sub_caps'] ) );

				$chosen_level = $posted_data[ $role_name ][ $capability ];
				if ( in_array( $chosen_level, array( 'enable', 'disable' ) ) ) {
					$user_config[ $role_name ][ $capability ] = ( 'enable' === $chosen_level );
					continue;
				}

				$user_config[ $role_name ][ $chosen_level ] = true;
				//set included capabilities
				if ( ! isset( $capability_args['sub_caps'][ $chosen_level ]['includes'] ) ) {
					continue;
				}
				$user_config = $this->set_included_caps( $user_config, $role_name, $capability_args['sub_caps'][ $chosen_level ]['includes'] );
			}
		}

		$this->save_user_config( $user_config );
		$this->set_caps_to_roles();
		return $input;
	}

	private function set_caps_to_roles() {
		$user_config = $this->get_user_config();
		foreach ( $user_config as $role_name => $capabilities ) {
			$role = get_role( $role_name );
			//if disabled basic remove all others
			if ( ! $capabilities[ self::BASIC_CAP ] ) {
				foreach ( $capabilities as $capability => $is_on ) {
					$role->remove_cap( $capability );
				}
				continue;
			}
			foreach ( $capabilities as $capability => $is_on ) {
				if ( $is_on ) {
					$role->add_cap( $capability );
				} else {
					$role->remove_cap( $capability );
				}
			}
		}
	}

	private function on_install_upgrade() {
		$user_config = $this->get_user_config();
		$elementor_config = $this->get_capabilities( 'flat' );
		foreach ( $elementor_config as $capability => $args ) {
			foreach ( $args['default'] as $role ) {
				if ( isset( $user_config[ $role ][ $capability ] ) ) {
					continue;
				}
				$user_config[ $role ][ $capability ] = true;
			}
		}
		$this->save_user_config( $user_config );
		$this->set_caps_to_roles();
	}

	/**
	 * @return array
	 */
	public function get_user_config() {
		return get_option( self::OPTION_NAME, [] );
	}

	/**
	 * @param $user_config
	 */
	public function save_user_config( $user_config ) {
		update_option( self::OPTION_NAME, $user_config );
	}

	/**
	 * RoleManager constructor.
	 */
	public function __construct() {

		add_action( 'elementor/admin/after_create_settings/' . Settings::PAGE_ID, [ $this, 'register_role_manager_fields' ], 60 );
		//todo move to pro
		foreach ( $this->get_role_names() as $role_name => $role ) {
			add_filter( 'elementor/role_manager/role/capabilities/' . $role_name, [ $this, 'get_edit_caps_column' ], 10, 3 );
		}
		add_filter( 'elementor/editor/capabilities', function( $array ) {
			return $this->get_user_capabilities();
		});
	}
}