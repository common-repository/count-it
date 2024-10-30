<?php
	/**
	 * Plugin Name: Count-IT
	 * Plugin URI: https://www.count-it.eu/koppeling/woocommerce/
	 * Description: Improvements for working with Count-IT
	 * Version: 1.0.1
	 * Author: Count-IT
	 * Author URI: https://www.count-it.eu
	 */

	//Don't disable the webhook
	function countit_overrule_webhook_disable_limit($number){
		return 10000;
	}
	add_filter('woocommerce_max_webhook_delivery_failures', 'countit_overrule_webhook_disable_limit');

	//Add custom listener to handle the request
	function countit_webhook_listener_custom($http_args, $response, $duration, $arg, $id){
		$responseCode = wp_remote_retrieve_response_code($response);
		//Check response code
		if($responseCode < 200 || $responseCode > 299){
			$webhook = new WC_Webhook($id);
			//Check if webhook is from Count-IT domain
			if($webhook->get_id() > 0 && preg_match('/^https:\/\/[a-z\-.]+.count-it.eu\//', $webhook->get_delivery_url())){
				//If failed, retry every 5 minutes
				$timestamp = new DateTime('+5 minutes');
				$argsArray = array('webhook_id' => $id, 'arg' => $arg);
				WC()->queue()->schedule_single($timestamp, 'woocommerce_deliver_webhook_async', $argsArray, 'count-it-webhooks');
			}
		}
	}
	add_action('woocommerce_webhook_delivery', 'countit_webhook_listener_custom', 10, 5);

//	function cit_dump($var, $return = false){
//		if($return) ob_start();
//		echo '<pre>';
//			if(is_array($var)) print_r($var);
//			else var_dump($var);
//		echo '</pre>';
//		if($return) return ob_get_clean();
//	}
//	// LOAD THE WC LOGGER
//	$logger = wc_get_logger();
//	// LOG NEW PRICE TO CUSTOM "price-changes" LOG
//	$logger->info('Test logging: ' .  PHP_EOL . cit_dump(array(1 => 'a', 2 => 'b'), true), array('source' => 'count-it'));