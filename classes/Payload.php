<?php

namespace Yapapaya\DevOps\WPD {

	class Payload {

		private $content;
		private $config;
		private $raw;

		public function __construct( $config ) {

			$this->config = $config;
	
		}
		
		public function setup(){
			$this->load();
			
			if(!$this->is_IP_valid() || !$this->is_token_valid()){
				return false;
			}
			
			return $this->content;
		}

		/**
		 * @since 0.1
		 */
		public function load() {
			// 
			$this->raw = file_get_contents( 'php://input' );

			$this->content = json_decode( $this->raw, true );

			if ( empty( $this->content ) ) {
				error(
					'400 Bad Request', 'No payload received'
				);
			}
		}

		/**
		 * 
		 * @param type $ip
		 * @param type $protocol
		 * @since 0.1
		 * @return boolean
		 */
		public function is_IP_valid() {
			
			$ip_header = empty($this->config->schema[ 'ip_param' ])? 'REMOTE_ADDR': $this->config->schema[ 'ip_param' ];
			
			$ip = $_SERVER[ $ip_header ];
			
			if ( empty( $ip ) ) {
				error('400 Bad Request', 'Invalid Remote IP' );
			}

			if ( empty( $this->config->schema[ 'ip_whitelist' ] ) ) {
				return true;
			}
			
			if ( ! $this->cidr_match( $ip, $this->config->schema[ 'ip_whitelist' ] ) ) {
				error('400 Bad Request', 'Untrusted Remote IP' );
			}

			return true;
		}

		/**
		 * 
		 * @param type $ip
		 * @param type $cidr
		 * @since 0.1
		 * @return boolean
		 */
		public function cidr_match( $ip, $cidr ) {
			list($subnet, $mask) = explode( '/', $cidr );

			if ( (ip2long( $ip ) & ~((1 << (32 - $mask)) - 1) ) == ip2long( $subnet ) ) {
				return true;
			}

			return false;
		}

		/**
		 * @since 0.1
		 * @return boolean
		 */
		public function is_token_valid() {

			if ( empty( $this->config->repo[ 'token' ] ) ) {
				return true;
			}

			$token_header = $this->config->schema[ 'token']['header' ];

			if ( empty( $token_header ) ) {
				return true;
			}

			$valid = false;
			
			$hash_algo = $this->config->schema[ 'token']['hashed'];

			if ( empty($hash_algo) ) {
				return ($_SERVER[ $token_header ] === $this->config->repo[ 'token' ]);
			}
			
			list($algo, $provided_hash) = explode('=', $_SERVER[$token_header], 2) + array('', '');
			
			if (!in_array($algo, hash_algos(), TRUE)) {
				error('400 Bad Request',"Hash algorithm '$algo' is not supported.");
			}
			
			$computed_hash = hash_hmac( $provided_hash, $this->raw, $this->config->repo[ 'token' ] );
			
			return hash_equals( $provided_hash, $computed_hash );
		}

	}

}