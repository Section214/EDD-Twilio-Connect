<?php
/**
 * Plugin Name:		Easy Digital Downloads - Twilio Connect
 * Plugin URI:		https://easydigitaldownloads.com/extension/twilio-connect
 * Description:		Get real-time SMS notifications from Twilio when you make sales!
 * Version:			1.1.0
 * Author:			Daniel J Griffiths
 * Author URI:		http://section214
 * Text Domain:		edd-twilio-connect
 *
 * @package			EDD\TwilioConnect
 * @author			Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright		Copyright (c) 2014, Daniel J Griffiths
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_Twilio_Connect' ) ) {

	/**
	 * Main EDD_Twilio_Connect class
	 *
	 * @since		1.0.0
	 */
	class EDD_Twilio_Connect {

		/**
		 * @var			EDD_Twilio_Connect $instance The one true EDD_Twilio_Connect
		 * @since		1.0.0
		 */
		private static $instance;

		/**
		 * Get active instance
		 *
		 * @access		public
		 * @since		1.0.1
		 * @return		object self::$instance The one true EDD_Twilio_Connect
		 */
		public static function instance() {
			if( !self::$instance ) {
				self::$instance = new EDD_Twilio_Connect();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access		private
		 * @since		1.0.1
		 * @return		void
		 */
		public function setup_constants() {
			// Plugin path
			define( 'TWILIO_CONNECT_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'TWILIO_CONNECT_URL', plugin_dir_url( __FILE__ ) );

			// Plugin version
			define( 'TWILIO_CONNECT_VER', '1.1.0' );
		}


		/**
		 * Include necessary files
		 *
		 * @access		private
		 * @since		1.1.0
		 * @return		void
		 */
		private function includes() {

		}


		/**
		 * Run action and filter hooks
		 *
		 * @access		private
		 * @since		1.0.1
		 * @return		void
		 */
		private function hooks() {
			// Edit plugin metalinks
			add_filter( 'plugin_row_meta', array( $this, 'plugin_metalinks' ), null, 2 );

			// Handle license
			if( class_exists( 'EDD_License' ) ) {
				$license = new EDD_License( __FILE__, 'Twilio Connect', TWILIO_CONNECT_VER, 'Daniel J Griffiths' );
			}

			// Register settings
			add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

			// Build message
			add_action( 'edd_complete_purchase', array( $this, 'build_sms'), 100, 1 );
		}


		/**
		 * Internationalization
		 *
		 * @access		public
		 * @since		1.0.0
		 * @return		void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'EDD_Twilio_Connect_lang_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale		= apply_filters( 'plugin_locale', get_locale(), '' );
			$mofile		= sprintf( '%1$s-%2$s.mo', 'edd-twilio-connect', $locale );

			// Setup paths to current locale file
			$mofile_local	= $lang_dir . $mofile;
			$mofile_global	= WP_LANG_DIR . '/edd-twilio-connect/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-twilio-connect/ folder
                load_textdomain( 'edd-twilio-connect', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-twilio-connect/languages/ folder
                load_textdomain( 'edd-twilio-connect', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-twilio-connect', false, $lang_dir );
            }
		}


        /**
         * Modify plugin metalinks
         *
         * @access      public
         * @since       1.1.0
         * @param       array $links The current links array
         * @param       string $file A specific plugin table entry
         * @return      array $links The modified links array
         */
        public function plugin_metalinks( $links, $file ) {
            if( $file == plugin_basename( __FILE__ ) ) {
                $help_link = array(
                    '<a href="https://easydigitaldownloads.com/support/forum/add-on-plugins/twilio-connect/" target="_blank">' . __( 'Support Forum', 'edd-balanced-gateway' ) . '</a>'
                );

                $docs_link = array(
                    '<a href="http://section214.com/docs/category/edd-twilio-connect/" target="_blank">' . __( 'Docs', 'edd-balanced-gateway' ) . '</a>'
                );

                $links = array_merge( $links, $help_link, $docs_link );
            }

            return $links;
		}


		/**
		 * Add settings
		 *
		 * @access		public
		 * @since		1.0.0
		 * @param		array $settings the existing plugin settings
		 * @return		array
		 */
		public function settings( $settings ) {
			$new_settings = array(
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

			return array_merge( $settings, $new_settings );
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
			if( edd_get_option( 'edd_twilio_connect_account_sid' ) && edd_get_option( 'edd_twilio_connect_auth_token' ) ) {

				$payment_meta	= edd_get_payment_meta( $payment_id );

				$cart_items		= isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

				if( empty( $cart_items ) || !$cart_items )
					$cart_items = maybe_unserialize( $payment_meta['downloads'] );

				if( $cart_items ) {
					$i = 0;

					$message = __( 'New Order', 'edd-twilio-connect' ) . ' @ ' . get_bloginfo( 'name' ) . urldecode( '%0a' );

					if( edd_get_option( 'edd_twilio_connect_itemize' ) ) {
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
			if( edd_get_option( 'edd_twilio_connect_account_sid' ) && edd_get_option( 'edd_twilio_connect_auth_token' ) ) {

				require_once ( dirname( __FILE__ ) . '/includes/twilio-php/Twilio.php' );

				$account_sid = edd_get_option( 'edd_twilio_connect_account_sid', '' );
				$auth_token = edd_get_option( 'edd_twilio_connect_auth_token', '' );

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
			if( edd_get_option( 'edd_twilio_connect_account_sid' ) && edd_get_option( 'edd_twilio_connect_auth_token' ) && edd_get_option( 'edd_twilio_connect_number' ) && edd_get_option( 'edd_twilio_connect_phone_number' ) ) {

				require_once ( dirname( __FILE__ ) . '/includes/twilio-php/Twilio.php' );

				$account_sid = edd_get_option( 'edd_twilio_connect_account_sid' );
				$auth_token = edd_get_option( 'edd_twilio_connect_auth_token' );
				$twilio_number = edd_get_option( 'edd_twilio_connect_number' );
				$phone_numbers = explode( ',', edd_get_option( 'edd_twilio_connect_phone_number' ) );

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


/**
 * The main function responsible for returning the one true EDD_Twilio_Connect
 * instance to functions everywhere
 *
 * @since		1.0.0
 * @return		EDD_Twilio_Connect The one true EDD_Twilio_Connect
 */
function EDD_Twilio_Connect_load() {
	if( !class_exists( 'Easy_Digital_Downloads' ) ) {
		deactivate_plugins( __FILE__ );
		unset( $_GET['activate'] );

		// Display notice
		add_action( 'admin_notices', 'EDD_Twilio_Connect_missing_edd_notice' );
	} else {
		return EDD_Twilio_Connect::instance();
	}
}
add_action( 'plugins_loaded', 'EDD_Twilio_Connect_load' );


/**
 * We need Easy Digital Downloads... if it isn't present, notify the user!
 *
 * @since		1.1.0
 * @return		void
 */
function EDD_Twilio_Connect_missing_edd_notice() {
	echo '<div class="error"><p>' . __( 'Twilio Connect requires Easy Digital Downloads! Please install it to continue!', 'edd-twilio-connect' ) . '</p></div>';
}
