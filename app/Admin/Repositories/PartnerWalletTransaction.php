<?php

namespace App\Admin\Repositories;

use App\Models\PartnerWalletTransaction as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class PartnerWalletTransaction extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}
