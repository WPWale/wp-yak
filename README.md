# WP Deploy

Written in familiar PHP, WPDeploy (WPD) is a simple, but powerful deployment tool for WordPress Plugin & Theme Development

    Tested only for GitHub. Don't use yet.


## Pre-Requisites
Make sure that the following are installed:
 * `git`
 * `svn` for Slim Deploys using GitHub

This is because GitHub doesn't allow a command like this:

`git archive --remote=git@github.com:your-organisation-or-username/your-plugin.git`


The slimness of the slim deploy is because, the whole repository isn't cloned or maintained on the servers. Using [`git archive`](https://git-scm.com/docs/git-archive), WPDeploy is able to only copy the files at a  particular branch or tag, without the commit history (the `.git` folder).


Fotunately, [GitHub supports svn clients](https://help.github.com/articles/support-for-subversion-clients/). So, we use the svn equivalent of `git archive`, [`svn export`](http://svnbook.red-bean.com/en/1.7/svn.ref.svn.c.export.html). See: 

## Usage

### 1. Getting Started

 1. Install WPDeploy 
 1. Setup schema for GitLab
 1. Configure repos
 1. Setup constants


#### 1.1. Install WPDeploy

Right now there're no automattic installation methods, you'd have to clone this repo or archive its `master` branch using `git` or `svn` or upload the files manually.

If you're using EasyEngine, follow these steps, after logging in via ssh, clone this repository to your webroot

```
git clone git@github.com:Yapapaya/wp-deploy.git /var/www/yoursite.com/htdocs/
```

(_Optional_)Move `wdp-config` outside the web root for security, especially if you're using tokens

```
mv /var/www/yoursite.com/htdocs/wp-deploy/wp-deploy-config /var/www/yoursite.com/wp-deploy-config
```

#### 1.2. (Optional) Set up schema for GitLab


If your remote repository is on [GitHub](https://github.com) or [BitBucket](https://bitbucket.org), you can skip step 1.2 and start with 1.3.

If your remote repository is on a self-hosted instance of GitLab CE, you need to set up schema for it.

```
vim /var/www/yoursite.com/htdocs/wp-deploy-config/webhook-payload-schema.php
```

or

```
vim /var/www/yoursite.com/wp-deploy-config/webhook-payload-schema.php
```

The schema for GitLab looks like this

```
// Payload Schema for GitLab Instance
	// your Gitlab instance's domain
	'git.yoursite.com' => array(
		// your GitLab instance's IP range in CIDR format
		// use http://www.ipaddressguide.com/cidr to get it
		"ip_whitelist" => '127.0.0.0/32',
		"token" => array(
			"header" => 'HTTP_X_Gitlab_Token',
			"hashed" => false,
		),
		"ref" => array(
			"param" => array( 'ref' ),
			"pattern" => '^refs\/head\/',
		),
		"branch_name" => array(
			"param" => array( 'ref' ),
			"pattern" => '^refs\/head\/(.*)$',
		),
		"git_archive" => true,
	),
```
Just add your instance's domain name and optionally, a valid IP range for added security.

#### 1.3. Configure Repos

Open repository configuration

```
vim /var/www/yoursite.com/htdocs/wp-deploy-config/repositories-to-deploy.php
```

or

```
vim /var/www/yoursite.com/wp-deploy-config/repositories-to-deploy.php
```

For each of your repos, create a config item in the `$config` array. There are enough examples in the file itself. Each item is intern an array with the following keys

 * `git_url` The git url of the repo in the format `git@github.com:your-organisation-or-username/your-theme.git`
 * `type` (`'theme'`, `'plugin'`, `'mu-plugin'`) The type of project that will be deployed.
 * `branch` The name of the branch to deploy.
 * `token` (_optional_) For security, gitLab & GitHub allow you to set a secret token when setting up webhooks.

#### 1.4. (Optional) Setup Environment Constants

Open constants file

Open repository configuration

```
vim /var/www/yoursite.com/htdocs/wp-deploy-config/application-constants.php
```

or

```
vim /var/www/yoursite.com/wp-deploy-config/application-constants.php
```


```
define( 'LOG', true );
```

This logs all the requests, for debugging or any other reason.

```
define( 'LOGFILE', '/path/to/directory' );
```

Log to a custom file, instead of the default.


```
define( 'SLIM', false);
```

By default, WPDeploy performs slim deploys using `git archive` or `svn export`(for GitHub). This means that the whole repository is not maintained on the server. This can save up a lot of space and data and is similar to downloading a zip file of the specified branch or tag without the commit history (the `.git` directory).

Set this to `true`, if you want to or need to maintain the whole git repository on your servers. 

### 2. Setup Deploy Keys

Generate ssh key pairs on each of your servers (if not done already) and add the public key as deploy key for each of your repos.

 * [Instructions for GitHub](https://developer.github.com/guides/managing-deploy-keys/#deploy-keys)
 * [Instructions for BitBucket](https://confluence.atlassian.com/bitbucket/use-access-keys-294486051.html)
 * [Instructions for GitLab](https://docs.gitlab.com/ce/ssh/README.html#deploy-keys)

### 3. Setup Webhooks

Setup a webhook on your remote git repository. For the webhook url, if your repository is `git@github.com:your-organisation-or-username/your-theme.git`, use the following format:

```
https://yoursite.com/wpd/wpd.php?deploy=your-theme
```
where the value of the `deploy` parameter of the querystring is the same as the name of the repository without the `.git` suffix.

 * [Instructions for GitHub](https://developer.github.com/webhooks/creating/)
 * [Instructions for BitBucket](https://confluence.atlassian.com/bitbucket/manage-webhooks-735643732.html)
 * [Instructions for GitLab](https://docs.gitlab.com/ce/user/project/integrations/webhooks.html)

### 4. Deploy

Now, write code as usual and push to a branch. If the branch is mapped to a server, the code will get automatically deployed.

No cloning or other setup needed. WP Deploy will automatically clone, initialise, pull, etc as needed.