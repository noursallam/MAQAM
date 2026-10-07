<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\ShippingAddress;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The signed-in user's address book. Every query is scoped to the owner.
 */
class AddressController extends ApiController
{
    private const MAX_ADDRESSES = 20;

    public function index(Request $request): JsonResponse
    {
        return $this->ok($this->addresses($request)->get()->map(ApiPresenter::address(...))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $userId = $request->user()->id;

        if ($this->addresses($request)->count() >= self::MAX_ADDRESSES) {
            throw new ApiException('ADDRESS_LIMIT_REACHED', __('api.address.limit_reached'), 422);
        }

        $address = DB::transaction(function () use ($data, $userId) {
            $isFirst = ! ShippingAddress::where('user_id', $userId)->exists();
            $address = ShippingAddress::create($this->columns($data) + [
                'user_id' => $userId,
                'country' => 'Egypt',
                'is_default' => false,
            ]);

            if ($isFirst || ! empty($data['is_default'])) {
                $this->makeDefault($address);
            }

            return $address;
        });

        return $this->ok(ApiPresenter::address($address->fresh()), __('api.saved'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $address = $this->addresses($request)->findOrFail($id);
        $data = $this->validated($request);

        DB::transaction(function () use ($address, $data) {
            $address->update($this->columns($data));

            if (! empty($data['is_default'])) {
                $this->makeDefault($address);
            }
        });

        return $this->ok(ApiPresenter::address($address->fresh()), __('api.saved'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = $this->addresses($request)->findOrFail($id);

        DB::transaction(function () use ($address, $request) {
            // Soft delete: orders already shipped to it keep showing it
            $address->delete();

            if ($address->is_default && ($next = $this->addresses($request)->first())) {
                $this->makeDefault($next);
            }
        });

        return $this->ok(null, __('api.saved'));
    }

    private function addresses(Request $request)
    {
        return ShippingAddress::where('user_id', $request->user()->id)->orderByDesc('is_default')->latest('id');
    }

    private function validated(Request $request): array
    {
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);

        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^(20)?01[0125]\d{8}$/'],
            'governorate' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (str_starts_with($data['phone'], '20')) {
            $data['phone'] = '0'.substr($data['phone'], 2);
        }

        return $data;
    }

    private function columns(array $data): array
    {
        return [
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'governorate' => $data['governorate'],
            'city' => $data['city'],
            'address_line1' => $data['address'],
            'address_line2' => $data['notes'] ?? null,
        ];
    }

    private function makeDefault(ShippingAddress $address): void
    {
        ShippingAddress::where('user_id', $address->user_id)->whereKeyNot($address->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }
}
