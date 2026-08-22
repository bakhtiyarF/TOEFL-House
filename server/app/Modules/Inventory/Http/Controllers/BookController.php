<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Services\BookSaleService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService,
        private BookSaleService $bookSaleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('books')
            ->when($request->query('search'), fn($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderBy('title');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'stock' => 'integer|min:0',
            'is_chapter' => 'boolean',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();
        DB::table('books')->insert([
            'id' => $id,
            ...$validated,
            'stock' => $validated['stock'] ?? 0,
            'is_chapter' => $validated['is_chapter'] ?? false,
            'entry_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('books')->where('id', $id)->first(), 201);
    }

    public function restock(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($id, $validated) {
            DB::table('books')->where('id', $id)->increment('stock', $validated['quantity']);

            DB::table('book_restock_history')->insert([
                'id' => Str::uuid()->toString(),
                'book_id' => $id,
                'date' => now()->toDateString(),
                'quantity' => $validated['quantity'],
                'price' => $validated['price'] ?? 0,
                'purchase_price' => $validated['purchase_price'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json(DB::table('books')->where('id', $id)->first());
    }

    public function sell(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank_transfer',
            'customer_name' => 'nullable|string|max:255',
            'student_id' => 'nullable|uuid',
        ]);

        $book = DB::table('books')->where('id', $id)->first();
        if (!$book) return response()->json(['message' => 'Not found'], 404);

        try {
            $result = $this->bookSaleService->sellBook(
                bookId: $id,
                quantity: $validated['quantity'],
                discountAmount: $validated['discount_amount'] ?? 0,
                paymentMethod: $validated['payment_method'],
                customerName: $validated['customer_name'] ?? null,
                studentId: $validated['student_id'] ?? null,
                branchId: $book->branch_id,
                operatorName: $request->user()->full_name,
            );

            return response()->json($result, 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function refund(Request $request, string $saleId): JsonResponse
    {
        $sale = DB::table('book_sales')->where('id', $saleId)->first();
        if (!$sale) return response()->json(['message' => 'Not found'], 404);

        try {
            $result = $this->bookSaleService->refundSale(
                saleId: $saleId,
                branchId: $sale->branch_id,
                operatorName: $request->user()->full_name,
            );

            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function sales(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('book_sales')
            ->join('books', 'book_sales.book_id', '=', 'books.id')
            ->select('book_sales.*', 'books.title as book_title')
            ->orderByDesc('book_sales.date');

        if (!$scope['isAll']) {
            $query->where('book_sales.branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }
}
