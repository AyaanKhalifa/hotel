<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Terms of Service — Royale Vista';
require __DIR__ . '/header.php';
?>
<div style="padding-top:100px;min-height:80vh;background:linear-gradient(180deg, var(--bg2) 0%, var(--bg) 100%);">
    <div class="container section">
        <div class="section-label">Legal</div>
        <h1 style="font-family:var(--serif);font-size:clamp(32px,5vw,56px);font-weight:400;margin-bottom:40px">Terms of Service</h1>
        
        <div style="max-width:800px;line-height:1.8;color:var(--text2);font-size:15px">
            <p style="margin-bottom:24px">Welcome to Royale Vista. By accessing our website and services, you agree to comply with the following terms and conditions of use.</p>
            
            <h2 style="font-family:var(--serif);color:var(--gold);margin:32px 0 16px;font-weight:400">1. Reservations & Payments</h2>
            <p>All reservations must be guaranteed with a valid credit card at the time of booking. For non-refundable rates, full payment is required at the time of reservation.</p>
            
            <h2 style="font-family:var(--serif);color:var(--gold);margin:32px 0 16px;font-weight:400">2. Cancellation Policy</h2>
            <p>Standard cancellations must be made 48 hours prior to the arrival date. For specialty suites and peak season bookings, a 14-day notice may be required. Failure to cancel within the specified time frame will result in a one-night stay charge.</p>
            
            <h2 style="font-family:var(--serif);color:var(--gold);margin:32px 0 16px;font-weight:400">3. Use of Services</h2>
            <p>The Royale Vista website and Habibi AI concierge are provided for lawful use only. Any attempt to disrupt our digital infrastructure or misuse guest information is strictly prohibited.</p>
            
            <h2 style="font-family:var(--serif);color:var(--gold);margin:32px 0 16px;font-weight:400">4. Intellectual Property</h2>
            <p>All content, including images, branding, and text, is the property of Royale Vista. unauthorized use or reproduction is prohibited.</p>
            
            <div style="margin-top:60px;padding:30px;background:var(--card2);border:1px solid var(--bdr2);border-radius:var(--radius-lg);text-align:center">
                <p style="margin-bottom:12px">Last updated: March 30, 2026</p>
                <p>If you have any questions regarding these terms, contact us at <strong>legal@royalevista.com</strong></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>