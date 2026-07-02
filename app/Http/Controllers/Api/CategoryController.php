<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InventoryService;
use App\Http\Requests\CategoryRequest;

class CategoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index()
    {
        $categories = $this->inventoryService->getAllCategories();
        return response()->json([
            'status' => 'success',
            'message' => 'Categories retrieved successfully',
            'data' => $categories,
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $category = $this->inventoryService->createCategory($request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category,
        ]);
    }

    public function update(CategoryRequest $request, $id)
    {
        $category = $this->inventoryService->updateCategory($id, $request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    public function destroy($id)
    {
        $this->inventoryService->deleteCategory($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully',
        ]);
    }
}
