<?php
namespace SMA\PAA\AGENT\LIVEETA;

use DateTime;
use SMA\PAA\TOOL\DateTools;
use SMA\PAA\ORM\PortCallModel;

use const SMA\PAA\SERVICE\STATUS_PORT;

class LiveEtaTools
{
    public function convert(array $data, int $imo): array
    {
        // Note: we have also here ETA for a destination
        /*
        if (!empty($data["features"][0]["properties"]["destination"]["eta"])) {
            $liveEtaDestinationTime = $data["features"][0]["properties"]["destination"]["eta"];
        }
        */

        if (!empty($data["features"][0]["properties"]["crosspoint"]["eta"])) {
            $liveEtaCrosspointTime = $data["features"][0]["properties"]["crosspoint"]["eta"];
        } else {
            error_log("Cannot convert data: " . json_encode($data));

            if (empty($data["features"])) {
                // We are getting empty results, this should not happen
                throw new \Exception("Received features array empty for IMO: " . $imo);
            }

            return [];
        }

        $liveEtaCrosspointTime = preg_replace("/(.*)\.[0-9]+Z$/", "$1Z", $liveEtaCrosspointTime);
        $time = "";
        $dateTime = DateTime::createFromFormat("Y-m-d\TH:i:sP", $liveEtaCrosspointTime);
        if ($dateTime !== false) {
            $time = $dateTime->format("Y-m-d\TH:i:sO");
        } else {
            throw new \Exception("Can't create DateTime from time " . $liveEtaCrosspointTime);
        }

        $tools = new DateTools();
        $payload["source"] = "Live_ETA";
        $payload["updated_at"] = $tools->now();
        $payload["original_message"] = $data;

        return [
            "imo"           => $imo,
            "time"          => $time,
            "payload"       => $payload
        ];
    }
    public function resolveImosToPoll(array $datas, int $minimumEtaDeltaBeforePollHours, string $nowIsoString)
    {
        return array_reduce($datas, function ($carry, $data) use ($minimumEtaDeltaBeforePollHours, $nowIsoString) {
            $tools = new DateTools();
            if ($data["state"] === PortCallModel::STATUS_ARRIVING && $data["imo"] < 100000000) {
                if ($tools->differenceSeconds(
                    $nowIsoString,
                    $data["current_eta"]
                ) < $minimumEtaDeltaBeforePollHours * 60 * 60
                ) {
                    $carry[] = $data["imo"];
                }
            }
            return $carry;
        }, []);
    }
}
