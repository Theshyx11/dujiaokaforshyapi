<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\PartnerRedemption;
use App\Models\Partner;
use App\Models\PartnerRedemption as PartnerRedemptionModel;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class PartnerRedemptionController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new PartnerRedemption(['partner', 'goods']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'DESC');
            $grid->column('id')->sortable();
            $grid->column('redemption_no')->copyable();
            $grid->column('partner.email', '合伙人');
            $grid->column('goods.gd_name', '商品');
            $grid->column('quantity');
            $grid->column('total_amount');
            $grid->column('status')->using(PartnerRedemptionModel::getStatusMap())->label();
            $grid->column('error_message')->limit(30);
            $grid->column('created_at');

            $grid->disableCreateButton();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('partner_id', '合伙人')->select(Partner::query()->pluck('email', 'id'));
                $filter->equal('redemption_no');
                $filter->equal('status')->select(PartnerRedemptionModel::getStatusMap());
            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableEdit();
                $actions->disableDelete();
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new PartnerRedemption(['partner', 'goods']), function (Show $show) {
            $show->field('id');
            $show->field('redemption_no');
            $show->field('partner.email', '合伙人');
            $show->field('goods.gd_name', '商品');
            $show->field('quantity');
            $show->field('unit_price');
            $show->field('total_amount');
            $show->field('status')->using(PartnerRedemptionModel::getStatusMap());
            $show->field('error_message');
            $show->field('codes')->unescape()->as(function ($codes) {
                $value = is_array($codes) ? implode(PHP_EOL, $codes) : (string) $codes;
                return '<textarea class="form-control" rows="12">' . e($value) . '</textarea>';
            });
            $show->field('created_at');
            $show->field('updated_at');
        });
    }
}
