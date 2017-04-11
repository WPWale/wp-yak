# WP Deploy

Written in familiar PHP, WP Deploy (WPD) is a simple, but powerful deployment tool for WordPress Plugin & Theme Development

    Tested only for GitHub (BitBucket & GitLab pending). Don't use yet.

Using remote git repos on GitHub, BitBucket or your own self-hosted instance of GitLab, you can automate your deployment workflow between development, staging and production servers.

Whether you're building a theme, plugin, mu-plugin or combinations of those for a client website, WP Deploy will automatically deploy the latest code intended for a server, based on your configuration. Here's an example:

## Example Workflow

| git branch  | workflow stage   | server                       |
| -----------:|:----------------:| :--------------------------- |
| master      | Development	 | https://dev.yoursite.com     |
| review      | Code Review	 | - NA -                       |
| custom      | Custom Stage	 | https://custom.yoursite.com  |
| staging     | Staging		 | https://staging.yoursite.com |
| production  | Live/ Production | https://yoursite.com         |

* While developing in a team: `git push origin master` will deploy code to `https://dev.yoursite.com`
* Once the code is reviewed internally and is ready for client review: `git push origin staging` will deploy code to `https://staging.yoursite.com`
* Once the clients' reviewed everything and gives a go ahead for going live: `git push origin production` will deploy code on the live site, `https://yoursite.com`

## Slim & Faster Deploys

Instead of cloning and maintaining the whole repository on servers, WP Deploy tries to only deploy the code without the scm data (or the `.git` directory, etc). Using [`git archive`](https://git-scm.com/docs/git-archive), WPDeploy is able to only copy the files at a  particular branch or tag, without the commit history:

`git archive --remote=git@github.com:your-organisation-or-username/your-plugin.git`

GitHub doesn't allow `git archive`, but fortunately, [GitHub supports svn clients](https://help.github.com/articles/support-for-subversion-clients/). So, we use the svn equivalent of `git archive`, [`svn export`](http://svnbook.red-bean.com/en/1.7/svn.ref.svn.c.export.html). See: [http://stackoverflow.com/a/18324428/1589999](http://stackoverflow.com/a/18324428/1589999)

## Regular Deploys

Of course, if you wish to maintain the whole repository on your servers, you can disable the slim deploy. To do that, set the `SLIM` constant to `false` in `wp-deploy-config/constants.php`.

This way, WP Deploy will use `git pull` and maintain a local copy with commit history inside the `wp-deploy/wpd-repos/` directory and copy over the latest code to the deploy path. This is done, instead of maintaining the repo in the actual deploy path (say `wp-content/themes/your-theme`) to prevent over-writing by a manual upload. Without this, if someone uploads the theme/ plugin manually, the scm information will be overwritten and the deploy would break. With this mechanism, a manual upload will be overwritten in the next push!

## Pre-Requisites
Make sure that the following are installed:
 * `git`
 * `svn` for Slim Deploys using GitHub

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
vim /var/www/yoursite.com/htdocs/wp-deploy-config/webhook-schema.php
```

or

```
vim /var/www/yoursite.com/wp-deploy-config/webhook-schema.php
```

The schema for GitLab looks like this

```
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
```
The key for each schema is the domain name of the remote. The keys inside the schema:

 * `ip_whitelist` A whitelisted range of IP's
 * `ip_param` The header that contains the IP of the remote. Leave it empty if your server directly talks to remote without a proxy in between. In the example above, the `HTTP_CF_CONNECTING_IP` is where CloudFlare stores the webhook's original IP address.
 * `token` GitHub and GitLab (but not Bitbucket) allow you to set an additional security token that is sent as a header.
  * `header` The header that contains the security token
  * `hashed` GitHub hashes the security key, GitLab doesn't. Set it to false, if it isn't hashed or true, if it is.
 * `ref` Schema for the ref (branch/tag) the payload is for.
  * `param` The parameter that contains the ref information
  * `pattern` The regex pattern to match against to know that it is a branch
 * `branch_name` Schema for the branch name in the payload
  * `param` The parameter that contains the branch name
  * `pattern` The regex pattern to match against to get the branch name
 * `git_archive` Whether the remote supports `git archive` command. Is true for everyone except GitHub

To setup deployments with your own Gitlab instance, just change the key (to the domain name) and optionally,set the `ip_whitelist`. To account for Cloudflare or similar proxies, set the `ip_param`. Nothing else needs to be changed.

#### 1.3. Configure Repos

Open repository configuration

```
vim /var/www/yoursite.com/htdocs/wp-deploy-config/repositories.php
```

or

```
vim /var/www/yoursite.com/wp-deploy-config/repositories.php
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
vim /var/www/yoursite.com/htdocs/wp-deploy-config/constants.php
```

or

```
vim /var/www/yoursite.com/wp-deploy-config/constants.php
```


```
define( 'LOG', true );
```

(not implement, yet) This logs all the requests, for debugging or any other reason.

```
define( 'LOGFILE', '/path/to/directory' );
```

(not implement, yet) Log to a custom file, instead of the default.


```
define( 'SLIM', true);
```

By default, WPDeploy performs slim deploys using `git archive` or `svn export`(for GitHub). This means that the whole repository is not maintained on the server. This can save up a lot of space and data and is similar to downloading a zip file of the specified branch or tag without the commit history (the `.git` directory).

Set this to `false`, if you want to or need to maintain the whole git repository on your servers. 

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