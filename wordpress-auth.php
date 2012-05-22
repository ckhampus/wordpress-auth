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
$authenticator->register('facebook', new FacebookStrategy('APPID', 'SECRET'));

add_action('init', function () use ($authenticator) {
    $authenticator->handle();
});

function get_login_url($provider)
{
    global $authenticator;
    return $authenticator['url_generator']->generate('auth_login', array('provider' => $provider), true);
}
