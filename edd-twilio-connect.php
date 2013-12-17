<?php
/*
Plugin Name: Easy Digital Downloads - Twilio Connect
Plugin URI: https://easydigitaldownloads.com/extension/twilio-connect
Description: Get real-time SMS notifications from Twilio when you make sales!
Version: 1.0.1
Author: Daniel J Griffiths
Author URI: http://ghost1227.com
*/

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Twilio_Connect' ) ) {

	class EDD_Twilio_Connect {

		private static $instance;


		/**
		 * Get active instance
		 *
		 * @since		1.0.1
		 * @access		public
		 * @static
		 * @return		object self::$instance
		 */
		public static function get_instance() {
			if( !self::$instance )
				self::$instance = new EDD_Twilio_Connect();

			return self::$instance;
		}


		/**
		 * Class constructor
		 *
		 * @since		1.0.1
		 * @access		public
		 * @return		void
		 */
		public function __construct() {
			// Load our custom updater
			if( !class_exists( 'EDD_License' ) )
				include( dirname( __FILE__ ) . '/includes/EDD_License_Handler.php' );

			$this->init();
		}


		/**
		 * Run action and filter hooks
		 *
		 * @since		1.0.1
		 * @access		private
		 * @return		void
		 */
		private function init() {
			// Make sure EDD is active
			if( !class_exists( 'Easy_Digital_Downloads' ) ) return;

			global $edd_options;

			// Internationalization
			add_action( 'init', array( $this, 'textdomain' ) );

			// Register settings
			add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

			// Handle license
			$license = new EDD_License( __FILE__, 'Twilio Connect', '1.0.1', 'Daniel J Griffiths' );

			// Build message
			add_action( 'edd_complete_purchase', array( $this, 'build_sms'), 100, 1 );
		}


		/**
		 * Internationalization
		 *
		 * @access		private
		 * @since		1.0.0
		 * @return		void
		 */
		private function textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_twilio_connect_lang_directory', $lang_dir );

			// Load translations
			load_plugin_textdomain( 'edd-twilio-connect', false, $lang_dir );
		}


		/**
		 * Add settings
		 *
		 * @access		private
		 * @since		1.0.0
		 * @param		array $settings the existing plugin settings
		 * @return		array
		 */
		private function settings( $settings ) {
			$edd_twilio_connect_settings = array(
				array(
					'id'	=> 'edd_twilio_connect_settings',
					'name'	=> '<strong>' . __( 'Twilio Connect Settings', 'edd-twilio-connect' ) . '</strong>',
					'desc'	=> __( 'Configure Twilio Settings', 'edd-twilio-connect' ),
					'type'	=> 'header'
				),
				array(
					'id'	=> 'edd_twilio_connect_account_sid',
					'name'	=> __( 'Account SID', 'edd-twilio-connect' ),
					'desc'	=> __( 'Enter your Twilio account SID (available on the <a href="https://www.twilio.com/user/account" target="_blank">account page</a>)' ),
					'type'	=> 'text',
					'size'	=> 'regular'
				),
				array(
					'id'	=> 'edd_twilio_connect_auth_token',
					'name'	=> __( 'Auth Token', 'edd-twilio-connect' ),
					'desc'	=> __( 'Enter your Twilio auth token (available on the <a href="https://www.twilio.com/user/account" target="_blank">account page</a>)' ),
					'type'	=> 'text',
					'size'	=> 'regular'
				),
				array(
					'id'	=> 'edd_twilio_connect_number',
					'name'	=> __( 'Twilio Number', 'edd-twilio-connect' ),
					'desc'	=> __( 'Select the number you want to send through', 'edd-twilio-connect' ),
					'type'	=> 'select',
					'options'	=> $this->get_numbers()
				),
				array(
					'id'	=> 'edd_twilio_connect_phone_number',
					'name'	=> __( 'Phone Number', 'edd-twilio-connect' ),
					'desc'	=> __( 'Enter the number(s) you want messages delivered to, formatted as \'+xxxxxxxxxx\' and comma separated', 'edd-twilio-connect' ),
					'type'	=> 'text',
					'size'	=> 'regular'
				),
				array(
					'id'	=> 'edd_twilio_connect_itemize',
					'name'	=> __( 'Itemized Notification', 'edd-twilio-connect' ),
					'desc'	=> __( 'Select whether or not you want itemized SMS notifications', 'edd-twilio-connect' ),
					'type'	=> 'checkbox'
				)
			);

			return array_merge( $settings, $edd_twilio_settings );
		}


		/**
		 * Build the message to be passed to Twilio
		 *
		 * @access		public
		 * @since		1.0.0
		 * @param		string $payment_id
		 * @return		void
		 */
		public function build_sms( $payment_id ) {
			global $edd_options;

			if( !empty( $edd_options['edd_twilio_connect_account_sid'] ) && !empty( $edd_options['edd_twilio_connect_auth_token'] ) ) {

				$payment_meta	= edd_get_payment_meta( $payment_id );

				$cart_items		= isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

				if( empty( $cart_items ) || !$cart_items )
					$cart_items = maybe_unserialize( $payment_meta['downloads'] );

				if( $cart_items ) {
					$i = 0;

					$message = __( 'New Order', 'edd-twilio-connect' ) . ' @ ' . get_bloginfo( 'name' ) . urldecode( '%0a' );

					if( $edd_options['edd_twilio_connect_itemize'] ) {
						foreach( $cart_items as $key => $cart_item ) {
							$id = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
							$price_override = isset( $payment_meta['cart_details'] ) ? $cart_item['price'] : null;
							$price = edd_get_download_final_price( $id, $user_info, $price_override );

							$message .= get_the_title( $id );

							if( isset( $cart_items[$key]['item_number'] ) ) {
								$price_options = $cart_items[$key]['item_number']['options'];

								if( isset( $price_options['price_id'] ) )
									$message .= ' - ' . edd_get_price_option_name( $id, $price_options['price_id'], $payment_id );
							}

							$message .= ' - ' . html_entity_decode( edd_currency_filter( edd_format_amount( $price ) ) ) . urldecode( '%0a' );
						}
					}

					$message .= __( 'TOTAL', 'edd-twilio-connect' ) . ' - ' . html_entity_decode( edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment_id ) ) ) );

					if( strlen( $message ) > 160 ) {
						$messages = str_split( $message, 140 );
						$max = count( $messages );
						$count = 1;

						foreach( $messages as $message ) {
							$message = $count . '/' . $max . urldecode( '%0a' ) . $message;
							$this->send_sms( $message );
						}
					} else {
						$this->send_sms( $message );
					}
				}
			}
		}


		/**
		 * Get valid numbers from Twilio API
		 *
		 * @access		public
		 * @since		1.0.0
		 * @return		array
		 */
		function get_numbers() {
			global $edd_options;

			if( !empty( $edd_options['edd_twilio_connect_account_sid'] ) && !empty( $edd_options['edd_twilio_connect_auth_token'] ) ) {

				require_once ( dirname( __FILE__ ) . '/includes/twilio-php/Twilio.php' );

				$account_sid = $edd_options['edd_twilio_connect_account_sid'];
				$auth_token = $edd_options['edd_twilio_connect_auth_token'];

				try {
					$client = new Services_Twilio( $account_sid, $auth_token );

					foreach ($client->account->incoming_phone_numbers as $twilio_connect_number) {
						$numbers[$twilio_connect_number->phone_number] = $twilio_connect_number->friendly_name;
					}

					return $numbers;
				} catch( Exception $e ) {
					return array();
				}
			}

			return array( '', __( 'Please enter valid API details!', 'edd-twilio-connect' ) );
		}


		/**
		 * Send an SMS
		 *
		 * @access		public
		 * @since		1.0.0
		 * @param		string $message the message to send
		 * @return		void
		 */
		function send_sms( $message ) {
			global $edd_options;

			if( !empty( $edd_options['edd_twilio_connect_account_sid'] ) && !empty( $edd_options['edd_twilio_connect_auth_token'] ) && !empty( $edd_options['edd_twilio_connect_number'] ) && !empty( $edd_options['edd_twilio_connect_phone_number'] ) ) {

				require_once ( dirname( __FILE__ ) . '/includes/twilio-php/Twilio.php' );

				$account_sid = $edd_options['edd_twilio_connect_account_sid'];
				$auth_token = $edd_options['edd_twilio_connect_auth_token'];
				$twilio_number = $edd_options['edd_twilio_connect_number'];
				$phone_numbers = explode( ',', $edd_options['edd_twilio_connect_phone_number'] );

				foreach( $phone_numbers as $phone_number ) {
					try {
						$client = new Services_Twilio( $account_sid, $auth_token );

						$data = $client->account->sms_messages->create(
							$twilio_number,
							$phone_number,
							$message
						);
					} catch( Exception $e ) {
						return;
					}
				}
			}
		}
	}
}


function edd_twilio_connect_load() {
	$edd_twilio_connect = new EDD_Twilio_Connect();
}
add_action( 'plugins_loaded', 'edd_twilio_connect_load' );
