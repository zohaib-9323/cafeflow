<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/** Starter pages, FAQs and testimonials. Safe in production: no accounts or secrets. */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => [$title, $description, $body]) {
            CmsPage::query()->updateOrCreate(['slug' => $slug], [
                'title' => $title, 'body' => $body, 'seo_description' => $description,
                'is_published' => true, 'show_in_footer' => true, 'published_at' => now(),
            ]);
        }

        if (Faq::query()->doesntExist()) {
            foreach ($this->faqs() as $i => [$q, $a]) {
                Faq::query()->create(['question' => $q, 'answer' => $a, 'sort_order' => $i, 'is_published' => true]);
            }
        }

        if (Testimonial::query()->doesntExist()) {
            foreach ($this->testimonials() as $i => [$name, $title, $quote, $rating]) {
                Testimonial::query()->create(['author_name' => $name, 'author_title' => $title, 'quote' => $quote, 'rating' => $rating, 'sort_order' => $i, 'is_published' => true]);
            }
        }
    }

    private function pages(): array
    {
        return [
            'about' => ['Our story', 'How a small coffee bar on Mill Lane became the neighbourhood kitchen it is today.', <<<'MD'
Larkspur opened in a former bicycle workshop on Mill Lane with one espresso machine, four stools and a tray of croissants that sold out by nine.

We still work the same way. Coffee is roasted in small batches by a partner roastery a few streets away and rested for at least five days before it reaches the grinder. Pastry dough is laminated the afternoon before and baked from 5 a.m. The kitchen menu follows what the market has that week, which is why the bowls change more often than the sign outside.

## What we care about

- **Coffee you can taste the origin in.** We rotate single-origin beans on the brew bar and keep one house espresso blend that's built for milk.
- **Food made here.** Bread, granola, sauces and most of the cakes are made in our own kitchen.
- **A room people want to stay in.** No laptops-out rush, no music you have to shout over.

## Visit

You'll find us at 42 Mill Lane, Riverton. Order ahead on this site for pickup at the counter, or have it delivered within our local area.
MD],
            'privacy-policy' => ['Privacy policy', 'What personal information Larkspur Coffee House collects, why, and how long we keep it.', <<<'MD'
*This is sample policy text for a demonstration site. Have a qualified adviser review it before using it for a real business.*

## What we collect

When you create an account or order, we collect your name, email address, phone number and, for deliveries, your address. We keep a record of your orders so you can track them and re-order.

We do **not** store card numbers. Online payments are handled by our payment provider, and we only receive a reference and the payment result.

## Why we use it

- To prepare, deliver and update you about your orders.
- To answer messages you send through the contact form.
- To send occasional news and offers, only if you opted in. You can opt out at any time from your account.

## How long we keep it

Order records are kept for as long as we're required to for tax and accounting purposes. You can ask us to delete your account at any time; past orders are then anonymised.

## Your rights

You can request a copy of your data, ask us to correct it, or ask us to delete it by writing to hello@larkspur.test.
MD],
            'terms-and-conditions' => ['Terms & conditions', 'The terms for ordering from Larkspur Coffee House online.', <<<'MD'
*Sample terms for a demonstration site.*

## Ordering

Prices shown at checkout include any discount you applied. Sales tax is added on food and drink; delivery fees are shown before you place the order. We confirm every order before we start preparing it.

## Availability

Some items are made in limited batches. If we can't make something you ordered, we'll contact you and refund that part of the order.

## Cancellations

You can cancel an order from your account while it's still waiting to be confirmed. After that, please call us and we'll do our best.

## Allergens

Our kitchen handles gluten, dairy, eggs, nuts, sesame and soy. Ask us before ordering if you have an allergy; we can't guarantee any item is free of traces.

## Promo codes

Promo codes can't be exchanged for cash, can't be combined, and may have a minimum spend or expiry date.
MD],
        ];
    }

    private function faqs(): array
    {
        return [
            ['How long does an order take?', 'Most drinks and pastries are ready in about 10 minutes. Cooked dishes take 15–20 minutes at busy times. You\'ll see your order move through each step on the tracking page.'],
            ['Where do you deliver?', 'We deliver within roughly 3 km of Mill Lane. Delivery is $4.50, and free on orders over $50 (after any discount).'],
            ['Do you have plant-based milk?', 'Yes. Oat milk is available on every milk drink for a small extra charge. Ask the barista if you\'d like something else; we keep a little almond milk behind the bar.'],
            ['Can I change or cancel my order?', 'You can cancel from your account until we confirm the order. Once it\'s being prepared, give us a call and we\'ll sort it out.'],
            ['How do I pay?', 'Pay by card online at checkout, or choose cash and pay when you collect or when the courier arrives.'],
            ['Do you cater for allergies?', 'Every product page lists what\'s in it, but our kitchen handles common allergens, so we can\'t promise anything is completely free of traces. Please tell us about allergies in the order notes.'],
            ['Can I book a table?', 'We don\'t take bookings for small groups. For six or more, send us a message through the contact page and we\'ll keep a table for you.'],
            ['Do you sell beans to take home?', 'Yes, our current espresso blend and the single origin on the brew bar are available in 250 g bags at the counter.'],
        ];
    }

    private function testimonials(): array
    {
        return [
            ['Hannah W.', 'Regular since 2023', 'The flat white is the most consistent in the neighbourhood, and they remember how I take it.', 5],
            ['Tomás R.', 'Orders for the office on Fridays', 'Ordering ahead for twelve people used to be a headache. Now it\'s ready when I walk in, labelled and still hot.', 5],
            ['Grace O.', 'Weekend brunch', 'The harvest bowl changes with the seasons and it\'s never been the same twice. Never been disappointed either.', 5],
            ['Daniel K.', 'Works around the corner', 'Croissants are the real thing. Get there before ten or order online, they do sell out.', 4],
        ];
    }
}
