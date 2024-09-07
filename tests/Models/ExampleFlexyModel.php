<?php

namespace AuroraWebSoftware\FlexyField\Tests\Models;

use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Traits\Flexy;
use Illuminate\Database\Eloquent\Model;

class ExampleFlexyModel extends Model implements FlexyModelContract
{
    use Flexy;

    protected $table = 'ff_example_flexy_models';

    protected $guarded = [];
}
