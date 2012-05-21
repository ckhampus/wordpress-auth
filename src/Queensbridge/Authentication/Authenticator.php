<?php

namespace Queensbridge\Authentication;

use Queensbridge\Authentication\Strategies\StrategyCollection;
use Queensbridge\Authentication\Strategies\Strategy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Authenticator extends \Pimple
{
    function __construct()
    {
        $app = $this;

        $this['routes'] = $this->share(function () {
            return new RouteCollection();
        });

        $this['routes']->add('auth', new Route('/auth/{provider}', array(
            '_controller' => array($this, 'authenticate')
        )));

        $this['routes']->add('auth_callback', new Route('/auth/{provider}/callback', array(
            '_controller' => array($this, 'callback')
        )));

        $this['request_context'] = $this->share(function () {
            return new RequestContext();
        });

        $this['url_matcher'] = $this->share(function () use ($app) {
            return new UrlMatcher($app['routes'], $app['request_context']);
        });

        $this['url_generator'] = $this->share(function () use ($app) {
            return new UrlGenerator($app['routes'], $app['request_context']);
        });

        $this['strategies'] = $this->share(function () {
            return new StrategyCollection();
        });
    }

    /**
     * Register a new authentication strategy.
     * 
     * @param  string                                          $name     The strategy name.
     * @param  Strategy $strategy The authentication strategy.
     */
    public function register($name, Strategy $strategy)
    {
        $this['strategies']->add($name, $strategy);
        $strategy->connect($name, $this);
    }

    public function authenticate(Request $request, $provider)
    {
        $strategy = $this['strategies']->get($provider);

        $url = $strategy->authenticate($request);

        return new RedirectResponse($url);
    }

    /**
     * This method handles the authentication callback.
     * 
     *
     * @param  Request  $request  The request object.
     * @param  string   $provider The authentication provider.
     * @return Response           The response.
     */
    public function callback(Request $request, $provider)
    {
        $strategy = $this['strategies']->get($provider);

        $userdata = $strategy->callback($request);

        $pid = sha1(serialize($userdata['provider'].$userdata['uid']));

        if (empty($userdata)) {
            return new RedirectResponse($strategy->getAuthUrl());
        } else {
            // get the currently signed in user
            $user = wp_get_current_user();

            // check if user is logged in
            if (($user instanceof \WP_User) && $user->ID != 0) {
                $uid = $user->ID;
                $pids = get_user_meta($uid, 'pids');

                if (empty($pids)) {
                    add_user_meta($uid, 'pids', $pid);
                    return new Response('account linked to '.$userdata['provider']);
                } elseif (in_array($pid, $pids)) {
                    return new Response('account already linked to '.$userdata['provider']);
                }
            } else {
                $user = $this->findUser($pid);

                if ($user != null) {
                    wp_set_auth_cookie($user->data->ID, true);
                    return new Response('successfully logged in using '.$userdata['provider']);
                } else {
                    return new Response('no user found');
                }
            }

            return new Response('wp');
        }
    }

    /**
     * Handles incoming authentication requests.
     *
     * @param  Request $request The request object.
     */
    public function handle($request = null)
    {
        if ($request === null) {
            $this['request'] = $request = Request::createFromGlobals();
        }

        $this['request_context']->fromRequest($request);

        if (strpos($request->getPathInfo(), '/auth') === 0) {

            try {
                $request->attributes->add($this['url_matcher']->match(rtrim($request->getPathInfo(), '/')));
                $response = call_user_func($request->attributes->get('_controller'), $request, $request->attributes->get('provider'));
            } catch (ResourceNotFoundException $e) {
                $path = $request->getBaseUrl().$request->getPathInfo();
                $slash = substr(get_option('permalink_structure'), -1) === '/';

                if (substr($path, -1) === '/') {
                    if ($slash === false) {
                        $path = rtrim($path, '/');
                    }
                } else {
                    if ($slash) {
                        $path .= '/';
                    }
                }

                $response = new RedirectResponse($path);
            } catch (Exception $e) {
                $response = new Response('An error occurred', 500);
            }

            $response->send();
            die();
        }
    }

    public function findUser($pid)
    {
        $user = get_users(array(
            'meta_key' => 'pids',
            'meta_value' => $pid,
            'fields' => 'ID'
        ));

        return !empty($user) ? new \WP_User($user[0]) : null;
    }
}