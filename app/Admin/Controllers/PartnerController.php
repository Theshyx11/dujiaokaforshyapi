<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Partner;
use App\Models\Partner as PartnerModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Hash;

class PartnerController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new Partner(['inviter']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'DESC');
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('email')->copyable();
            $grid->column('referral_code')->copyable();
            $grid->column('inviter.email', '上级合伙人');
            $grid->column('status')->using(PartnerModel::getStatusMap())->label([
                PartnerModel::STATUS_ENABLED => 'success',
                PartnerModel::STATUS_DISABLED => 'danger',
            ]);
            $grid->column('last_login_at');
            $grid->column('created_at');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('name');
                $filter->equal('email');
                $filter->equal('referral_code');
                $filter->equal('status')->select(PartnerModel::getStatusMap());
                $filter->equal('inviter_id', '上级合伙人')->select(
                    PartnerModel::query()->orderBy('id', 'DESC')->pluck('email', 'id')
                );
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new Partner(['inviter']), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('email');
            $show->field('referral_code');
            $show->field('inviter.email', '上级合伙人');
            $show->field('status')->using(PartnerModel::getStatusMap());
            $show->field('last_login_at');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    protected function form()
    {
        return Form::make(new Partner(['inviter']), function (Form $form) {
            $form->display('id');
            $form->text('name')->required();
            $form->email('email')->required();
            $form->text('referral_code')->required();
            $form->select('inviter_id', '上级合伙人')->options(
                PartnerModel::query()->orderBy('id', 'DESC')->pluck('email', 'id')
            );
            $form->password('password')->customFormat(function () {
                return '';
            })->help('留空则不修改密码');
            $form->radio('status')->options(PartnerModel::getStatusMap())->default(PartnerModel::STATUS_ENABLED);
            $form->display('last_login_at');
            $form->display('created_at');
            $form->display('updated_at');
            $form->disableDeleteButton();

            $form->saving(function (Form $form) {
                if ($form->inviter_id && (int) $form->inviter_id === (int) $form->getKey()) {
                    throw new \RuntimeException('上级合伙人不能设置为自己');
                }
                $form->email = strtolower(trim((string) $form->email));
                $form->referral_code = strtoupper(trim((string) $form->referral_code));
                if (!$form->password) {
                    if ($form->isCreating()) {
                        throw new \RuntimeException('新增合伙人时必须设置密码');
                    }
                    $form->deleteInput('password');
                } else {
                    $form->password = Hash::make($form->password);
                }
            });
        });
    }
}
