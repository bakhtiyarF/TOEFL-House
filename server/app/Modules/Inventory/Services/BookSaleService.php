<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Book Sale Service
 *
 * Implements the sale/refund flow from 10_INVENTORY_MODULE.md §5:
 * - Sale: decrement stock + insert sale + shared income recording (atomic)
 * - Refund: restock + mark refunded + expense transaction (atomic)
 * - Stock guard: reject before transaction if quantity > stock
 */
class BookSaleService
{
    /**
     * Sell books (10 §5 — Sale flow)
     *
     * In one transaction: decrement stock, insert book_sales,
     * call shared income-recording (writes financial_transactions + 5% savings sweep).
     */
    public function sellBook(
        string $bookId,
        int $quantity,
        float $discountAmount,
        string $paymentMethod,
        ?string $customerName,
        ?string $studentId,
        string $branchId,
        string $operatorName,
    ): array {
        $book = DB::table('books')->where('id', $bookId)->first();
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        // Stock guard: checked BEFORE the transaction (10 §5)
        if ($quantity > $book->stock) {
            throw new \RuntimeException(
                "Insufficient stock. Requested: {$quantity}, Available: {$book->stock}",
                409
            );
        }

        return DB::transaction(function () use ($book, $quantity, $discountAmount, $paymentMethod, $customerName, $studentId, $branchId, $operatorName) {
            $totalAmount = $book->price * $quantity;
            $netAmount = $totalAmount - $discountAmount;

            // 1. Decrement stock
            DB::table('books')->where('id', $book->id)->decrement('stock', $quantity);

            // 2. Insert book_sales
            $saleId = Str::uuid()->toString();
            $category = $book->is_chapter ? 'chapter' : 'book';

            DB::table('book_sales')->insert([
                'id' => $saleId,
                'book_id' => $book->id,
                'quantity' => $quantity,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'date' => now()->toDateString(),
                'customer_name' => $customerName,
                'student_id' => $studentId,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. Shared income-recording (10 §5 — same function as payments)
            $txId = Str::uuid()->toString();
            DB::table('financial_transactions')->insert([
                'id' => $txId,
                'type' => 'income',
                'category' => $category,
                'amount' => $netAmount,
                'date' => now()->toDateString(),
                'description' => "Book sale: {$book->title} × {$quantity}",
                'reference_id' => $saleId,
                'operator_name' => $operatorName,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Apply 5% savings sweep (02 §7.4 #12)
            $savingPercent = (float)(DB::table('system_settings')->where('key', 'daily_saving_percent')->value('value') ?? 5);
            $savingAmount = $netAmount * ($savingPercent / 100);

            if ($savingAmount > 0) {
                DB::table('system_settings')->where('key', 'saving_balance')->increment('value', $savingAmount);
            }

            return [
                'sale_id' => $saleId,
                'transaction_id' => $txId,
                'total_amount' => $totalAmount,
                'net_amount' => $netAmount,
                'saving_amount' => round($savingAmount, 2),
                'remaining_stock' => $book->stock - $quantity,
            ];
        });
    }

    /**
     * Refund a book sale (10 §5 — Refund flow)
     *
     * In one transaction: restock, mark refunded, expense transaction,
     * decrement main_account_balance.
     *
     * Blocked if already refunded (409).
     */
    public function refundSale(string $saleId, string $branchId, string $operatorName): array
    {
        $sale = DB::table('book_sales')->where('id', $saleId)->first();
        if (!$sale) {
            throw new \RuntimeException('Sale not found');
        }

        if ($sale->status === 'refunded') {
            throw new \RuntimeException('Sale already refunded', 409);
        }

        return DB::transaction(function () use ($sale, $branchId, $operatorName) {
            // 1. Restock
            DB::table('books')->where('id', $sale->book_id)->increment('stock', $sale->quantity);

            // 2. Mark sale as refunded
            DB::table('book_sales')->where('id', $sale->id)->update([
                'status' => 'refunded',
                'updated_at' => now(),
            ]);

            // 3. Decrement main account balance
            DB::table('system_settings')->where('key', 'main_account_balance')
                ->decrement('value', $sale->net_amount);

            // 4. Insert expense transaction
            $txId = Str::uuid()->toString();
            DB::table('financial_transactions')->insert([
                'id' => $txId,
                'type' => 'expense',
                'category' => 'book_refund',
                'amount' => $sale->net_amount,
                'date' => now()->toDateString(),
                'description' => "Book refund: sale {$sale->id}",
                'reference_id' => $sale->id,
                'operator_name' => $operatorName,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'sale_id' => $sale->id,
                'transaction_id' => $txId,
                'refunded_amount' => (float)$sale->net_amount,
                'restocked_quantity' => $sale->quantity,
            ];
        });
    }
}
