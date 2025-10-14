<?php

namespace App\Http\Controllers\API\V1\Carts;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Carts\CartItemResource;
use App\Http\Resources\API\V1\Carts\CartResource;
use App\Services\Carts\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class CartController extends Controller
{
    public function __construct(private readonly CartService $service) {}

    public function show(): CartResource
    {
        // Get the authenticated user's ID, if available
        $userId = auth()->user() ? auth()->user()->id : null;

        // Determine if the user is authenticated or a guest
        if ($userId !== null) {
            // Get the cart ID associated with the authenticated user
            $cartId = $this->service->findActiveByUser($userId)->id;

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
            $cartData = $this->service->getGuestCart($hash);
        }

        return CartResource::make($cartData);
    }

    /**
     * @throws Throwable
     */
    public function store(): JsonResponse
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
                secure: false,
                sameSite: 'lax'
            );

            return CartResource::make($cart)->response()->withCookie($cookie);
        }
    }

    /**
     * @throws Throwable
     */
    public function addItem(): JsonResponse
    {
        $cartId = $this->findCartId();

        $productData = [
            'product_id' => request()->input('product_id'),
            'product_variant_id' => request()->input('product_variant_id'),
        ];

        $cart = $this->service->addProductToCart($cartId, $productData);

        return CartResource::make($cart)->response()->setStatusCode(200);
    }

    /**
     * @throws Throwable
     */
    public function removeItem(): Response
    {
        $cartId = $this->findCartId();
        $cartItemId = request()->input('cart_item_id');
        $this->service->deleteCartItem($cartId, $cartItemId);

        return response()->noContent();
    }

    /**
     * @throws Throwable
     */
    public function clearCart(): Response
    {
        $cartId = $this->findCartId();
        $this->service->clearCart($cartId);

        return response()->noContent();
    }

    /**
     * @throws Throwable
     */
    public function increaseItemQuantity(): JsonResponse
    {
        $cartId = $this->findCartId();
        $cartItemId = request()->input('cart_item_id');
        $cartItem = $this->service->increaseCartItemQuantity($cartId, $cartItemId);

        return CartItemResource::make($cartItem)->response()->setStatusCode(200);
    }

    /**
     * @throws Throwable
     */
    public function decreaseItemQuantity(): JsonResponse
    {
        $cartId = $this->findCartId();
        $cartItemId = request()->input('cart_item_id');
        $cartItem = $this->service->decreaseCartItemQuantity($cartId, $cartItemId);

        return CartItemResource::make($cartItem)->response()->setStatusCode(200);
    }

    private function findCartId(): int
    {
        $cartToken = request()->cookie('cart_token');
        // If cart token is provided, use it to find the guest cart
        if ($cartToken !== null) {
            $hash = $this->service->hashCartToken($cartToken);
            $cartId = $this->service->findActiveByTokenHash($hash)->id;
        } else {
            $userId = auth()->user() ? auth()->user()->id : null;
            if ($userId === null) {
                abort(400, 'No cart token provided for guest user');
            }

            $cartId = $this->service->findActiveByUser($userId)->id;
        }

        return $cartId;
    }
}
