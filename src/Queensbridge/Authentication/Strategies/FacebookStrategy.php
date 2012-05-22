<?php

namespace Queensbridge\Authentication\Strategies;

use Symfony\Component\HttpFoundation\Request;

class FacebookStrategy extends Strategy
{
    private $facebook;

    public function __construct($appId, $secret)
    {
        $this->facebook = new \Facebook(array(
            'appId' => $appId,
            'secret' => $secret
        ));
    }

    public function authenticate(Request $request)
    {
        $fb = $this->facebook;
        $uid = $fb->getUser();

        //var_dump(wp_get_current_user());

        try {
            $profile = $fb->api('/me','GET');
        } catch (\Exception $e) {
            $params = array(
                'scope' => 'email',
                'redirect_uri' => $this->getCallbackUrl()
            );


            return $fb->getLoginUrl($params);
        }


        return $this->getCallbackUrl();
    }

    public function callback(Request $request)
    {
        $fb = $this->facebook;
        $uid = $fb->getUser();

        try {
            $profile = $fb->api('/me','GET');

            return array(
                'provider' => $this->getName(),
                'uid' => $profile['id'],
                'info' => array(
                    'name' => $profile['name'],
                    'email' => $profile['email'],
                    'username' => $profile['username'],
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name']
                )
            );
        } catch (\Exception $e) {
            return array();
        }
    }
}
