<?php
error_reporting(E_ALL); ini_set('display_errors', '1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// If POST with purchase details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = (float)($_POST['amount'] ?? 0);
    $forName = clean($_POST['for_name'] ?? '');
    $forEmail = clean($_POST['for_email'] ?? '');
    $msg = clean($_POST['message'] ?? '');
    
    // Save to session temporarily for checkout
    $_SESSION['gc_checkout'] = [
        'amount'   => $amount,
        'forName'  => $forName,
        'forEmail' => $forEmail,
        'message'  => $msg
    ];
    // POST-redirect-GET pattern: Redirect to exactly here, without POST vars, to show checkout
    header("Location: checkout-giftcard.php");
    exit;
}

if (empty($_SESSION['gc_checkout'])) {
    header("Location: gift-cards.php");
    exit;
}

$gc = $_SESSION['gc_checkout'];
$amount = $gc['amount'];

// Handle Final Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $code = strtoupper(implode('-', str_split(substr(bin2hex(random_bytes(8)), 0, 16), 4)));
    $expires = date('Y-m-d', strtotime('+2 years'));
    $purchaser = $_SESSION['user_id'] ?? null;
    
    $stmt = $pdo->prepare("INSERT INTO gift_cards (code, value_usd, balance_usd, purchased_by, for_name, for_email, message, expires_at) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$code, $amount, $amount, $purchaser, $gc['forName'], $gc['forEmail'], $gc['message'], $expires]);
    
    unset($_SESSION['gc_checkout']);
    // Put code in session for success display in gift-cards.php
    $_SESSION['gc_success'] = ['code'=>$code, 'amount'=>$amount, 'for'=>$gc['forName'], 'expires'=>$expires];
    header("Location: gift-cards.php?success=1");
    exit;
}

