<?php
/*

This script differs from traditional wordpress permission philosophies in that it assumes files and dirs in wp-content will
need to be writable by the system user, not just the server.  We feel like our opinion is supported by things like the rising
using of wp-cli.  We try to accomplish this by creating an additional group and adding both the webserver and system user to 
that group.

How to do this:

    # create wordpress group
    sudo groupadd wordpress

    # add 'www-data' user to 'wordpress' group
    sudo usermod -a -G wordpress www-data

    # add 'ubuntu' user to 'wordpress' group
    sudo usermod -a -G wordpress ubuntu


Special thanks to https://gist.github.com/Adirael/3383404, as this was the bedrock we built from.
*/

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Sets permissions for wordpress files & dirs
 *
 * @alias perms
 * @when after_wp_load
 */
$permissions_command = function($args, $assoc_args) {

    $config_args = [];

    if (file_exists(__DIR__ . '/config.json')) {
        $config_args = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
    }
    
    $merged_args = array_merge($config_args, $assoc_args);
    
    $wp_owner = isset($merged_args['wp_owner']) ? $merged_args['wp_owner'] : 'wordpress';
    $wp_group = isset($merged_args['wp_group']) ? $merged_args['wp_group'] : 'wordpress';

    if (!system_user_exists($wp_owner)) {
        trigger_error("'$wp_owner' is not a valid system user");
    }

    if (!system_group_exists($wp_group)) {
        trigger_error("'$wp_group' is not a valid system group");
    }
    
    $mode = isset($merged_args['mode']) ? $merged_args['mode'] : 'production';

    $cmd = [];

    $cmd[] = 'WP_OWNER=' . $wp_owner;
    $cmd[] = 'WP_GROUP=' . $wp_group;
    $cmd[] = 'WP_ROOT="' . get_home_path() . '"';
    
    if ($mode == 'dev') {       
        // reset to safe defaults
        $cmd[] = 'find ${WP_ROOT} -exec chown ${WP_OWNER}:${WP_GROUP} {} \;';
        $cmd[] = 'find ${WP_ROOT} -type d -exec chmod 775 {} \;';
        $cmd[] = 'find ${WP_ROOT} -type f -exec chmod 664 {} \;';
    }
    // production
    else {
        // reset to safe defaults
        $cmd[] = 'find ${WP_ROOT} -exec chown ${WP_OWNER}:${WP_GROUP} {} \;';
        $cmd[] = 'find ${WP_ROOT} -type d -exec chmod 755 {} \;';
        $cmd[] = 'find ${WP_ROOT} -type f -exec chmod 644 {} \;';
        
        // allow wordpress to manage wp-config.php (but prevent world access)
        $cmd[] = 'chmod 660 ${WP_ROOT}/wp-config.php';
    }

    // allow wordpress to manage wp-content
    $cmd[] = 'find ${WP_ROOT}/wp-content -type d -exec chmod 775 {} \;';
    $cmd[] = 'find ${WP_ROOT}/wp-content -type f -exec chmod 664 {} \;';
    
    // preserve the wordpress group across all files
    $cmd[] = 'find ${WP_ROOT}/wp-content -type d -exec chmod g+s {} \;';       
    
    $cmd = join("\n", $cmd);

    
    if (isset($merged_args['dry-run'])) {
        echo $cmd . "\n\n";
    }
    else {
        passthru($cmd);
    }
    
};

function system_user_exists($user_name){
    $user_name = preg_replace('/[^a-z\-]/i', '', $user_name);
    $user_name = escapeshellarg($user_name);
    exec("getent passwd '$user_name'", $output, $return_var);
    return (bool) ($return_var == 0);
}

function system_group_exists($group_name){
    $group_name = preg_replace('/[^a-z\-]/i', '', $group_name);
    $group_name = escapeshellarg($group_name);
    exec("getent group '$group_name'", $output, $return_var);
    return (bool) ($return_var == 0);
}

WP_CLI::add_command('permissions', $permissions_command );

WP_CLI::add_command('perms', $permissions_command );
