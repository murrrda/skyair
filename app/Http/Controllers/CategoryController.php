<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::with('fields')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'fields' => $category->fields->map(fn ($field) => [
                    'id' => $field->id,
                    'field_name' => $field->field_name,
                    'field_type' => $field->field_type,
                    'required' => $field->required,
                    'display_label' => $field->display_label,
                    'prompt' => $field->prompt,
                    'validation_rule' => $field->validation_rule,
                    'reference_table' => $field->reference_table,
                    'reference_column' => $field->reference_column,
                ]),
            ]);

        return Inertia::render('admin/kategorije/index', [
            'categories' => $categories,
        ]);
    }
}
