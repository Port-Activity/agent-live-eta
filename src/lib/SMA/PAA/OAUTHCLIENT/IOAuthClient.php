<?php
namespace SMA\PAA\OAUTHCLIENT;

use SMA\PAA\CURL\IApi;

interface IOAuthClient
{
    public function getAccessToken(
        string $clientId,
        string $clientSecret,
        string $grantType,
        string $audience,
        string $url,
        string $path,
        IApi $api = null
    ): ?OAuthModel;
}
