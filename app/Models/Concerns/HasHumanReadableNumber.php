<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait HasHumanReadableNumber
{
    public static function bootHasHumanReadableNumber(): void
    {
        static::creating(function ($model) {
            if (empty($model->number)) {
                $seq = DB::selectOne("SELECT nextval('{$model->getNumberSequenceName()}') AS val")->val;
                $model->number = static::numberPrefix().'-'.str_pad($seq, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    abstract public static function numberPrefix(): string;

    public function getNumberSequenceName(): string
    {
        return $this->getTable().'_number_seq';
    }
}
