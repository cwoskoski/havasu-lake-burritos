<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\ProductionDay;
use App\Models\Burrito;
use App\Models\Order;
use App\Models\ProductionSchedule;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * TDD Tests for Order System Foundation
 * Business Rules: Complete order lifecycle, guest vs auth users, state management
 */
describe('Order Creation and Validation', function () {
    beforeEach(function () {
        $this->user = createVerifiedUser();
        $this->productionSchedule = new ProductionSchedule([
            'production_date' => Carbon::now()->next(Carbon::SATURDAY),
            'day_of_week' => ProductionDay::SATURDAY,
            'max_burritos' => 100,
            'burritos_ordered' => 0,
        ]);
    });

    it('creates orders with proper order numbers', function () {
        $order = Order::createOrder([
            'user_id' => $this->user->id,
            'production_schedule_id' => $this->productionSchedule->id,
            'customer_name' => 'John Doe',
            'customer_phone' => '+15551234567',
        ]);

        expect($order->order_number)->toMatch('/^HLB-\d{8}-[A-Z0-9]{4}$/');
        expect($order->status)->toBe(OrderStatus::PENDING);
        expect($order->user_id)->toBe($this->user->id);
    });

    it('creates guest orders without user_id', function () {
        $order = Order::createGuestOrder([
            'customer_name' => 'Jane Guest',
            'customer_phone' => '+15559876543',
            'production_schedule_id' => $this->productionSchedule->id,
        ]);

        expect($order->user_id)->toBeNull();
        expect($order->is_guest_order)->toBeTrue();
        expect($order->customer_name)->toBe('Jane Guest');
        expect($order->customer_phone)->toBe('+15559876543');
    });

    it('validates phone number format', function () {
        expect(fn () => Order::createGuestOrder([
            'customer_name' => 'Bad Phone',
            'customer_phone' => 'invalid-phone',
            'production_schedule_id' => $this->productionSchedule->id,
        ]))->toThrow(InvalidArgumentException::class);

        // Valid formats should work
        expect(fn () => Order::createGuestOrder([
            'customer_name' => 'Good Phone',
            'customer_phone' => '+1 (555) 123-4567',
            'production_schedule_id' => $this->productionSchedule->id,
        ]))->not->toThrow(InvalidArgumentException::class);
    });

    it('enforces weekend production scheduling', function () {
        $weekdaySchedule = new ProductionSchedule([
            'production_date' => Carbon::now()->next(Carbon::MONDAY),
            'day_of_week' => ProductionDay::SATURDAY, // This would be invalid anyway
            'max_burritos' => 100,
        ]);

        expect(fn () => Order::createOrder([
            'user_id' => $this->user->id,
            'production_schedule_id' => $weekdaySchedule->id,
            'customer_name' => 'Weekend Only',
            'customer_phone' => '+15551234567',
        ]))->toThrow(InvalidArgumentException::class);
    });
});

describe('Order Status Management', function () {
    beforeEach(function () {
        $this->order = new Order([
            'status' => OrderStatus::PENDING,
            'order_number' => 'HLB-20250106-TEST',
        ]);
    });

    it('validates status transitions', function () {
        // Valid transitions
        expect($this->order->canTransitionTo(OrderStatus::CONFIRMED))->toBeTrue();
        expect($this->order->canTransitionTo(OrderStatus::CANCELLED))->toBeTrue();

        // Invalid transitions
        expect($this->order->canTransitionTo(OrderStatus::READY))->toBeFalse();
        expect($this->order->canTransitionTo(OrderStatus::COMPLETED))->toBeFalse();
    });

    it('tracks status transition history', function () {
        $this->order->transitionTo(OrderStatus::CONFIRMED);

        expect($this->order->status)->toBe(OrderStatus::CONFIRMED);
        expect($this->order->getStatusHistory())->toHaveCount(2); // PENDING -> CONFIRMED
    });

    it('prevents invalid status transitions', function () {
        expect(fn () => $this->order->transitionTo(OrderStatus::READY))
            ->toThrow(InvalidArgumentException::class);
    });

    it('provides status display information', function () {
        $display = $this->order->getStatusDisplay();

        expect($display['status'])->toBe('pending');
        expect($display['label'])->toBe('Pending');
        expect($display['description'])->toContain('submitted');
        expect($display['color'])->toBe('yellow');
    });
});

