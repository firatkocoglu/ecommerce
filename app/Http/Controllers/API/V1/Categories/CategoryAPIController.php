<?php

namespace App\Http\Controllers\API\V1\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Categories\CategoryService;
use App\Http\Resources\API\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryAPIController extends Controller
{
    public function __construct(private readonly CategoryService $service){

    }

    /**
     * GET /api/v1/categories
     * Paginated list
     */

    public function index(): JsonResponse
    {   
        $perPage = (int) request('per_page', 20);

        $paginator = $this->service->listPaginated($perPage);

        return CategoryResource::collection($paginator)->response();
    } 
}