<?php

namespace App\Http\Controllers\API\V1\ReturnRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\ReturnRequests\RejectReturnRequest;
use App\Http\Requests\API\V1\ReturnRequests\StoreReturnRequest;
use App\Http\Resources\API\V1\ReturnRequests\ReturnRequestResource;
use App\Services\ReturnRequests\ReturnRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class ReturnRequestController extends Controller
{
    public function __construct(private readonly ReturnRequestService $returnRequestService){}
    public function index(): AnonymousResourceCollection
    {
        $userId = auth()->user()->id;
        $returnRequests = $this->returnRequestService->listReturnRequestsByUser($userId);
        return ReturnRequestResource::collection($returnRequests);
    }

    public function show($returnRequestId): ReturnRequestResource
    {
        $userId = auth()->user()->id;
        $returnRequest = $this->returnRequestService->getReturnRequestByUser($userId, $returnRequestId);
        return ReturnRequestResource::make($returnRequest);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreReturnRequest $request): ReturnRequestResource
    {

        $userId = auth()->user()->id;
        $requestData = $request->validated();
        $requestData['user_id'] = $userId;
        $created = $this->returnRequestService->createReturnRequest($requestData);

        return ReturnRequestResource::make($created);
    }

    public function destroy($returnRequestId): JsonResponse
    {
        $userId = auth()->user()->id;
        $this->returnRequestService->deleteReturnRequest($userId, $returnRequestId);
        return response()->json(null, 204);
    }

    public function adminIndex(): JsonResponse | AnonymousResourceCollection
    {
        if (! auth('admin')->check())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $returnRequests = $this->returnRequestService->adminListReturnRequests();
        return ReturnRequestResource::collection($returnRequests);
    }

    public function adminShow($returnRequestId): JsonResponse | ReturnRequestResource
    {
        if (! auth('admin')->check())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $returnRequest = $this->returnRequestService->adminGetReturnRequest($returnRequestId);
        return ReturnRequestResource::make($returnRequest);
    }

    /**
     * @throws Throwable
     */
    public function adminApprove($returnRequestId): JsonResponse
    {
        if (! auth('admin')->check())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $adminId = auth()->user()->id;
        $this->returnRequestService->adminApproveReturnRequest($returnRequestId, $adminId);
        return response()->json(null, 204);
    }

    public function adminReject(RejectReturnRequest $request, $returnRequestId): JsonResponse
    {
        if (! auth('admin')->check())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $adminId = auth()->user()->id;
        $reason = $request->validated()['reason'];

        $this->returnRequestService->adminRejectReturnRequest($returnRequestId, $adminId, $reason);
        return response()->json(null, 204);
    }
}
