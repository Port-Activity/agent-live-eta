<?php
namespace SMA\PAA\OAUTHCLIENT;

class OAuthModel
{
    public function __construct(array $data, int $time = null)
    {
        $this->data = $data;
        $this->time = $time;
    }
    public function token()
    {
        return $this->returnIfExists("access_token", $this->data);
    }
    public function expiresAt()
    {
        return $this->time + $this->returnIfExists("expires_in", $this->data);
    }
    private function returnIfExists($key, $array)
    {
        return array_key_exists($key, $array) ? $array[$key] : null;
    }
}
