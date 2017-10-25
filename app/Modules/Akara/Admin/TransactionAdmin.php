<?php

namespace Modules\Akara\Admin;


use Modules\Admin\Components\ModelAdmin;
use Modules\Akara\Forms\TransactionForm;
use Modules\Akara\Models\Transaction;

class TransactionAdmin extends ModelAdmin
{
    public function getModel()
    {
        return new Transaction;
    }

    public function getColumns()
    {
        return [
            'coin',
            'user',
            'ico',
            'amount',
            'base_conversion_rate',
            'bonus',
            'type',
            'status',
            'created_at'
        ];
    }

    public function getCreateForm()
    {
        return TransactionForm::class;
    }
}