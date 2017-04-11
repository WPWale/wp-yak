<?php
/**
 * Contains configuration of repositories to deploy
 * 
 * @package WP-Deploy
 * @subpackage WP-Deploy-Config
 * 
 * @author Saurabh Shukla <saurabh@yapapaya.com>
 * 
 * @since 0.1.0
 */
return $config = array(

	// theme config
	array(
		'git_url' => "git@github.com:your-organisation-or-username/your-theme.git",
		'type' => 'theme',
		'branch' => 'master',
		'token' => "",
	),

	// plugin config
	array(
		'git_url' => "git@github.com:your-organisation-or-username/your-plugin.git",
		'type' => 'plugin',
		'branch' => 'master',
		'token' => "",
	),

	// mu-plugin config
	array(
		'git_url' => "git@github.com:your-organisation-or-username/your-plugin.git",
		'type' => 'mu-plugin',
		'branch' => 'master',
		'token' => "",
	),

	// custom config
	/*
	array(
		'git_url' => "git@github.com:your-organisation-or-username/project-name.git",
		'type' => 'custom-type',
		'branch' => 'master',
		'token' => "",
	),
	 */
);