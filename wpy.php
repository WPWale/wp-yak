<?php

/**
 * WPYak
 * 
 * Written in familiar PHP, WPYak is a simple, powerful, extendable deployment for WordPress Pugin & Theme Development
 * 
 * @package WP-Yak
 * @version 0.1.0
 * 
 * @author Saurabh Shukla <saurabh@yapapaya.com>
 * 
 * @license MIT
 * 
 * @copyright (c) 2017, Saurabh Shukla
 * 
 * 
 * Permission is hereby granted, free of charge, to any person
 *  obtaining a copy of this software and associated documentation files (the "Software"),
 *  to deal in the Software without restriction, including without limitation
 *  the rights to use, copy, modify, merge, publish, distribute, sublicense,
 *  and/or sell copies of the Software, and to permit persons to whom
 *  the Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included
 *  in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 *  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 *  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 *  IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 *  CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 *  TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 *  WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Yapapaya\DevOps\WPD {

	// filter and get the protocol
	$server_protocol = filter_input( \INPUT_SERVER, 'SERVER_PROTOCOL' );

	/**
	 * HTTP Protocol
	 * 
	 * @since 0.1
	 */
	define( 'PROTOCOL', isset( $server_protocol ) ? $server_protocol : 'HTTP/1.0'  );

	/**
	 * Outputs messages in response
	 * 
	 * @param string $status The HTTP Status
	 * @param string $message The message to include in the body
	 */
	function error( $status, $message ) {
		header( PROTOCOL . ' ' . $status );
		die( "<h1>WPDeploy: $message</h1>"
			. "<p>Ask the server administrator to see the WPDeploy log file for more details.</p>" );
	}

	/*
	 * Some constants
	 * ==============
	 */

	/**
	 * Absolute Path
	 * 
	 * @since 0.1
	 */
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

	$path = '';

	// if config directory is outside the root
	if ( file_exists( dirname( dirname( ABSPATH ) ) . '/wp-yak-config/repositories.php' ) ) {

		$path = dirname( dirname( ABSPATH ) ) . '/wp-yak-config/';
	} elseif ( file_exists( ABSPATH . 'wp-yak-config/repositories.php' ) ) {

		$path = ABSPATH . 'wp-yak-config/';
	} else {

		// no configuration found
		error(
			'501 Not Implemented', 'Configuration not found in expected paths'
		);
	}

	/**
	 * Configuration Path
	 * 
	 * @since 0.1
	 */
	define( 'CONFIGPATH', dirname( dirname( ABSPATH ) ) . '/wp-yak-config/' );


	/**
	 * Import Constants set by user if any
	 */
	require_once(CONFIGPATH . 'constants.php');


	if ( ! defined( 'LOG' ) ) {
		/**
		 * Whether to use log
		 * 
		 * @since 0.1
		 */
		define( 'LOG', false );
	}

	if ( ! defined( 'LOGFILE' ) ) {
		/**
		 * Path to the log file
		 * 
		 * @since 0.1
		 */
		define( 'LOGFILE', dirname( ABSPATH ) . 'logs/wp-yak.log' );
	}

	if ( ! defined( 'SLIM' ) ) {
		/**
		 * Whether to run slim deploys
		 * 
		 * @since 0.1
		 */
		define( 'SLIM', true );
	}


	/**
	 * Include class that loads configuration
	 * 
	 * @since 0.1
	 */
	require_once( ABSPATH . 'classes/Config.php' );

	/**
	 * Include class that processes the webhook payload 
	 * 
	 * @since 0.1
	 */
	require_once( ABSPATH . 'classes/Payload.php' );

	/**
	 * Include class that maps payload to schema
	 * 
	 * @since 0.1
	 */
	require_once( ABSPATH . 'classes/Map.php' );

	/**
	 * Include main class that handles deployment
	 * 
	 * @since 0.1
	 */
	require_once( ABSPATH . 'classes/Deploy.php' );


	/*
	 * Deploy
	 * ======
	 */

	// instantiate the Deploy class
	$wpd = new Deploy();

	//and deploy!
	$wpd->deploy();
}

/*
 * now that the code is live, a haiku
 * 
 * By itself, code flows
 * blocked by clients' cues, but not you;
 * deploy, you're a pro.
 */