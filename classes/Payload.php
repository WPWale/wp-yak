<?php
/**
 * Contains class that processes payload
 * 
 * @package WP-Deploy
 * 
 * @author Saurabh Shukla <saurabh@yapapaya.com>
 * 
 * @since 0.1.0
 */

namespace Yapapaya\DevOps\WPD {

	/**
	 * Processes Webhook payload
	 * 
	 * @since 0.1
	 */
	class Payload {

		/**
		 * Payload contents in array format
		 *  
		 * @var array 
		 * 
		 * @since 0.1
		 */
		private $content;

		/**
		 * Deployment config
		 * 
		 * @var object
		 * 
		 * @since 0.1
		 */
		private $config;

		/**
		 * Raw payload body
		 *
		 * @var string 
		 */
		private $raw;

		/**
		 * Constructor
		 * 
		 * @param \Yapapaya\DevOps\WPD\Config $config
		 */
		public function __construct( Config $config ) {

			$this->config = $config;

		}

		/**
		 * Setup the payload content
		 * 
		 * @return boolean
		 */
		public function setup(){
			$this->load();
			
			// security checks
			if(!$this->is_IP_valid() || !$this->is_token_valid()){
				return false;
			}
			
			// return payload content for deployment
			return $this->content;
		}

		/**
		 * Loads payload content
		 * 
		 * @since 0.1
		 */
		public function load() {

			// save raw payload body for hash checks, etc
			$this->raw = file_get_contents( 'php://input' );

			// convert payload content from json to array
			$this->content = json_decode( $this->raw, true );

			// no content, no deployment
			if ( empty( $this->content ) ) {
				error(
					'400 Bad Request', 'No payload received'
				);
			}
		}

		/**
		 * Checks if IP is valid
		 * 
		 * @since 0.1
		 * 
		 * @return boolean
		 */
		public function is_IP_valid() {

			// what header to check for
			// if not specified, use the default one
			$ip_header = empty($this->config->schema[ 'ip_param' ])? 'REMOTE_ADDR': $this->config->schema[ 'ip_param' ];

			/*
			 * Note on Cloudflare and proxies
			 * ==============================
			 * 
			 * If you use cloudflare, you will get Cloudflare's IP
			 *  in 'REMOTE_ADDR'; the original IP of the remote would be
			 *  in 'HTTP_CF_CONNECTING_IP' instead.
			 * 
			 * For such proxies, find out the header that will give you
			 *  the original IP and set that in the 'ip_param' of the schema.
			 */

			// filter and get the IP
			$ip  = filter_input( \INPUT_SERVER, $ip_header );

			// IP not there can be strange, bail
			if ( empty( $ip ) ) {
				error('400 Bad Request', 'Invalid Remote IP' );
			}

			// if we don't have an ip whitelist to check against, we're good to go
			if ( empty( $this->config->schema[ 'ip_whitelist' ] ) ) {
				return true;
			}

			// otherwise, check if IP is in the whitelisted range
			if ( ! $this->cidr_match( $ip, $this->config->schema[ 'ip_whitelist' ] ) ) {
				error('400 Bad Request', 'Untrusted Remote IP' );
			}

			// IP is good, payload is good to process
			return true;
		}

		/**
		 * Checks if an IP is in an IP range
		 * 
		 * @param string $ip The IP address to check
		 * @param string $cidr The IP range to check against, in CIDR format
		 * 
		 * @since 0.1
		 * 
		 * @return boolean
		 */
		public function cidr_match( $ip, $cidr ) {
			
			// split IP range into subnet and mask
			list($subnet, $mask) = explode( '/', $cidr );

			// check if IP address is in the range
			if ( (ip2long( $ip ) & ~((1 << (32 - $mask)) - 1) ) == ip2long( $subnet ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Checks if payload token is valid
		 * 
		 * @since 0.1
		 * 
		 * @return boolean
		 */
		public function is_token_valid() {

			// no token specified, bail
			if ( empty( $this->config->repo[ 'token' ] ) ) {
				return true;
			}

			// otherwise, get the token header param from schema
			$token_header = $this->config->schema[ 'token']['header' ];

			// no header, bail
			if ( empty( $token_header ) ) {
				return true;
			}

			// filter and get token
			$token  = filter_input( \INPUT_SERVER, $token_header );

			// assume token is invalid
			$valid = false;

			// get the hash algorithm from schema
			$hash_algo = $this->config->schema[ 'token']['hashed'];

			// no hash algorithm means token is plain text
			if ( empty($hash_algo) ) {
				return ($token === $this->config->repo[ 'token' ]);
			}

			// otherwise, split the token into hash and hashed token
			list($algo, $provided_hash) = explode('=', $token, 2) + array('', '');

			// is the hash algorithm available to the system?
			if (!in_array($algo, hash_algos(), TRUE)) {
				error('400 Bad Request',"Hash algorithm '$algo' is not supported.");
			}

			// calculate the hash using the token and the raw body
			$computed_hash = hash_hmac( $provided_hash, $this->raw, $this->config->repo[ 'token' ] );

			// the token either checks out or doesn't
			return hash_equals( $provided_hash, $computed_hash );
		}

	}

}