<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    private int $lowStockThreshold = 5;

    public function products()
    {
        $this->ensureProductImageColumns();
        $this->ensureSubcategoryTables();
        $this->ensureProductDetailsTable();

        $rows = DB::table('product')
            ->join('category', 'product.category_code', '=', 'category.category_code')
            ->leftJoin('subcategory', 'product.subcategory_id', '=', 'subcategory.subcategory_id')
            ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
            ->select('product.product_id', 'category.category_name', 'subcategory.subcategory_name', 'product.product_name', 'product.price', 'stock.quantity_available', 'product.has_warranty', 'product.is_active', 'product.image_front')
            ->orderBy('product.product_name')
            ->get();

        return view('admin.modules.table', [
            'title' => 'Products',
            'subtitle' => 'Manage product details, stock, warranty, and active status.',
            'icon' => 'bi-box-seam',
            'actionLabel' => 'Add Product',
            'actionRoute' => route('admin.products.create'),
            'columns' => ['Image', 'Product Name', '7-Digit Product ID', 'Category', 'Subcategory', 'Price', 'Stock Status', 'Product Details', 'View Product', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->image_front
                    ? '<img src="' . asset($row->image_front) . '" alt="' . e($row->product_name) . '" class="admin-product-thumb">'
                    : '<span class="admin-product-thumb admin-product-thumb-empty"><i class="bi bi-image"></i></span>',
                $row->product_name,
                $row->product_id,
                $row->category_name,
                $row->subcategory_name ?? '-',
                'PKR ' . number_format($row->price, 2),
                $this->stockStatus((int) ($row->quantity_available ?? 0), (bool) $row->is_active),
                $this->productDetailsSummary($row->product_id),
                '<a href="' . route('store.product', $row->product_id) . '" class="table-btn-action" target="_blank" title="View Product"><i class="bi bi-eye"></i></a>',
                '<div class="action-row"><a href="' . route('admin.products.edit', $row->product_id) . '" class="table-btn-action"><i class="bi bi-pencil"></i></a><form method="POST" action="' . route('admin.products.destroy', $row->product_id) . '" class="inline-action-form">' . csrf_field() . method_field('DELETE') . '<button class="table-btn-action delete" title="Delete"><i class="bi bi-trash"></i></button></form></div>',
            ]),
            'empty' => 'No products found.',
        ]);
    }

    public function productForm(?string $product = null)
    {
        $this->ensureProductImageColumns();
        $this->ensureSubcategoryTables();
        $this->ensureProductDetailsTable();
        $record = $product ? DB::table('product')->where('product_id', $product)->first() : null;
        $categories = DB::table('category')->orderBy('category_name')->get();
        $subcategories = DB::table('subcategory')->orderBy('subcategory_name')->get();
        $stock = $record ? DB::table('stock')->where('product_id', $record->product_id)->first() : null;
        $productDetails = $record
            ? DB::table('product_detail')->where('product_id', $record->product_id)->orderBy('display_order')->orderBy('detail_id')->limit(5)->get()
            : collect();

        while ($productDetails->count() < 5) {
            $productDetails->push((object) ['title' => '', 'body' => '']);
        }

        return view('admin.modules.product-form', compact('record', 'categories', 'subcategories', 'stock', 'productDetails'));
    }

    public function productDetails()
    {
        $this->ensureProductDetailsTable();

        $rows = DB::table('product')
            ->join('category', 'product.category_code', '=', 'category.category_code')
            ->leftJoin('product_detail', 'product.product_id', '=', 'product_detail.product_id')
            ->select('product.product_id', 'product.product_name', 'category.category_name', DB::raw('COUNT(product_detail.detail_id) as details_count'))
            ->groupBy('product.product_id', 'product.product_name', 'category.category_name')
            ->orderBy('product.product_name')
            ->get();

        return view('admin.modules.table', [
            'title' => 'Product Details',
            'subtitle' => 'Questions and answers shown in the brown Product Details section on each product page.',
            'icon' => 'bi-card-checklist',
            'actionLabel' => 'Edit Products',
            'actionRoute' => route('admin.products.index'),
            'columns' => ['Product', 'Product ID', 'Category', 'Detail Count', 'Detail Questions', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->product_name,
                $row->product_id,
                $row->category_name,
                $row->details_count,
                $this->productDetailsSummary($row->product_id),
                '<a href="' . route('admin.products.edit', $row->product_id) . '" class="table-btn-action"><i class="bi bi-pencil"></i></a>',
            ]),
            'empty' => 'No product details found.',
        ]);
    }

    public function saveProduct(Request $request, ?string $product = null)
    {
        $this->ensureProductImageColumns();
        $this->ensureSubcategoryTables();
        $this->ensureProductDetailsTable();

        $data = $request->validate([
            'category_code' => ['required', 'exists:category,category_code'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategory,subcategory_id'],
            'product_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'has_warranty' => ['nullable'],
            'warranty_months' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable'],
            'image_front' => [$product ? 'nullable' : 'required', 'image', 'max:4096'],
            'image_hover' => ['nullable', 'image', 'max:4096'],
            'detail_titles' => ['nullable', 'array'],
            'detail_titles.*' => ['nullable', 'string', 'max:160'],
            'detail_bodies' => ['nullable', 'array'],
            'detail_bodies.*' => ['nullable', 'string'],
        ]);

        $hasWarranty = $request->boolean('has_warranty');
        $isActive = $request->boolean('is_active', true);

        if ($product) {
            $payload = [
                'category_code' => $data['category_code'],
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'product_name' => $data['product_name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'has_warranty' => $hasWarranty,
                'warranty_months' => $hasWarranty ? ($data['warranty_months'] ?? null) : null,
                'is_active' => $isActive,
                'updated_at' => now(),
            ];

            if ($request->hasFile('image_front')) {
                $payload['image_front'] = $this->storeProductImage($request, 'image_front', $product);
            }

            if ($request->hasFile('image_hover')) {
                $payload['image_hover'] = $this->storeProductImage($request, 'image_hover', $product);
            }

            DB::table('product')->where('product_id', $product)->update($payload);

            DB::table('stock')->updateOrInsert(
                ['product_id' => $product],
                ['quantity_available' => $data['stock_quantity'] ?? 0, 'last_restocked_at' => now()]
            );

            $this->saveProductDetails($request, $product);

            return redirect()->route('admin.products.index')->with('status', 'Product updated.');
        }

        $lastNumber = DB::table('product')
            ->where('category_code', $data['category_code'])
            ->max('product_number');
        $nextNumber = str_pad(((int) $lastNumber) + 1, 5, '0', STR_PAD_LEFT);
        $productId = $data['category_code'] . $nextNumber;
        $frontImage = $this->storeProductImage($request, 'image_front', $productId);
        $hoverImage = $request->hasFile('image_hover') ? $this->storeProductImage($request, 'image_hover', $productId) : null;

        DB::table('product')->insert([
            'product_id' => $productId,
            'category_code' => $data['category_code'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'product_number' => $nextNumber,
            'product_name' => $data['product_name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'image_front' => $frontImage,
            'image_hover' => $hoverImage,
            'has_warranty' => $hasWarranty,
            'warranty_months' => $hasWarranty ? ($data['warranty_months'] ?? null) : null,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock')->insert([
            'product_id' => $productId,
            'quantity_available' => $data['stock_quantity'] ?? 0,
            'last_restocked_at' => now(),
        ]);

        $this->saveProductDetails($request, $productId);

        return redirect()->route('admin.products.index')->with('status', 'Product added.');
    }

    public function deleteProduct(string $product)
    {
        $hasOrders = DB::table('order_item')->where('product_id', $product)->exists();

        if ($hasOrders) {
            return back()->withErrors(['product' => 'This product has order history, so it cannot be deleted. Set it inactive instead.']);
        }

        DB::table('product_detail')->where('product_id', $product)->delete();
        DB::table('stock')->where('product_id', $product)->delete();
        DB::table('product')->where('product_id', $product)->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    public function categories()
    {
        $this->ensureSubcategoryTables();
        $rows = DB::table('category')->orderBy('category_code')->get();

        return view('admin.modules.table', [
            'title' => 'Categories',
            'subtitle' => 'Product categories with their required 2-digit product code.',
            'icon' => 'bi-tags',
            'actionLabel' => 'Add Category',
            'actionRoute' => route('admin.categories.create'),
            'columns' => ['Code', 'Category Name', 'Subcategories', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->category_code,
                $row->category_name,
                DB::table('subcategory')->where('category_code', $row->category_code)->count(),
                '<div class="action-row"><a href="' . route('admin.categories.edit', $row->category_code) . '" class="table-btn-action"><i class="bi bi-pencil"></i></a><form method="POST" action="' . route('admin.categories.destroy', $row->category_code) . '" class="inline-action-form">' . csrf_field() . method_field('DELETE') . '<button class="table-btn-action delete" title="Delete"><i class="bi bi-trash"></i></button></form></div>',
            ]),
            'empty' => 'No categories found.',
        ]);
    }

    public function categoryForm(?string $category = null)
    {
        $this->ensureSubcategoryTables();
        $record = $category ? DB::table('category')->where('category_code', $category)->first() : null;
        abort_if($category && ! $record, 404);
        $subcategories = $record
            ? DB::table('subcategory')->where('category_code', $record->category_code)->orderBy('subcategory_name')->get()
            : collect();

        return view('admin.modules.category-form', compact('record', 'subcategories'));
    }

    public function saveCategory(Request $request, ?string $category = null)
    {
        $this->ensureSubcategoryTables();
        $data = $request->validate($category ? [
            'category_name' => ['required', 'string', 'max:60', 'unique:category,category_name,' . $category . ',category_code'],
        ] : [
            'category_code' => ['required', 'digits:2', 'unique:category,category_code'],
            'category_name' => ['required', 'string', 'max:60', 'unique:category,category_name'],
        ]);

        if ($category) {
            DB::table('category')->where('category_code', $category)->update([
                'category_name' => $data['category_name'],
            ]);

            return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
        }

        DB::table('category')->insert($data);

        return redirect()->route('admin.categories.index')->with('status', 'Category added.');
    }

    public function deleteCategory(string $category)
    {
        $hasProducts = DB::table('product')->where('category_code', $category)->exists();

        if ($hasProducts) {
            return back()->withErrors(['category' => 'This category has products, so it cannot be deleted. Move or delete those products first.']);
        }

        DB::table('subcategory')->where('category_code', $category)->delete();
        DB::table('category')->where('category_code', $category)->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }

    public function saveSubcategory(Request $request, string $category)
    {
        $this->ensureSubcategoryTables();
        abort_unless(DB::table('category')->where('category_code', $category)->exists(), 404);

        $data = $request->validate([
            'subcategory_name' => ['required', 'string', 'max:80'],
        ]);

        DB::table('subcategory')->updateOrInsert(
            ['category_code' => $category, 'subcategory_name' => $data['subcategory_name']],
            ['category_code' => $category, 'subcategory_name' => $data['subcategory_name']]
        );

        return back()->with('status', 'Subcategory added.');
    }

    public function deleteSubcategory(int $subcategory)
    {
        $this->ensureSubcategoryTables();
        DB::table('product')->where('subcategory_id', $subcategory)->update(['subcategory_id' => null]);
        DB::table('subcategory')->where('subcategory_id', $subcategory)->delete();

        return back()->with('status', 'Subcategory removed.');
    }

    public function stock(bool $lowOnly = false)
    {
        $query = DB::table('stock')
            ->join('product', 'stock.product_id', '=', 'product.product_id')
            ->select('stock.product_id', 'product.product_name', 'stock.quantity_available', 'stock.last_restocked_at');

        if ($lowOnly) {
            $query->where('stock.quantity_available', '<=', $this->lowStockThreshold);
        }

        $rows = $query->orderBy('stock.quantity_available')->get();

        return view('admin.modules.table', [
            'title' => $lowOnly ? 'Low / Out of Stock' : 'Stock Levels',
            'subtitle' => 'Monitor current product inventory from the stock table.',
            'icon' => 'bi-boxes',
            'columns' => ['Product ID', 'Product', 'Quantity Available', 'Last Restocked'],
            'rows' => $rows->map(fn ($row) => [
                $row->product_id,
                $row->product_name,
                $row->quantity_available,
                $row->last_restocked_at ? date('d M Y', strtotime($row->last_restocked_at)) : '-',
            ]),
            'empty' => 'No stock records found.',
        ]);
    }

    public function orders(Request $request, ?string $delivery = null)
    {
        $query = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->join('customer', 'orders.customer_id', '=', 'customer.customer_id')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->join('delivery_type', 'order_item.delivery_code', '=', 'delivery_type.delivery_code')
            ->leftJoin('payment', 'orders.order_id', '=', 'payment.order_id')
            ->select('orders.order_id', 'order_item.order_number', 'customer.full_name', 'product.product_name', 'delivery_type.delivery_name', DB::raw('(order_item.quantity * order_item.unit_price) as amount'), DB::raw('COALESCE(payment.payment_status, "pending") as payment_status'), 'orders.order_status', 'orders.order_date');

        $deliveryCode = $delivery ?: $request->query('delivery_code');

        if ($deliveryCode) {
            $query->where('order_item.delivery_code', $deliveryCode);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('orders.order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('orders.order_date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('orders.order_status', $request->status);
        }

        $rows = $query->orderByDesc('orders.order_date')->limit(50)->get();

        return view('admin.modules.table', [
            'title' => $delivery ? 'Orders by Delivery Type' : 'All Orders',
            'subtitle' => 'Filter orders by date range, delivery type, and status from this page.',
            'icon' => 'bi-clipboard-check',
            'filters' => view('admin.modules.partials.order-filters', ['deliveryTypes' => DB::table('delivery_type')->orderBy('delivery_name')->get()]),
            'columns' => ['Order No.', 'Customer', 'Product', 'Delivery Type', 'Amount', 'Payment Status', 'Order Status', 'Date', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->order_number ?? '-',
                $row->full_name,
                $row->product_name,
                $row->delivery_name,
                'PKR ' . number_format($row->amount, 2),
                ucwords(str_replace('_', ' ', $row->payment_status)),
                ucwords(str_replace('_', ' ', $row->order_status)),
                date('d M Y', strtotime($row->order_date)),
                '<a href="' . route('admin.orders.show', $row->order_id) . '" class="table-btn-action"><i class="bi bi-eye"></i></a>',
            ]),
            'empty' => 'No orders found.',
        ]);
    }

    public function orderDetail(int $order)
    {
        $header = DB::table('orders')
            ->join('customer', 'orders.customer_id', '=', 'customer.customer_id')
            ->join('customer_address', 'orders.shipping_address_id', '=', 'customer_address.address_id')
            ->where('orders.order_id', $order)
            ->select('orders.*', 'customer.full_name', 'customer.email', 'customer.phone', 'customer_address.address_line1', 'customer_address.address_line2', 'customer_address.city', 'customer_address.state', 'customer_address.postal_code')
            ->first();

        abort_unless($header, 404);

        $items = DB::table('order_item')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->join('delivery_type', 'order_item.delivery_code', '=', 'delivery_type.delivery_code')
            ->where('order_item.order_id', $order)
            ->select('order_item.*', 'product.product_name', 'delivery_type.delivery_name')
            ->get();

        $payment = DB::table('payment')->where('order_id', $order)->first();
        $dispatch = DB::table('dispatch')->whereIn('order_item_id', $items->pluck('order_item_id'))->get();

        return view('admin.modules.order-detail', compact('header', 'items', 'payment', 'dispatch'));
    }

    public function employees()
    {
        $rows = DB::table('employee')->orderByDesc('created_at')->get();

        return view('admin.modules.table', [
            'title' => 'Employees',
            'subtitle' => 'Admin-created employee accounts. Employees change their own passwords.',
            'icon' => 'bi-person-badge',
            'actionLabel' => 'Add Employee',
            'actionRoute' => route('admin.employees.create'),
            'columns' => ['Name', 'Email', 'Phone', 'Status', 'Date Added', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->full_name,
                $row->email,
                $row->phone ?? '-',
                ucfirst($row->status),
                date('d M Y', strtotime($row->created_at)),
                $row->status === 'active'
                    ? '<form method="POST" action="' . route('admin.employees.deactivate', $row->employee_id) . '" class="inline-action-form">' . csrf_field() . '<button class="table-btn-action" title="Deactivate"><i class="bi bi-person-x"></i></button></form>'
                    : '<form method="POST" action="' . route('admin.employees.activate', $row->employee_id) . '" class="inline-action-form">' . csrf_field() . '<button class="table-btn-action" title="Reactivate"><i class="bi bi-person-check"></i></button></form>',
            ]),
            'empty' => 'No employees found.',
        ]);
    }

    public function employeeForm()
    {
        return view('admin.modules.employee-form');
    }

    public function storeEmployee(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s.\'-]+$/'],
            'email' => ['required', 'email', 'max:150', 'unique:employee,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        DB::table('employee')->insert([
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'status' => 'active',
            'created_by' => session('admin_id', 1),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.employees.index')->with('status', 'Employee created.');
    }

    public function deactivateEmployee(int $employee)
    {
        DB::table('employee')->where('employee_id', $employee)->update(['status' => 'inactive']);

        return back()->with('status', 'Employee deactivated.');
    }

    public function activateEmployee(int $employee)
    {
        DB::table('employee')->where('employee_id', $employee)->update(['status' => 'active']);

        return back()->with('status', 'Employee reactivated.');
    }

    public function customers(bool $inactive = false)
    {
        $rows = DB::table('customer')
            ->where('status', $inactive ? 'inactive' : 'active')
            ->orderByDesc('registered_at')
            ->get();

        return view('admin.modules.table', [
            'title' => $inactive ? 'Deactivated Accounts' : 'Registered Customers',
            'subtitle' => 'Read-only customer records for support. Admin can activate or deactivate accounts only.',
            'icon' => 'bi-people',
            'columns' => ['Name', 'Email', 'Phone', 'Registered', 'Status', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->full_name,
                $row->email,
                $row->phone ?? '-',
                date('d M Y', strtotime($row->registered_at)),
                ucfirst($row->status),
                '<a href="' . route('admin.customers.show', $row->customer_id) . '" class="table-btn-action"><i class="bi bi-eye"></i></a>',
            ]),
            'empty' => 'No customers found.',
        ]);
    }

    public function customerDetail(int $customer)
    {
        $record = DB::table('customer')->where('customer_id', $customer)->first();
        abort_unless($record, 404);
        $addresses = DB::table('customer_address')->where('customer_id', $customer)->get();
        $orders = DB::table('orders')->where('customer_id', $customer)->orderByDesc('order_date')->get();

        return view('admin.modules.customer-detail', compact('record', 'addresses', 'orders'));
    }

    public function payments(string $method)
    {
        $rows = DB::table('payment')
            ->join('orders', 'payment.order_id', '=', 'orders.order_id')
            ->join('customer', 'orders.customer_id', '=', 'customer.customer_id')
            ->where('payment.payment_method', $method)
            ->select('payment.*', 'customer.full_name')
            ->orderByDesc('payment.payment_date')
            ->get();

        return view('admin.modules.table', [
            'title' => ucwords(str_replace(['_', 'vpp_cod'], [' ', 'VPP / Cash on Delivery'], $method)) . ' Payments',
            'subtitle' => $method === 'cheque' ? 'Cheque payments can be marked cleared after bank confirmation.' : 'Payment statuses are viewed from the payment records.',
            'icon' => 'bi-credit-card',
            'columns' => ['Payment ID', 'Customer', 'Amount', 'Status', 'Date', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->payment_id,
                $row->full_name,
                'PKR ' . number_format($row->amount, 2),
                ucfirst($row->payment_status),
                date('d M Y', strtotime($row->payment_date)),
                $method === 'cheque' && $row->payment_status === 'pending'
                    ? '<form method="POST" action="' . route('admin.payments.cheque.clear', $row->payment_id) . '" class="inline-action-form">' . csrf_field() . '<button class="table-btn-action" title="Mark Cleared"><i class="bi bi-check2"></i></button></form>'
                    : '<span class="text-muted-green">View only</span>',
            ]),
            'empty' => 'No payments found.',
        ]);
    }

    public function returns()
    {
        $rows = DB::table('return_replace_request')
            ->join('order_item', 'return_replace_request.order_item_id', '=', 'order_item.order_item_id')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->join('customer', 'orders.customer_id', '=', 'customer.customer_id')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->select('return_replace_request.*', 'order_item.order_number', 'customer.full_name', 'product.product_name')
            ->orderByDesc('request_date')
            ->get();

        return view('admin.modules.table', [
            'title' => 'Returns & Replacements',
            'subtitle' => 'Review return and replacement requests, approve/reject, and mark refunds issued.',
            'icon' => 'bi-arrow-counterclockwise',
            'columns' => ['Order No.', 'Product', 'Customer', 'Type', 'Reason', 'Request Date', 'Status', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->order_number,
                $row->product_name,
                $row->full_name,
                ucfirst($row->request_type),
                $row->reason ?? '-',
                date('d M Y', strtotime($row->request_date)),
                ucfirst($row->status),
                $row->status === 'requested'
                    ? '<div class="action-row"><form method="POST" action="' . route('admin.returns.approve', $row->request_id) . '" class="inline-action-form">' . csrf_field() . '<button class="table-btn-action" title="Approve"><i class="bi bi-check2"></i></button></form><form method="POST" action="' . route('admin.returns.reject', $row->request_id) . '" class="inline-action-form">' . csrf_field() . '<button class="table-btn-action delete" title="Reject"><i class="bi bi-x"></i></button></form></div>'
                    : '<span class="text-muted-green">Reviewed</span>',
            ]),
            'empty' => 'No pending requests.',
        ]);
    }

    public function warranty()
    {
        $rows = DB::table('warranty_card')
            ->join('order_item', 'warranty_card.order_item_id', '=', 'order_item.order_item_id')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->join('customer', 'orders.customer_id', '=', 'customer.customer_id')
            ->select('warranty_card.*', 'product.product_name', 'customer.full_name')
            ->orderByDesc('warranty_start_date')
            ->get();

        return view('admin.modules.table', [
            'title' => 'Warranty Records',
            'subtitle' => 'Search issued warranty cards by product or customer.',
            'icon' => 'bi-shield-check',
            'columns' => ['Product', 'Customer', 'Start Date', 'End Date', 'Terms'],
            'rows' => $rows->map(fn ($row) => [$row->product_name, $row->full_name, $row->warranty_start_date, $row->warranty_end_date, $row->terms ?? '-']),
            'empty' => 'No warranty records found.',
        ]);
    }

    public function feedback()
    {
        $rows = DB::table('feedback')
            ->join('customer', 'feedback.customer_id', '=', 'customer.customer_id')
            ->select('feedback.*', 'customer.full_name')
            ->orderByDesc('submitted_at')
            ->get();

        return view('admin.modules.table', [
            'title' => 'Feedback',
            'subtitle' => 'Review customer ratings and mark feedback as reviewed.',
            'icon' => 'bi-chat-square-heart',
            'columns' => ['Customer', 'Rating', 'Message', 'Submitted', 'Reviewed'],
            'rows' => $rows->map(fn ($row) => [
                $row->full_name,
                $row->rating ?? '-',
                $row->message,
                date('d M Y', strtotime($row->submitted_at)),
                property_exists($row, 'reviewed_at') && $row->reviewed_at
                    ? '<span class="text-muted-green">Reviewed</span>'
                    : '<form method="POST" action="' . route('admin.feedback.reviewed', $row->feedback_id) . '" class="inline-action-form">' . csrf_field() . '<button class="table-btn-action" title="Mark Reviewed"><i class="bi bi-check2"></i></button></form>',
            ]),
            'empty' => 'No feedback yet.',
        ]);
    }

    public function faq()
    {
        $rows = DB::table('faq')->orderBy('display_order')->get();

        return view('admin.modules.table', [
            'title' => 'FAQ Management',
            'subtitle' => 'Add, edit, delete, and reorder question-answer pairs.',
            'icon' => 'bi-question-circle',
            'actionLabel' => 'Add FAQ',
            'actionRoute' => route('admin.faq.create'),
            'columns' => ['Order', 'Question', 'Answer', 'Action'],
            'rows' => $rows->map(fn ($row) => [
                $row->display_order,
                $row->question,
                $row->answer,
                '<div class="action-row"><a href="' . route('admin.faq.edit', $row->faq_id) . '" class="table-btn-action"><i class="bi bi-pencil"></i></a><form method="POST" action="' . route('admin.faq.destroy', $row->faq_id) . '" class="inline-action-form">' . csrf_field() . method_field('DELETE') . '<button class="table-btn-action delete" title="Delete"><i class="bi bi-trash"></i></button></form></div>',
            ]),
            'empty' => 'No FAQs found.',
        ]);
    }

    public function clearChequePayment(int $payment)
    {
        DB::table('payment')->where('payment_id', $payment)->where('payment_method', 'cheque')->update(['payment_status' => 'cleared']);

        return back()->with('status', 'Cheque payment marked as cleared.');
    }

    public function approveReturn(int $request)
    {
        DB::table('return_replace_request')->where('request_id', $request)->update([
            'status' => 'approved',
            'processed_by' => session('admin_id', 1),
            'resolved_date' => now(),
        ]);

        return back()->with('status', 'Request approved.');
    }

    public function rejectReturn(int $request)
    {
        DB::table('return_replace_request')->where('request_id', $request)->update([
            'status' => 'rejected',
            'processed_by' => session('admin_id', 1),
            'resolved_date' => now(),
        ]);

        return back()->with('status', 'Request rejected.');
    }

    public function markFeedbackReviewed(int $feedback)
    {
        $this->ensureFeedbackReviewedColumn();
        DB::table('feedback')->where('feedback_id', $feedback)->update(['reviewed_at' => now()]);

        return back()->with('status', 'Feedback marked as reviewed.');
    }

    public function faqForm(?int $faq = null)
    {
        $record = $faq ? DB::table('faq')->where('faq_id', $faq)->first() : null;
        abort_if($faq && ! $record, 404);

        return view('admin.modules.faq-form', compact('record'));
    }

    public function saveFaq(Request $request, ?int $faq = null)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = [
            'question' => $data['question'],
            'answer' => $data['answer'],
            'display_order' => $data['display_order'] ?? 0,
        ];

        if ($faq) {
            DB::table('faq')->where('faq_id', $faq)->update($payload);
            return redirect()->route('admin.faq.index')->with('status', 'FAQ updated.');
        }

        $payload['created_by'] = session('admin_id', 1);
        DB::table('faq')->insert($payload);

        return redirect()->route('admin.faq.index')->with('status', 'FAQ added.');
    }

    public function deleteFaq(int $faq)
    {
        DB::table('faq')->where('faq_id', $faq)->delete();

        return back()->with('status', 'FAQ deleted.');
    }

    private function ensureFeedbackReviewedColumn(): void
    {
        $hasColumn = DB::select("SHOW COLUMNS FROM feedback LIKE 'reviewed_at'");

        if (! $hasColumn) {
            DB::statement('ALTER TABLE feedback ADD reviewed_at DATETIME NULL');
        }
    }

    private function ensureProductImageColumns(): void
    {
        if (! DB::select("SHOW COLUMNS FROM product LIKE 'image_front'")) {
            DB::statement('ALTER TABLE product ADD image_front VARCHAR(255) NULL AFTER price');
        }

        if (! DB::select("SHOW COLUMNS FROM product LIKE 'image_hover'")) {
            DB::statement('ALTER TABLE product ADD image_hover VARCHAR(255) NULL AFTER image_front');
        }
    }

    private function ensureSubcategoryTables(): void
    {
        if (! DB::select("SHOW TABLES LIKE 'subcategory'")) {
            DB::statement('CREATE TABLE subcategory (
                subcategory_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                category_code CHAR(2) NOT NULL,
                subcategory_name VARCHAR(80) NOT NULL,
                UNIQUE KEY unique_category_subcategory (category_code, subcategory_name),
                CONSTRAINT fk_subcategory_category FOREIGN KEY (category_code) REFERENCES category(category_code) ON DELETE CASCADE
            ) ENGINE=InnoDB');
        }

        if (! DB::select("SHOW COLUMNS FROM product LIKE 'subcategory_id'")) {
            DB::statement('ALTER TABLE product ADD subcategory_id BIGINT UNSIGNED NULL AFTER category_code');
        }
    }

    private function ensureProductDetailsTable(): void
    {
        if (! DB::select("SHOW TABLES LIKE 'product_detail'")) {
            DB::statement('CREATE TABLE product_detail (
                detail_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id CHAR(7) NOT NULL,
                title VARCHAR(160) NOT NULL,
                body TEXT NOT NULL,
                display_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX product_detail_product_id_index (product_id),
                CONSTRAINT fk_product_detail_product FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
            ) ENGINE=InnoDB');
        }
    }

    private function saveProductDetails(Request $request, string $productId): void
    {
        $titles = $request->input('detail_titles', []);
        $bodies = $request->input('detail_bodies', []);
        $rows = [];

        for ($index = 0; $index < 5; $index++) {
            $title = trim((string) ($titles[$index] ?? ''));
            $body = trim((string) ($bodies[$index] ?? ''));

            if ($title === '' && $body === '') {
                continue;
            }

            $rows[] = [
                'product_id' => $productId,
                'title' => $title !== '' ? $title : 'Product detail',
                'body' => $body !== '' ? $body : 'Details will be updated soon.',
                'display_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('product_detail')->where('product_id', $productId)->delete();

        if ($rows) {
            DB::table('product_detail')->insert($rows);
        }
    }

    private function productDetailsSummary(string $productId): string
    {
        $details = DB::table('product_detail')
            ->where('product_id', $productId)
            ->orderBy('display_order')
            ->orderBy('detail_id')
            ->limit(5)
            ->pluck('title');

        if ($details->isEmpty()) {
            return '<span class="text-muted">No details yet</span>';
        }

        return '<ol class="admin-detail-list">' . $details->map(fn ($title) => '<li>' . e($title) . '</li>')->implode('') . '</ol>';
    }

    private function storeProductImage(Request $request, string $field, string $productId): string
    {
        $file = $request->file($field);
        $directory = public_path('assets/store/products');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = $file->getClientOriginalExtension();
        $filename = $productId . '-' . Str::slug($field) . '-' . time() . '.' . $extension;
        $file->move($directory, $filename);

        return 'assets/store/products/' . $filename;
    }

    private function stockStatus(int $quantity, bool $isActive): string
    {
        if (! $isActive) {
            return '<span class="status-pill status-pill-muted">Inactive</span>';
        }

        if ($quantity <= 0) {
            return '<span class="status-pill status-pill-danger">Out of stock</span>';
        }

        if ($quantity <= $this->lowStockThreshold) {
            return '<span class="status-pill status-pill-warning">Low stock</span>';
        }

        return '<span class="status-pill status-pill-success">In stock</span>';
    }

    public function settings(string $page = 'profile')
    {
        return view('admin.modules.settings', compact('page'));
    }
}
