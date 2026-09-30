<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    private function productsQuery(Request $request)
    {
        $query = DB::table('product')
            ->join('category', 'product.category_code', '=', 'category.category_code')
            ->leftJoin('subcategory', 'product.subcategory_id', '=', 'subcategory.subcategory_id')
            ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
            ->where('product.is_active', 1)
            ->select('product.*', 'category.category_name', 'subcategory.subcategory_name', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'));

        if ($request->filled('q')) {
            $query->where(function ($inner) use ($request) {
                $inner->where('product.product_name', 'like', '%' . $request->q . '%')
                    ->orWhere('category.category_name', 'like', '%' . $request->q . '%')
                    ->orWhere('subcategory.subcategory_name', 'like', '%' . $request->q . '%')
                    ->orWhere('product.product_id', $request->q);
            });
        }

        if ($request->filled('category')) {
            $query->where('product.category_code', $request->category);
        }

        if ($request->filled('subcategory')) {
            $query->where('product.subcategory_id', $request->subcategory);
        }

        if ($request->filled('min_price')) {
            $query->where('product.price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('product.price', '<=', $request->max_price);
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock.quantity_available', '>', 0);
        }

        if ($request->filled('warranty')) {
            $query->where('product.has_warranty', $request->warranty === 'yes');
        }

        match ($request->get('sort')) {
            'price_low' => $query->orderBy('product.price'),
            'price_high' => $query->orderByDesc('product.price'),
            'newest' => $query->orderByDesc('product.created_at'),
            default => $query->orderBy('product.product_name'),
        };

        return $query;
    }

    private function categories()
    {
        return DB::table('category')->orderBy('category_name')->get();
    }

    private function subcategories()
    {
        return DB::table('subcategory')->orderBy('subcategory_name')->get();
    }

    private function cartItems()
    {
        $cart = session('cart', []);
        if (! $cart) {
            return collect();
        }

        $products = DB::table('product')
            ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
            ->whereIn('product.product_id', array_keys($cart))
            ->select('product.*', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'))
            ->get()
            ->keyBy('product_id');

        return collect($cart)->map(function ($quantity, $productId) use ($products) {
            $product = $products->get($productId);
            return $product ? (object) [
                'product' => $product,
                'quantity' => (int) $quantity,
                'line_total' => (int) $quantity * (float) $product->price,
            ] : null;
        })->filter()->values();
    }

    private function cartResponse(string $status)
    {
        $homeCartItems = $this->cartItems();
        $cartCount = $homeCartItems->sum('quantity');

        return response()->json([
            'status' => $status,
            'cart_count' => $cartCount,
        ]);
    }

    private function activeAccountRedirect()
    {
        $message = 'Please logout before signing into another account.';

        if (session('admin_id')) {
            return redirect()->route('admin.dashboard')->with('status', $message);
        }

        if (session('employee_id')) {
            return redirect()->route('employee.dashboard')->with('status', $message);
        }

        if (session('customer_id')) {
            return redirect()->route('customer.account')->with('status', $message);
        }

        return null;
    }

    public function home()
    {
        return view('frontend.kider-home');
    }

    public function about(Request $request)
    {
        $feedback = DB::table('feedback')
            ->join('customer', 'feedback.customer_id', '=', 'customer.customer_id')
            ->leftJoin('product', 'feedback.product_id', '=', 'product.product_id')
            ->select('customer.full_name', 'customer.profile_photo', 'feedback.rating', 'feedback.message', 'product.product_name')
            ->orderByDesc('feedback.submitted_at')
            ->limit(6)
            ->get();

        $faqQuery = DB::table('faq')->orderBy('display_order');
        if ($request->filled('q')) {
            $faqQuery->where('question', 'like', '%' . $request->q . '%')
                ->orWhere('answer', 'like', '%' . $request->q . '%');
        }
        $faqs = $faqQuery->get();

        return view('store.about', compact('feedback', 'faqs'));
    }

    public function faqPage(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $faqQuery = DB::table('faq')->orderBy('display_order');
        if ($search !== '') {
            $faqQuery->where(function ($query) use ($search) {
                $query->where('question', 'like', '%' . $search . '%')
                    ->orWhere('answer', 'like', '%' . $search . '%');
            });
        }

        $faqs = $faqQuery->get();

        return view('store.faq', compact('faqs', 'search'));
    }

    public function products(Request $request)
    {
        $categories = $this->categories();
        $subcategories = $this->subcategories();
        $products = $this->productsQuery($request)->paginate(9)->withQueryString();

        return view('store.products', compact('categories', 'subcategories', 'products'));
    }

    public function product(string $product)
    {
        $record = DB::table('product')
            ->join('category', 'product.category_code', '=', 'category.category_code')
            ->leftJoin('subcategory', 'product.subcategory_id', '=', 'subcategory.subcategory_id')
            ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
            ->where('product.product_id', $product)
            ->select('product.*', 'category.category_name', 'subcategory.subcategory_name', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'))
            ->first();
        abort_unless($record, 404);

        $this->seedMissingProductDetails();

        $details = DB::table('product_detail')
            ->where('product_id', $record->product_id)
            ->orderBy('display_order')
            ->orderBy('detail_id')
            ->get();

        $related = DB::table('product')
            ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
            ->leftJoin('subcategory', 'product.subcategory_id', '=', 'subcategory.subcategory_id')
            ->where('product.category_code', $record->category_code)
            ->where('product.product_id', '!=', $product)
            ->where('product.is_active', 1)
            ->select('product.*', 'subcategory.subcategory_name', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'))
            ->orderBy('product.product_name')
            ->get();

        $reviews = DB::table('feedback')
            ->join('customer', 'feedback.customer_id', '=', 'customer.customer_id')
            ->where('feedback.product_id', $record->product_id)
            ->select('feedback.*', 'customer.full_name')
            ->orderByDesc('feedback.submitted_at')
            ->get();
        $averageRating = $reviews->count() ? round($reviews->avg('rating'), 1) : null;

        return view('store.product-detail', compact('record', 'related', 'details', 'reviews', 'averageRating'));
    }

    public function cart()
    {
        $items = $this->cartItems();
        $subtotal = $items->sum('line_total');

        return view('store.cart', compact('items', 'subtotal'));
    }

    public function addToCart(Request $request, string $product)
    {
        $request->validate(['quantity' => ['nullable', 'integer', 'min:1']]);
        $record = DB::table('product')
            ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
            ->where('product.product_id', $product)
            ->where('product.is_active', 1)
            ->select('product.product_id', 'product.product_name', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'))
            ->first();
        abort_unless($record, 404);

        $cart = session('cart', []);
        $requestedQuantity = (int) $request->input('quantity', 1);
        $newQuantity = ($cart[$product] ?? 0) + $requestedQuantity;

        if ($newQuantity > (int) $record->stock_qty) {
            $message = $record->stock_qty > 0
                ? 'Only ' . $record->stock_qty . ' item(s) are available for ' . $record->product_name . '.'
                : $record->product_name . ' is out of stock.';

            if ($request->expectsJson()) {
                return response()->json(['status' => $message, 'cart_count' => collect($cart)->sum()], 422);
            }

            return back()->withErrors(['cart' => $message]);
        }

        $cart[$product] = $newQuantity;
        session(['cart' => $cart]);

        if ($request->expectsJson()) {
            return $this->cartResponse('Product added to cart.');
        }

        return redirect()->route('cart.index')->with('status', 'Product added to cart.');
    }

    public function updateCart(Request $request, string $product)
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0']]);
        $cart = session('cart', []);
        if ((int) $request->quantity === 0) {
            unset($cart[$product]);
        } else {
            $record = DB::table('product')
                ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
                ->where('product.product_id', $product)
                ->where('product.is_active', 1)
                ->select('product.product_name', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'))
                ->first();
            abort_unless($record, 404);

            if ((int) $request->quantity > (int) $record->stock_qty) {
                $message = 'Only ' . $record->stock_qty . ' item(s) are available for ' . $record->product_name . '.';

                if ($request->expectsJson()) {
                    return response()->json(['status' => $message, 'cart_count' => collect($cart)->sum()], 422);
                }

                return back()->withErrors(['cart' => $message]);
            }

            $cart[$product] = (int) $request->quantity;
        }
        session(['cart' => $cart]);

        if ($request->expectsJson()) {
            return $this->cartResponse('Cart updated.');
        }

        return redirect()->route('cart.index')->with('status', 'Cart updated.');
    }

    public function loginForm()
    {
        if ($redirect = $this->activeAccountRedirect()) {
            return $redirect;
        }

        return view('store.auth', ['mode' => 'login']);
    }

    public function registerForm()
    {
        if ($redirect = $this->activeAccountRedirect()) {
            return $redirect;
        }

        return view('store.auth', ['mode' => 'register']);
    }

    public function forgotPasswordForm()
    {
        if ($redirect = $this->activeAccountRedirect()) {
            return $redirect;
        }

        return view('store.auth', ['mode' => 'forgot']);
    }

    public function login(Request $request)
    {
        if ($redirect = $this->activeAccountRedirect()) {
            return $redirect;
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(['customer', 'employee'])],
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($data['role'] === 'employee') {
            $employee = DB::table('employee')->where('email', $data['email'])->where('status', 'active')->first();

            if (! $employee || ! Hash::check($data['password'], $employee->password_hash)) {
                return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
            }

            session()->regenerate();
            session(['employee_id' => $employee->employee_id]);
            return redirect()->route('employee.dashboard');
        }

        $customer = DB::table('customer')->where('email', $data['email'])->where('status', 'active')->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password_hash)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        session()->regenerate();
        session(['customer_id' => $customer->customer_id]);
        return redirect(session()->pull('intended_customer_url', route('checkout.index')));
    }

    public function register(Request $request)
    {
        if ($redirect = $this->activeAccountRedirect()) {
            return $redirect;
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z\s.\'-]+$/'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z\s.\'-]+$/'],
            'email' => ['required', 'email', 'max:150', 'unique:customer,email', 'unique:employee,email'],
            'role' => ['required', Rule::in(['customer', 'employee'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'newsletter' => ['nullable'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);

        if ($data['role'] === 'employee') {
            $id = DB::table('employee')->insertGetId([
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'full_name' => $fullName,
                'phone' => $data['phone'] ?? null,
                'status' => 'active',
                'created_by' => session('admin_id', 1),
                'created_at' => now(),
            ]);

            session()->forget(['admin_id', 'customer_id']);
            session()->regenerate();
            session(['employee_id' => $id]);

            return redirect()->route('employee.dashboard');
        }

        $id = DB::table('customer')->insertGetId([
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'full_name' => $fullName,
            'phone' => $data['phone'] ?? null,
            'status' => 'active',
            'registered_at' => now(),
        ]);

        session()->forget(['admin_id', 'employee_id']);
        session()->regenerate();
        session(['customer_id' => $id]);
        return redirect(session()->pull('intended_customer_url', route('checkout.index')));
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'role' => ['required', Rule::in(['customer', 'employee'])],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        $table = $data['role'] === 'employee' ? 'employee' : 'customer';
        $key = $data['role'] === 'employee' ? 'employee_id' : 'customer_id';

        $account = DB::table($table)
            ->where('email', $data['email'])
            ->where('status', 'active')
            ->first();

        if (! $account) {
            return back()->withErrors(['email' => 'No active account was found for that email and role.'])->withInput();
        }

        DB::table($table)->where($key, $account->{$key})->update([
            'password_hash' => Hash::make($data['password']),
        ]);

        return redirect()->route('customer.login')->with('status', 'Password reset successfully. You can sign in with your new password.');
    }

    public function logout()
    {
        session()->forget(['customer_id', 'employee_id', 'admin_id']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('customer.login');
    }

    public function checkout()
    {
        if (! session('customer_id')) {
            return redirect()->route('customer.login')->with('status', 'Please log in or create an account before checkout.');
        }

        $items = $this->cartItems();
        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $customerId = session('customer_id');
        $addresses = $customerId
            ? DB::table('customer_address')->where('customer_id', $customerId)->get()
            : collect();
        $deliveryTypes = DB::table('delivery_type')->orderBy('delivery_code')->get();
        $subtotal = $items->sum('line_total');

        return view('store.checkout', compact('items', 'addresses', 'deliveryTypes', 'subtotal'));
    }

    public function account()
    {
        $customerId = session('customer_id');
        $customer = DB::table('customer')->where('customer_id', $customerId)->first();
        abort_unless($customer, 404);

        $orders = DB::table('orders')
            ->where('customer_id', $customerId)
            ->orderByDesc('order_date')
            ->limit(3)
            ->get();
        $orderCount = DB::table('orders')->where('customer_id', $customerId)->count();
        $addressCount = DB::table('customer_address')->where('customer_id', $customerId)->count();
        $addresses = DB::table('customer_address')->where('customer_id', $customerId)->get();

        return view('store.account', compact('customer', 'orders', 'orderCount', 'addressCount', 'addresses'));
    }

    public function updateCustomerProfile(Request $request)
    {
        $request->validate([
            'profile_photo' => ['required', 'image', 'max:2048'],
        ]);

        $customer = DB::table('customer')->where('customer_id', session('customer_id'))->first();
        abort_unless($customer, 404);

        $directory = public_path('assets/frontend/uploads/customers');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'customer-' . $customer->customer_id . '.' . $request->file('profile_photo')->getClientOriginalExtension();
        $request->file('profile_photo')->move($directory, $filename);

        DB::table('customer')->where('customer_id', $customer->customer_id)->update([
            'profile_photo' => 'assets/frontend/uploads/customers/' . $filename,
        ]);

        return back()->with('status', 'Profile photo updated.');
    }

    public function updateCustomerPassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        $customer = DB::table('customer')->where('customer_id', session('customer_id'))->first();
        abort_unless($customer, 404);

        if (! Hash::check($data['current_password'], $customer->password_hash)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        DB::table('customer')->where('customer_id', $customer->customer_id)->update([
            'password_hash' => Hash::make($data['password']),
        ]);

        return back()->with('status', 'Password updated.');
    }

    public function placeOrder(Request $request)
    {
        if (! session('customer_id')) {
            return redirect()->route('customer.login')->with('status', 'Please log in or create an account before placing an order.');
        }

        $items = $this->cartItems();
        if ($items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'address_line1' => ['required_without:address_id', 'nullable', 'string', 'max:150'],
            'address_line2' => ['nullable', 'string', 'max:150'],
            'city' => ['required_without:address_id', 'nullable', 'string', 'max:80'],
            'state' => ['required_without:address_id', 'nullable', 'string', 'max:80'],
            'postal_code' => ['required_without:address_id', 'nullable', 'string', 'max:15'],
            'delivery_code' => ['required', 'exists:delivery_type,delivery_code'],
            'payment_method' => ['required', Rule::in(['credit_card', 'cheque', 'vpp_cod', 'dd'])],
            'card_holder_name' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'max:100'],
            'card_number' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'min:12'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'max:10'],
            'card_cvv' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'max:4'],
            'cheque_number' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:30'],
            'dd_number' => ['required_if:payment_method,dd', 'nullable', 'string', 'max:30'],
            'bank_name' => ['required_if:payment_method,cheque', 'required_if:payment_method,dd', 'nullable', 'string', 'max:100'],
            'cheque_date' => ['required_if:payment_method,cheque', 'nullable', 'date'],
            'dd_date' => ['required_if:payment_method,dd', 'nullable', 'date'],
        ]);

        $customerId = session('customer_id');

        $orderId = DB::transaction(function () use ($data, $items, $customerId) {
            $productIds = $items->map(fn ($item) => $item->product->product_id)->values();
            $stockRows = DB::table('stock')
                ->whereIn('product_id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($items as $item) {
                $stock = $stockRows->get($item->product->product_id);
                $available = (int) ($stock->quantity_available ?? 0);

                if ($available < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => 'Only ' . $available . ' item(s) are available for ' . $item->product->product_name . '. Please update your cart.',
                    ]);
                }
            }

            $addressId = $data['address_id'] ?? null;
            if ($addressId) {
                $ownsAddress = DB::table('customer_address')
                    ->where('address_id', $addressId)
                    ->where('customer_id', $customerId)
                    ->exists();

                if (! $ownsAddress) {
                    throw ValidationException::withMessages([
                        'address_id' => 'Please choose one of your saved addresses or enter a new address.',
                    ]);
                }
            }

            if (! $addressId) {
                $addressId = DB::table('customer_address')->insertGetId([
                    'customer_id' => $customerId,
                    'address_line1' => $data['address_line1'],
                    'address_line2' => $data['address_line2'] ?? null,
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'postal_code' => $data['postal_code'],
                    'is_default' => 0,
                ]);
            }

            $orderStatus = $data['payment_method'] === 'vpp_cod' ? 'placed' : 'payment_pending';
            $orderId = DB::table('orders')->insertGetId([
                'customer_id' => $customerId,
                'shipping_address_id' => $addressId,
                'order_date' => now(),
                'order_status' => $orderStatus,
            ]);

            $itemSequence = substr(str_pad((string) $orderId, 8, '0', STR_PAD_LEFT), -8);
            foreach ($items as $item) {
                DB::table('order_item')->insert([
                    'order_id' => $orderId,
                    'product_id' => $item->product->product_id,
                    'delivery_code' => $data['delivery_code'],
                    'item_sequence' => $itemSequence,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->price,
                    'item_status' => $orderStatus,
                ]);

                DB::table('stock')
                    ->where('product_id', $item->product->product_id)
                    ->where('quantity_available', '>=', $item->quantity)
                    ->decrement('quantity_available', $item->quantity);
            }

            $paymentId = DB::table('payment')->insertGetId([
                'order_id' => $orderId,
                'payment_method' => $data['payment_method'],
                'amount' => $items->sum('line_total'),
                'payment_status' => 'pending',
                'payment_date' => now(),
            ]);

            if ($data['payment_method'] === 'credit_card') {
                DB::table('payment_credit_card')->insert([
                    'payment_id' => $paymentId,
                    'card_last4' => substr(preg_replace('/\D/', '', $data['card_number']), -4),
                    'card_holder_name' => $data['card_holder_name'],
                    'gateway_txn_ref' => 'PENDING-' . $paymentId,
                ]);
            }

            if ($data['payment_method'] === 'cheque') {
                DB::table('payment_cheque')->insert([
                    'payment_id' => $paymentId,
                    'cheque_number' => $data['cheque_number'],
                    'bank_name' => $data['bank_name'],
                    'cheque_date' => $data['cheque_date'],
                ]);
            }

            if ($data['payment_method'] === 'dd') {
                DB::table('payment_dd')->insert([
                    'payment_id' => $paymentId,
                    'dd_number' => $data['dd_number'],
                    'bank_name' => $data['bank_name'],
                    'dd_date' => $data['dd_date'],
                    'clearance_date' => null,
                ]);
            }

            return $orderId;
        });

        session()->forget('cart');
        return redirect()->route('order.confirmation', $orderId);
    }

    public function confirmation(int $order)
    {
        $header = DB::table('orders')->where('order_id', $order)->where('customer_id', session('customer_id'))->first();
        abort_unless($header, 404);
        $items = DB::table('order_item')->join('product', 'order_item.product_id', '=', 'product.product_id')->where('order_id', $order)->select('order_item.*', 'product.product_name')->get();
        $payment = DB::table('payment')->where('order_id', $order)->first();
        $dispatch = DB::table('dispatch')->whereIn('order_item_id', $items->pluck('order_item_id'))->get();

        return view('store.confirmation', compact('header', 'items', 'payment', 'dispatch'));
    }

    public function myOrders()
    {
        $orders = DB::table('orders')->where('customer_id', session('customer_id'))->orderByDesc('order_date')->get();
        return view('store.my-orders', compact('orders'));
    }

    public function myOrderDetail(int $order)
    {
        $header = DB::table('orders')->where('order_id', $order)->where('customer_id', session('customer_id'))->first();
        abort_unless($header, 404);
        $items = DB::table('order_item')->join('product', 'order_item.product_id', '=', 'product.product_id')->where('order_id', $order)->select('order_item.*', 'product.product_name', 'product.has_warranty', 'product.warranty_months')->get();
        $payment = DB::table('payment')->where('order_id', $order)->first();
        $dispatch = DB::table('dispatch')->whereIn('order_item_id', $items->pluck('order_item_id'))->get();
        $returnRequests = DB::table('return_replace_request')->whereIn('order_item_id', $items->pluck('order_item_id'))->get()->keyBy('order_item_id');
        $warrantyCards = DB::table('warranty_card')->whereIn('order_item_id', $items->pluck('order_item_id'))->get()->keyBy('order_item_id');

        return view('store.order-detail', compact('header', 'items', 'payment', 'dispatch', 'returnRequests', 'warrantyCards'));
    }

    public function cancelOrderItem(int $order, int $item)
    {
        $record = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->where('orders.customer_id', session('customer_id'))
            ->where('orders.order_id', $order)
            ->where('order_item.order_item_id', $item)
            ->select('order_item.*')
            ->first();
        abort_unless($record, 404);

        if (in_array($record->item_status, ['dispatched', 'delivered', 'cancelled', 'return_requested', 'returned', 'replace_requested', 'replaced'], true)) {
            return back()->withErrors(['order' => 'This item can no longer be cancelled.']);
        }

        DB::transaction(function () use ($record, $item, $order) {
            DB::table('order_item')->where('order_item_id', $item)->update(['item_status' => 'cancelled']);
            $this->restoreStock($record->product_id, (int) $record->quantity);

            $remainingActive = DB::table('order_item')
                ->where('order_id', $order)
                ->where('item_status', '!=', 'cancelled')
                ->exists();

            if (! $remainingActive) {
                DB::table('orders')->where('order_id', $order)->update(['order_status' => 'cancelled']);
            }
        });

        return back()->with('status', 'Order item cancelled.');
    }

    private function restoreStock(string $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $updated = DB::table('stock')
            ->where('product_id', $productId)
            ->increment('quantity_available', $quantity, ['last_restocked_at' => now()]);

        if (! $updated) {
            DB::table('stock')->insert([
                'product_id' => $productId,
                'quantity_available' => $quantity,
                'last_restocked_at' => now(),
            ]);
        }
    }

    public function requestReturnReplace(Request $request, int $order, int $item)
    {
        $data = $request->validate([
            'request_type' => ['required', Rule::in(['return', 'replace'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $record = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->leftJoin('dispatch', 'order_item.order_item_id', '=', 'dispatch.order_item_id')
            ->where('orders.customer_id', session('customer_id'))
            ->where('orders.order_id', $order)
            ->where('order_item.order_item_id', $item)
            ->select('order_item.*', 'dispatch.actual_delivery_date')
            ->first();
        abort_unless($record, 404);

        if ($record->item_status !== 'delivered' || ! $record->actual_delivery_date) {
            return back()->withErrors(['order' => 'Return or replacement can only be requested after delivery.']);
        }

        if (now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($record->actual_delivery_date)->startOfDay(), false) < -7) {
            return back()->withErrors(['order' => 'Return or replacement is only available within 7 days of delivery.']);
        }

        $alreadyRequested = DB::table('return_replace_request')
            ->where('order_item_id', $item)
            ->whereIn('status', ['requested', 'approved'])
            ->exists();

        if ($alreadyRequested) {
            return back()->withErrors(['order' => 'A return or replacement request already exists for this item.']);
        }

        DB::table('return_replace_request')->insert([
            'order_item_id' => $item,
            'request_type' => $data['request_type'],
            'reason' => $data['reason'] ?? null,
            'request_date' => now(),
            'status' => 'requested',
        ]);

        DB::table('order_item')->where('order_item_id', $item)->update([
            'item_status' => $data['request_type'] === 'return' ? 'return_requested' : 'replace_requested',
        ]);

        return back()->with('status', ucfirst($data['request_type']) . ' request submitted.');
    }

    public function addresses()
    {
        $addresses = DB::table('customer_address')->where('customer_id', session('customer_id'))->get();
        return view('store.addresses', compact('addresses'));
    }

    public function saveAddress(Request $request)
    {
        $data = $request->validate([
            'address_line1' => ['required', 'string', 'max:150'],
            'address_line2' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'postal_code' => ['required', 'string', 'max:15'],
        ]);
        $data['customer_id'] = session('customer_id');
        $data['is_default'] = 0;
        DB::table('customer_address')->insert($data);

        return redirect()->route('customer.account')->with('status', 'Address saved.');
    }

    public function feedback()
    {
        $purchasedItems = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->where('orders.customer_id', session('customer_id'))
            ->select('orders.order_id', 'order_item.product_id', 'order_item.order_number', 'product.product_name')
            ->orderByDesc('orders.order_date')
            ->get();

        return view('store.feedback', compact('purchasedItems'));
    }

    public function submitFeedback(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:product,product_id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'message' => ['required', 'string'],
            'order_id' => ['nullable', 'integer'],
        ]);

        $hasPurchasedProduct = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->where('orders.customer_id', session('customer_id'))
            ->where('order_item.product_id', $data['product_id'])
            ->when(! empty($data['order_id']), fn ($query) => $query->where('orders.order_id', $data['order_id']))
            ->exists();

        if (! $hasPurchasedProduct) {
            return back()
                ->withErrors(['product_id' => 'Please select a product from your purchases.'])
                ->withInput();
        }

        DB::table('feedback')->insert([
            'customer_id' => session('customer_id'),
            'order_id' => $data['order_id'] ?? null,
            'product_id' => $data['product_id'],
            'rating' => $data['rating'],
            'message' => $data['message'],
            'submitted_at' => now(),
        ]);

        return back()->with('status', 'Feedback submitted.');
    }

    private function seedProductDetails(object $product): void
    {
        $existing = DB::table('product_detail')->where('product_id', $product->product_id)->count();
        if ($existing >= 5) {
            return;
        }

        if ($existing > 0) {
            DB::table('product_detail')->where('product_id', $product->product_id)->delete();
        }

        $category = $product->category_name ?? 'gift';
        $subcategory = $product->subcategory_name ?: $category;
        $stockQty = (int) ($product->stock_qty ?? 0);
        $stockText = $stockQty > 0
            ? "{$product->product_name} is currently available with {$stockQty} piece(s) ready for checkout."
            : "{$product->product_name} is currently out of stock, but you can check back for the next Bits&Bobbins restock.";

        $rows = [
            [
                'title' => "What makes {$product->product_name} special?",
                'body' => "{$product->product_name} is selected for the {$category} collection with the playful, practical Bits&Bobbins feel families expect.",
            ],
            [
                'title' => "Who is {$product->product_name} best for?",
                'body' => "This pick works beautifully for shoppers browsing {$subcategory}, everyday surprises, birthdays, small rewards, and sweet gifting moments.",
            ],
            [
                'title' => 'What should I know before ordering?',
                'body' => $product->description ?: "You will receive the product shown with clear pricing, product ID {$product->product_id}, and easy checkout from the product page.",
            ],
            [
                'title' => 'Is it available right now?',
                'body' => $stockText,
            ],
            [
                'title' => 'Does this product include warranty support?',
                'body' => ! empty($product->has_warranty)
                    ? 'Yes. Warranty support is available where applicable, and our team can help with product questions after ordering.'
                    : 'This item does not list a warranty, but our team can still help with order and product questions.',
            ],
        ];

        foreach ($rows as $index => $row) {
            DB::table('product_detail')->insert([
                'product_id' => $product->product_id,
                'title' => $row['title'],
                'body' => $row['body'],
                'display_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedMissingProductDetails(): void
    {
        $products = DB::table('product')
            ->join('category', 'product.category_code', '=', 'category.category_code')
            ->leftJoin('subcategory', 'product.subcategory_id', '=', 'subcategory.subcategory_id')
            ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
            ->where('product.is_active', 1)
            ->select('product.*', 'category.category_name', 'subcategory.subcategory_name', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'))
            ->get();

        foreach ($products as $product) {
            $this->seedProductDetails($product);
        }
    }
}
