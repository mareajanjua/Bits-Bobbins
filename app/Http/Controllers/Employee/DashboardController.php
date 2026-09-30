<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    private function ordersQuery(Request $request)
    {
        $query = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->join('customer', 'orders.customer_id', '=', 'customer.customer_id')
            ->join('customer_address', 'orders.shipping_address_id', '=', 'customer_address.address_id')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->join('delivery_type', 'order_item.delivery_code', '=', 'delivery_type.delivery_code')
            ->leftJoin('payment', 'orders.order_id', '=', 'payment.order_id')
            ->leftJoin('dispatch', 'order_item.order_item_id', '=', 'dispatch.order_item_id')
            ->select(
                'orders.order_id',
                'orders.order_date',
                'orders.order_status',
                'order_item.order_item_id',
                'order_item.order_number',
                'order_item.quantity',
                'order_item.item_status',
                'customer.full_name',
                'customer.email',
                'product.product_name',
                'delivery_type.delivery_name',
                'order_item.delivery_code',
                DB::raw('COALESCE(payment.payment_status, "pending") as payment_status'),
                DB::raw('COALESCE(payment.payment_method, "vpp_cod") as payment_method'),
                'dispatch.dispatch_date',
                'dispatch.expected_delivery_date',
                'dispatch.actual_delivery_date',
                'dispatch.courier_tracking_number'
            );

        if ($request->filled('date_from')) {
            $query->whereDate('orders.order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('orders.order_date', '<=', $request->date_to);
        }

        if ($request->filled('delivery_code')) {
            $query->where('order_item.delivery_code', $request->delivery_code);
        }

        if ($request->filled('status')) {
            $query->where('order_item.item_status', $request->status);
        }

        if ($request->filled('q')) {
            $query->where(function ($inner) use ($request) {
                $inner->where('order_item.order_number', 'like', '%' . $request->q . '%')
                    ->orWhere('customer.full_name', 'like', '%' . $request->q . '%')
                    ->orWhere('customer.email', 'like', '%' . $request->q . '%')
                    ->orWhere('product.product_name', 'like', '%' . $request->q . '%')
                    ->orWhere('delivery_type.delivery_name', 'like', '%' . $request->q . '%');
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $ordersReceivedToday = DB::table('orders')->whereDate('order_date', now()->toDateString())->count();
        $pendingDispatch = DB::table('order_item')
            ->join('payment', 'order_item.order_id', '=', 'payment.order_id')
            ->whereIn('order_item.item_status', ['placed', 'payment_cleared'])
            ->where(function ($query) {
                $query->where('payment.payment_status', 'cleared')
                    ->orWhere('payment.payment_method', 'vpp_cod');
            })
            ->count();
        $dispatchedToday = DB::table('dispatch')->whereDate('dispatch_date', now()->toDateString())->count();
        $deliveredThisWeek = DB::table('dispatch')->whereDate('actual_delivery_date', '>=', now()->startOfWeek()->toDateString())->count();
        $overdueDispatch = DB::table('orders')
            ->whereDate('order_date', '<', now()->subDays(2)->toDateString())
            ->whereIn('order_status', ['placed', 'payment_cleared'])
            ->count();

        $deliveryTypes = DB::table('delivery_type')->orderBy('delivery_code')->get();
        $orders = $this->ordersQuery($request)->orderByDesc('orders.order_date')->limit(25)->get();

        return view('employee.dashboard', compact('ordersReceivedToday', 'pendingDispatch', 'dispatchedToday', 'deliveredThisWeek', 'overdueDispatch', 'deliveryTypes', 'orders'));
    }

    public function orders(Request $request)
    {
        $deliveryTypes = DB::table('delivery_type')->orderBy('delivery_code')->get();
        $orders = $this->ordersQuery($request)->orderByDesc('orders.order_date')->paginate(20)->withQueryString();
        $pageTitle = 'Orders';
        $pageSubtitle = 'View and filter live orders by date, delivery type, status, order number, or customer name.';

        return view('employee.orders', compact('orders', 'deliveryTypes', 'pageTitle', 'pageSubtitle'));
    }

    public function showOrder(int $item)
    {
        $record = $this->ordersQuery(new Request())->where('order_item.order_item_id', $item)->first();
        abort_unless($record, 404);
        $address = DB::table('orders')
            ->join('customer_address', 'orders.shipping_address_id', '=', 'customer_address.address_id')
            ->where('orders.order_id', $record->order_id)
            ->select('customer_address.*')
            ->first();

        return view('employee.order-detail', compact('record', 'address'));
    }

    public function updateOrder(Request $request, int $item)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['dispatched', 'delivered'])],
            'dispatch_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'actual_delivery_date' => ['nullable', 'date'],
            'courier_tracking_number' => ['nullable', 'string', 'max:60'],
            'current_item_status' => ['nullable', 'string'],
        ]);

        $record = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->leftJoin('payment', 'orders.order_id', '=', 'payment.order_id')
            ->where('order_item.order_item_id', $item)
            ->select(
                'order_item.*',
                'product.has_warranty',
                'product.warranty_months',
                DB::raw('COALESCE(payment.payment_method, "vpp_cod") as payment_method'),
                DB::raw('COALESCE(payment.payment_status, "pending") as payment_status')
            )
            ->first();
        abort_unless($record, 404);

        if (($data['current_item_status'] ?? $record->item_status) !== $record->item_status) {
            return back()->withErrors(['status' => 'This order was updated by someone else. Please refresh before saving.']);
        }

        if (in_array($data['status'], ['dispatched', 'delivered'], true) && in_array($record->payment_method, ['credit_card', 'cheque', 'dd'], true) && $record->payment_status !== 'cleared') {
            return back()->withErrors(['status' => 'Credit card, cheque, and demand draft orders cannot be dispatched until payment is cleared.']);
        }

        DB::table('order_item')->where('order_item_id', $item)->update(['item_status' => $data['status']]);

        if ($data['status'] === 'delivered' && $record->payment_method === 'vpp_cod') {
            $hasPendingItems = DB::table('order_item')
                ->where('order_id', $record->order_id)
                ->where('item_status', '!=', 'delivered')
                ->exists();

            if (! $hasPendingItems) {
                DB::table('payment')
                    ->where('order_id', $record->order_id)
                    ->where('payment_method', 'vpp_cod')
                    ->where('payment_status', 'pending')
                    ->update([
                        'payment_status' => 'cleared',
                        'payment_date' => now(),
                    ]);
            }
        }

        DB::table('orders')->where('order_id', $record->order_id)->update(['order_status' => $data['status']]);

        DB::table('dispatch')->updateOrInsert(
            ['order_item_id' => $item],
            [
                'dispatched_by' => session('employee_id'),
                'dispatch_date' => $data['dispatch_date'] ?? now(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? now()->addDays(3)->toDateString(),
                'actual_delivery_date' => $data['status'] === 'delivered' ? ($data['actual_delivery_date'] ?? now()->toDateString()) : null,
                'courier_tracking_number' => $data['courier_tracking_number'] ?? null,
            ]
        );

        if ($data['status'] === 'delivered' && (bool) $record->has_warranty) {
            $this->createWarrantyCard($item, (int) $record->warranty_months, $data['actual_delivery_date'] ?? now()->toDateString());
        }

        return back()->with('status', 'Order delivery status updated.');
    }

    private function createWarrantyCard(int $orderItemId, int $warrantyMonths, string $startDate): void
    {
        if ($warrantyMonths <= 0) {
            return;
        }

        $start = \Carbon\Carbon::parse($startDate)->toDateString();
        $end = \Carbon\Carbon::parse($startDate)->addMonths($warrantyMonths)->toDateString();

        DB::table('warranty_card')->updateOrInsert(
            ['order_item_id' => $orderItemId],
            [
                'warranty_start_date' => $start,
                'warranty_end_date' => $end,
                'terms' => 'Warranty coverage is valid for ' . $warrantyMonths . ' month(s) from delivery date.',
            ]
        );
    }

    public function deliveryQueue(Request $request, string $type)
    {
        $pageTitle = match ($type) {
            'pending' => 'Pending Dispatch',
            'dispatched' => 'Dispatched / In Transit',
            'delivered' => 'Delivered',
        };
        $pageSubtitle = match ($type) {
            'pending' => 'Orders ready for dispatch after cleared payment, plus VPP orders.',
            'dispatched' => 'Orders already dispatched or currently in transit.',
            'delivered' => 'Orders marked delivered with delivery records.',
        };

        $request->merge([
            'status' => match ($type) {
                'pending' => $request->get('status', 'payment_cleared'),
                'dispatched' => $request->get('status', 'dispatched'),
                'delivered' => $request->get('status', 'delivered'),
                default => $request->get('status'),
            },
        ]);

        $deliveryTypes = DB::table('delivery_type')->orderBy('delivery_code')->get();
        $orders = $this->ordersQuery($request)->orderByDesc('orders.order_date')->paginate(20)->withQueryString();

        return view('employee.orders', compact('orders', 'deliveryTypes', 'pageTitle', 'pageSubtitle'));
    }

    public function reports(Request $request)
    {
        $deliveryTypes = DB::table('delivery_type')->orderBy('delivery_code')->get();
        $query = DB::table('dispatch')
            ->join('order_item', 'dispatch.order_item_id', '=', 'order_item.order_item_id')
            ->join('delivery_type', 'order_item.delivery_code', '=', 'delivery_type.delivery_code')
            ->select('dispatch.*', 'order_item.order_number', 'order_item.item_status', 'delivery_type.delivery_name');

        if ($request->filled('date_from')) {
            $query->whereDate('dispatch.dispatch_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('dispatch.dispatch_date', '<=', $request->date_to);
        }

        if ($request->filled('delivery_code')) {
            $query->where('order_item.delivery_code', $request->delivery_code);
        }

        $records = $query->orderByDesc('dispatch.dispatch_date')->get();
        $totals = [
            'dispatched' => $records->whereNotNull('dispatch_date')->count(),
            'delivered' => $records->whereNotNull('actual_delivery_date')->count(),
            'overdue' => $records->filter(fn ($row) => $row->expected_delivery_date && ! $row->actual_delivery_date && $row->expected_delivery_date < now()->toDateString())->count(),
        ];

        return view('employee.reports', compact('records', 'totals', 'deliveryTypes'));
    }

    public function account()
    {
        $employee = DB::table('employee')->where('employee_id', session('employee_id'))->first();
        abort_unless($employee, 404);

        return view('employee.account', compact('employee'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'profile_photo' => ['required', 'image', 'max:2048'],
        ]);

        $employee = DB::table('employee')->where('employee_id', session('employee_id'))->first();
        abort_unless($employee, 404);

        $directory = public_path('assets/dashboard/uploads/employees');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'employee-' . $employee->employee_id . '.' . $request->file('profile_photo')->getClientOriginalExtension();
        $request->file('profile_photo')->move($directory, $filename);

        DB::table('employee')->where('employee_id', $employee->employee_id)->update([
            'profile_photo' => 'assets/dashboard/uploads/employees/' . $filename,
        ]);

        return back()->with('status', 'Profile photo updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        $employee = DB::table('employee')->where('employee_id', session('employee_id'))->first();
        abort_unless($employee, 404);

        if (! Hash::check($data['current_password'], $employee->password_hash)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        DB::table('employee')->where('employee_id', $employee->employee_id)->update(['password_hash' => Hash::make($data['password'])]);

        return back()->with('status', 'Password changed.');
    }

}
