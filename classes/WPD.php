<?php

namespace Yapapaya\DevOps\WPD {

	/**
	 * Main deployment functionality
	 * 
	 * Deploys code for WP related workflows
	 * 
	 * @package WP Deploy
	 * 
	 * @author Saurabh Shukla <saurabh@yapapaya.com>
	 * 
	 */
	class WPD {

		/**
		 *
		 * @var array 
		 * @since 0.1
		 */
		public $config = array();

		/**
		 *
		 * @var array
		 * @since 0.1
		 */
		public $payload = array();
		public $ref;

		/**
		 * 
		 * @param type $config
		 * @since 0.1
		 * @return type
		 */
		public function __construct( $config ) {

			$this->test_commands();
		}

		public function load() {

			$this->config = new \Yapapaya\DevOps\WPD\Config();

			$payload = new \Yapapaya\DevOps\WPD\Payload( $this->config );

			$this->payload = $payload->setup();

			$map = new \Yapapaya\DevOps\WPD\MapSchema();

			$map->init( $this->config, $this->payload );
		}
		
		public function deploy(){
			$this->load();
			$this->setup_dirs();
			$this->init_repo();
			$this->_deploy();
		}

		public function test_commands() {
			if ( ! $this->test_cmd( 'git' ) ) {
				error( '501 Not Implemented', '<code>git</code> is not installed' );
			}
			if ( SLIM && ($this->config->schema[ 'git-archive' ] === false) && ! $this->test_cmd( "svn" ) ) {
				error( '501 Not Implemented', '<code>svn</code> is not installed' );
			}
		}

		/**
		 * @since 0.1
		 */
		public function setup_dirs() {

			if ( ! SLIM ) {
				if ( ! is_dir( 'wpd-repos' ) ) {
					mkdir( 'wpd-repos' );
				}

				if ( ! is_dir( "wpd-repos/" . $this->config->repo[ 'name' ] ) ) {
					mkdir( "wpd-repos/" . $this->config->repo[ 'name' ] );
				}
			}

			$deploy_dir = $this->config->repo[ 'path' ];

			if ( ! is_dir( $deploy_dir ) ) {
				mkdir( $deploy_dir );
			}
		}

		/**
		 * @since 0.1
		 * @return type
		 */
		public function init_repo() {

			if ( SLIM ) {
				return;
			}

			if ( is_dir( "wpd-repos/{$this->config->repo[ 'name' ]}/.git" ) ) {
				return;
			}

			$original_dir = getcwd();

			chdir( "wpd-repos/{$this->config->repo[ 'name' ]}" );

			exec( 'git init' );

			exec( "git remote add origin" . $this->config->repo[ 'git_url' ] );

			chdir( $original_dir );
		}

		/**
		 * @since 0.1
		 * @return type
		 */
		public function _deploy() {

			// if we're here, everything was good, we can deploy
			if ( SLIM ) {
				$this->run_slim_cmd();
			} else {
				$this->run_cmd();
			}
			
			error('200 OK', 'Deployment completed successfully');
		}

		/**
		 * @since 0.1
		 * @return type
		 */
		public function log() {
			# Log posts
			if ( ! WP_DEPLOY_LOG ) {
				return;
			}

			$log_file = $_SERVER[ 'SCRIPT_FILENAME' ] . '.log';
			$log_content = "Web Hook Post: " . date( "F j, Y, g:i a" ) . "\n" . $this->payload . "\n\n";
			file_put_contents( $log_file, $log_content, FILE_APPEND );
		}

		/**
		 * 
		 * @param type $ref_type
		 * @param type $ref_name
		 * @since 0.1
		 */
		public function run_cmd() {

			$branch = $this->config->repo[ 'branch' ];

			$path = $this->config->repo[ 'path' ];

			$original_dir = getcwd();


			chdir( "wpd-repos/{$this->config->repo[ 'name' ]}" );

			exec( "git pull origin $branch" );


			exec( "git archive $branch | (cd " . $path . " && tar -x)" );

			chdir( $original_dir );
		}

		/**
		 * 
		 */
		public function run_slim_cmd() {

			$branch = $this->config->repo[ 'branch' ];

			$path = $this->config->repo[ 'path' ];

			$git_archive_supported = ! empty( $this->config->schema[ 'git-archive' ] ) ? true : false;

			if ( $git_archive_supported !== false ) {
				exec( "git archive --remote={$this->config->repo[ 'git_url' ]} $branch | (cd " . $path . " && tar -x)" );
			}else{
				$ref_svn = '';


				if ( $branch === 'master' ) {
					$ref_svn = 'trunk/';
				} else{

					$ref_svn = "branches/$branch/";
				}

				$svn_url = $this->payload[ 'repository' ][ 'svn_url' ];

				exec( 'svn export ' . $svn_url . $ref_svn . $path . "--force" );
			}
		}

		/**
		 * 
		 * @return type
		 */
		public function test_cmd( $cmd ) {
			$return_var = "";
			exec( "hash $cmd", null, $return_var );
			return ! empty( $return_var );
		}

	}

}
