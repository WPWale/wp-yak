<?php
/**
 * Contains class that loads configuration
 * 
 * @package WP-Deploy
 * 
 * @author Saurabh Shukla <saurabh@yapapaya.com>
 * 
 * @since 0.1.0
 */

namespace Yapapaya\DevOps\WPD {

	/**
	 * Main deployment functionality
	 * 
	 * Deploys code for WP related workflows
	 * 
	 * @since 0.1
	 */
	class Deploy {

		/**
		 * Deploy Configuration
		 * 
		 * @var array
		 * 
		 * @since 0.1
		 */
		public $config = array();

		/**
		 * Webhook Payload
		 * 
		 * @var array
		 * 
		 * @since 0.1
		 */
		public $payload = array();

		/**
		 * Constructor
		 * 
		 * @since 0.1
		 */
		public function __construct() {
			
			// setup config
			$this->config = new Config();

			// test commands
			$this->test_commands();
		}

		/**
		 * Runs the deploy process
		 * 
		 * @since 0.1
		 */
		public function deploy(){
			// load all configuration, payload, etc
			$this->load();
			
			// setup directories
			$this->setup_dirs();
			
			// initialise local git repositories
			$this->init_repo();
			
			// run the deployment
			$this->_deploy();
		}
		
		/**
		 * Loads the payload and maps the schema
		 * 
		 * @since 0.1
		 */
		public function load() {

			// load the payload
			$payload = new \Yapapaya\DevOps\WPD\Payload( $this->config );
			$this->payload = $payload->setup();

			// map the payload with the configuration
			$map = new \Yapapaya\DevOps\WPD\Map();
			$map->init( $this->config, $this->payload );
		}

		/**
		 * Tests commands needed for deployment
		 * 
		 * @since 0.1
		 */
		public function test_commands() {
			
			// test git
			if ( ! $this->test_cmd( 'git' ) ) {
				error( '501 Not Implemented', '<code>git</code> is not installed' );
			}
			
			// if we're running a slim deploy but the remote deosn't support `git archive`,
			// we need svn to run `svn export`
			if (
				SLIM 
				&& ($this->config->schema[ 'git_archive' ] === false) 
				&& ! $this->test_cmd( "svn" )
			) {
				error( '501 Not Implemented', '<code>svn</code> is not installed' );
			}
		}

		/**
		 * Setup directories
		 * 
		 * @since 0.1
		 */
		public function setup_dirs() {

			/*
			 * Note on Slim vs Regular Deploys
			 * ===============================
			 * 
			 * For regular deploys, we pull the code into a directory
			 *  different from the actual deploy path and then
			 *  copy the necessary code to the deploy path.
			 * 
			 * This way any accidental overwrite of the deployed folder
			 *  by a manual upload/etc will not delete the .git directory
			 *  and break our deploy process.
			 * 
			 * So a repo with a git url like this:
			 *  git@github.com:your-organisation-or-username/your-theme.git
			 *  will be maintained inside the 'wp-deploy/wpd-repos/your-theme/'
			 *  directory and then just the code (without the .git folder)
			 *  will be copied over to 'wp-content/themes/your-theme/'
			 * 
			 * For *Slim* deploys, we don't manage the repository locally,
			 *  so, even a manual overwrite wil be fixed on the next deployment.
			 * 
			 */
			// not a slim deply
			if ( ! SLIM ) {
				
				// create the directory for maintaining repos
				if ( ! is_dir( 'wpd-repos' ) ) {
					mkdir( 'wpd-repos' );
				}

				// create a sub-directory for this repo
				if ( ! is_dir( "wpd-repos/" . $this->config->repo[ 'name' ] ) ) {
					mkdir( "wpd-repos/" . $this->config->repo[ 'name' ] );
				}
			}

			// the actual deployment path where files will be copied to
			$deploy_dir = $this->config->repo[ 'path' ];

			// create it if it doen't exist
			if ( ! is_dir( $deploy_dir ) ) {
				mkdir( $deploy_dir );
			}
		}

