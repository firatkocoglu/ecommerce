<?php

namespace App\Services\Addresses;

use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Throwable;

class AddressService
{
    public function listForUser(int $userId)
    {
        // Logic to list addresses for a specific user
        return Address::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();
    }

    public function getForUser(int $userId, int $addressId)
    {
        // Logic to get a specific address for a user
        return Address::whereKey($addressId)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    /**
     * @throws Throwable
     */
    public function create(int $userId, array $addressData): ?Address
    {
        return DB::transaction(function () use ($userId, $addressData) {
            // Logic to create a new address for a user
            $isDefault = (bool) ($addressData['is_default'] ?? false);
            $addressType = $addressData['type'];

            // If is_default is true, unset other default addresses of the same type
            if ($isDefault === true) {
                Address::where('user_id', $userId)
                    ->where('type', $addressType)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $addressData['user_id'] = $userId;
            $created = Address::create($addressData);

            if ($isDefault === false) {
                $existingDefault = Address::where('user_id', $userId)
                    ->where('type', $addressType)
                    ->where('is_default', true)
                    ->exists();

                if (! $existingDefault) {
                    $created->is_default = true;
                    $created->save();
                }

            }

            return $created->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function update(int $userId, int $addressId, array $addressData): ?Address
    {
        // Logic to update an existing address for a user
        return DB::transaction(function () use ($userId, $addressId, $addressData) {
            // Lock address row for update
            $address = Address::whereKey($addressId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            // Check if is_default is being set to true
            $isDefault = array_key_exists('is_default', $addressData) ? $addressData['is_default'] : null;

            // If so, unset other default addresses of the same type
            if ($isDefault === true) {
                Address::where('user_id', $userId)
                    ->where('type', $address->type)
                    ->where('is_default', true)
                    ->where('id', '!=', $addressId)
                    ->update(['is_default' => false]);
            }

            $address->fill($addressData);

            if (! $address->isDirty()) {
                return $address;
            }

            $address->save();

            return $address->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function delete(int $userId, int $addressId): void
    {
        // Logic to delete an address for a user
        DB::transaction(function () use ($userId, $addressId) {
            // Lock address row for deletion
            $address = Address::whereKey($addressId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            // Is default address?
            $wasDefault = $address->is_default;
            $addressType = $address->type;

            if ($wasDefault) {
                // Find another address of the same type to set as default
                $newDefault = Address::where('user_id', $userId)
                    ->where('type', $addressType)
                    ->where('id', '!=', $addressId)
                    ->first();

                if ($newDefault) {
                    $newDefault->is_default = true;
                    $newDefault->save();
                }
            }

            $address->delete();
        });
    }

    /**
     * @throws Throwable
     */
    public function setDefault(int $userId, int $addressId): ?Address
    {
        // Logic to set an address as the default for a user
        return DB::transaction(function () use ($userId, $addressId) {
            $address = Address::whereKey($addressId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $existingDefault = Address::where('user_id', $userId)
                ->where('type', $address->type)
                ->where('id', '!=', $addressId)
                ->where('is_default', true)
                ->first();

            if ($existingDefault) {
                $existingDefault->is_default = false;
                $existingDefault->save();
            }

            $address->is_default = true;
            $address->save();

            return $address->refresh();
        });

    }

    public function clearAddressesForUser(int $userId): void
    {
        // Logic to delete all addresses for a specific user
        Address::where('user_id', $userId)->delete();
    }
}
