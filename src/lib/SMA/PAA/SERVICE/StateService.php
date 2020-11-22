<?php
namespace SMA\PAA\SERVICE;

use SMA\PAA\Session;

class StateService implements IStateService
{
    public function get(string $key)
    {
        $client = new RedisClient();
        $data = $client->get($key);
        if ($data) {
            return unserialize($data);
        }
        return null;
    }
    public function set(string $key, $data)
    {
        $client = new RedisClient();
        return $client->set($key, serialize($data));
    }
    public function getSet(string $key, callable $callback, int $expires = null)
    {
        $client = new RedisClient();
        $data = $client->get($key);
        if ($data) {
            $data = unserialize($data);
        } else {
            $data = call_user_func($callback);
            $client->set($key, serialize($data));
        }
        if ($expires) {
            $client->expire($key, $expires);
        }
        return $data;
    }
    public function setExpires(string $key, int $expires)
    {
        $client = new RedisClient();
        return $client->expire($key, $expires);
    }
    public function delete(string $key)
    {
        $client = new RedisClient();
        $client->del($key);
    }
}
