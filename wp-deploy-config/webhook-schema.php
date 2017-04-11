<?php
/**
 * Contains schemas/patterns for webhook payloads
 * 
 * @package WP-Deploy
 * @subpackage WP-Deploy-Config
 * 
 * @author Saurabh Shukla <saurabh@yapapaya.com>
 * 
 * @since 0.1.0
 */
return $config = array(
	
	// Payload Schema for GitLab Instance
	// your Gitlab instance's domain
	'git.yoursite.com' => array(
		// your GitLab instance's IP range in CIDR format
		// use http://www.ipaddressguide.com/cidr to get it
		"ip_whitelist" => '127.0.0.0/32',
		"ip_param" => 'HTTP_CF_CONNECTING_IP',
		"token" => array(
			"header" => 'HTTP_X_Gitlab_Token',
			"hashed" => false,
		),
		"ref" => array(
			"param" => array( 'ref' ),
			"pattern" => '/^refs\/head\//',
		),
		"branch_name" => array(
			"param" => array( 'ref' ),
			"pattern" => '/^refs\/head\/(.*)$/',
		),
		"git_archive" => true,
	),

	// Payload Schema for GitHub.com
	"github.com" => array(
		"ip_whitelist" => '192.30.252.0/22',
		"ip_param" => 'HTTP_CF_CONNECTING_IP',
		"token" => array(
			"header" => 'HTTP_X_Hub_Signature',
			"hashed" => "sha1",
		),
		"ref" => array(
			"param" => array( 'ref' ),
			"pattern" => '/^refs\/heads\//',
		),
		"branch_name" => array(
			"param" => array( 'ref' ),
			"pattern" => '/^refs\/heads\/(.*)$/',
		),
		"git_archive" => false,
	),
	
	// Payload Schema for BitBucket.org
	"bitbucket.org" => array(
		"ip_whitelist" => '104.192.143.0/24',
		"ip_param" => 'HTTP_CF_CONNECTING_IP',
		"token" => array(
			"header" => false,
		),
		"ref" => array(
			"param" => array( 'push', 'changes', 'new', 'type' ),
			"pattern" => '/^branch$/',
		),
		"branch_name" => array(
			"param" => array( 'push', 'changes', 'new', 'name' ),
			"pattern" => '/^(.*$)/',
		),
		"git_archive" => false,
	),
	
);

