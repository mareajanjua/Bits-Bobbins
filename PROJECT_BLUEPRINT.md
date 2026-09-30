# Bits&Bobbins Project Blueprint

## Application scope

One Laravel application handles:

- visitor storefront
- customer registration/login
- customer cart, checkout, orders, returns and replacements
- admin/dealer management
- employee dispatch and delivery workflow

## Canonical database

The canonical schema is:

```text
database/bits_and_bobbins.sql
```

The MySQL database name used for setup is:

```text
bitsandbobbins
```

The SQL file does not create the database. It only creates tables inside the selected database.

## Core tables

- `admin`
- `employee`
- `customer`
- `customer_address`
- `category`
- `subcategory`
- `product`
- `product_detail`
- `stock`
- `delivery_type`
- `orders`
- `order_item`
- `payment`
- `payment_credit_card`
- `payment_cheque`
- `payment_dd`
- `dispatch`
- `warranty_card`
- `return_replace_request`
- `feedback`
- `faq`

Laravel default API/Sanctum tables and mismatched plural-table migrations are not part of this submission.

## Implemented business rules

- Only registered customers can checkout.
- Cart and checkout cannot exceed available stock.
- Checkout writes orders, order items, payments and stock changes inside a database transaction.
- Delivery types are seeded in the SQL file as one-digit codes:
  - `1` Courier
  - `2` VPP
  - `3` Registered Post
- The 16-digit order number is generated from delivery code + product id + item sequence.
- Card, cheque and DD payments must be cleared before dispatch.
- VPP / Cash on Delivery orders can be dispatched before payment clearance.
- VPP payment is marked cleared when the last item in the order is delivered.
- Cancelled orders cannot be manually marked payment-cleared later.
- Cancelling an item restores stock. If every item is cancelled, the order becomes cancelled.
- Warranty cards are issued after delivery for warranty products.
- Return and replacement requests are allowed only for delivered items within 7 days of delivery.
- Approved returns record refund amount and restore stock.
- Approved replacements mark the original item as replaced.
- Rejected return/replacement requests restore the item back to delivered.

## Manual/out-of-system handling

- Refund transfer/payment handling for cancelled paid orders is handled outside the system.
- Physical replacement shipment is handled manually by the shop after replacement approval.

