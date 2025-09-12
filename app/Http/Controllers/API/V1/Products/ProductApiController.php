<?php

namespace App\Http\Controllers\API\V1\Products;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Products\ProductService;
use App\Http\Resources\API\V1\Products\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductApiController extends Controller
{
    public function __construct(private readonly ProductService $service) {

    }

    public function index(): AnonymousResourceCollection
    {
        $perPage = (int) request('per_page') ?? 20;

        $paginator = $this->service->listPaginated($perPage);

        return ProductResource::collection($paginator);
    }

    public function show(int $id): ProductResource {
        // Find the product by ID
        // In product service, the existence of given ID will be checked by findOrFail
        $product = $this->service->findById($id);

        return ProductResource::make($product);
    }
}
