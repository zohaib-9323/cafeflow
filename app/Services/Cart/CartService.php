<?php

namespace App\Services\Cart;

use App\Exceptions\BusinessRuleException;
use Illuminate\Contracts\Session\Session;

/**
 * Session-backed cart. It stores only product/option ids and quantities;
 * PricingService prices everything fresh from the database on every read.
 */
class CartService
{
    private const KEY = 'cart.lines';

    public function __construct(private readonly Session $session) {}

    /** @return array<string, CartLine> */
    public function lines(): array
    {
        return collect($this->session->get(self::KEY, []))
            ->map(fn (array $line) => CartLine::fromArray($line))
            ->keyBy(fn (CartLine $line) => $line->key())
            ->all();
    }

    public function add(CartLine $line): string
    {
        $lines = $this->lines();
        $existing = $lines[$line->key()] ?? null;
        $max = (int) config('cafeflow.max_item_quantity');
        $quantity = min($max, ($existing?->quantity ?? 0) + $line->quantity);

        if (! $existing && count($lines) >= (int) config('cafeflow.max_cart_lines')) {
            throw new BusinessRuleException('Your cart is full. Please check out or remove an item first.', field: 'cart');
        }

        $lines[$line->key()] = $line->withQuantity($quantity);
        $this->store($lines);

        return $line->key();
    }

    public function update(string $key, int $quantity): void
    {
        $lines = $this->lines();
        if (! isset($lines[$key])) {
            return;
        }
        if ($quantity <= 0) {
            unset($lines[$key]);
        } else {
            $lines[$key] = $lines[$key]->withQuantity(min($quantity, (int) config('cafeflow.max_item_quantity')));
        }
        $this->store($lines);
    }

    public function remove(string $key): void
    {
        $this->update($key, 0);
    }

    public function count(): int
    {
        return array_sum(array_map(fn (CartLine $l) => $l->quantity, $this->lines()));
    }

    public function clear(): void
    {
        $this->session->forget([self::KEY, 'cart.promo_code']);
    }

    public function promoCode(): ?string
    {
        return $this->session->get('cart.promo_code');
    }

    public function setPromoCode(?string $code): void
    {
        $code ? $this->session->put('cart.promo_code', strtoupper(trim($code))) : $this->session->forget('cart.promo_code');
    }

    public function fulfillment(): string
    {
        return $this->session->get('cart.fulfillment', 'pickup');
    }

    public function setFulfillment(string $type): void
    {
        $this->session->put('cart.fulfillment', $type);
    }

    /** @param array<string, CartLine> $lines */
    private function store(array $lines): void
    {
        $this->session->put(self::KEY, array_map(fn (CartLine $l) => $l->toArray(), array_values($lines)));
    }
}
