# Bits & Bobbins Project Blueprint

## One Laravel application
Use one Laravel project for the storefront, admin pages, and employee pages. Keep the code simple and easy to follow.

## Simple database design
The project uses a small online shopping cart database:

- `users` - admin, employee, and customer accounts
- `categories` - product groups with a 2 digit category code
- `products` - product details, price, stock, optional image, warranty days, and 7 digit product code
- `cart_items` - products saved in a customer's cart before checkout
- `orders` - one customer order with total, shipping, dispatch, delivery, and return status
- `order_items` - products inside an order
- `payments` - credit card, cheque, VPP, or DD payment details
- `feedback` - customer messages and ratings
- `faqs` - questions and answers shown on the website

Laravel also has its normal helper tables:

- `password_resets`
- `failed_jobs`
- `personal_access_tokens`

## Important rule
Do not drop the database or drop tables just to create this project.

Use migrations:

```bash
php artisan migrate
```

Or import `database/bits_and_bobbins.sql`, which uses `CREATE TABLE IF NOT EXISTS` and does not delete old data.

## Frontend pages
- Home
- Shop / Products
- Product detail
- Cart
- Checkout
- Order success
- My Account
- My Orders
- FAQ
- Contact / Feedback

## Basic customer flow
1. Guest can browse products.
2. Customer registers or logs in.
3. Customer adds products to cart.
4. Customer checks out.
5. App creates one order and one or more order items.
6. Payment record is saved for the order.
7. Customer can view order status.

## Simple assignment rules
- Product code is 7 digits: 2 digit category code + 5 digit product number.
- Guests can browse products.
- Only registered customers can place orders.
- Admin manages products, employees, stock, orders, payments, feedback, and FAQs.
- Employees can view/update orders and change only their own password.
- Credit card, cheque, and DD orders should be dispatched only after payment is cleared.
- VPP orders can be paid at delivery.
- Customer can cancel only before dispatch.
- Customer can request return/replacement within 7 days after delivery.

## Admin pages
- Dashboard
- Categories
- Products
- Orders
- Payments
- Customers
- Employees
- Feedback
- FAQs

## Employee pages
- Dashboard
- Orders
- Profile
- Change Password

## Recommended build order
1. Database migrations and models
2. Authentication and roles
3. Product/category admin pages
4. Storefront product pages
5. Cart
6. Checkout and orders
7. Payments
8. Feedback and FAQ
9. Simple dashboards
