<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryField extends Model
{
    protected $table = 'category_field';

    protected $fillable = [
        'field_name',
        'field_type',
        'required',
        'display_label',
        'prompt',
        'validation_rule',
        'reference_table',
        'reference_column',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
