<?php

namespace App\Http\Controllers\API\V1\Carts;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Carts\CartResource;
use App\Services\Carts\CartService;
use Throwable;

class CartController extends Controller
{
    public function __construct(private readonly CartService $service) {}

    public function show(int $cartId): CartResource
    {
        // Get the authenticated user's ID, if available
        $userId = auth()->user() ? auth()->user()->id : null;

        // Determine if the user is authenticated or a guest
        if ($userId !== null) {
            // Authenticated user
            $cartData = $this->service->getUserCart($cartId, $userId);
        } else {
            // Guest user

            // Retrieve the raw cart token from the request cookies
            $cartToken = request()->cookie('cart_token');

            // If no cart token is found, return a 404 error
            if (! $cartToken) {
                abort(404, 'Cart not found');
            }

            // Hash the cart token to match the stored hash in the database
            $hash = $this->service->hashCartToken($cartToken);

            // Fetch the cart using the cart ID and hashed token
            $cartData = $this->service->getGuestCart($cartId, $hash);
        }

        return CartResource::make($cartData);

    }

    /**
     * @throws Throwable
     */
    public function store(): CartResource
    {
        // Determine if the user is authenticated or a guest
        $userId = auth()->user() ? auth()->user()->id : null;

        if ($userId !== null) {
            // Authenticated user
            $cart = $this->service->createUserCart($userId);

            return CartResource::make($cart)->response()->setStatusCode(201);
        } else {
            // Guest user
            $cartToken = request()->cookie('cart_token');

            // If no cart token is found, generate a new one
            if (! $cartToken) {
                $cartToken = $this->service->generateCartToken();
            }

            // Hash the cart token to store in the database
            $hash = $this->service->hashCartToken($cartToken);

            // Create a new guest cart with the hashed token
            $cart = $this->service->createGuestCart($hash);

            // Set the raw cart token (not hashed) in a secure, HTTP-only cookie
            $cookie = cookie(
                name: 'cart_token',
                value: $cartToken,
                minutes: 60 * 24 * 7, // 7 days
                path: '/',
                secure: true,
                httpOnly: true,
                sameSite: 'lax'
            );

            return CartResource::make($cart)->response()->withCookie($cookie);
        }
    }
}
