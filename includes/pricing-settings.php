<?php
/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Pricing_Settings {

	/**
	 * The single instance of WordPress_Plugin_Template_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Available settings for plugin in the product area.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $product_settings = array();

	/**
	 * Constructor function.
	 *
	 * @param object $parent Parent object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		$this->base = 'wpt_';

		// Initialise settings.
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array(
				$this,
				'add_settings_link',
			)
		);

		// Configure placement of plugin settings page. See readme for implementation.
		add_filter( $this->base . 'menu_settings', array( $this, 'configure_settings' ) );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();

	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$args = $this->menu_settings();

		// Do nothing if wrong location key is set.
		if ( is_array( $args ) && isset( $args['location'] ) && function_exists( 'add_' . $args['location'] . '_page' ) ) {
			switch ( $args['location'] ) {
				case 'options':
				case 'submenu':
					$page = add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
					break;
				case 'menu':
					$page = add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position'] );
					break;
				default:
					return;
			}
			add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
		}
	}

	/**
	 * Prepare default settings page arguments
	 *
	 * @return mixed|void
	 */
	private function menu_settings() {
		return apply_filters(
			$this->base . 'menu_settings',
			array(
				'location'    => 'options', // Possible settings: options, menu, submenu.
				'parent_slug' => 'options-general.php',
				'page_title'  => __( 'Plugin Settings', 'pricing' ),
				'menu_title'  => __( 'Plugin Settings', 'pricing' ),
				'capability'  => 'manage_options',
				'menu_slug'   => $this->parent->_token . '_settings',
				'function'    => array( $this, 'settings_page' ),
				'icon_url'    => '',
				'position'    => null,
			)
		);
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function configure_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function configure_product_settings( $settings = array() ) {
		return $product_settings;
	}

	/**
	 * Load settings JS & CSS
	 *
	 * @return void
	 */
	public function settings_assets() {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below.
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );

		// We're including the WP media scripts here because they're needed for the image upload field.
		// If you're not including an image upload then you can leave this function call out.
		wp_enqueue_media();

		wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0', true );
		wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links.
	 * @return array        Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'Pricing' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['standard'] = array(
			'title'       => __( 'Standard', 'Pricing' ),
			'description' => __( 'Here you can set a default configuration for your products.', 'Pricing' ),
			'fields'      => array(
				array(
					'id'          => 'default_rice',
					'label'       => __( 'Default price', 'Pricing' ),
					'description' => __( 'This value can be modify in the product administration area.', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Example 30,00€', 'Pricing' ),
				),
				array(
					'id'          => 'max_price',
					'label'       => __( 'Max Price', 'Pricing' ),
					'description' => __( 'This is the maximun price set by default for all the games.', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Example 70,00€', 'Pricing' ),
				),
				array(
					'id'          => 'min_price',
					'label'       => __( 'Min Price', 'Pricing' ),
					'description' => __( 'This is the minimum price set by default for all the games.', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Example 5,00€', 'Pricing' ),
				),
				array(
					'id'          => 'change_amount',
					'label'       => __( 'Amount of change', 'Pricing' ),
					'description' => __( 'This value represent how much the price will be increase or decrease depeding of the results get from the market after the periocity is complete.', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Example 5% or 3€ or ...', 'Pricing' ),
				),
				array(
					'id'          => 'change_amount_type',
					'label'       => __( 'Periocity type', 'Pricing' ),
					'description' => __( 'Set the period as.', 'Pricing' ),
					'type'        => 'radio',
					'options'     => array(
						'percentage'    => 'percentage %', 
						'fix'    		=> 'Fix price €', 
						//'auto'	 		=> 'Value auto generatic',
					),
					'default'     => '',
				),
				array(
					'id'          => 'periocity',
					'label'       => __( 'Periocity', 'Pricing' ),
					'description' => __( 'Number that will set the periocity of the .', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Example 7 days or 1 month or ...', 'Pricing' ),
				),/*
				array(
					'id'          => 'time_type',
					'label'       => __( 'An Option', 'Pricing' ),
					'description' => __( 'A standard checkbox - if you save this option as checked then it will store the option as \'on\', otherwise it will be an empty string.', 'Pricing' ),
					'type'        => 'checkbox',
					'default'     => '',
				),*/
				array(
					'id'          => 'periocity_type',
					'label'       => __( 'Periocity type', 'Pricing' ),
					'description' => __( 'Set the period as.', 'Pricing' ),
					'type'        => 'select',
					'options'     => array(
						'seconds'    => 'Seconds', 
						'minutes'    => 'Minutes', 
						'hours' 	 => 'Hours', 
						'days'		 => 'Days', 
						'months' 	 => 'Months'

					),
					'default'     => 'Days',
				),/*
				array(
					'id'          => 'radio_buttons',
					'label'       => __( 'Some Options', 'Pricing' ),
					'description' => __( 'A standard set of radio buttons.', 'Pricing' ),
					'type'        => 'radio',
					'options'     => array(
						'superman' => 'Superman',
						'batman'   => 'Batman',
						'ironman'  => 'Iron Man',
					),
					'default'     => 'batman',
				),
				array(
					'id'          => 'multiple_checkboxes',
					'label'       => __( 'Some Items', 'Pricing' ),
					'description' => __( 'You can select multiple items and they will be stored as an array.', 'Pricing' ),
					'type'        => 'checkbox_multi',
					'options'     => array(
						'square'    => 'Square',
						'circle'    => 'Circle',
						'rectangle' => 'Rectangle',
						'triangle'  => 'Triangle',
					),
					'default'     => array( 'circle', 'triangle' ),
				),*/
			),
		);

		$settings['extra'] = array(
			'title'       => __( 'Extra', 'Pricing' ),
			'description' => __( 'These section is to configure the big changes of tendence.', 'Pricing' ),
			'fields'      => array(				
				array(
					'id'          => 'tendence_change_up',
					'label'       => __( 'Tendency percentage', 'Pricing' ),
					'description' => __( 'If the tendence of the is becoming bigger this value the price will be increase.', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( '10%', 'Pricing' ),
				),	
				array(
					'id'          => 'tendence_change_down',
					'label'       => __( 'Tendency percentage', 'Pricing' ),
					'description' => __( 'If the tendence of the is going down the price will be decrece.', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( '10%', 'Pricing' ),
				),
				array(
					'id'          => 'change_amount',
					'label'       => __( 'Amount of change', 'Pricing' ),
					'description' => __( 'This value represent how much the price will be increase or decrease depeding of the results get from the market after the periocity is complete.', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					//'placeholder' => __( 'Example 5% or 3€ or ...', 'Pricing' ),
				),
				array(
					'id'          => 'change_amount_type',
					'label'       => __( 'Periocity type', 'Pricing' ),
					'description' => __( 'Set the period as.', 'Pricing' ),
					'type'        => 'radio',
					'options'     => array(
						'percentage'    => 'percentage %', 
						'fix'    => 'Fix price', 
						'auto'	 => 'value auto generatic',
					),
					'default'     => '',
				),
				array(
					'id'          => 'periocity',
					'label'       => __( 'Periocity', 'Pricing' ),
					'description' => __( 'Number that will set the periocity of the .', 'Pricing' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( '7 days or 1 month or ...', 'Pricing' ),
				),				
				array(
					'id'          => 'periocity_type',
					'label'       => __( 'Periocity type', 'Pricing' ),
					'description' => __( 'Set the period as.', 'Pricing' ),
					'type'        => 'select',
					'options'     => array(
						'seconds'    => 'Seconds', 
						'minutes'    => 'Minutes', 
						'hours' 	 => 'Hours', 
						'days'		 => 'Days', 
						'months' 	 => 'Months'
					),
					'default'     => '',
				),/*
				array(
					'id'          => 'number_field',
					'label'       => __( 'A Number', 'Pricing' ),
					'description' => __( 'This is a standard number field - if this field contains anything other than numbers then the form will not be submitted.', 'Pricing' ),
					'type'        => 'number',
					'default'     => '',
					'placeholder' => __( '42', 'Pricing' ),
				),
				array(
					'id'          => 'colour_picker',
					'label'       => __( 'Pick a colour', 'Pricing' ),
					'description' => __( 'This uses WordPress\' built-in colour picker - the option is stored as the colour\'s hex code.', 'Pricing' ),
					'type'        => 'color',
					'default'     => '#21759B',
				),
				array(
					'id'          => 'an_image',
					'label'       => __( 'An Image', 'Pricing' ),
					'description' => __( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'Pricing' ),
					'type'        => 'image',
					'default'     => '',
					'placeholder' => '',
				),
				array(
					'id'          => 'multi_select_box',
					'label'       => __( 'A Multi-Select Box', 'Pricing' ),
					'description' => __( 'A standard multi-select box - the saved data is stored as an array.', 'Pricing' ),
					'type'        => 'select_multi',
					'options'     => array(
						'linux'   => 'Linux',
						'mac'     => 'Mac',
						'windows' => 'Windows',
					),
					'default'     => array( 'linux' ),
				),*/
			),
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab.
			//phpcs:disable
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}
			//phpcs:enable

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field.
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page.
					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->_token . '_settings',
						$section,
						array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Settings section.
	 *
	 * @param array $section Array of section ids.
	 * @return void
	 */
	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html; //phpcs:ignore
	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML.
		$html      = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Plugin Settings', 'Pricing' ) . '</h2>' . "\n";

			$tab = '';
		//phpcs:disable
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= $_GET['tab'];
		}
		//phpcs:enable

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) { //phpcs:ignore
					if ( 0 === $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) { //phpcs:ignore
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link.
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				if ( isset( $_GET['settings-updated'] ) ) { //phpcs:ignore
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab.
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++$c;
			}

			$html .= '</h2>' . "\n";
		}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields.
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();

				$html     .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'Pricing' ) ) . '" />' . "\n";
				$html     .= '</p>' . "\n";
			$html         .= '</form>' . "\n";
		$html             .= '</div>' . "\n";

		echo $html; //phpcs:ignore
	}

	/**
	 * Main WordPress_Plugin_Template_Settings Instance
	 *
	 * Ensures only one instance of WordPress_Plugin_Template_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WordPress_Plugin_Template()
	 * @param object $parent Object instance.
	 * @return object WordPress_Plugin_Template_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of WordPress_Plugin_Template_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of WordPress_Plugin_Template_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	} // End __wakeup()


	//////////////////////////////////////////////////////
	/*
	add_action('manage_product_posts_custom_column', 'dpw_product_columns_content', 5, 2);
	public function dpw_product_columns_content($column_name, $post_ID) {

		if ($column_name == 'proveedor') {

			//Buscamos los valores del atributo 'proveedor' y los mostramos.
			if($proveedores = get_the_terms( $post_ID, 'pa_proveedor')){
					foreach ( $proveedores as $proveedor ) {
						echo $proveedor->name;
					}
			}
		}
		
	}
*/
}
