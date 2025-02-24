<?php

namespace Zaengle\Audit\Tests;

use Illuminate\Database\Eloquent\Model;
use Zaengle\Audit\MakesAudits;

class TestModel extends Model
{
    use MakesAudits;

    protected $table = 'test_models';

    protected $fillable = ['name'];

    protected $casts = [
        'audits' => 'json',
    ];
}
