<?php

namespace Yapapaya\DevOps\WPD {

	class Config {

		public $repo;
		public $schema;

		const REPO_PATTERN = '/^git@(.*):.*\/(.*)\.git$/';

		public function __construct() {

			$this->init();

			$this->set_path();

			$this->set_schema();
		}

		public function init() {

			$repos = include_once(CONFIGPATH . 'repositories.php' );

			$requested_deploy = filter_input( \INPUT_GET, 'deploy', \FILTER_SANITIZE_STRING );

			$selected = false;

			if ( empty( $requested_deploy ) ) {

				$selected = $this->maybe_select_deploy( $repos[ 0 ], $requested_deploy );
			} else {

				foreach ( $repos as $deploy_config ) {

					$selected = $this->maybe_select_deploy( $deploy_config, $requested_deploy );

					if ( $selected === true ) {
						break;
					}
				}
			}

			if ( $selected === false ) {
				error(
					'501 Not Implemented', 'Configuration not found for the requested deploy'
				);
			}
		}

		public function maybe_select_deploy( $deploy_config, $requested_deploy ) {
			$parsed = $this->parse_git_url( $deploy_config[ 'git_url' ] );
			
			if ( $parsed === false ) {
				return false;
			}

			if ( $this->repo[ 'name' ] === $requested_deploy || empty($requested_deploy) ) {
				$this->repo += $deploy_config;
				return true;
			}
			return false;
		}

		public function parse_git_url( $git_url ) {
			$matches = array();
			
			preg_match( self::REPO_PATTERN, $git_url, $matches );
			
			if ( empty( $matches ) || ! is_array( $matches ) ) {
				return false;
			}

			if ( ! isset( $matches[ 2 ] ) ) {
				return false;
			}

			$this->repo[ 'remote_domain' ] = $matches[ 1 ];

			$this->repo[ 'name' ] = $matches[ 2 ];

			return true;
		}

		public function set_path() {
			$path_types = include_once(CONFIGPATH . 'path-types.php' );

			$base_path = $path_types[ $this->repo[ 'type' ] ];

			$this->is_path_valid( $base_path );
			
			$this->repo[ 'path' ] = $base_path . '/' . $this->repo[ 'name' ];
		}

		public function is_path_valid( $path ) {
			
			if ( ! isset( $path ) ) {
				error(
					'501 Not Implemented', 'Path not configured for the requested deploy type'
				);
			}

			if ( empty( $path ) ) {
				error(
					'501 Not Implemented', 'Path empty for the requested deploy type'
				);
			}

			if ( ! is_dir( $path ) ) {
				error(
					'501 Not Implemented', 'Path does not exist for the requested deploy'
				);
			}
		}

		public function set_schema() {
			$schemas = include_once(CONFIGPATH . 'webhook-schema.php' );

			$schema = isset( $schemas[ $this->repo[ 'remote_domain' ] ] ) ? $schemas[ $this->repo[ 'remote_domain' ] ] : false;

			if ( empty( $schema ) ) {
				error(
					'501 Not Implemented', 'Schema not found for remote'
				);
			}

			$this->schema = $schema;
		}

	}

}  