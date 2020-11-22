<?php
namespace SMA\PAA\OAUTHCLIENT;

use SMA\PAA\CURL\IApi;
use SMA\PAA\CURL\Api;

use Exception;

final class OAuthClient implements IOAuthClient
{
    public function getAccessToken(
        string $clientId,
        string $clientSecret,
        string $grantType,
        string $audience,
        string $url,
        string $path,
        IApi $api = null
    ): ?OAuthModel {
        $accessToken = null;

        if ($api === null) {
            $api = new Api($url);
        }

        $values = [];
        $values["client_id"] = $clientId;
        $values["client_secret"] = $clientSecret;
        $values["grant_type"] = $grantType;
        $values["audience"] = $audience;
        $res = $api->post("", $path, $values);
        if (!empty($res["access_token"])) {
            return new OAuthModel($res, time());
        }

        throw new \Exception("Could not fetch access token.");
    }
}
