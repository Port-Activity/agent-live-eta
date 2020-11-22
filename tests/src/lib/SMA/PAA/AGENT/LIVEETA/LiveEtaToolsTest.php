<?php

namespace SMA\PAA\AGENT\LIVEETA;

use PHPUnit\Framework\TestCase;

use SMA\PAA\FAKECURL\FakeCurlRequest;
use SMA\PAA\FAKERESULTPOSTER\FakeResultPoster;
use SMA\PAA\AGENT\ApiConfig;

final class LiveEtaToolsTest extends TestCase
{
    private function mockUpdatedAt(array $data)
    {
        if (isset($data["payload"]) && isset($data["payload"]["updated_at"])) {
            $data["payload"]["updated_at"] = "removed";
        }
        return $data;
    }
    public function testParseValidData(): void
    {
        $inJson = file_get_contents(__DIR__ . "/ValidServerData.json");
        $inData = json_decode($inJson, true);
        $outJson = file_get_contents(__DIR__ . "/ValidPosterData.json");
        $outData = json_decode($outJson, true);
        $outData = $outData[0];
        $tools = new LiveEtaTools();

        $this->assertEquals(
            $outData,
            $this->mockUpdatedAt($tools->convert($inData, 9295347))
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Received features array empty for IMO: 9295347
     */
    public function testParseEmptyData(): void
    {
        $inJson = file_get_contents(__DIR__ . "/EmptyServerData.json");
        $inData = json_decode($inJson, true);

        $tools = new LiveEtaTools();

        $tools->convert($inData, 9295347);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can't create DateTime from time
     */
    public function testParseInvalidTimeData(): void
    {
        $inJson = file_get_contents(__DIR__ . "/InvalidServerTimeData.json");
        $inData = json_decode($inJson, true);

        $tools = new LiveEtaTools();
        $tools->convert($inData, 9295347);
    }
    private function datas()
    {
        // phpcs:ignore
        return json_decode('[{"imo":100000170,"state":"arriving","current_eta":"2020-06-29 11:47:27+00"},{"imo":9624861,"state":"at berth","current_eta":"2020-06-29 14:00:00+00"},{"imo":9491496,"state":"at berth","current_eta":"2020-07-01 04:45:00+00"},{"imo":8912481,"state":"at berth","current_eta":"2020-07-01 17:30:00+00"},{"imo":8902589,"state":"arriving","current_eta":"2020-07-02 00:30:00+00"},{"imo":9237034,"state":"arriving","current_eta":"2020-07-02 17:00:00+00"},{"imo":9386524,"state":"arriving","current_eta":"2020-07-03 05:00:00+00"},{"imo":9431032,"state":"arriving","current_eta":"2020-07-03 13:00:00+00"},{"imo":9124419,"state":"arriving","current_eta":"2020-07-04 20:00:00+00"},{"imo":9267560,"state":"arriving","current_eta":"2020-07-05 16:00:00+00"},{"imo":9201803,"state":"arriving","current_eta":"2020-07-05 23:00:00+00"},{"imo":9523548,"state":"arriving","current_eta":"2020-07-06 06:00:00+00"}]', true);
    }
    public function testResolvingEtasToPoll()
    {
        $tools = new LiveEtaTools();
        $this->assertEquals([8902589, 9237034], $tools->resolveImosToPoll($this->datas(), 24, "2020-07-02T00:00:00Z"));
    }
    public function testResolvingEtasToPollWhenAllEtaMoreThan24HoursInFuture()
    {
        $tools = new LiveEtaTools();
        $this->assertEquals([], $tools->resolveImosToPoll($this->datas(), 24, "2020-06-02T00:00:00Z"));
    }
    public function testResolvingEtasToPollWhenAllEtasInPast()
    {
        $tools = new LiveEtaTools();
        $this->assertEquals(
            [8902589, 9237034, 9386524, 9431032, 9124419, 9267560, 9201803, 9523548],
            $tools->resolveImosToPoll($this->datas(), 24, "2020-08-02T00:00:00Z")
        );
    }
}
