<?php

namespace Queensbridge\Authentication\Strategies;

use Queensbridge\Authentication\Authenticator;

use Symfony\Component\HttpFoundation\Request;

abstract class Strategy
{
    private $name;

    private $authenticator;

    abstract public function authenticate(Request $request);

    abstract public function callback(Request $request);

    public function connect($name, Authenticator $authenticator)
    {
        $this->name = $name;

        $this->authenticator = $authenticator;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAuthenticator()
    {
        return $this->authenticator;
    }

    public function getAuthUrl()
    {
        return $this->generate('auth', array('provider' => $this->name));
    }

    public function getCallbackUrl()
    {
        return $this->generate('auth_callback', array('provider' => $this->name));
    }

    public function generate($name, $data)
    {
        return $this->authenticator['url_generator']->generate($name, $data, true);
    }

    public function hash($userId)
    {
        return sha1($this->name.$userId);
    }
}
