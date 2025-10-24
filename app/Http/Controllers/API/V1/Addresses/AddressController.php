<?php

namespace App\Http\Controllers\API\V1\Addresses;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\Addresses\AddressService;
use Illuminate\Http\Request;
use App\Http\Requests\API\V1\Address\StoreAddressRequest;
use App\Http\Requests\API\V1\Address\UpdateAddressRequest;
use App\Http\Resources\API\V1\Address\AddressResource;
use Throwable;

class AddressController extends Controller
{
public function __construct(private readonly AddressService $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->user()->id;
        $addresses = $this->service->listForUser($userId);
        return AddressResource::collection($addresses);
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */
    public function store(StoreAddressRequest $request)
    {
        $addressData = $request->validated();
        $userId = auth()->user()->id;
        $address = $this->service->create($userId, $addressData);

        return AddressResource::make($address)->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $addressId)
    {
        $userId = auth()->user()->id;
        $address = $this->service->getForUser($userId, $addressId);

        return AddressResource::make($address);
    }

    /**
     * Update the specified resource in storage.
     * @throws Throwable
     */
    public function update(UpdateAddressRequest $request, int $addressId)
    {
        $addressData = $request->validated();
        $userId = auth()->user()->id;
        $address = $this->service->update($userId, $addressId, $addressData);

        return AddressResource::make($address);
    }

    /**
     * Remove the specified resource from storage.
     * @throws Throwable
     */
    public function destroy(int $addressId)
    {
        $userId = auth()->user()->id;
        $this->service->delete($userId, $addressId);

        return response()->noContent();
    }

    /**
     * @throws Throwable
     */
    public function toggleDefault(UpdateAddressRequest $request, int $addressId)
    {
        $userId = auth()->user()->id;
        $address = $this->service->setDefault($userId, $addressId);

        return AddressResource::make($address);
    }

    public function clearAll()
    {
        $userId = auth()->user()->id;
        $this->service->clearAddressesForUser($userId);

        return response()->noContent();
    }
}

