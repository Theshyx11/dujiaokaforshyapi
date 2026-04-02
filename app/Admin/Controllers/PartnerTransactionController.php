<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\PartnerWalletTransaction;
use App\Models\Partner;
use App\Models\PartnerWalletTransaction as PartnerWalletTransactionModel;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class PartnerTransactionController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new PartnerWalletTransaction(['partner', 'sourcePartner', 'order', 'goods']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'DESC');
            $grid->column('id')->sortable();
            $grid->column('partner.email', '合伙人');
            $grid->column('type')->using(PartnerWalletTransactionModel::getTypeMap())->label();
            $grid->column('amount');
            $grid->column('status')->using(PartnerWalletTransactionModel::getStatusMap())->label();
            $grid->column('level');
            $grid->column('rate');
            $grid->column('order.order_sn', '关联订单')->copyable();
            $grid->column('sourcePartner.email', '来源合伙人');
            $grid->column('goods.gd_name', '关联商品');
            $grid->column('available_at');
            $grid->column('description')->limit(30);
            $grid->column('created_at');

            $grid->disableCreateButton();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('partner_id', '合伙人')->select(Partner::query()->pluck('email', 'id'));
                $filter->equal('type')->select(PartnerWalletTransactionModel::getTypeMap());
                $filter->equal('status')->select(PartnerWalletTransactionModel::getStatusMap());
                $filter->equal('order_id');
            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableEdit();
                $actions->disableDelete();
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new PartnerWalletTransaction(['partner', 'sourcePartner', 'order', 'goods', 'redemption']), function (Show $show) {
            $show->field('id');
            $show->field('partner.email', '合伙人');
            $show->field('type')->using(PartnerWalletTransactionModel::getTypeMap());
            $show->field('amount');
            $show->field('status')->using(PartnerWalletTransactionModel::getStatusMap());
            $show->field('level');
            $show->field('rate');
            $show->field('order.order_sn', '关联订单');
            $show->field('sourcePartner.email', '来源合伙人');
            $show->field('goods.gd_name', '关联商品');
            $show->field('redemption.redemption_no', '兑换记录');
            $show->field('available_at');
            $show->field('description');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }
}
