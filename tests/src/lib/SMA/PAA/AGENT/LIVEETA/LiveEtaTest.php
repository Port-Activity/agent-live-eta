<?php

namespace SMA\PAA\AGENT\LIVEETA;

use PHPUnit\Framework\TestCase;

use SMA\PAA\FAKECURL\FakeApi;
use SMA\PAA\FAKERESULTPOSTER\FakeResultPoster;
use SMA\PAA\AGENT\ApiConfig;

final class LiveEtaTest extends TestCase
{
    private function mockUpdatedAt(array $datas)
    {
        foreach ($datas as $k => $data) {
            if (isset($data["payload"]) && isset($data["payload"]["updated_at"])) {
                $data["payload"]["updated_at"] = "removed";
                $datas[$k] = $data;
            }
        }
        return $datas;
    }
    public function testExecuteValid(): void
    {
        $fakeApi = new FakeApi();
        $resultPoster = new FakeResultPoster();
        $liveEta = new LiveEta("url", "path", "token", null, $fakeApi, $resultPoster);
        $fakeApi->getResponse = json_decode(file_get_contents(__DIR__ . "/ValidServerData.json"), true);
        $liveEta->execute(
            new ApiConfig("key", "http://url/foo", ["foo"]),
            9295347,
            12.345,
            67.89,
            new GeometryModel("POLYGON((1.0 1.0, 2.0 2.0, 3.0 3.0))")
        );

        /*
        file_put_contents(
            __DIR__ . "/ValidPosterData.json",
            json_encode($this->mockUpdatedAt($resultPoster->results), null, JSON_PRETTY_PRINT)
        );
        */

        $this->assertEquals(
            json_decode(file_get_contents(__DIR__ . "/ValidPosterData.json"), true),
            $this->mockUpdatedAt($resultPoster->results)
        );
    }
    public function testExecuteEmpty(): void
    {
        $fakeApi = new FakeApi();
        $resultPoster = new FakeResultPoster();
        $liveEta = new LiveEta("url", "path", "token", null, $fakeApi, $resultPoster);
        $fakeApi->getResponse = json_decode(file_get_contents(__DIR__ . "/EmptyServerData.json"), true);
        $res = $liveEta->execute(
            new ApiConfig("key", "http://url/foo", ["foo"]),
            9295347,
            12.345,
            67.89,
            new GeometryModel("POLYGON((1.0 1.0, 2.0 2.0, 3.0 3.0))")
        );

        $this->assertEquals(
            null,
            $resultPoster->results
        );
    }
}
