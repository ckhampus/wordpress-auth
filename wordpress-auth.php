<?php

/*
Plugin Name: WordPress Authentication
Description: Authenticate using Facebook.
Version: 0.0.1
Author: Queensbridge AB
Author URI: http://queensbridge.se
*/

require __DIR__.'/vendor/autoload.php';

use Queensbridge\Authentication\Authenticator;
use Queensbridge\Authentication\Strategies\FacebookStrategy;

$authenticator = new Authenticator();
$authenticator->register('facebook', new FacebookStrategy('230248893752703', '8b625fbf0c57d0b49f571283f1d00b3c'));

add_action('init', function () use ($authenticator) {
    $authenticator->handle();
});

function get_login_url($provider)
{
    global $authenticator;
    return $authenticator['url_generator']->generate('auth_login', array('provider' => $provider), true);
}
