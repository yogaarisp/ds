<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dailySales(Request $request)
    {
        $startDate = $request->get('start_date', $request->get('date', today()->toDateString()));
        $endDate = $request->get('end_date', $request->get('date', today()->toDateString()));

        $transactions = Transaction::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->completed()
            ->get();

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total'),
            'total_discount' => $transactions->sum('discount'),
            'by_order_type' => $transactions->groupBy('order_type')->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ]),
        ]);
    }

    public function salesByProduct(Request $request)
    {
        $fromDate = $request->get('from_date', today()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', today()->toDateString());

        $sales = TransactionItem::whereHas('transaction', function ($q) use ($fromDate, $toDate) {
            $q->whereBetween('created_at', [$fromDate, $toDate])
                ->where('status', 'completed');
        })
            ->where('item_type', 'product')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_id')
            ->with('product:id,name,sku')
            ->orderByDesc('total_qty')
            ->get();

        return response()->json([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'data' => $sales,
        ]);
    }

    public function salesByPackage(Request $request)
    {
        $fromDate = $request->get('from_date', today()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', today()->toDateString());

        $sales = TransactionItem::whereHas('transaction', function ($q) use ($fromDate, $toDate) {
            $q->whereBetween('created_at', [$fromDate, $toDate])
                ->where('status', 'completed');
        })
            ->where('item_type', 'package')
            ->select(
                'package_id',
                'package_combination_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->groupBy('package_id', 'package_combination_id')
            ->with(['package:id,name,code', 'packageCombination:id,name'])
            ->orderByDesc('total_qty')
            ->get();

        return response()->json([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'data' => $sales,
        ]);
    }



    public function productStock()
    {
        $products = Product::select('id', 'name', 'sku', 'stock', 'min_stock')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                ...$p->toArray(),
                'is_low_stock' => $p->isLowStock(),
            ]);

        return response()->json($products);
    }

    public function inventoryStock(Request $request)
    {
        $query = InventoryItem::select('id', 'name', 'code', 'type', 'unit', 'stock', 'min_stock');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $items = $query->orderBy('name')
            ->get()
            ->map(fn($i) => [
                ...$i->toArray(),
                'is_low_stock' => $i->isLowStock(),
            ]);

        return response()->json($items);
    }

    public function monthlySummary(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $transactions = Transaction::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->completed()
            ->get();

        $dailyData = $transactions->groupBy(fn($t) => $t->created_at->format('Y-m-d'))
            ->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ]);

        return response()->json([
            'year' => $year,
            'month' => $month,
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total'),
            'average_per_transaction' => $transactions->count() > 0
                ? round($transactions->sum('total') / $transactions->count())
                : 0,
            'daily_data' => $dailyData,
        ]);
    }

    public function inventoryHistory(Request $request)
    {
        $query = \App\Models\InventoryTransaction::with(['inventoryItem', 'user', 'transaction.customer.customerType']);

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date . ' 00:00:00');
        } elseif ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
        }

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['in', 'out', 'adjustment'])) {
            $query->where('type', $request->type);
        }

        // Filter by inventory item
        if ($request->has('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(25);

        return response()->json($transactions);
    }

    public function productHistory(Request $request)
    {
        $query = \App\Models\ProductStockTransaction::with(['product', 'user', 'transaction.customer.customerType']);

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date . ' 00:00:00');
        } elseif ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
        }

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['in', 'out', 'adjustment'])) {
            $query->where('type', $request->type);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(25);

        return response()->json($transactions);
    }
}
