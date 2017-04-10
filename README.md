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
git clone git@github.com:Yapapaya/wpd.git /var/www/yoursite.com/htdocs/
```

(_Optional_)Move `wdp-config` outside the web root for security, especially if you're using tokens

```
mv /var/www/yoursite.com/htdocs/wpd/wpd-config.php /var/www/yoursite.com/wpd-config.php
```

#### 1.2. (Optional) Set up schema for GitLab


If your remote repository is on [GitHub](https://github.com) or [BitBucket](https://bitbucket.org), you can skip step 1 and start with 2.

If your remote repository is on a self-hosted instance of GitLab CE, you need to set up schema for it.

```
vim /var/www/yoursite.com/htdocs/wpd/payload.schema
```
The schema for GitLab looks like this

```
// Payload Schema for GitLab
"GitLab" => array(

	// your Gitlab instance's domain
	"domain" => 'git.yapapaya.in', 

	// your GitLab instance's IP range in CIDR format
	// use http://www.ipaddressguide.com/cidr to get it
	"ip_whitelist" => '192.157.221.84/32',


	"token_header" => 'X-Hub-SignatureX-Gitlab-Token',
	"ref_type_param" => array( 'ref' ),
	"ref_type_pattern" => array(
			'branch' => '^refs\/head\/',
			'tag' => '^refs\/tag\/'
	),
	"ref_name_param" => array('ref'),
	"ref_name_pattern" =>  array(
			'branch' => '^refs\/head\/(.*)$',
			'tag' => '^refs\/tag\/(.*)$'
	),
),
```
Just add your instance's domain name and optionally, a valid IP range for added security.

You can configure more than one instance of GitLab, but the key would still be GitLab for all such instances:

```
// Payload Schema for first GitLab CE instance
"GitLab" => array(

	// your Gitlab instance's domain
	"domain" => 'git.yapapaya.in', 

	// your GitLab instance's IP range in CIDR format
	// use http://www.ipaddressguide.com/cidr to get it
	"ip_whitelist" => '192.157.221.84/32',


	"token_header" => 'X-Hub-SignatureX-Gitlab-Token',
	"ref_type_param" => array( 'ref' ),
	"ref_type_pattern" => array(
			'branch' => '^refs\/head\/',
			'tag' => '^refs\/tag\/'
	),
	"ref_name_param" => array('ref'),
	"ref_name_pattern" =>  array(
			'branch' => '^refs\/head\/(.*)$',
			'tag' => '^refs\/tag\/(.*)$'
	),
),
// Payload Schema for second GitLab CE instance
"GitLab" => array(

	// your Gitlab instance's domain
	"domain" => 'git.baapwp.me', 

	// your GitLab instance's IP range in CIDR format
	// use http://www.ipaddressguide.com/cidr to get it
	"ip_whitelist" => '',


	"token_header" => 'X-Hub-SignatureX-Gitlab-Token',
	"ref_type_param" => array( 'ref' ),
	"ref_type_pattern" => array(
			'branch' => '^refs\/head\/',
			'tag' => '^refs\/tag\/'
	),
	"ref_name_param" => array('ref'),
	"ref_name_pattern" =>  array(
			'branch' => '^refs\/head\/(.*)$',
			'tag' => '^refs\/tag\/(.*)$'
	),
),
```
#### 1.3. Configure Repos

Open `wpd-config.php` for editing

```
vim /var/www/yoursite.com/wpd-config.php
```
For each of your repos, create a config item in the `$config` array. There are enogh examples in the file itself. Each item is intern an array with the following keys

 * `ref_type` (`'branch'` or `'tag'`) The type of git reference that we'll sync server's code with.
 * `ref_name` The name of the reference, i.e., the branch name. This wil be ignored if the `ref_type` is a tag. WPDeploy will always sync with the latest tag (release).
 * `path` The absolute path where the code will be synced to. For a theme, on EasyEngine, it'd be `/var/www/yoursite.com/htdocs/wp-content/themes/`
 * `git_url` The git url of the repo of the type `git@github.com:your-organisation-or-username/your-theme.git`,
 * `token` (_optional_) For security, gitLab & GitHub allow you to set a secret token when setting up webhooks.

#### 1.4. (Optional) Setup Environment Constants

You can uncomment and set the following constants to change how WPDeploy behaves.

```
define( 'WPD_LOG', true );
```

This logs all the requests, for debugging or any other reason.


```
define( 'WPD_SLIM', false);
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

To deploy on a production server, make sure that `ref_type` is set as `'tag'` in the config and create a new tag (or a release) and the latest release will be deployed automatically.

You could even map the prduction server to a branch instead of a tag. However, mapping production to tags or releases is a better mental model to work with, IMO.