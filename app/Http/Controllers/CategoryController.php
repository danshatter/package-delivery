<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get all the categories
     */
    public function index()
    {
        $categories = Category::all();

        return $this->sendSuccess('Request successful', $categories);
    }

    /**
     * Create a category
     */
    public function store()
    {
        $validator = validator()->make(request()->all(), [
            'name' => ['required', 'unique:categories']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $category = Category::create(compact('name'));

        return $this->sendSuccess('Category created successfully', $category, 201);
    }

    /**
     * Get a category
     */
    public function show($categoryId)
    {
        $category = Category::find($categoryId);

        if (is_null($category)) {
            return $this->sendErrorMessage('Category not found', 404);
        }

        return $this->sendSuccess('Request successful', $category);
    }

    /**
     * Update a category
     */
    public function update($categoryId)
    {
        $category = Category::find($categoryId);

        if (is_null($category)) {
            return $this->sendErrorMessage('Category not found', 404);
        }

        $validator = validator()->make(request()->all(), [
            'name' => ['required', 'unique:categories,name,'.$category->id]
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $category->update(compact('name'));

        return $this->sendSuccess('Category updated successfully', $category);
    }

    /**
     * Delete a category
     */
    public function destroy($categoryId)
    {
        $category = Category::find($categoryId);

        if (is_null($category)) {
            return $this->sendErrorMessage('Category not found', 404);
        }

        $category->delete();

        return $this->sendSuccess('Category deleted successfully');
    }

}
