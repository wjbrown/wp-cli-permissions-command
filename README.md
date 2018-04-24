wjbrown/wp-cli-permissions-command
==================================

This command attempts to set proper wordpress file &amp; dir permissions based on the current environment (dev, production).  

This script is *loosely* based on the set of commands found here: https://gist.github.com/Adirael/3383404.

Quick links: [Important](#important) | [Using](#using) | [Installing](#installing)


## Important

This script differs from traditional wordpress permission philosophies in that it assumes files and dirs in wp-content will need to be writable by the system user, not just the server.  I've tried to accomplish this by creating an additional group and adding both the webserver and system user to 
that group.

Here's how you would do something like that:

~~~
# create wordpress group
sudo groupadd wordpress

# add 'www-data' user to 'wordpress' group
sudo usermod -a -G wordpress www-data

# add 'ubuntu' user to 'wordpress' group
sudo usermod -a -G wordpress ubuntu
~~~


## Using

~~~
wp permissions [--wp_owner=<owner>] [--wp_group=<group>] [--dry-run] [--mode=<dev|production>]
~~~

If you want to specify a default set of arguments to pass to the script, you can do so by cloning the config-sample.json file to config.json and updating the values to what suits you.
This is useful if you run a server with multiple wordpress installations and don't want to 
have to be overly verbose each time you run the script.


## Installing

Installing this package requires WP-CLI v1.1.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with:

    wp package install git@github.com:wjbrown/wp-cli-permissions-command.git

