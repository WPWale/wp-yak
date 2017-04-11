<?php
/**
 * Contains class that maps payload with configuration
 * 
 * @package WP-Deploy
 * 
 * @author Saurabh Shukla <saurabh@yapapaya.com>
 * 
 * @since 0.1.0
 */

namespace Yapapaya\DevOps\WPD {

	/**
	 * Maps payload to configuration
	 */
	class Map {

		/**
		 * Initialise
		 * 
		 * @param \Yapapaya\DevOps\WPD\Config $config
		 * @param array $payload
		 * 
		 * @since 0.1
		 */
		public function init( Config $config, $payload ) {
			
			// if the payload doesn't match our deploy config, there's nothing to do
			if(!$this->is_branch( $config, $payload ) || !$this->is_right_branch( $config, $payload )){
				error('200 OK', 'No deploy configured for this ref');
			}
			
		}
		
		/**
		 * Checks if the webhook was for a branch
		 * 
		 * @param \Yapapaya\DevOps\WPD\Config $config
		 * @param array $payload
		 * 
		 * @since 0.1
		 * 
		 * @return boolean
		 */
		public function is_branch(Config $config, $payload){
			
			// get the parameter with the ref type (branch or tag) from the payload
			$ref_type_param = $this->get_nested_array_value( $payload, $config->schema[ 'ref']['param' ] );
			
			$matches = array();
			
			// get the regex pattern for ref type from schema
			$pattern = $config->schema[ 'ref']['pattern' ];
			
			// match to get the ref type
			preg_match( $pattern, $ref_type_param, $matches );

			// could not get a ref type, this payload is not meant for deployment
			if ( !isset( $matches[ 0 ] ) || empty( $matches[ 0 ] ) ) {
				return false;
			}

			return true;
		}
		
		/**
		 * Checks if webhook was for the configured branch
		 * 
		 * @param \Yapapaya\DevOps\WPD\Config $config
		 * @param array $payload
		 * 
		 * @since 0.1
		 * 
		 * @return boolean
		 */
		public function is_right_branch(Config $config, $payload){
			
			// get the parameter with the branch name from the payload
			$ref_name_param = $this->get_nested_array_value( $payload, $config->schema[ 'branch_name']['param' ] );

			$matches = array();

			// get the regex pattern for the branch name from schema
			$pattern = $config->schema[ 'branch_name']['pattern' ];

			// match to get the branch name
			preg_match( $pattern, $ref_name_param, $matches );

			// branch name wasn't found, payload not meant for us
			if ( !isset( $matches[ 1 ] ) || empty( $matches[ 1 ] ) ) {
				return false;
			}
			
			// branch name found but is different from the config, payload not meant for us
			if($matches[1]!== $config->repo['branch']){
				return false;
			}
			
			// otherwise, the payload is for the configured branch
			// and we need to deploy! :D
			return true;
		}

		/**
		 * Gets the value of a nested key in payload
		 * 
		 * @param array $array
		 * @param array $pathParts
		 * @since 0.1
		 * @return string
		 */
		public function get_nested_array_value( $array, $pathParts ) {

			$current = $array;
			foreach ( $pathParts as $key ) {
				$current = &$current[ $key ];
			}

			return $current;
		}

	}

}