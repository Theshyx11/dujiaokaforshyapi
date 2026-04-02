<?php

namespace App\Admin\Repositories;

use App\Models\Partner as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Partner extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}
