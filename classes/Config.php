<?php
/**
 * Contains class that loads configuration
 * 
 * @package WP-Yak
 * 
 * @author Saurabh Shukla <saurabh@yapapaya.com>
 * 
 * @since 0.1.0
 */
namespace Yapapaya\DevOps\WPD {

	/**
	 * Loads Configuration
	 * 
	 * Picks up the requested deploy from webhook url
	 *  http://yoursite.com/wp-yak/wpy.php, or
	 *  http://yoursite.com/wp-yak/wpy.php?deploy=your-theme,
	 *  sets up paths for deployment and schema to use for processing payload
	 * 
	 * @since 0.1
	 */
	class Config {

		/**
		 * Configuration for the repository to deploy
		 * 
		 * @var array 
		 * 
		 * @since 0.1
		 */
		public $repo;

		/**
		 * Schema Configuration for the Webhook
		 * 
		 * @var array
		 * 
		 * @since 0.1
		 */
		public $schema;

		/**
		 * Regex pattern for git urls of the repo
		 * 
		 * @since 0.1
		 */
		const GIT_URL_PATTERN = '/^git@(.*):.*\/(.*)\.git$/';

		/**
		 * Constructor
		 * 
		 * @since 0.1
		 */
		public function __construct() {

			$this->init_repo();

			$this->set_path();

			$this->set_schema();
		}

		/**
		 * Picks up the repo that will be deployed
		 * 
		 * @since 0.1
		 */
		public function init_repo() {

			// include the repository configuration
			$repos = include_once(CONFIGPATH . 'repositories.php' );

			// the $_GET['deploy'] parameter from request
			$requested_deploy = filter_input( \INPUT_GET, 'deploy', \FILTER_SANITIZE_STRING );

			// assume that no repository is selected for deployment
			$selected = false;

			// if no deploy param was found, maybe pick up the first (and often, only) repository config
			if ( empty( $requested_deploy ) ) {

				$selected = $this->maybe_select_deploy( $repos[ 0 ], $requested_deploy );

			// otherwise, loop through repositories to select the requested one
			} else {
				
				foreach ( $repos as $deploy_config ) {

					$selected = $this->maybe_select_deploy( $deploy_config, $requested_deploy );

					// if we found one, break the loop
					if ( $selected === true ) {
						break;
					}
				}
			}

			// we didn't find any, throw an error
			if ( $selected === false ) {
				error(
					'501 Not Implemented', 'Configuration not found for the requested deploy'
				);
			}
		}

		/**
		 * Checks if a given repository configuration matches the webhook request
		 * 
		 * @param array $deploy_config
		 * @param string $requested_deploy
		 * 
		 * @todo throw a different error for malformed git url
		 * 
		 * @since 0.1
		 * 
		 * @return boolean
		 */
		public function maybe_select_deploy( $deploy_config, $requested_deploy ) {

			// parse the git url of the repository
			$parsed = $this->parse_git_url( $deploy_config[ 'git_url' ] );

			// if the git url is malformed
			if ( $parsed === false ) {
				return false;
			}

			// if the name of the reo matches requested deploy or if no specific deploy was requested
			if ( $this->repo[ 'name' ] === $requested_deploy || empty($requested_deploy) ) {
				
				// merge the config into the current repo config
				// (which already has a few elements from parsing)
				$this->repo += $deploy_config;
				return true;
			}

			// otherwise, something failed
			return false;
		}

		/**
		 * Parse git url to get domain and repository name
		 * 
		 * @param string $git_url
		 * 
		 * @since 0.1
		 * 
		 * @return boolean
		 */
		public function parse_git_url( $git_url ) {

			// no git url, no deploy
			if ( empty( $git_url ) ) {
				return false;
			}

			$matches = array();

			// match regex against url
			preg_match( self::GIT_URL_PATTERN, $git_url, $matches );

			// didn't match, git url is not proper, no deploy
			if ( empty( $matches ) || ! is_array( $matches ) ) {
				return false;
			}

			// didn't get the name of repo from url, url is malformed, no deploy
			if ( ! isset( $matches[ 2 ] ) ) {
				return false;
			}

			// first matches key has the domain name of remote,
			// useful for picking appropriate schema
			$this->repo[ 'remote_domain' ] = $matches[ 1 ];

			// name of the repo, used to create the appropriate directories
			$this->repo[ 'name' ] = $matches[ 2 ];

			// everything went well
			return true;
		}

		/**
		 * Setup an absolute path for deployment
		 * 
		 * @since 0.1
		 */
		public function set_path() {
			
			// pick up the path types
			$path_types = include_once(CONFIGPATH . 'path-types.php' );

			// get the base path for the deploy type
			$base_path = $path_types[ $this->repo[ 'type' ] ];

			// check if it is a valid path
			$this->is_path_valid( $base_path );

			// append the repositories name to get the final deploy path
			$this->repo[ 'path' ] = $base_path . '/' . $this->repo[ 'name' ];
		}
		
		/**
		 * Checks if the deploy path is valid
		 * 
		 * @param string $path
		 * 
		 * @since 0.1
		 */
		public function is_path_valid( $path ) {

			// no path configured for the deploy type
			if ( ! isset( $path ) ) {
				error(
					'501 Not Implemented', 'Path not configured for the requested deploy type'
				);
			}

			// path is empty
			if ( empty( $path ) ) {
				error(
					'501 Not Implemented', 'Path empty for the requested deploy type'
				);
			}

			// path doesn't exist
			if ( ! is_dir( $path ) ) {
				error(
					'501 Not Implemented', 'Path does not exist for the requested deploy'
				);
			}
		}

		/**
		 * Selects the appropriate schema based on the repo & request
		 * 
		 * @since 0.1
		 */
		public function set_schema() {

			// include the schema file
			$schemas = include_once(CONFIGPATH . 'webhook-schema.php' );

			// the domain name of the repo is the key used in schema
			$schema = isset( $schemas[ $this->repo[ 'remote_domain' ] ] ) ? $schemas[ $this->repo[ 'remote_domain' ] ] : false;

			// no schema configured for the remote domain
			if ( empty( $schema ) ) {
				error(
					'501 Not Implemented', 'Schema not found for remote'
				);
			}

			// otherwise, set the schema
			$this->schema = $schema;
		}

	}

}