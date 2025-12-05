<?php

/**
 * Payment Flow Troubleshooting Guide
 * 
 * SCENARIO: User registers, makes a payment, but premium status doesn't update
 */

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Flow Guide</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
            color: #333;
        }

        h1,
        h2,
        h3 {
            color: #1a73e8;
        }

        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .step {
            background: #e8f0fe;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #1a73e8;
            border-radius: 4px;
        }

        .status-ok {
            color: #0d652d;
            background: #dcf8e7;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .status-error {
            color: #9c27b0;
            background: #f3e5f5;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .code-block {
            background: #f8f8f8;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }

        .todo {
            list-style: none;
            padding: 0;
        }

        .todo li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .todo li:before {
            content: "✓ ";
            color: #0d652d;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h1>💳 CodePlay Payment Flow - Complete Guide</h1>

    <div class="section">
        <h2>🔄 Normal Payment Flow (Expected Behavior)</h2>

        <div class="step">
            <strong>1. User clicks "Upgrade" button on dashboard</strong><br>
            Dashboard shows premium status box with Upgrade/Manage button
        </div>

        <div class="step">
            <strong>2. User selects package (1/3/12 months)</strong><br>
            SweetAlert shows pricing options with calculated discounts
        </div>

        <div class="step">
            <strong>3. System creates Midtrans payment token</strong><br>
            POST to <code>midtrans/create_payment.php</code> with duration_months<br>
            Response: <code>{success: true, token: "...", order_id: "..."}</code>
        </div>

        <div class="step">
            <strong>4. Midtrans Snap opens payment gateway</strong><br>
            User selects payment method and completes payment
        </div>

        <div class="step">
            <strong>5. Payment succeeds → Midtrans sends webhook</strong><br>
            Webhook → <code>midtrans/notification.php</code><br>
            Creates: subscription record + updates user.is_premium = 1
        </div>

        <div class="step">
            <strong>6. Dashboard syncs premium status</strong><br>
            Calls <code>includes/sync_premium_status.php</code><br>
            Page reloads → shows "Premium" status ✅
        </div>
    </div>

    <div class="section">
        <h2>⚠️ Common Issues & Solutions</h2>

        <h3>Issue #1: Status stuck on "Free" after successful payment</h3>
        <p><strong>Cause:</strong> Midtrans webhook didn't fire (network/configuration issue)</p>
        <div class="step">
            <strong>Solution:</strong>
            <ul class="todo">
                <li>Check if Midtrans webhook URL is configured in Midtrans Dashboard</li>
                <li>URL should be: <code>http://YOUR_DOMAIN/HIWEB/midtrans/notification.php</code></li>
                <li>For local testing, use ngrok: <code>ngrok http 8080</code></li>
                <li>Or click the "Refresh" button on dashboard to manually check payment status</li>
                <li>If still pending, use manual settlement: <code>php test_manual_settlement.php</code></li>
            </ul>
        </div>

        <h3>Issue #2: Payment stuck on "PENDING" in Midtrans</h3>
        <p><strong>Cause:</strong> User didn't complete payment in Snap UI or payment timed out</p>
        <div class="step">
            <strong>Solution:</strong>
            <ul class="todo">
                <li>Make sure to complete entire payment flow in Midtrans Snap</li>
                <li>For sandbox credit card testing, use: 4111 1111 1111 1111</li>
                <li>Test with real payment if webhook still not firing</li>
                <li>Check logs in <code>logs/midtrans.log</code> for errors</li>
            </ul>
        </div>

        <h3>Issue #3: Subscription not created</h3>
        <p><strong>Cause:</strong> Webhook fired but couldn't create subscription (DB error)</p>
        <div class="step">
            <strong>Solution:</strong>
            <ul class="todo">
                <li>Check if plan exists: <code>SELECT * FROM subscription_plans WHERE id_plan='PLAN_1M';</code></li>
                <li>Check user exists: <code>SELECT * FROM users WHERE id_user='...';</code></li>
                <li>Look for PHP errors in server logs</li>
                <li>Test manually: <code>php test_subscription_flow.php</code></li>
            </ul>
        </div>
    </div>

    <div class="section">
        <h2>🔧 Manual Testing & Debugging</h2>

        <h3>1. Check User Premium Status</h3>
        <div class="code-block">
            php check_azizaan_status.php
        </div>
        <p>Shows: user premium flag, subscriptions, transactions</p>

        <h3>2. Manually Settle Payment</h3>
        <div class="code-block">
            php test_manual_settlement.php
        </div>
        <p>Use after getting order_id from check_azizaan_status.php</p>

        <h3>3. Verify Payment Flow</h3>
        <div class="code-block">
            php test_subscription_flow.php
        </div>
        <p>Simulates complete payment flow locally</p>

        <h3>4. Check Pending Transactions</h3>
        <div class="code-block">
            php get_pending_order.php
        </div>
        <p>Get latest PENDING order ID for testing</p>
    </div>

    <div class="section">
        <h2>📊 Database Status Check</h2>

        <h3>1. User Has Active Subscription?</h3>
        <div class="code-block">
            SELECT * FROM user_subscriptions
            WHERE id_user='USER_ID' AND payment_status='PAID' AND end_date > NOW();
        </div>

        <h3>2. User is_premium Flag</h3>
        <div class="code-block">
            SELECT id_user, is_premium FROM users WHERE id_user='USER_ID';
        </div>
        <p><span class="status-ok">Should be 1 if has active subscription</span></p>

        <h3>3. Transaction Status</h3>
        <div class="code-block">
            SELECT order_id, status FROM transactions WHERE order_id='ORD...' LIMIT 1;
        </div>
        <p><span class="status-ok">Should be SETTLEMENT after payment</span></p>
    </div>

    <div class="section">
        <h2>🆕 For New Users</h2>

        <h3>Complete Flow for Testing:</h3>
        <ol>
            <li><strong>Register</strong> new account → verify email if needed</li>
            <li><strong>Login</strong> to dashboard</li>
            <li><strong>Click "Upgrade"</strong> button</li>
            <li><strong>Select package</strong> (1 month recommended)</li>
            <li><strong>Complete payment</strong> in Midtrans Snap
                <ul>
                    <li>Choose payment method (Credit Card or E-wallet)</li>
                    <li>Fill payment details (sandbox test card: 4111 1111 1111 1111)</li>
                    <li>Click "Confirm" or "Complete"</li>
                </ul>
            </li>
            <li><strong>Wait for redirect</strong> to dashboard</li>
            <li><strong>Refresh page</strong> or click "Refresh" button if needed</li>
            <li><strong>Verify status changed to "Premium"</strong> ✅</li>
        </ol>

        <h3>If status still shows "Free" after payment:</h3>
        <ol>
            <li>Click the <strong>"Refresh"</strong> button on premium box</li>
            <li>System will check Midtrans API and settle if payment succeeded</li>
            <li>Page reloads with updated status</li>
            <li>If still not working, contact support with Order ID</li>
        </ol>
    </div>

    <div class="section">
        <h2>📋 Checklist for Production</h2>
        <ul class="todo">
            <li>Configure Midtrans webhook URL in Midtrans Dashboard</li>
            <li>Test webhook with Midtrans webhook simulator</li>
            <li>Enable HTTPS for all payment pages</li>
            <li>Configure proper error logging</li>
            <li>Test with real payment methods before launch</li>
            <li>Set up monitoring for failed webhooks</li>
            <li>Document payment issue escalation process</li>
        </ul>
    </div>

    <div class="section">
        <h2>📞 Quick Support</h2>
        <p><strong>Q: Payment completed but status shows "Free"?</strong><br>
            A: Click "Refresh" button on dashboard to sync status.</p>

        <p><strong>Q: Still showing "Free" after refresh?</strong><br>
            A: Payment may not have gone through. Try payment again.</p>

        <p><strong>Q: How long for webhook to fire?</strong><br>
            A: Usually instant, but can take up to 5 minutes in sandbox.</p>

        <p><strong>Q: Can I test without real payment?</strong><br>
            A: Yes, use manual settlement: <code>php test_manual_settlement.php</code></p>
    </div>

</body>

</html>