<?php
namespace SMA\PAA\SERVICE;

interface IStateService
{
     // Note 1: there is relation from this key in agent-fenix so don't change this
     // Note 2: this key should always be awailable for fenix agent
    const LATEST_PORT_CALL_IMOS     = "port_call_imos.latest";
     // Note 3: this key should always be awailable for agent-shiplog
     const LATEST_PORT_CALL_STATUSES = "port_call_statuses.latest";

    const LATEST_PORT_CALLS         = "port_calls.latest";
    const LATEST_LOGISTICS          = "logistics.latest";
    const PINNED_VESSELS            = "pinned_vessels";
    public function get(string $key);
    public function getSet(string $key, callable $callback);
    public function delete(string $key);
}
