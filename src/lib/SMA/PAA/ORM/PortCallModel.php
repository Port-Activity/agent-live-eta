<?php
namespace SMA\PAA\ORM;

class PortCallModel
{
    const STATUS_ARRIVING       = "arriving"; // eta ata etd
    const STATUS_AT_BERTH       = "at berth"; // ops etd
    const STATUS_DEPARTING      = "departing";
    const STATUS_DEPARTED       = "departed"; // atd
    const STATUS_DONE           = "done";     // no more on timeline

    const NEXT_EVENT_ETA        = "ETA";
    const NEXT_EVENT_ETD        = "ETD";
    const NEXT_EVENT_ATD        = "ATD";
}
