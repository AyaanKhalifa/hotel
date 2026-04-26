<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/lang.php';
require_once __DIR__.'/includes/db.php';
requireLogin();
$pageTitle = t('wishlist').' — Royale Vista';
$uid = $_SESSION['user_id'];
$items = $pdo->prepare("SELECT rt.*,ri.image_url,w.created_at as wishlisted_at FROM wishlists w JOIN room_types rt ON w.room_type_id=rt.id LEFT JOIN room_images ri ON ri.room_type_id=rt.id AND ri.is_primary=1 WHERE w.user_id=? ORDER BY w.created_at DESC");
$items->execute([$uid]);
$wishlist = $items->fetchAll();
require __DIR__.'/header.php';
?>
<style>
/* Wishlist specific */
.wish-card { background:var(--card); border:1px solid var(--bdr2); border-radius:20px; overflow:hidden; transition:all .3s ease; display:flex; flex-direction:column; box-shadow:0 10px 30px rgba(0,0,0,0.1); }
.wish-card:hover { transform:translateY(-8px); border-color:var(--gold); box-shadow:0 20px 60px rgba(0,0,0,.2); }
.wish-img-wrap { position:relative; height:180px; overflow:hidden; background:var(--card2); display:flex; align-items:center; justify-content:center; }
.wish-img-wrap img { width:100%; height:100%; object-fit:cover; transition:transform .5s ease; }
.wish-card:hover .wish-img-wrap img { transform:scale(1.08); }
.wish-remove { position:absolute; top:12px; right:12px; width:32px; height:32px; border-radius:50%; background:rgba(239,68,68,.85); border:none; color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:14px; backdrop-filter:blur(4px); transition:all .2s; z-index:2; }
.wish-remove:hover { background:#ef4444; transform:scale(1.1); }
.wish-body { padding:18px 20px; flex:1; display:flex; flex-direction:column; }
.wish-name { font-family:var(--serif); font-size:20px; color:var(--text); margin-bottom:6px; }
.wish-price { font-family:var(--serif); font-size:22px; color:var(--gold); margin-bottom:16px; }
.wish-price span { font-size:12px; font-family:var(--sans); color:var(--muted); }
.wish-empty { text-align:center; padding:80px 20px; color:var(--muted); }
.wish-empty i { font-size:48px; margin-bottom:20px; opacity:.2; display:block; }
</style>

<div class="wishlist-page" style="padding-top:120px; padding-bottom:80px;">
  <div class="container">
    <div class="section-label"><?= t('account') ?></div>
    <h1 style="font-family:var(--serif);font-size:42px;margin-bottom:30px"><?= t('wishlist') ?></h1>

    <?php if(empty($wishlist)): ?>
    <div class="wish-empty" style="text-align:center; padding:100px 0;">
      <i class="fas fa-heart-broken" style="font-size:64px; color:var(--muted); margin-bottom:20px; opacity:0.3;"></i>
      <h3 style="font-family:var(--serif);font-size:26px;color:var(--text);margin-bottom:12px">Your sanctuary awaits...</h3>
      <p style="color:var(--muted); font-size:16px;">Discover your favorite rooms and save them here for your next stay.</p>
      <a href="<?= $B ?>/rooms.php" class="btn btn-gold" style="margin-top:30px">Explore Our Rooms</a>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:30px">
      <?php foreach($wishlist as $rm): ?>
      <div class="wish-card" id="wish-card-<?= $rm['id'] ?>" style="background:var(--card); border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.1); border:1px solid var(--bdr2);">
        <div class="wish-img-wrap" style="height:200px; position:relative; overflow:hidden;">
          <?php if($rm['image_url']): ?>
            <img src="<?= htmlspecialchars($rm['image_url']) ?>" alt="<?= htmlspecialchars($rm['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <div style="width:100%; height:100%; background:var(--card2); display:flex; align-items:center; justify-content:center; font-size:64px; opacity:0.1;">🏨</div>
          <?php endif; ?>
          <button class="wish-remove" onclick="removeWishlist(<?= $rm['id'] ?>,this)" title="Remove from wishlist" style="position:absolute; top:15px; right:15px; width:36px; height:36px; border-radius:50%; background:rgba(239,68,68,0.9); border:none; color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 5px 15px rgba(0,0,0,0.2); transition:all 0.3s;">
            <i class="fas fa-heart"></i>
          </button>
        </div>
        <div class="wish-body" style="padding:25px;">
          <div class="wish-name" style="font-family:var(--serif); font-size:22px; margin-bottom:8px;"><?= htmlspecialchars($rm['name']) ?></div>
          <div class="wish-price" style="font-family:var(--serif); font-size:24px; color:var(--gold); margin-bottom:20px;"><?= formatPrice($rm['price_usd']) ?> <span style="font-size:14px; color:var(--muted); font-family:var(--sans);">/night</span></div>
          <a href="<?= $B ?>/rooms.php?type=<?= $rm['id'] ?>" class="btn btn-gold btn-block" style="text-align:center; display:block;"><?= t('book_now') ?></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
async function removeWishlist(id,btn){
  const fd=new FormData();fd.append('room_type_id',id);
  try {
    const res=await fetch('<?= $B ?>/api/wishlist.php',{method:'POST',body:fd});
    const data=await res.json();
    if(!data.wishlisted){
      const card = document.getElementById('wish-card-'+id);
      if(card) {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => {
          card.remove();
          if(document.querySelectorAll('.wish-card').length === 0) location.reload();
        }, 300);
      }
      toast('Removed from wishlist','info');
    }
  } catch(e) { toast('Error updating wishlist','error'); }
}
</script>
<?php require __DIR__.'/footer.php'; ?>