		/**
		 * Initialises local git repos
		 * 
		 * @since 0.1
		 * 
		 * @return
		 */
		public function init_repo() {

			// we don't need to maintain local repos for slim deploys
			if ( SLIM ) {
				return;
			}

			// if the repo is already initialised, we don't need to
			if ( is_dir( "wpd-repos/{$this->config->repo[ 'name' ]}/.git" ) ) {
				return;
			}

			// store the directory we're in right now, so we can come back
			$original_dir = getcwd();

			// switch the repo's directory
			chdir( "wpd-repos/{$this->config->repo[ 'name' ]}" );

			// initialise git
			exec( 'git init' );

			// add the remote repo as a remote called origin
			exec( "git remote add origin " . $this->config->repo[ 'git_url' ] );

			// switch back to the original directory
			chdir( $original_dir );
		}

		/**
		 * Deploys the repository
		 * 
		 * @since 0.1
		 */
		public function _deploy() {

			// differnt commands for slim and regular deploys
			if ( SLIM ) {
				$this->run_slim_cmd();	
			} else {
				$this->run_cmd();	
			}
			
			// deploy complete!
			error('200 OK', 'Deployment completed successfully');
			/*
			 * Another haiku, ;)
			 * 
			 * Less is more and so,
			 * elegant and adept you
			 * will be, so less slow 
			 */
		}

		/**
		 * Log function
		 * 
		 * @todo Implement this
		 * @since 0.1
		 * 
		 * @return
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
		 * Runs regular deployment
		 * 
		 * @since 0.1
		 */
		public function run_cmd() {

			// get the branch name
			$branch = $this->config->repo[ 'branch' ];

			// get the deploy path
			$path = $this->config->repo[ 'path' ];

			// save the current directory, so we can come back
			$original_dir = getcwd();

			// switch to the directory where the repo is maintained
			chdir( "wpd-repos/{$this->config->repo[ 'name' ]}" );
			
			// pull the latest code from the branch
			exec( "git pull origin $branch" );

			// copy only the code to the deploy path
			exec( "git archive $branch | (cd " . $path . " && tar -x)" );
			
			/*
			 * Note on `git archive`
			 * ====================
			 * 
			 * `git archive` will just take the latest code sepcified by
			 *  additional parameters and make an archive that we unzip (untar)
			 *  into the deployment path.
			 * 
			 * This is being run on the local repo after pulling the code.
			 *  It doesn't matter if a remote doesn't support it.
			 */

			chdir( $original_dir );
		}

		/**
		 * Runs slim deployment
		 * 
		 * @todo What about non-GitHub remotes that don't support git archive?
		 * 
		 * @since 0.1
		 */
		public function run_slim_cmd() {

			// get the branch
			$branch = $this->config->repo[ 'branch' ];

			// get the deploy path
			$path = $this->config->repo[ 'path' ];

			// does the remote support `git archive`?
			$git_archive_supported = ! empty( $this->config->schema[ 'git-archive' ] ) ? true : false;

			// it does, archive the latest code directly into the deploy path
			if ( $git_archive_supported !== false ) {
				exec( "git archive --remote={$this->config->repo[ 'git_url' ]} $branch | (cd " . $path . " && tar -x)" );
			}else{
				// doesn't support `git archive` (GitHub, people!)
				
				$ref_svn = '';

				// using the svn client, we map master branch to trunk
				// and other branches to svn branches
				if ( $branch === 'master' ) {
					$ref_svn = 'trunk/';
				} else{
					$ref_svn = "branches/$branch/";
				}

				// get the svn url
				$svn_url = $this->payload[ 'repository' ][ 'svn_url' ];

				// run svn export to directly deploy code
				exec( 'svn export ' . $svn_url . $ref_svn . $path . "--force" );
			}
		}

		/**
		 * Tests system commands
		 * 
		 * @param string $cmd The command to test
		 * 
		 * @since 0.1
		 * 
		 * @return boolean
		 */
		public function test_cmd( $cmd ) {
			$return_var = "";
			
			// test the command using hash
			exec( "hash $cmd", $op, $return_var );
			
			// if the command exists, no output will happen and vice versa
			return empty( $return_var );
		}

	}

}
