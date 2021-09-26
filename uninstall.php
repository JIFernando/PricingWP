<?php
/**
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Ideally you will add all your clean-up scripts here
 * that will clean-up unused meta, options, etc. in the database.
 *
 * @package WordPress Plugin Template/Uninstall
 */

// If plugin is not being uninstalled, exit (do nothing).
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	global $wpdb;

    $sql ="DROP EVENT IF EXISTS {$wpdb->prefix}daily_calculation_event;";
    $sql1 ="DROP EVENT IF EXISTS {$wpdb->prefix}daily_prices_calculation_event;";
    $sql2 ="DROP PROCEDURE IF EXISTS {$wpdb->prefix}CalculateSales;";
    $sql3 ="DROP PROCEDURE IF EXISTS {$wpdb->prefix}CalculateNextPrices;";
    $sql4 ="DROP FUNCTION IF EXISTS {$wpdb->prefix}GetNewPrice;";
    $sql5 ="DROP FUNCTION IF EXISTS {$wpdb->prefix}Tendence;";
    $sql6 ="DROP TABLE IF EXISTS {$wpdb->prefix}product_pricing_sales_results;";
    $sql7 ="DROP TABLE IF EXISTS {$wpdb->prefix}product_pricing_setting;";

     $wpdb->query($sql);    
	 $wpdb->query($sql1);  
	 $wpdb->query($sql2);   
	 $wpdb->query($sql3);   
	 $wpdb->query($sql4);   
	 $wpdb->query($sql5);   
	 $wpdb->query($sql6);    
	 $wpdb->query($sql7);    
	 exit;
	 //die();
}

// Do something here if plugin is being uninstalled.
