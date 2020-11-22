<?php
namespace SMA\PAA\AGENT\LIVEETA;

use SMA\PAA\CURL\IApi;
use SMA\PAA\CURL\Api;
use SMA\PAA\CURL\CurlRequest;
use SMA\PAA\RESULTPOSTER\IResultPoster;
use SMA\PAA\RESULTPOSTER\ResultPoster;
use SMA\PAA\AGENT\ApiConfig;
use SMA\PAA\AINO\AinoClient;

use Exception;

class LiveEta
{
    private $aino;
    private $liveEtaUrl;
    private $liveEtaPath;
    private $accessToken;
    private $liveEtaApi;
    private $resultPoster;

    public function __construct(
        string $liveEtaUrl,
        string $liveEtaPath,
        string $accessToken,
        AinoClient $aino = null,
        IApi $liveEtaApi = null,
        IResultPoster $resultPoster = null
    ) {
        $this->liveEtaUrl = $liveEtaUrl;
        $this->liveEtaPath = $liveEtaPath;
        $this->accessToken = $accessToken;
        $this->aino = $aino;
        $this->liveEtaApi = $liveEtaApi ?: new Api($liveEtaUrl);
        $this->resultPoster = $resultPoster ?: new ResultPoster(new CurlRequest());

        date_default_timezone_set("UTC");
    }

    public function execute(
        ApiConfig $apiConfig,
        int $imo,
        float $destinationLat,
        float $destinationLon,
        GeometryModel $crossingGeometry
    ) {
        $rawResults = $this->fetchResults($imo, $destinationLat, $destinationLon, $crossingGeometry);
        $parsedResults = $this->parseResults($rawResults, $imo);
        $this->postResults($apiConfig, $parsedResults);
    }

    private function query(
        int $imo,
        float $destinationLat,
        float $destinationLon,
        GeometryModel $crossingGeometry
    ): array {
        return  [
            "imo" => $imo,
            "destination_lat" => $destinationLat,
            "destination_lon" => $destinationLon,
            "crossing_geometry" => $crossingGeometry->geometry()
        ];
    }

    private function fetchResults(
        int $imo,
        float $destinationLat,
        float $destinationLon,
        GeometryModel $crossingGeometry
    ): array {
        $query = $this->query($imo, $destinationLat, $destinationLon, $crossingGeometry);
        $res = $this->liveEtaApi->get($this->accessToken, $this->liveEtaPath, $query);

        $ainoTimestamp = gmdate("Y-m-d\TH:i:s\Z");

        if ($res === false) {
            throw new \Exception("Can't fetch data for imo: " . $imo);
        }

        return $res;
    }

    private function parseResults(array $rawResults, int $imo): array
    {
        $converted = [];
        $tools = new LiveEtaTools();

        $ainoTimestamp = gmdate("Y-m-d\TH:i:s\Z");

        try {
            $converted = $tools->convert($rawResults, $imo);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            if (isset($this->aino)) {
                $this->aino->failure(
                    $ainoTimestamp,
                    "Live ETA agent failed",
                    "Parse",
                    "timestamp",
                    ["imo" => $imo],
                    ["exception" => json_encode($e->getMessage())]
                );
            }
        }

        return $converted;
    }

    private function postResults(ApiConfig $apiConfig, array $result)
    {
        if (empty($result)) {
            return;
        }

        $ainoTimestamp = gmdate("Y-m-d\TH:i:s\Z");

        $ainoFlowId = $this->resultPoster->resultChecksum($apiConfig, $result);
        try {
            $this->resultPoster->postResult($apiConfig, $result);
            if (isset($this->aino)) {
                $this->aino->succeeded(
                    $ainoTimestamp,
                    "Live ETA agent succeeded",
                    "Post",
                    "timestamp",
                    ["imo" => $result["imo"]],
                    [],
                    $ainoFlowId
                );
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            if (isset($this->aino)) {
                $this->aino->failure(
                    $ainoTimestamp,
                    "Live ETA agent failed",
                    "Post",
                    "timestamp",
                    [],
                    [],
                    $ainoFlowId
                );
            }
        }
    }
}
