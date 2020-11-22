<?php

namespace SMA\PAA\AGENT\LIVEETA;

use PHPUnit\Framework\TestCase;

use SMA\PAA\FAKECURL\FakeApi;
use SMA\PAA\FAKERESULTPOSTER\FakeResultPoster;
use SMA\PAA\AGENT\ApiConfig;

final class GeometryModelTest extends TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testSettingInvalidGeometryFails(): void
    {
        new GeometryModel("0.0,foo");
    }
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid coordinate in geometry: 17.5142
     */
    public function testSettingGeometryFailsWhenPolygonWithBadCoordinate(): void
    {
        $geometry = "POLYGON((17.5142,17.6021 60.71119999999999))";
        $model = new GeometryModel($geometry);
        $this->assertEquals($geometry, $model->geometry());
    }
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid coordinate in geometry: 18.5142 a
     */
    public function testSettingGeometryFailsWhenPolygonWithBadCoordinateWhenNotNumeric(): void
    {
        $geometry = "POLYGON((18.5142 a,17.6021 60.71119999999999))";
        $model = new GeometryModel($geometry);
        $this->assertEquals($geometry, $model->geometry());
    }
    public function testSettingProperGeometryIsAcceptedByModel(): void
    {
        // phpcs:ignore
        $geometry = "POLYGON((17.5142 60.62209999999999,17.6021 60.71119999999999,17.5829 60.775000000000006,17.4724 60.7894,17.4133 60.78739999999999,17.2499 60.74340000000001,17.1249 60.67660000000001,17.2664 60.63389999999998,17.5122 60.6224,17.5142 60.62209999999999,17.5142 60.62209999999999))";
        $model = new GeometryModel($geometry);
        $this->assertEquals($geometry, $model->geometry());
    }
}
