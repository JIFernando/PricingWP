<?php
/**
 * Main plugin class file.
 *
 * @package WordPress Plugin Template/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Pricing {

	/**
	 * The single instance of WordPress_Plugin_Template.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * Local instance of Pricing_Admin_API
	 *
	 * @var Pricing_Admin_API|null
	 */
	public $admin = null;

	/**
	 * Settings class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version; //phpcs:ignore

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token; //phpcs:ignore

	/**
	 * The main plugin file.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor funtion.
	 *
	 * @param string $file File constructor.
	 * @param string $version Plugin version.
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token   = 'pricingwp';

		// Load plugin environment variables.
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Load API for generic admin functions.
		if ( is_admin() ) {
			$this->admin = new Pricing_Admin_API();
		}

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Register post type function.
	 *
	 * @param string $post_type Post Type.
	 * @param string $plural Plural Label.
	 * @param string $single Single Label.
	 * @param string $description Description.
	 * @param array  $options Options array.
	 *
	 * @return bool|string|Pricing_Post_Type
	 */
	public function register_post_type( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) {
			return false;
		}

		$post_type = new Pricing_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $plural Plural Label.
	 * @param string $single Single Label.
	 * @param array  $post_types Post types to register this taxonomy for.
	 * @param array  $taxonomy_args Taxonomy arguments.
	 *
	 * @return bool|string|Pricing_Taxonomy
	 */
	public function register_taxonomy( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) {
			return false;
		}

		$taxonomy = new Pricing_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 *
	 * @access  public
	 * @return void
	 * @since   1.0.0
	 */
	public function enqueue_styles() {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function enqueue_scripts() {
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Admin enqueue style.
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'wordpress-plugin-template', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = 'wordpress-plugin-template';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main WordPress_Plugin_Template Instance
	 *
	 * Ensures only one instance of WordPress_Plugin_Template is loaded or can be loaded.
	 *
	 * @param string $file File instance.
	 * @param string $version Version parameter.
	 *
	 * @return Object WordPress_Plugin_Template instance
	 * @see WordPress_Plugin_Template()
	 * @since 1.0.0
	 * @static
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of WordPress_Plugin_Template is forbidden' ) ), esc_attr( $this->_version ) );

	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of WordPress_Plugin_Template is forbidden' ) ), esc_attr( $this->_version ) );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function install() {
		$this->_log_version_number();
		$this->_create_db_structure();
	} // End install ()

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function _log_version_number() { //phpcs:ignore
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

	/**
	 * Create database structure.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function _create_db_structure () {
		global $wpdb;

		$sql ="CREATE TABLE IF NOT EXISTS {$wpdb->prefix}product_pricing_setting(
			`setting_id` BIGINT NOT NULL AUTO_INCREMENT, 
			`product_id` BIGINT NOT NULL,
			`default_price` DECIMAL,
			`start_date` DATETIME,
			`min_price` DECIMAL,
			`max_price` DECIMAL,
			`change_amount` DECIMAL,
			`periocity` INTEGER,
			`initialSales` BIGINT NOT NULL,
			`lastTotalSales` BIGINT NULL, -- last period
			PRIMARY KEY (`setting_id`),
			FOREIGN KEY (`product_id`) REFERENCES wp_posts(id));";
		$wpdb->query($sql); 
			
		$sql1 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}product_pricing_sales_results(
			`results_id` BIGINT NOT NULL AUTO_INCREMENT, 
			`setting_id` BIGINT NOT NULL,
			`date` DATETIME NOT NULL,
			`price` DECIMAL,
			`periocity` INTEGER,
			`sales` BIGINT NULL,
			PRIMARY KEY (`results_id`),
			FOREIGN KEY (`setting_id`) REFERENCES {$wpdb->prefix}product_pricing_setting(setting_id));";
		$wpdb->query($sql1);   
			
		$sql2 = "CREATE FUNCTION IF NOT EXISTS {$wpdb->prefix}Tendence (
					productId BIGINT,
					periocity INTEGER,
					sDate DATETIME
				)
			RETURNS DECIMAL
			DETERMINISTIC
			BEGIN
				DECLARE mediaSales1 double(10,2);
				DECLARE	mediaSales2 double(10,2);
				DECLARE	mediaDates1 double(10,2);
				DECLARE	mediaDates2 double(10,2);		
				DECLARE	solution DECIMAL(10,2);
				SET mediaSales1 = (SELECT AVG(sales) FROM {$wpdb->prefix}product_pricing_sales_results r
								WHERE  r.date BETWEEN DATE_SUB(sDate, INTERVAL periocity * 2 DAY) 
								AND DATE_SUB(sDate, INTERVAL periocity DAY) AND product_id = productId);
				SET mediaDates1 = (SELECT AVG(UNIX_TIMESTAMP(selected_date)) FROM 
								(SELECT ADDDATE('1970-01-01',t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) selected_date FROM
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v
								WHERE selected_date BETWEEN DATE_SUB(sDate, INTERVAL periocity * 2 DAY) AND DATE_SUB(sDate, INTERVAL periocity DAY));
				SET mediaSales2 = (SELECT AVG(sales) FROM {$wpdb->prefix}product_pricing_sales_results r
								WHERE  r.date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL periocity DAY) 
								AND CURRENT_DATE() AND {$wpdb->prefix}product_id = productId);
				SET mediaDates2 = (SELECT AVG(UNIX_TIMESTAMP(selected_date)) FROM 
								(SELECT ADDDATE('1970-01-01',t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) selected_date FROM
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,
								(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v
								WHERE selected_date BETWEEN DATE_SUB(sDate, INTERVAL periocity DAY) AND sDate);
				SET solution = ((mediaSales2 - mediaSales1) / (mediaDates2 - mediaDates1) * (unix_timestamp(CURRENT_DATE()) - mediaDates1)) + mediaSales1;
				RETURN solution; 
		
			END;";
		$wpdb->query($sql2); 
			
		$sql3 = "CREATE FUNCTION IF NOT EXISTS {$wpdb->prefix}GetNewPrice (price DECIMAL, changeAmount DECIMAL, minPrice DECIMAL, maxPrice DECIMAL, tendence0 DECIMAL, tendence1 DECIMAL)
			RETURNS DECIMAL
			DETERMINISTIC
			BEGIN
				DECLARE newPrice DECIMAL;
				IF (tendence0 <= tendence1 AND maxPrice >= price + changeAmount)
				THEN
					-- Positive number of sales => Increase value
					SET newPrice = changeAmount + price;		
				ELSEIF (maxPrice < price - changeAmount)
				THEN 
					SET newPrice = maxPrice;			
				ELSEIF (minPrice <= price - changeAmount)
				THEN
					-- Negative number of sales => Decrease value
					SET newPrice = price - changeAmount;		
				ELSEIF (minPrice > price - changeAmount)
				THEN 		
					SET newPrice = minPrice;
				END IF;
				RETURN newPrice;
			END;"; 
		$wpdb->query($sql3);  
			
		$sql4 = "CREATE PROCEDURE IF NOT EXISTS {$wpdb->prefix}CalculateNextPrices()
			BEGIN
			DECLARE done INT DEFAULT FALSE;
			
			DECLARE productId BIGINT;
			DECLARE settingId BIGINT;
			DECLARE maxResult BIGINT;
			DECLARE tendenceP1 DECIMAL; 
			DECLARE tendenceP2 DECIMAL;
			DECLARE newPrice DECIMAL;
			DECLARE sales BIGINT;
			DECLARE price DECIMAL;
			DECLARE periocity INT;
			DECLARE productCursor CURSOR FOR SELECT p.product_id, p.periocity
												FROM {$wpdb->prefix}product_pricing_setting as p 
												WHERE p.periocity * 3 <= (SELECT COUNT(1) FROM product_pricing_sales_results as r WHERE r.setting_id = p.setting_id);
												
			DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
		
			OPEN productCursor;
			read_loop: LOOP
				FETCH productCursor INTO productId, price, settingId, periocity;
				IF done THEN
				LEAVE read_loop;
				END IF;
									
				-- Get taotal ammount of sales during the current period	
				SET sales = (SELECT meta_value FROM wp_postmeta WHERE meta_key = 'total_sales' AND post_id = productId LIMIT 1);
								
				SET tendenceP1 = (SELECT {$wpdb->prefix}Tendence(productId,periocity, (SELECT DATE_SUB(CURRENT_DATE(), INTERVAL periocity DAY))));		
				SET tendenceP2 = (SELECT {$wpdb->prefix}Tendence(productId,periocity, CURRENT_DATE()));	
				
				SET newPrice = (SELECT {$wpdb->prefix}GetNewPrice(price, changeAmount, minPrice, maxPrice, tendenceP1, tendenceP2));
					
				UPDATE wp_postmeta
				SET meta_value = (SELECT CONVERT(price, CHAR))
				WHERE  post_id = productId AND meta_key IN ('_price','_sale_price');
					
			END LOOP;
		
			CLOSE productCursor;
			END;";        
		$wpdb->query($sql4); 

		$sql5 = "CREATE PROCEDURE IF NOT EXISTS {$wpdb->prefix}CalculateSales()
			BEGIN
			DECLARE s_id BIGINT;
			DECLARE p_id BIGINT;
			DECLARE lastTotalSales BIGINT;
			DECLARE currentPeriodSales BIGINT;
		
			DECLARE salesCursor CURSOR FOR SELECT p.product_id, p.setting_id, p.lastTotalSales
				FROM {$wpdb->prefix}product_pricing_setting as p 
				INNER JOIN wp_postmeta as pm ON pm.post_id = product.product_id
				WHERE p.periocity * 2 <= (SELECT COUNT(1) FROM {$wpdb->prefix}product_pricing_sales_results as r WHERE r.setting_id = p.setting_id);
			OPEN salesCursor;
			read_loop: LOOP
				FETCH salesCursor INTO p_id, s_id, lastTotalSales;
				IF done THEN
				LEAVE read_loop;
				END IF;
									
				-- Get taotal ammount of sales during the current period	
				SET currentPeriodSales = (SELECT meta_value - lastTotalSales FROM wp_postmeta WHERE meta_key = 'total_sales' AND post_id = productId LIMIT 1);
				
				-- Update the current total sales for the next day
				UPDATE {$wpdb->prefix}product_pricing_setting
				SET lastTotalSales = (SELECT meta_value FROM wp_postmeta WHERE meta_key = 'total_sales' AND post_id = productId LIMIT 1)
				WHERE setting_id = s_id;
						
				-- Save the results 		
				INSERT INTO {$wpdb->prefix}product_pricing_sales_results (`setting_id`,`date`, `sales`, `price`)
				VALUES(s_id, CURRENT_DATE(), currentPeriodSales, (SELECT CAST(meta_value as DECIMAL) FROM wp_postmeta WHERE  post_id = p_id AND meta_key = '_price'));
				
			END LOOP;
		
			CLOSE salesCursor;
			END;"; 
		$wpdb->query($sql5); 
			
		$sql6 = "CREATE event IF NOT EXISTS {$wpdb->prefix}daily_calculation_event ON schedule every 1 day starts (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY)  do CALL {$wpdb->prefix}CalculateSales();";         
		$wpdb->query($sql6); 
		$sql7 = "CREATE event IF NOT EXISTS {$wpdb->prefix}daily_prices_calculation_event ON schedule every 1 day starts (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 1 HOUR)  do CALL {$wpdb->prefix}CalculateNextPrices();";         
		$wpdb->query($sql7);    
    
	}
}
