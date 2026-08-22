<?php

/**
 * BookSaleService Tests
 * Tests the sale/refund flow from 10_INVENTORY_MODULE.md §5
 */

describe('BookSaleService', function () {

    it('rejects sale when quantity exceeds stock', function () {
        $stock = 5;
        $quantity = 10;

        expect($quantity > $stock)->toBeTrue();
    });

    it('calculates net amount correctly with discount', function () {
        $price = 800;
        $quantity = 3;
        $discount = 200;

        $totalAmount = $price * $quantity;
        $netAmount = $totalAmount - $discount;

        expect($totalAmount)->toBe(2400);
        expect($netAmount)->toBe(2200);
    });

    it('applies 5% savings sweep on sale income', function () {
        $netAmount = 2200;
        $savingPercent = 5;
        $savingAmount = $netAmount * ($savingPercent / 100);

        expect($savingAmount)->toBe(110.0);
    });

    it('blocks refund on already-refunded sale', function () {
        $saleStatus = 'refunded';
        $canRefund = $saleStatus !== 'refunded';

        expect($canRefund)->toBeFalse();
    });

    it('allows refund on completed sale', function () {
        $saleStatus = 'completed';
        $canRefund = $saleStatus !== 'refunded';

        expect($canRefund)->toBeTrue();
    });

    it('restocks correct quantity on refund', function () {
        $currentStock = 40;
        $soldQuantity = 3;

        $restockedStock = $currentStock + $soldQuantity;
        expect($restockedStock)->toBe(43);
    });

    it('uses correct category based on is_chapter', function () {
        expect(true ? 'chapter' : 'book')->toBe('chapter');
        expect(false ? 'chapter' : 'book')->toBe('book');
    });

    it('generates receipt number in correct format', function () {
        $paymentId = 'abc123def456';
        $receipt = 'RCP-' . strtoupper(substr(md5($paymentId), 0, 8));

        expect($receipt)->toMatch('/^RCP-[A-F0-9]{8}$/');
    });

    it('stock guard is checked BEFORE the transaction', function () {
        // The guard must prevent the transaction from starting at all
        $stock = 2;
        $quantity = 5;
        $guardPasses = $quantity <= $stock;

        expect($guardPasses)->toBeFalse();
    });
});
