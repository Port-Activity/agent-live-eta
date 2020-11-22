<?php
namespace SMA\PAA\TOOL;

use DateTime;
use DateTimeZone;

class DateTools
{
    public function now()
    {
        return $this->isoDate("");
    }
    public function isoDate(string $dateTime): String
    {
        $time = new DateTime($dateTime);
        if (isset($time)) {
            $time->setTimeZone(new DateTimeZone("UTC"));
            return $time->format("Y-m-d\TH:i:sP");
        }
        return null;
    }
    public function isValidIsoDateTime(string $date)
    {
        $dateTime = DateTime::createFromFormat(DateTime::ATOM, $date);
        return $dateTime instanceof DateTime && $dateTime->format(DateTime::ATOM) === $date;
    }
    public function differenceSeconds(string $fromDate, string $toDate)
    {
        return strtotime($toDate) - strtotime($fromDate);
    }
    public function formatUtc(string $dateTime, string $format): String
    {
        $time = new DateTime($dateTime);
        if (isset($time)) {
            $time->setTimeZone(new DateTimeZone("UTC"));
            return $time->format($format);
        }
        return null;
    }
}
