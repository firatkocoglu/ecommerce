<?php

namespace App\Http\Controllers\API\V1\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Categories\StoreCategoryRequest;
use App\Http\Requests\API\V1\Categories\UpdateCategoryRequest;
use App\Http\Resources\API\V1\Categories\CategoryResource;
use App\Http\Resources\API\V1\Categories\CategoryTreeResource;
use App\Models\Category;
use App\Services\Categories\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryAPIController extends Controller
{
    public function __construct(private readonly CategoryService $service) {}

    /**
     * GET /api/v1/categories
     * Paginated list
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) request('per_page', 20), 100));

        $paginator = $this->service->listPaginated($perPage);

        return CategoryResource::collection($paginator);
    }

    public function tree(): AnonymousResourceCollection
    {
        $depth = (int) request('depth', 2);
        $tree = $this->service->tree($depth);

        return CategoryTreeResource::collection($tree);
    }

    public function show(int $id): CategoryResource
    {
        // Find the category by ID
        // In category service, the existence of given ID will be checked by findOrFail
        $category = $this->service->findById($id);

        return CategoryResource::make($category);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Create the category
        $category = $this->service->create($data);

        return CategoryResource::make($category)->response()->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        // Update the category
        $category = $this->service->update($id, $data);

        return CategoryResource::make($category)->response()->setStatusCode(200);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