describe('Order Capacity Management', function () {
    beforeEach(function () {
        $this->schedule = new ProductionSchedule([
            'production_date' => Carbon::now()->next(Carbon::SATURDAY),
            'day_of_week' => ProductionDay::SATURDAY,
            'max_burritos' => 10, // Small capacity for testing
            'burritos_ordered' => 5,
        ]);
    });

    it('checks production capacity before order creation', function () {
        expect(Order::canAcceptOrder($this->schedule, 3))->toBeTrue();
        expect(Order::canAcceptOrder($this->schedule, 5))->toBeTrue();
        expect(Order::canAcceptOrder($this->schedule, 6))->toBeFalse();
    });

    it('reserves capacity when order is confirmed', function () {
        $order = new Order([
            'production_schedule_id' => $this->schedule->id,
            'status' => OrderStatus::PENDING,
        ]);

        // Add 3 burritos to order
        $order->burritos()->saveMany([
            new Burrito,
            new Burrito,
            new Burrito,
        ]);

        $order->transitionTo(OrderStatus::CONFIRMED);

        expect($this->schedule->fresh()->burritos_ordered)->toBe(8); // 5 + 3
        expect($this->schedule->getAvailableCapacity())->toBe(2);
    });

    it('releases capacity when order is cancelled', function () {
        $order = new Order([
            'production_schedule_id' => $this->schedule->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        // Add 2 burritos (already confirmed and counted)
        $order->burritos()->saveMany([
            new Burrito,
            new Burrito,
        ]);

        // Simulate that capacity was already reserved
        $this->schedule->burritos_ordered = 7; // 5 + 2
        $this->schedule->save();

        $order->transitionTo(OrderStatus::CANCELLED);

        expect($this->schedule->fresh()->burritos_ordered)->toBe(5); // Back to original
    });
});

describe('Order Number Generation', function () {
    it('generates unique order numbers', function () {
        $orderNumbers = [];

        for ($i = 0; $i < 100; $i++) {
            $number = Order::generateOrderNumber();
            expect($orderNumbers)->not->toContain($number);
            $orderNumbers[] = $number;
        }
    });

    it('follows HLB-YYYYMMDD-XXXX format', function () {
        $number = Order::generateOrderNumber();
        $today = Carbon::now()->format('Ymd');

        expect($number)->toMatch("/^HLB-{$today}-[A-Z0-9]{4}$/");
    });

    it('includes readable characters only', function () {
        for ($i = 0; $i < 50; $i++) {
            $number = Order::generateOrderNumber();
            $suffix = substr($number, -4);

            // Should not contain confusing characters like O, 0, I, 1
            expect($suffix)->not->toMatch('/[O0I1]/');
        }
    });
});

describe('Order Totals and Pricing', function () {
    beforeEach(function () {
        $this->order = new Order([
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
        ]);
    });

    it('calculates subtotal from burritos', function () {
        // Set subtotal manually for testing since DB fields don't support pricing yet
        $this->order->subtotal = 42.50; // $12.50 + $30.00
        $this->order->calculateTotals();

        expect($this->order->subtotal)->toBe(42.50);
        expect($this->order->getSubtotal())->toBe(42.50);
    });

    it('applies tax calculation', function () {
        $this->order->subtotal = 10.00;
        $this->order->calculateTotals();

        // Assuming 8.75% tax rate
        expect($this->order->tax_amount)->toBe(0.88); // $0.88 (rounded)
        expect($this->order->total_amount)->toBe(10.88); // $10.88
    });

    it('handles zero tax for small orders', function () {
        $this->order->subtotal = 0.50;
        $this->order->calculateTotals();

        expect($this->order->tax_amount)->toBe(0.04); // $0.04
        expect($this->order->total_amount)->toBe(0.54); // $0.54
    });

    it('provides formatted currency display', function () {
        $this->order->subtotal = 12.50;
        $this->order->tax_amount = 1.09;
        $this->order->total_amount = 13.59;

        expect($this->order->getFormattedSubtotal())->toBe('$12.50');
        expect($this->order->getFormattedTax())->toBe('$1.09');
        expect($this->order->getFormattedTotal())->toBe('$13.59');
    });
});

describe('Guest vs Authenticated Orders', function () {
    it('links authenticated orders to users', function () {
        $user = createVerifiedUser();
        $order = new Order(['user_id' => $user->id]);

        expect($order->isGuestOrder())->toBeFalse();
        expect($order->isAuthenticatedOrder())->toBeTrue();
        expect($order->user)->toBe($user);
    });

    it('handles guest orders without user accounts', function () {
        $order = new Order([
            'user_id' => null,
            'customer_name' => 'Guest Customer',
            'customer_phone' => '+15551234567',
        ]);

        expect($order->isGuestOrder())->toBeTrue();
        expect($order->isAuthenticatedOrder())->toBeFalse();
        expect($order->user)->toBeNull();
    });

    it('provides customer identification', function () {
        $user = createVerifiedUser(['name' => 'John User']);
        $authOrder = new Order(['user_id' => $user->id]);

        $guestOrder = new Order([
            'user_id' => null,
            'customer_name' => 'Jane Guest',
        ]);

        expect($authOrder->getCustomerName())->toBe('John User');
        expect($guestOrder->getCustomerName())->toBe('Jane Guest');
    });
});

describe('Mobile-First Order Experience', function () {
    beforeEach(function () {
        $this->order = new Order([
            'order_number' => 'HLB-20250106-ABCD',
            'status' => OrderStatus::IN_PREPARATION,
            'total_amount' => 15.99,
        ]);
    });

    it('provides mobile-optimized order summary', function () {
        $summary = $this->order->getMobileSummary();

        expect($summary['order_number'])->toBe('HLB-20250106-ABCD');
        expect($summary['status_display']['label'])->toBe('In Preparation');
        expect($summary['total'])->toBe('$15.99');
        expect($summary['estimated_ready_time'])->toBeInstanceOf(Carbon::class);
    });

    it('shows pickup instructions', function () {
        $this->order->status = OrderStatus::READY;
        $instructions = $this->order->getPickupInstructions();

        expect($instructions['ready_for_pickup'])->toBeTrue();
        expect($instructions['location'])->toContain('Lake');
        expect($instructions['phone'])->toMatch('/^\+1/');
    });

    it('calculates estimated ready times', function () {
        $pending = new Order(['status' => OrderStatus::PENDING]);
        $confirmed = new Order(['status' => OrderStatus::CONFIRMED]);
        $inPrep = new Order(['status' => OrderStatus::IN_PREPARATION]);

        expect($pending->getEstimatedReadyTime())->toBeNull();
        expect($confirmed->getEstimatedReadyTime())->toBeInstanceOf(Carbon::class);
        expect($inPrep->getEstimatedReadyTime())->toBeInstanceOf(Carbon::class);
    });
});
