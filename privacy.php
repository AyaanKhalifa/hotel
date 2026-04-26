<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Privacy Policy — Royale Vista';
require __DIR__ . '/header.php';
?>
<div style="padding-top:100px;min-height:80vh;background:linear-gradient(180deg, var(--bg2) 0%, var(--bg) 100%);">
    <div class="container section">
        <div class="section-label">Legal</div>
        <h1 style="font-family:var(--serif);font-size:clamp(32px,5vw,56px);font-weight:400;margin-bottom:40px">Privacy Policy</h1>
        
        <div style="max-width:800px;line-height:1.8;color:var(--text2);font-size:15px">
            <p style="margin-bottom:24px">At Royale Vista, we take your privacy with the utmost seriousness. This policy outlines how we collect, use, and protect your personal data in accordance with global luxury hospitality standards and GDPR regulations.</p>
            
            <h2 style="font-family:var(--serif);color:var(--gold);margin:32px 0 16px;font-weight:400">1. Data Collection</h2>
            <p>We collect information that you provide directly to us when booking a room, creating an account, or communicating with our digital concierge (Habibi AI). This includes your name, email, phone number, and stay preferences.</p>
            
            <h2 style="font-family:var(--serif);color:var(--gold);margin:32px 0 16px;font-weight:400">2. Use of Information</h2>
            <p>Your information is used strictly to provide personalized services, process reservations, and improve your experience at Royale Vista. We never sell your data to third parties.</p>
            
            <h2 style="font-family:var(--serif);color:var(--gold);margin:32px 0 16px;font-weight:400">3. Cookies & Tracking</h2>
            <p>We use cookies to enhance your browsing experience and remember your preferences. You can manage your cookie settings at any time via our <a href="<?= BASE ?>/cookies.php" style="color:var(--gold);text-decoration:none">Cookie Preference Center</a>.</p>
            
            <h2 style="font-family:var(--serif);color:var(--gold);margin:32px 0 16px;font-weight:400">4. Data Security</h2>
            <p>Your data is encrypted using industry-standard SSL technology and stored in high-security environments. Access is restricted to authorized personnel only.</p>
            
            <div style="margin-top:60px;padding:30px;background:var(--card2);border:1px solid var(--bdr2);border-radius:var(--radius-lg);text-align:center">
                <p style="margin-bottom:12px">Last updated: March 30, 2026</p>
                <p>If you have any questions regarding your privacy, contact our Data Protection Officer at <strong>privacy@royalevista.com</strong></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>