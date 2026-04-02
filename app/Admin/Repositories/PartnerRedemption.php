<?php

namespace App\Admin\Repositories;

use App\Models\PartnerRedemption as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class PartnerRedemption extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}
