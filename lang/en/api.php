<?php

return [
    'validation_failed' => 'The submitted data is invalid.',
    'unauthenticated' => 'You must sign in first.',
    'forbidden' => 'This action is not allowed.',
    'not_found' => 'The requested item was not found.',
    'too_many_requests' => 'Too many attempts, please try again shortly.',
    'request_failed' => 'The request could not be completed.',
    'server_error' => 'Something went wrong, please try again.',
    'account_frozen' => 'This account is suspended for review. Please contact support.',
    'saved' => 'Saved.',

    'upload_failed' => 'The file could not be uploaded. Please try again.',

    'account' => [
        'deleted' => 'Your account and personal data were deleted.',
        'phone_taken' => 'This number is already in use.',
        'phone_changed' => 'Your mobile number was changed.',
    ],

    'address' => [
        'not_found' => 'The selected address was not found.',
        'limit_reached' => 'You have reached the maximum number of saved addresses.',
    ],

    'chat' => [
        'unavailable' => 'The assistant is unavailable right now. Try later or contact support.',
        'limit_reached' => 'You have reached today\'s assistant message limit. Try again tomorrow.',
    ],

    'auth' => [
        'challenge_created' => 'Send the code to our WhatsApp number to receive your sign-in code.',
        'signed_in' => 'Signed in successfully.',
        'signed_out' => 'Signed out.',
        'default_name' => 'MAQAM Customer',
        'invalid_credentials' => 'The mobile number or password is incorrect.',
        'profile_completed' => 'Your details have been saved.',
        'password_updated' => 'Your password has been updated.',
        'wrong_current_password' => 'The current password is incorrect.',
        'verify_to_set_password' => 'Sign in with a WhatsApp code first to set a new password.',
    ],

    'qr' => [
        'locked' => 'Scanning is paused after repeated invalid codes. Try again in :minutes minutes.',
        'preview_ok' => 'Valid code. You will earn :points points.',
        'scan_ok' => 'Code scanned: :points points added to your wallet.',
        'sync_ok' => ':count codes synced.',
        'merchant_not_found' => 'Merchant code not found or not approved.',
        'self_scan' => 'You cannot use your own merchant code.',
    ],

    'wheel' => [
        'won' => 'Congratulations! You won a prize.',
        'lost' => 'Better luck next time.',
        'no_prize' => 'Better luck',
    ],

    'cart' => [
        'invalid_option' => 'The selected option does not belong to this product.',
        'added' => 'Added to cart.',
        'updated' => 'Cart updated.',
        'coupon_applied' => 'Coupon applied.',
    ],

    'checkout' => [
        'out_of_stock' => 'The requested quantity of ":product" is not available (in stock: :available).',
        'gateway_error' => 'Online payment could not be started. Nothing was charged, please try again.',
        'complete_payment' => 'Complete the payment on the payment gateway.',
        'placed' => 'Your order has been placed.',
        'gift_item' => 'Lucky wheel gift',
    ],

    'rank' => [
        'upgraded_title' => 'Congratulations! New rank',
        'upgraded_body' => 'You have been promoted to :rank. Enjoy your new benefits.',
    ],

    'payment_return' => [
        'paid_title' => 'Payment successful',
        'paid_body' => 'We got your order and are preparing it.',
        'pending_title' => 'Confirming your payment',
        'pending_body' => 'We will update your order as soon as the bank confirms it.',
        'failed_title' => 'The payment did not go through',
        'failed_body' => 'Nothing was charged. You can try again from the app.',
        'back_to_app' => 'Close this page and continue in the app.',
    ],

    'order' => [
        'status_title' => 'Order :number update',
        'placed' => 'We got your order and are preparing it.',
        'paid' => 'Payment received. Your order is being prepared.',
        'payment_failed' => 'The payment did not go through and nothing was charged.',
        'wa' => [
            'currency' => 'EGP',
            'status' => 'Status now: :status',
            'status_new' => 'Received',
            'status_processing' => 'Being prepared',
            'status_shipped' => 'Shipped',
            'status_delivered' => 'Delivered',
            'status_cancelled' => 'Cancelled',
            'status_refunded' => 'Refunded',
            'items' => 'Items:',
            'item' => 'Item',
            'free' => 'Free',
            'subtotal' => 'Subtotal: :amount',
            'discount' => 'Discount: :amount',
            'shipping' => 'Shipping: :amount',
            'total' => 'Total: :amount',
            'payment' => 'Payment: :method',
            'method_cod' => 'Cash on delivery',
            'method_kashier' => 'Card or mobile wallet',
            'method_wallet' => 'Loyalty points',
            'address' => 'Address: :address',
        ],
        'status_new' => 'Your order was received.',
        'status_processing' => 'Your order is being prepared.',
        'status_shipped' => 'Your order has shipped and is on its way.',
        'status_delivered' => 'Your order was delivered. Thank you for shopping with us.',
        'status_cancelled' => 'Your order was cancelled.',
        'status_refunded' => 'Your order was refunded.',
        'cancelled' => 'The order was cancelled.',
        'not_cancellable' => 'This order cannot be cancelled from the app. Please contact support.',
    ],

    'merchant' => [
        'already_applied' => 'You already have a merchant application.',
        'applied' => 'Your application was received and is under review.',
        'approved_title' => 'You are now an approved merchant',
        'approved_body' => 'Congratulations! Your merchant code :code is now active.',
        'rejected_title' => 'Merchant application not accepted',
        'rejected_body' => 'Sorry, we could not accept your application. Reason: :reason',
    ],
];
