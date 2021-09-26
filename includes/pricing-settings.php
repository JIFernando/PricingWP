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
		$this->preload_actions();
		
		//Load BoostrapsJS
		add_action('admin_enqueue_scripts', array( $this, 'QueueBootstrapJS'));
		
		//Load CSS
		add_action('admin_enqueue_scripts',array( $this, 'QueueBootstrapCSS'));

		//Load local scripts
		add_action('admin_enqueue_scripts ', array( $this, 'callback_for_setting_up_scripts'));

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
	 * Load local scripts
	 *
	 * @return void
	 */
	function callback_for_setting_up_scripts() {	
		$current_screen = get_current_screen();
		if ( strpos($current_screen->base, 'settings_page_pricingwp_settings') === false) {
			return;
		} else {
			wp_register_style( 'settings_page_pricingwp_settings', plugins_url('../assets/css/admin.css',__FILE__));
			wp_enqueue_style( 'settings_page_pricingwp_settings' );
			wp_enqueue_script( 'settings_page_pricingwp_settings', '../assets/css/js/admin.js', array( 'jquery' ) );
		}
	}

	/**
	 * Load BoostrapsJS
	 *
	 * @return void
	 */
	function QueueBootstrapJS(){
		$current_screen = get_current_screen();
		if ( strpos($current_screen->base, 'settings_page_pricingwp_settings') === false) {
			return ;
		}
		wp_enqueue_script('bootstrapJs',plugins_url('../admin/bootstrap/js/bootstrap.min.js',__FILE__),array('jquery'));
	}

	/**
	 * Load Boostrap CSS
	 *
	 * @return void
	 */
	function QueueBootstrapCSS(){
		$current_screen = get_current_screen();
		if ( strpos($current_screen->base, 'settings_page_pricingwp_settings') === false) {
			return ;
		}
		wp_enqueue_style('bootstrapCSS',plugins_url('admin/bootstrap/css/bootstrap.min.css',__FILE__));
	}
	
	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function preload_actions() {				
		//applyAction();
		$action = isset($_POST['wpt_com_action']) ? $_POST['wpt_com_action'] : '';
		$actionDeac = isset($_GET['_action']) ? $_GET['_action'] : '';

		switch ($action) {
			case "_activate":
				$this->save_new_configuration();
			break;
			case "_update":
				$this->update_configuration();
			break;
		}

		if($actionDeac === '_deactivate'){
			$this->deactivate_configuration();
		}
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {		
		$productId = '';		
		$productId .=  isset($_GET['product_id'] ) ? $_GET['product_id'] : '';
		$this->settings = $this->settings_fields($productId);

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
				'page_title'  => __( 'Pricing Settings', 'pricing' ),
				'menu_title'  => __( 'Pricing Settings', 'pricing' ),
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
	//private function settings_fields( $product ) {
	private function settings_fields($productId) {

		$currentPrice = '';
		$max_price = '';
		$min_price = '';
		$change_amount = '';
		$periocity = '';

		if (!empty($productId)){
			$settingInfo = $this->get_fields_setting_values($productId);

			$currentPrice = $settingInfo->current_price;
			$max_price = $settingInfo->max_price;
			$min_price = $settingInfo->min_price;
			$change_amount = $settingInfo->change_amount;
			$periocity = $settingInfo->periocity;
		}

		$settings['settings'] = array(
			//'title'       => __( $product[productName], 'Pricing' ),			
			'title'       => __( 'Settings', 'Pricing' ),
			'description' => __( 'Here you can set a default configuration for your products, please fill all the elements.', 'Pricing' ),
			'fields'      => array(
				array(
					'id'          => 'current_price',
					'label'       => __( 'Current price*', 'Pricing' ),
					'description' => __( 'This value can be modify in the product administration area.', 'Pricing' ),
					'type'        => 'text',
					'default'     => $currentPrice,
					'placeholder' => __( 'Example 30,00€', 'Pricing' ),
					'mandatory'	  => true,
				),
				array(
					'id'          => 'max_price',
					'label'       => __( 'Max Price*', 'Pricing' ),
					'description' => __( 'This is the maximun price set by default for all the games.', 'Pricing' ),
					'type'        => 'text',
					'default'     => $max_price,
					'placeholder' => __( 'Example 70,00€', 'Pricing' ),
					'mandatory'	  => true,
				),
				array(
					'id'          => 'min_price',
					'label'       => __( 'Min Price*', 'Pricing' ),
					'description' => __( 'This is the minimum price set by default for all the games.', 'Pricing' ),
					'type'        => 'text',
					'default'     => $min_price,
					'placeholder' => __( 'Example 5,00€', 'Pricing' ),
					'mandatory'	  => true,
				),
				array(
					'id'          => 'change_amount',
					'label'       => __( 'Amount of change*', 'Pricing' ),
					'description' => __( 'This value represent how much the price will be increase or decrease depeding of the results get from the market after the periocity is complete.', 'Pricing' ),
					'type'        => 'text',
					'default'     => $change_amount,
					'placeholder' => __( 'Example 5% or 3€ or ...', 'Pricing' ),
					'mandatory'	  => true,
				),
				array(
					'id'          => 'periocity',
					'label'       => __( 'Periocity*', 'Pricing' ),
					'description' => __( 'Number that will set how many days are needed to change the price.', 'Pricing' ),
					'type'        => 'text',
					'default'     => $periocity,
					'placeholder' => __( 'Example 7 days or 1 month or ...', 'Pricing' ),
					'mandatory'	  => true,
				),
			),
		);

		
		$settings['statistics'] = array(
			'title'       => __( 'Statistics', 'Pricing' ),
			'description' => __( 'These section is you will be able to see the impact of the plugin changes.', 'Pricing' ),
			'fields'      => array(	
				array(	
					'id'          => 'sales_by_day',
					'label'       => __( 'Sales by day', 'Pricing' ),
					'description' => __( 'In this graph we can see how the sales of the product evolves daily.', 'Pricing' ),
					'type'        => 'graphic',
				),array(	
					'id'          => 'price_by_day',
					'label'       => __( 'Price by day', 'Pricing' ),
					'description' => __( 'In this graph we can see how the price of the product evolves daily.', 'Pricing' ),
					'type'        => 'graphic',
				),
			),
		);


		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	//private function settings_fields( $product ) {
	private function get_fields_setting_values($productId) {

		global $wpdb;

		// Get database data
		$query = "SELECT postmeta.meta_value as current_price, pSetting.max_price, pSetting.min_price, pSetting.change_amount,  pSetting.periocity
		FROM 	   wp_postmeta	 				 as postmeta 
		LEFT  JOIN wp_product_pricing_setting	 as pSetting ON pSetting.product_id = postmeta.post_id 
		WHERE postmeta.post_id = $productId AND postmeta.meta_key = '_price'";
  
  		$product_settings= $wpdb->get_results($query);

		return $product_settings[0];
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
	public function product_list_page() {
		global $wpdb;
		
		//$productName
		$productName = isset($_GET['product_name']) ? $_GET['product_name'] : "";
		$filterPname = '';
		if(!empty($productName)){
			$filterPname = 'AND p.post_title = "' . $productName . '"';
		}
		// page number
		$paged = isset($_GET['paged']) ? $_GET['paged'] : 1;

		// sort by row column 
		$sort['name'] = isset($_GET['sort']) ? $_GET['sort'] : 'p.post_title';
		$sort['order'] = isset($_GET['order']) ? $_GET['order'] : 'DESC';
		$sort_by = ' ORDER BY ' . $sort['name'] . ' '. $sort['order'];

		// product name 
		$filter['product_name'] = $productName;

		// Get database data
		$sql = '
        SELECT DISTINCT p.ID as product_id, p.post_title as productName, pm.meta_value as price, pps.start_date, pps.max_price, pps.min_price, pps.change_amount, pps.periocity, pps.setting_id 
		FROM `wp_postmeta` pm 
		RIGHT JOIN wp_posts p ON p.ID = pm.post_id 
		LEFT JOIN wp_product_pricing_setting pps ON p.ID = pps.product_id 
		WHERE pm.meta_key = "_price" AND p.post_type = "product" ' . $filterPname  . '  ORDER BY ' . $sort['name'] . ' ' . $sort['order'];
        $rows = $wpdb->get_results($sql);
		
        $rows_per_page = 10;
		
        // initial link for pagination.
        // "page" must be the  menu slug / clean url from the add_menu_page
        $link = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "&"));


		//add_query_arg(array('tab' => 'settings','product_id' => $productId, '_action' => '_activate'));
		// Build page HTML.
		$html      = '<div class="wrap" id="' . $this->parent->_token . '_product_list">' . "\n";
			//$html .= '<h2>' . __( 'Product list', 'Pricing' ) . '</h2>' . "\n";

			$html .= '
            <form action="' . $link . '" method="get">
                <input type="hidden" name="page" value="pricingwp_settings">
                <input type="hidden" name="sort" value="'.$sort['name'].'">
                <input type="hidden" name="order" value="'.$sort['order'].'">
                <input type="hidden" name="paged" value="'.$paged.'">
                <label for="product_name">'.__('Product Name').'</label>
                <input type="text" name="product_name" value="' .$filter['product_name']. '" placeholder="name">
                <input type="submit" class="page-title-action" value="'.__('Send').'">
				<a href="' . $link . '" class="page-title-action"    role="button" >Reset</a>
            </form>';		

		// add pagination arguments from WordPress
		$pagination_args = array(
			'base' => add_query_arg('paged','%#%'),
			'format' => '',
			'total' => ceil(sizeof($rows)/$rows_per_page),
			'current' => $paged,
			'show_all' => false,
			'type' => 'plain',
		);

		$start = ($paged - 1) * $rows_per_page;
		$end_initial = $start + $rows_per_page;
		$end = (sizeof($rows) < $end_initial) ? sizeof($rows) : $end_initial;

		// if we have results
		if (count($rows) > 0) {
			// prepare link for pagination
			$link .= '&product_name=' . $filter['product_name'];

			$order = $sort['order'] == "ASC" ? "DESC" : "ASC";

			// html table head
			$html .= ' <table class="wp-list-table widefat fixed striped pages">
					<thead>
						<th>Product Name</th>
						<th >Current Price</th>
						<th >Maximum Price</th>
						<th >Minimun Price</th>
						<th >Change Amount</th>
						<th >Period</th>
						<th >Actions</th>
					</thead>
				<tbody id="porduct-list"> 
				';

			// add rows
			for ($index = $start; $index < $end;  ++$index) {

				$row = $rows[$index];
				//Get row values
				$productName = $row->productName; 
				$price       = $row->price; 
				$max_price   = $row->max_price; 
				$min_price   = $row->min_price; 
				$change      = $row->change_amount; 
				$period      = $row->periocity; 
				
				//create list of links
				$productId =  isset($row->product_id) ? $row->product_id : '';
				$act_link = add_query_arg(array('tab' => 'settings','product_id' => $productId, '_action' => '_activate'));
				$set_link = add_query_arg(array('tab' => 'settings','product_id' => $productId, '_action' => '_update'));			
				$Dea_link = add_query_arg(array( '_action' => '_deactivate', 'deactivate' => $productId) 	);

				$styleAct = '';
				$styleDea = '';
				$isActivated = isset($row->setting_id) ? true: false;

				if($isActivated){
					$styleAct = 'style="display:none"';
				}
				else 
				{
					$styleDea = 'style="display:none"';
				}

				$class_row = ($index % 2 == 1 ) ? ' class="alternate"' : '';
				$html .= '
					<tr>
						<td>' . $row->productName . '</td>
						<td>' . $row->price . '</td>
						<td>' . $max_price . '</td>
						<td>' . $min_price . '</td>
						<td>' . $change . '</td>
						<td>' . $period . '</td>
						<td class="actions-buttons">
							<a href="' . $act_link . '" '  . $styleAct . ' class="page-title-action" role="button" >Activate</a>
							<a href="' . $Dea_link . '" '  . $styleDea . ' class="page-title-action" role="button" >Deactivate</a>
							<a href="' . $set_link . '" '  . $styleDea . ' class="page-title-action" role="button" >Settings</a>
						</td>
					</tr> ';
			}

			$html .= '</tbody></table></div>';
	
			// add pagination links from WordPress
			$html .= '<div class="tablenav-pages" style="">' . paginate_links($pagination_args) . '</div';
		} else {
			$html .= '<p>' . __('No products have been found.') . '</p>';
		} // endif count($rows) 

		return $html; //phpcs:ignore
	}
	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML.
		$html      = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Pricing Settings', 'Pricing' ) . '</h2>' . "\n";
			$html .= "<script src=\"https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js\"></script>";
		$productId = '';
		$tab = '';
		$action = '';

		//phpcs:disable
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= $_GET['tab'];
		}
		
		$action .=  isset($_GET['_action'] ) ? $_GET['_action'] : '';
		
		$productId .=  isset($_GET['product_id'] ) ? $_GET['product_id'] : '';
		
		//phpcs:enable
		if(empty($productId)){
			$html .= $this->product_list_page();
		}
		else if( !(is_null($_GET['tab'] ) 
			|| empty($_GET['tab'] )		
		)){
			
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
					$tab_link = add_query_arg( 
						array( 
							'tab' => $section
						) 
					);

					if ( isset( $_GET['settings-updated'] ) ) { //phpcs:ignore
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab.
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			global $wp;
			$homeLink = home_url( $wp->request ) . '/wp-admin/options-general.php?page=pricingwp_settings';

				$html .= '<form method="post" action="' . $homeLink . '" enctype="multipart/form-data">' . "\n";

					// Get settings fields.
					ob_start();
					settings_fields( $this->parent->_token . '_settings' );
					do_settings_sections( $this->parent->_token . '_settings' );
					$html .= ob_get_clean();

					$html     .= '<p class="submit">' . "\n";
						$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
						$html .= '<input id="product_id" style="display:none" type="text" name="' . esc_attr( $this->base.'product_id' ) . '" value="' . esc_attr( $productId ) . '" />' . "\n";
						$html .= '<input id="com_action" style="display:none" type="text" name="' . esc_attr( $this->base.'com_action' ) . '" value="' . esc_attr( $action ) . '" />' . "\n";
						if($tab === 'settings'){
							$html .= '<input name="Submit" type="submit" class="page-title-action" value="' . esc_attr( __( 'Save Settings', 'Pricing' ) ) . '" />' . "\n";
						}
						$html .= '<a href="' . $homeLink . '" class=" page-title-action" role="button" >' . esc_attr( __( 'Back', 'Pricing' ) ) . '</a>' . "\n";
						
					$html     .= '</p>' . "\n";
				$html         .= '</form>' . "\n";
		}
		$html             .= '</div>' . "\n";
		$html             .= '<script type=\'text/javascript\' src=\'../wp-content/plugins/Pricing/assets/js/graphic.js\'></script>' . "\n";

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

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function save_new_configuration() {
		
		global $wpdb;

		$id = $_POST[$this->base.'product_id'];
		$d_price = $_POST[$this->base.'current_price']; //Current price - represent the original price of the product
		$start_date = date("Y/m/d");
		$min_price = $_POST[$this->base.'min_price'];
		$max_price = $_POST[$this->base.'max_price'];
		$change_amount = $_POST[$this->base.'change_amount'];
		$periocity = $_POST[$this->base.'periocity'];     
					
		$query2 = "SELECT meta_value FROM wp_postmeta pm WHERE pm.post_id = $id AND meta_key = 'total_sales'";
		$initialSales = $wpdb->get_results($query2)[0];
		$lastTotalSales = $initialSales->meta_value;

		$query = "INSERT INTO wp_product_pricing_setting (product_id,  default_price, `start_date`,  min_price,  
		max_price,  change_amount,  periocity,  initialSales,  lastTotalSales) 
		VALUES ($id, $d_price, $start_date,  $min_price,  $max_price,  $change_amount,  $periocity,  
		$lastTotalSales,  $lastTotalSales)";
		$wpdb->query($query);					

		
		$query2 = "UPDATE wp_postmeta pm SET meta_value =  $d_price WHERE pm.post_id = $id AND meta_key IN ('_price','_sale_price');";
		$lastTotalSales = $wpdb->query($query2);
		echo '<script>alert("' . "The configuration have been save." . '")</script>';
	} // End save_new_configuration()

	
	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function update_configuration() {
		global $wpdb;
		$id = $_POST[$this->base.'product_id'];
		$d_price = $_POST[$this->base.'current_price']; //Current price - represent the original price of the product
		$min_price = $_POST[$this->base.'min_price'];
		$max_price = $_POST[$this->base.'max_price'];
		$change_amount = $_POST[$this->base.'change_amount'];
		$periocity = $_POST[$this->base.'periocity'];   
		
		$query = "SELECT meta_value FROM wp_postmeta pm WHERE pm.post_id = $id AND meta_key = 'total_sales'";
		$lastTotalSales = $wpdb->get_results($query);

		
		$query2 = "UPDATE wp_postmeta pm SET meta_value =  $d_price WHERE pm.post_id = $id AND meta_key IN ('_price','_sale_price');";
		$lastTotalSales = $wpdb->query($query2);

		$wpdb->query("UPDATE wp_product_pricing_setting 
		SET min_price = $min_price, 
			max_price = $max_price,  
			change_amount = $change_amount,  
			periocity = $periocity,  
			lastTotalSales = $lastTotalSales
		WHERE product_id = $id");		
		echo '<script>alert("' . "The configuration have been save." . '")</script>';
	} // End update_configuration()

	
	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function deactivate_configuration() {
		global $wpdb;
		$id = $_GET['deactivate'];
		$wpdb->query("DELETE FROM wp_product_pricing_sales_results WHERE product_id in (SELECT results_id FROM wp_product_pricing_setting WHERE product_id = $id)");
		$wpdb->query("DELETE FROM wp_product_pricing_setting WHERE product_id = $id");
		echo '<script>alert("' . "The configuration have deactivate." . '")</script>';
	} // End deactivate_configuration()

	/**
	 * Get graphic data to display 
	 */
	public function get_grphic_display_data($productId){
		global $wp;
		$sql = "SELECT pr.sales, pr.default_price as price, pr.date 
		FROM `wp_product_pricing_sales_results` as pr 
		INNER JOIN `wp_product_pricing_setting` ps ON ps.setting_id = pr.setting_id
		WHERE ps.product_id = $productId ORDER BY pr.date ASC";
		
		$rows = $wp->get_results($sql);
		return $rows;
	}
}
