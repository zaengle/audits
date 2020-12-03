<?php

namespace Zaengle\Audit\Tests;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class MakesAuditsTest extends BaseTestCase
{
    /** @test */
    public function it_audits_a_new_model_on_creation()
    {
        Carbon::setTestNow();

        $model = TestModel::create([
            'name' => 'test',
        ]);

        $this->assertNotNull($model->audits);
        $this->assertEquals($model->getKey(), Arr::get($model->audits[0], 'data.id'));
        $this->assertEquals('test', Arr::get($model->audits[0], 'data.name'));
        $this->assertEquals(now()->toDateTimeString(), Arr::get($model->audits[0], 'updated_at'));
    }

    /** @test */
    public function it_audits_a_new_model()
    {
        Carbon::setTestNow();

        $model = TestModel::create([
            'name' => 'test',
        ]);

        $this->assertNotNull($model->audits);
        $this->assertEquals($model->getKey(), Arr::get($model->audits[0], 'data.id'));
        $this->assertEquals('test', Arr::get($model->audits[0], 'data.name'));
        $this->assertEquals(now()->toDateTimeString(), Arr::get($model->audits[0], 'updated_at'));

        Carbon::setTestNow(now()->addDay());

        $model->update(['name' => 'new']);

        $this->assertEquals($model->getKey(), Arr::get($model->audits[1], 'data.id'));
        $this->assertEquals('new', Arr::get($model->audits[1], 'data.name'));
        $this->assertEquals(now()->toDateTimeString(), Arr::get($model->audits[1], 'updated_at'));
    }
}
