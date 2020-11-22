<?php
namespace SMA\PAA\AGENT\LIVEETA;

class GeometryModel
{
    private $geometry;
    public function __construct(string $geometry)
    {
        if (preg_match("/^POLYGON\(\((.*?)\)\)$/", $geometry, $matches)) {
            $tokens = array_map(function ($t) {
                return trim($t);
            }, explode(",", $matches[1]));
            foreach ($tokens as $one) {
                $tokens2 = array_map(function ($t) {
                    return trim($t);
                }, explode(" ", $one));
                if (sizeof($tokens2) !== 2) {
                    throw new \Exception("Invalid coordinate in geometry: " . $one);
                }
                if (is_numeric($tokens2[0]) && is_numeric($tokens2[1])) {
                    // good
                } else {
                    throw new \Exception("Invalid coordinate in geometry: " . $one);
                }
            }
            $this->geometry = $geometry;
        };
        if (!$this->geometry) {
            throw new \Exception("Invalid geometry: " . $geometry);
        }
    }
    public function geometry(): string
    {
        return $this->geometry;
    }
}
