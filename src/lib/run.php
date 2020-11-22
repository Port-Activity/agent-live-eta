<?php
namespace SMA\PAA\AGENT;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once "init.php";

use SMA\PAA\OAUTHCLIENT\OAuthClient;
use SMA\PAA\AGENT\LIVEETA\LiveEta;
use SMA\PAA\SERVICE\StateService;
use SMA\PAA\AINO\AinoClient;
use SMA\PAA\AGENT\LIVEETA\GeometryModel;
use SMA\PAA\AGENT\LIVEETA\LiveEtaTools;
use SMA\PAA\TOOL\DateTools;

const HOURS_BEFORE_ARRAVING = 36;
echo "Starting job.\n";

$apiKey = getenv("API_KEY");
$apiUrl = getenv("API_URL");
$ainoKey = getenv("AINO_API_KEY");
$oAuthUrl = getenv("LIVE_ETA_OAUTH_URL");
$oAuthPath = getenv("LIVE_ETA_OAUTH_PATH");
$oAuthClientId = getenv("LIVE_ETA_OAUTH_CLIENT_ID");
$oAuthClientSecret = getenv("LIVE_ETA_OAUTH_CLIENT_SECRET");
$oAuthGrantType = getenv("LIVE_ETA_OAUTH_GRANT_TYPE");
$oAuthAudience = getenv("LIVE_ETA_OAUTH_AUDIENCE");
$liveEtaUrl = getenv("LIVE_ETA_REQUEST_URL");
$liveEtaPath = getenv("LIVE_ETA_REQUEST_PATH");
$destinationCoordinates = getenv("DESTINATION_COORDINATES");
$crossingGeometry = new GeometryModel(getenv("CROSSING_GEOMETRY"));

$destinationCoordinatesArray = explode(",", $destinationCoordinates);
$destinationLat = $destinationCoordinatesArray[0];
$destinationLon = $destinationCoordinatesArray[1];
$aino = null;
if ($ainoKey) {
    $aino = new AinoClient($ainoKey, "Live ETA service", "Live ETA");
}
$ainoTimestamp = gmdate("Y-m-d\TH:i:s\Z");

$ainoToAgent = null;
if ($ainoKey) {
    $toApplication = parse_url($apiUrl, PHP_URL_HOST);
    $ainoToAgent = new AinoClient($ainoKey, "Live ETA", $toApplication);
}

$apiParameters = ["imo", "time", "payload"];
$apiConfig = new ApiConfig($apiKey, $apiUrl, $apiParameters);

// Get access token to live ETA
$accessToken = null;
$service = new StateService();
try {
    $key = 'agent-live-eta-token';
    $oAuthModel = $service->getSet(
        $key,
        function () use (
            $oAuthClientId,
            $oAuthClientSecret,
            $oAuthGrantType,
            $oAuthAudience,
            $oAuthUrl,
            $oAuthPath
        ) {
            $oAuthClient = new OAuthClient();
            return $oAuthClient->getAccessToken(
                $oAuthClientId,
                $oAuthClientSecret,
                $oAuthGrantType,
                $oAuthAudience,
                $oAuthUrl,
                $oAuthPath
            );
        }
    );
    $accessToken = $oAuthModel->token();
    // note: we have to store absolute and set expires related to it keep track of ttl
    // once expires reached there is no data anymore coming from redis and callback is run again
    $service->setExpires($key, $oAuthModel->expiresAt() - time() - 60);
} catch (\Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    if (isset($aino)) {
        $aino->failure($ainoTimestamp, "Live ETA agent failed", "Access token", "timestamp", [], []);
    }
    exit(0);
}

if (isset($aino)) {
    $aino->succeeded($ainoTimestamp, "Live ETA agent succeeded", "Access token", "timestamp", [], []);
}

// Get active IMOs
$datas = $service->get(StateService::LATEST_PORT_CALL_STATUSES) ?: [];
echo "Found " . sizeof($datas) . " active portcalls.\n";
echo "Raw data: " . print_r($datas, true) . ".\n";

$dateTools = new DateTools();
$liveEtaTools = new LiveEtaTools();
$imosToPoll = $liveEtaTools->resolveImosToPoll($datas, HOURS_BEFORE_ARRAVING, $dateTools->now());

echo "Found " . sizeof($imosToPoll) . " imos for polling.\n";
echo "IMOs: " . implode(", ", $imosToPoll) . "\n";
echo "Each of these vessels are arriving withing " . HOURS_BEFORE_ARRAVING . " hours.\n";

$agent = new LiveEta($liveEtaUrl, $liveEtaPath, $accessToken, $ainoToAgent);

$successCt = 0;
$failCt = 0;
$successImos = "";
$failImos = "";
foreach ($imosToPoll as $imo) {
    echo "Fetching Live ETA data for IMO: " . $imo ."\n";
    try {
        $agent->execute($apiConfig, $imo, $destinationLat, $destinationLon, $crossingGeometry);
        $successCt += 1;
        $successImos .= "," . $imo;
    } catch (\Exception $e) {
        $failCt += 1;
        $failImos .= "," . $imo;
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
}

$ainoMeta = [
    "success IMOs" => ltrim($successImos, ","),
    "fail IMOs" => ltrim($failImos, ","),
    "ok" => $successCt,
    "failed" => $failCt
];

if (sizeof($imosToPoll) > 0 && isset($aino)) {
    if ($successCt > 0) {
        $aino->succeeded(
            $ainoTimestamp,
            "Live ETA agent succeeded",
            "Batch run",
            "timestamp",
            [],
            $ainoMeta
        );
    } else {
        $aino->failure(
            $ainoTimestamp,
            "Live ETA agent failed",
            "Batch run",
            "timestamp",
            [],
            $ainoMeta
        );
    }
}

echo "All done.\n";