$pageTitle = 'Secure Checkout — Gift Card';
require __DIR__ . '/header.php';
?>
<style>
.co-bg { background: linear-gradient(135deg, var(--bg2), var(--bg)); min-height: 100vh; padding: 120px 20px 60px; }
.co-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; max-width: 1100px; margin: 0 auto; align-items: flex-start; }
@media(max-width: 850px) { .co-grid { grid-template-columns: 1fr; } }
.co-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: 16px; padding: 32px; box-shadow: var(--shadow2); }
.co-ttl { font-family: var(--serif); font-size: 26px; color: var(--gold); margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--bdr2); }
.sum-row { display: flex; justify-content: space-between; font-size: 15px; color: var(--text2); margin-bottom: 16px; }
.sum-tot { display: flex; justify-content: space-between; font-size: 22px; font-family: var(--serif); font-weight: 600; color: #fff; padding-top: 20px; border-top: 1px solid var(--bdr2); margin-top: 20px; }
.pm-tab { display: flex; gap: 10px; margin-bottom: 24px; background: var(--card2); padding: 6px; border-radius: 10px; }
.pm-btn { flex: 1; text-align: center; padding: 12px; cursor: pointer; border-radius: 8px; font-weight: 600; transition: all .2s; color: var(--text2); }
.pm-btn.act { background: var(--gold); color: #000; box-shadow: var(--gold-glow); }
.co-inp { background: var(--input); border: 1px solid var(--bdr2); padding: 14px 16px; width: 100%; border-radius: 8px; color: #fff; font-family: var(--sans); font-size: 15px; outline: none; margin-bottom: 16px; transition: border-color .2s; }
.co-inp:focus { border-color: var(--gold); }
.co-lbl { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold); margin-bottom: 6px; font-weight: 600; }
.pay-wrap { display: none; animation: fadeIn .3s ease; }
.pay-wrap.act { display: block; }
@keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
.upi-qr-box { background: #fff; padding: 20px; border-radius: 12px; display: inline-block; margin: 0 auto 20px; text-align: center; }
</style>

<div class="co-bg">
  <div class="co-grid">
    <!-- Order Summary -->
    <div class="co-card" style="background:linear-gradient(135deg, var(--charcoal), var(--charcoal2)); color:#fff; border:none; position:relative; overflow:hidden">
      <div style="position:absolute;right:20px;top:20px;font-size:80px;opacity:.06">✦</div>
      <div style="font-size:42px;margin-bottom:10px">🎁</div>
      <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:var(--gold)">Order Summary</div>
      <div style="font-family:var(--serif);font-size:32px;font-weight:600;margin:6px 0 24px">Gift Card</div>
      
      <div class="sum-row" style="color:var(--text2)"><span>Recipient Name</span><span><?= htmlspecialchars($gc['forName'] ?: 'Anonymous') ?></span></div>
      <div class="sum-row" style="color:var(--text2)"><span>Recipient Email</span><span><?= htmlspecialchars($gc['forEmail']) ?></span></div>
      
      <div class="sum-tot" style="border-color:rgba(255,255,255,.1)">
        <span>Total Due</span>
        <span style="color:var(--gold)"><?= formatPrice($amount) ?></span>
      </div>
      
      <div style="margin-top:24px;background:rgba(255,255,255,.05);padding:14px;border-radius:10px;font-size:13px;font-weight:600;display:flex;gap:10px;align-items:center">
        <i class="fas fa-lock" style="font-size:18px;color:var(--gold)"></i> Your payment is 256-bit encrypted and secure.
      </div>
    </div>
    
    <!-- Payment Form -->
    <div class="co-card">
      <div class="co-ttl">Select Payment Method</div>
      <div class="pm-tab">
        <div class="pm-btn act" onclick="switchPay('card')"><i class="fas fa-credit-card"></i> Credit Card</div>
        <div class="pm-btn" onclick="switchPay('upi')"><i class="fas fa-qrcode"></i> UPI App</div>
      </div>
      
      <form method="POST" id="payForm">
        <input type="hidden" name="payment_method" id="payMethod" value="card">
        
        <!-- CARD PAYMENT -->
        <div class="pay-wrap act" id="pw-card">
          <label class="co-lbl">Cardholder Name</label>
          <input type="text" class="co-inp" placeholder="JOHN DOE" required>
          
          <label class="co-lbl">Card Number</label>
          <input type="text" class="co-inp" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" oninput="let v=this.value.replace(/\D/g,'');this.value=v.replace(/(.{4})/g,'$1 ').trim()" required>
          
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div>
              <label class="co-lbl">Expiry Date</label>
              <input type="text" class="co-inp" placeholder="MM/YY" maxlength="5" oninput="let v=this.value.replace(/\D/g,'');if(v.length>2)v=v.slice(0,2)+'/'+v.slice(2,4);this.value=v" required>
            </div>
            <div>
              <label class="co-lbl">CVV/CVC</label>
              <input type="password" class="co-inp" placeholder="123" maxlength="4" required>
            </div>
          </div>
        </div>
        
        <!-- UPI PAYMENT -->
        <div class="pay-wrap" id="pw-upi" style="text-align:center">
          <div class="upi-qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=upi://pay?pa=royalevista@okaxis&pn=Royale%20Vista&am=<?= $amount ?>&cu=USD" alt="UPI QR Code">
            <div style="color:#000;font-family:var(--sans);font-size:13px;margin-top:8px;font-weight:600">Scan with any UPI App</div>
          </div>
          <div style="color:var(--text2);margin-bottom:16px">OR</div>
          <label class="co-lbl" style="text-align:left">Enter your UPI ID (VPA)</label>
          <input type="text" class="co-inp" placeholder="username@bank" required id="upi-inp" disabled>
        </div>
        
        <button type="submit" class="btn btn-gold btn-block" style="font-size:16px;padding:16px;margin-top:24px;border-radius:10px" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Processing...'">
          <i class="fas fa-lock"></i> Pay <?= formatPrice($amount) ?> Securely
        </button>
      </form>
      
    </div>
  </div>
</div>

<script>
function switchPay(method) {
  document.querySelectorAll('.pm-btn').forEach(b => b.classList.remove('act'));
  document.querySelectorAll('.pay-wrap').forEach(w => w.classList.remove('act'));
  
  if (method==='card') {
    document.querySelectorAll('.pm-btn')[0].classList.add('act');
    document.getElementById('pw-card').classList.add('act');
    document.getElementById('payMethod').value = 'card';
    
    // Toggle required fields
    document.getElementById('pw-card').querySelectorAll('input').forEach(i => i.disabled=false);
    document.getElementById('upi-inp').disabled = true;
  } else {
    document.querySelectorAll('.pm-btn')[1].classList.add('act');
    document.getElementById('pw-upi').classList.add('act');
    document.getElementById('payMethod').value = 'upi';
    
    // Toggle required fields
    document.getElementById('pw-card').querySelectorAll('input').forEach(i => i.disabled=true);
    document.getElementById('upi-inp').disabled = false;
  }
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
