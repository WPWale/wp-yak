<?php

/**
 * Contains class that maps payload with configuration
 * 
 * @package WP-Yak
 * 
 * @author Saurabh Shukla <saurabh@yapapaya.com>
 * 
 * @since 0.1.0
 * 
 * @todo Add a repository url check
 */

namespace Yapapaya\DevOps\WPD {

	/**
	 * Maps payload to configuration
	 * 
	 * @since 0.1
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

			// if the payload is for the correct repository
			if ( ! $this->is_correct_repo( $config, $payload ) ) {
				error( '501 Not Implemented', 'This repository is not configured' );
			}

			// if the payload doesn't match our deploy config, there's nothing to do
			if ( ! $this->is_branch( $config, $payload ) || ! $this->is_right_branch( $config, $payload ) ) {
				error( '200 OK', 'No deploy configured for this ref' );
			}
		}

		public function is_correct_repo( Config $config, $payload ) {

			// get the repo information from payload
			$payload_repo = $this->get_nested_array_value( $payload, $config->schema[ 'git_url_param' ] );

			// get the repo information from config
			$config_repo = $config->repo[ 'git_url' ];

			// if both are the same, the event is for the correct repo
			if ( $payload_repo === $config_repo ) {
				return true;
			}

			//BitBucket doesn't send the whole repo url
			if ( strstr( $config_repo, $payload_repo ) ) {
				return true;
			}

			// if we're here, we haven't receivedpayload for the right repo
			return false;
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
		public function is_branch( Config $config, $payload ) {

			// get the parameter with the ref type (branch or tag) from the payload
			$ref_type_param = $this->get_nested_array_value( $payload, $config->schema[ 'ref' ][ 'param' ] );

			$matches = array();

			// get the regex pattern for ref type from schema
			$pattern = $config->schema[ 'ref' ][ 'pattern' ];

			// match to get the ref type
			preg_match( $pattern, $ref_type_param, $matches );

			// could not get a ref type, this payload is not meant for deployment
			if ( ! isset( $matches[ 0 ] ) || empty( $matches[ 0 ] ) ) {
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
		public function is_right_branch( Config $config, $payload ) {

			// get the parameter with the branch name from the payload
			$ref_name_param = $this->get_nested_array_value( $payload, $config->schema[ 'branch_name' ][ 'param' ] );

			$matches = array();

			// get the regex pattern for the branch name from schema
			$pattern = $config->schema[ 'branch_name' ][ 'pattern' ];

			// match to get the branch name
			preg_match( $pattern, $ref_name_param, $matches );

			// branch name wasn't found, payload not meant for us
			if ( ! isset( $matches[ 1 ] ) || empty( $matches[ 1 ] ) ) {
				return false;
			}

			// branch name found but is different from the config, payload not meant for us
			if ( $matches[ 1 ] !== $config->repo[ 'branch' ] ) {
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