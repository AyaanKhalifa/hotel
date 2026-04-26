<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Guest Reviews — Royale Vista';
$roomTypeId = (int)($_GET['room'] ?? 0);
$roomTypes  = $pdo->query("SELECT id,name,price_usd,avg_rating,review_count FROM room_types ORDER BY sort_order")->fetchAll();
$selectedRoom = null;
if ($roomTypeId) foreach ($roomTypes as $rt) if ($rt['id'] === $roomTypeId) { $selectedRoom = $rt; break; }

// Submit review
$submitMsg = ''; $submitErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) { $submitErr = 'Please log in to submit a review.'; }
    else {
        $rtId   = (int)($_POST['room_type_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $title  = clean(trim($_POST['title'] ?? ''));
        $review = clean(trim($_POST['review'] ?? ''));
        $uid    = (int)$_SESSION['user_id'];
        if (!$rtId || $rating < 1 || $rating > 5) { $submitErr = 'Please select a room and rating.'; }
        elseif (strlen($review) < 20) { $submitErr = 'Please write at least 20 characters.'; }
        else {
            $isVerified = 0; $bookingRef = null;
            $bk = $pdo->prepare("SELECT b.booking_ref FROM bookings b JOIN booked_rooms br ON br.booking_ref=b.booking_ref WHERE b.user_id=? AND br.room_type_id=? AND b.status IN ('confirmed','checked_out') LIMIT 1");
            $bk->execute([$uid, $rtId]); $bkRow = $bk->fetch();
            if ($bkRow) { $isVerified = 1; $bookingRef = $bkRow['booking_ref']; }

            $guestName = $_SESSION['username'] ?? 'Guest';
            $pdo->prepare("INSERT INTO room_ratings (room_type_id,user_id,booking_ref,guest_name,rating,title,review,is_verified,is_approved) VALUES (?,?,?,?,?,?,?,?,1)")
                ->execute([$rtId, $uid, $bookingRef, $guestName, $rating, $title ?: null, $review, $isVerified]);
            $reviewId = (int)$pdo->lastInsertId();

            // Handle media uploads
            $mediaCount = 0;
            if (!empty($_FILES['media']['name'][0])) {
                $uploadDir = __DIR__ . '/uploads/reviews/media/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                foreach ($_FILES['media']['name'] as $k => $fname) {
                    if ($_FILES['media']['error'][$k] !== UPLOAD_ERR_OK) continue;
                    $ext  = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                    $isVid = in_array($ext, ['mp4','mov','webm','avi']);
                    $isImg = in_array($ext, ['jpg','jpeg','png','webp','gif']);
                    if (!$isVid && !$isImg) continue;
                    if ($_FILES['media']['size'][$k] > 50 * 1024 * 1024) continue;
                    $newName = 'rev_' . $reviewId . '_' . $k . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['media']['tmp_name'][$k], $uploadDir . $newName)) {
                        $url = BASE . '/uploads/reviews/media/' . $newName;
                        try { $pdo->prepare("INSERT INTO review_media (review_id,type,filename,url) VALUES (?,?,?,?)")->execute([$reviewId, $isVid?'video':'image', $newName, $url]); $mediaCount++; } catch(Exception $e) {}
                    }
                }
            }
            // Update averages
            $pdo->prepare("UPDATE room_types SET avg_rating=(SELECT AVG(rating) FROM room_ratings WHERE room_type_id=? AND is_approved=1),review_count=(SELECT COUNT(*) FROM room_ratings WHERE room_type_id=? AND is_approved=1) WHERE id=?")->execute([$rtId,$rtId,$rtId]);
            // Loyalty bonus
            try { $pdo->prepare("INSERT IGNORE INTO loyalty_points (user_id,total_points,lifetime_points) VALUES (?,0,0)")->execute([$uid]); $pdo->prepare("UPDATE loyalty_points SET total_points=total_points+50,lifetime_points=lifetime_points+50 WHERE user_id=?")->execute([$uid]); } catch(Exception $e){}

            $submitMsg = ($isVerified ? '✅ Verified review published!' : '✅ Review submitted!') . ' +50 loyalty points earned.' . ($mediaCount > 0 ? " $mediaCount media file(s) attached." : '');
            $roomTypes = $pdo->query("SELECT id,name,price_usd,avg_rating,review_count FROM room_types ORDER BY sort_order")->fetchAll();
            if ($roomTypeId) foreach ($roomTypes as $rt) if ($rt['id'] === $roomTypeId) { $selectedRoom = $rt; break; }
        }
    }
}

// Fetch reviews
$reviews = []; $distribution = array_fill(1,5,0);
if ($roomTypeId) {
    $sort = clean($_GET['sort'] ?? 'recent');
    $orderBy = ['helpful'=>'rr.helpful_count DESC,rr.created_at DESC','highest'=>'rr.rating DESC','lowest'=>'rr.rating ASC'][$sort] ?? 'rr.created_at DESC';
    $page = max(1,(int)($_GET['page']??1)); $perPage = 8;
    $rStmt = $pdo->prepare("SELECT rr.*,u.name as udname,u.profile_img FROM room_ratings rr LEFT JOIN users u ON rr.user_id=u.id WHERE rr.room_type_id=? AND rr.is_approved=1 ORDER BY $orderBy LIMIT ? OFFSET ?");
    $rStmt->execute([$roomTypeId, $perPage, ($page-1)*$perPage]);
    $reviews = $rStmt->fetchAll();
    // Attach media
    foreach ($reviews as &$rv) {
        try { $ms = $pdo->prepare("SELECT * FROM review_media WHERE review_id=?"); $ms->execute([$rv['id']]); $rv['media'] = $ms->fetchAll(); } catch(Exception $e) { $rv['media'] = []; }
    }
    unset($rv);
    $dStmt = $pdo->prepare("SELECT rating,COUNT(*) cnt FROM room_ratings WHERE room_type_id=? AND is_approved=1 GROUP BY rating");
    $dStmt->execute([$roomTypeId]);
    foreach ($dStmt->fetchAll() as $d) $distribution[$d['rating']] = (int)$d['cnt'];
    $totalCount = array_sum($distribution); $totalPages = ceil($totalCount/$perPage);
}

require __DIR__ . '/header.php';
?>
<style>
.rv-page { padding-top: 70px; min-height: 100vh; }
.rv-hero { background: linear-gradient(135deg, var(--bg2), var(--bg)); padding: 56px 0 40px; border-bottom: 1px solid var(--bdr2); }
.rv-layout { display: grid; grid-template-columns: 1fr 380px; gap: 36px; align-items: start; padding: 40px 0 60px; }
@media(max-width:900px){.rv-layout{grid-template-columns:1fr}}

/* Room selector */
.rt-sel-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(190px,1fr)); gap: 10px; margin-bottom: 28px; }
.rt-btn { padding: 14px 16px; background: var(--card); border: 1.5px solid var(--bdr2); border-radius: var(--radius-lg); cursor: pointer; text-align: left; transition: all .2s; text-decoration: none; display: block; }
.rt-btn:hover, .rt-btn.active { border-color: var(--gold); background: var(--gold-dim); }
.rt-name { font-family: var(--serif); font-size: 16px; font-weight: 400; }
.rt-rating { color: var(--gold); font-size: 12px; margin-top: 3px; }
.rt-ct { font-size: 11px; color: var(--muted); }

/* Star picker */
.star-pick { display: flex; flex-direction: row-reverse; gap: 4px; justify-content: flex-end; }
.star-pick input { display: none; }
.star-pick label { font-size: 34px; cursor: pointer; color: var(--sand); transition: color .12s, transform .12s; }
.star-pick input:checked ~ label,
.star-pick label:hover,
.star-pick label:hover ~ label { color: var(--gold); }
.star-pick label:hover { transform: scale(1.18); }

/* Review card */
.rv-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: var(--radius-lg); padding: 22px 24px; margin-bottom: 16px; transition: all .25s; }
.rv-card:hover { border-color: var(--border); box-shadow: var(--shadow2); }
.rv-stars { color: var(--gold); font-size: 14px; letter-spacing: 2px; }
.verified-tag { display: inline-flex; align-items: center; gap: 4px; background: var(--green-bg); color: var(--green); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 10px; margin-left: 8px; }
.helpful-btn { background: none; border: 1px solid var(--bdr2); border-radius: 6px; padding: 4px 11px; font-size: 12px; color: var(--muted); cursor: pointer; font-family: var(--sans); transition: all .15s; }
.helpful-btn:hover { border-color: var(--gold); color: var(--gold); }

/* Rating bars */
.rb-row { display: flex; align-items: center; gap: 10px; margin-bottom: 7px; }
.rb-track { flex: 1; height: 6px; background: var(--card2); border-radius: 3px; overflow: hidden; }
.rb-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg,var(--gold-dk),var(--gold)); }

/* Media grid */
.media-grid { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.media-thumb { position: relative; width: 80px; height: 80px; border-radius: 8px; overflow: hidden; cursor: pointer; border: 1px solid var(--bdr2); }
.media-thumb img { width: 100%; height: 100%; object-fit: cover; }
.media-thumb video { width: 100%; height: 100%; object-fit: cover; }
.media-thumb .play-ic { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.35); color: #fff; font-size: 20px; }

/* Upload zone */
.media-upload-zone { border: 2px dashed var(--border); border-radius: var(--radius-lg); padding: 24px; text-align: center; cursor: pointer; transition: all .2s; background: transparent; }
.media-upload-zone:hover { border-color: var(--gold); background: var(--gold-dim); }
.media-upload-zone i { font-size: 26px; color: var(--gold); display: block; margin-bottom: 8px; }
.media-preview-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.mp-thumb { position: relative; width: 68px; height: 68px; border-radius: 6px; overflow: hidden; border: 1px solid var(--bdr2); }
.mp-thumb img, .mp-thumb video { width: 100%; height: 100%; object-fit: cover; }
.mp-rm { position: absolute; top: 2px; right: 2px; width: 20px; height: 20px; border-radius: 50%; background: var(--red); border: none; color: #fff; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; }

.sort-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.sort-tab { padding: 6px 14px; border-radius: 20px; font-size: 12.5px; border: 1px solid var(--border); color: var(--text2); text-decoration: none; transition: all .2s; }
.sort-tab:hover,.sort-tab.active { background: var(--gold); color: #fff; border-color: var(--gold); font-weight: 600; }
.pg-btns { display: flex; gap: 6px; margin-top: 20px; flex-wrap: wrap; }
.pg-btn { padding: 7px 14px; border-radius: 8px; font-size: 13px; border: 1px solid var(--border); color: var(--text2); text-decoration: none; transition: all .2s; }
.pg-btn:hover,.pg-btn.cur { background: var(--gold); color: #fff; border-color: var(--gold); }
</style>

<div class="rv-page">
  <div class="rv-hero">
    <div class="container">
      <div class="lx-eyebrow" style="justify-content:flex-start;margin-bottom:10px">Guest Experiences</div>
      <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h1 class="lx-heading">Reviews & <em>Ratings</em></h1>
          <p style="color:var(--text2);font-size:14px;margin-top:8px">Earn <strong style="color:var(--gold)">+50 loyalty points</strong> for every published review. Share photos & videos!</p>
        </div>
        <?php if (isLoggedIn()): ?>
        <a href="<?= $B ?>/loyalty.php" class="btn btn-outline btn-sm"><i class="fas fa-coins"></i> My Points</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="rv-layout">
      <!-- Left: Reviews -->
      <div>
        <!-- Room selector -->
        <div style="margin-bottom:24px">
          <div class="form-label" style="margin-bottom:12px">Select Room Type to View Reviews</div>
          <div class="rt-sel-grid">
            <?php foreach ($roomTypes as $rt): ?>
            <a href="?room=<?= $rt['id'] ?>&sort=<?= clean($_GET['sort']??'recent') ?>" class="rt-btn <?= $roomTypeId===$rt['id']?'active':'' ?>">
              <div class="rt-name"><?= htmlspecialchars($rt['name']) ?></div>
              <div class="rt-rating"><?= str_repeat('★',(int)round($rt['avg_rating']??4.5)) ?> <?= number_format($rt['avg_rating']??4.5,1) ?></div>
              <div class="rt-ct"><?= $rt['review_count'] ?> reviews</div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($selectedRoom): ?>
        <!-- Rating overview -->
        <div class="rv-card" style="margin-bottom:20px">
          <div style="display:flex;align-items:center;gap:32px;flex-wrap:wrap">
            <div style="text-align:center">
              <div style="font-family:var(--serif);font-size:60px;font-weight:300;color:var(--gold);line-height:1"><?= number_format($selectedRoom['avg_rating']??4.5,1) ?></div>
              <div style="color:var(--gold);font-size:18px;letter-spacing:3px"><?= str_repeat('★',(int)round($selectedRoom['avg_rating']??4.5)) ?></div>
              <div style="font-size:12px;color:var(--muted);margin-top:4px"><?= $selectedRoom['review_count'] ?> reviews</div>
            </div>
            <div style="flex:1;min-width:200px">
              <?php for($s=5;$s>=1;$s--): $cnt=$distribution[$s]??0; $tot=array_sum($distribution); $pct=$tot>0?round($cnt/$tot*100):0; ?>
              <div class="rb-row">
                <span style="font-size:12px;color:var(--muted);min-width:20px"><?=$s?>★</span>
                <div class="rb-track"><div class="rb-fill" style="width:<?=$pct?>%"></div></div>
                <span style="font-size:12px;color:var(--muted);min-width:26px"><?=$cnt?></span>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Sort + count -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
          <div class="sort-tabs">
            <?php foreach (['recent'=>'Most Recent','helpful'=>'Most Helpful','highest'=>'Highest','lowest'=>'Lowest'] as $v=>$l): ?>
            <a href="?room=<?=$roomTypeId?>&sort=<?=$v?>" class="sort-tab <?= (clean($_GET['sort']??'recent')===$v)?'active':'' ?>"><?=$l?></a>
            <?php endforeach; ?>
          </div>
          <div style="font-size:13px;color:var(--muted)"><?=count($reviews)?> shown</div>
        </div>

        <?php if (empty($reviews)): ?>
        <div style="text-align:center;padding:60px;color:var(--muted)">
          <i class="fas fa-star" style="font-size:36px;margin-bottom:14px;display:block;opacity:.3"></i>
          <h3 style="font-family:var(--serif);font-size:20px;margin-bottom:8px">No reviews yet</h3>
          <p>Be the first to share your experience!</p>
        </div>
        <?php else: ?>
        <?php foreach ($reviews as $rv):
          $name = $rv['guest_name'] ?? $rv['udname'] ?? 'Anonymous';
          $diff = time()-strtotime($rv['created_at']);
          $ago  = $diff<3600?floor($diff/60).'m ago':($diff<86400?floor($diff/3600).'h ago':($diff<2592000?floor($diff/86400).'d ago':date('d M Y',strtotime($rv['created_at']))));
          $media = $rv['media'] ?? [];
        ?>
        <div class="rv-card">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px">
            <div style="display:flex;align-items:center;gap:12px">
              <?= userAvatar($rv['profile_img'] ?? null, $name, 42) ?>
              <div>
                <div style="font-weight:600;font-size:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                  <?= htmlspecialchars($name) ?>
                  <?php if ($rv['is_verified']): ?><span class="verified-tag"><i class="fas fa-check-circle"></i> Verified Stay</span><?php endif; ?>
                </div>
                <div style="font-size:12px;color:var(--muted)"><?= $ago ?></div>
              </div>
            </div>
            <div style="color:var(--gold);font-size:14px;letter-spacing:2px"><?= str_repeat('★',$rv['rating']) ?></div>
          </div>

          <?php if ($rv['title']): ?><div style="font-family:var(--serif);font-size:17px;font-weight:400;margin-bottom:8px"><?= htmlspecialchars($rv['title']) ?></div><?php endif; ?>
          <p style="font-size:13.5px;color:var(--text2);line-height:1.75;margin-bottom:12px"><?= nl2br(htmlspecialchars($rv['review'])) ?></p>

          <!-- Media -->
          <?php if (!empty($media)): ?>
          <div class="media-grid">
            <?php foreach ($media as $m): ?>
            <?php if ($m['type']==='image'): ?>
            <div class="media-thumb" onclick="openMedia('<?= htmlspecialchars($m['url']) ?>','image')">
              <img src="<?= htmlspecialchars($m['url']) ?>" alt="Review photo" loading="lazy" onerror="this.parentElement.style.display='none'">
            </div>
            <?php else: ?>
            <div class="media-thumb" onclick="openMedia('<?= htmlspecialchars($m['url']) ?>','video')">
              <video src="<?= htmlspecialchars($m['url']) ?>" muted preload="metadata" onerror="this.parentElement.style.display='none'"></video>
              <div class="play-ic"><i class="fas fa-play" style="font-size:16px"></i></div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div style="display:flex;align-items:center;gap:10px;margin-top:12px">
            <button class="helpful-btn" onclick="markHelpful(<?= $rv['id'] ?>,this)">
              <i class="fas fa-thumbs-up"></i> Helpful (<?= $rv['helpful_count'] ?? 0 ?>)
            </button>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (($totalPages??1) > 1): ?>
        <div class="pg-btns">
          <?php if ($page>1): ?><a href="?room=<?=$roomTypeId?>&sort=<?=clean($_GET['sort']??'recent')?>&page=<?=$page-1?>" class="pg-btn">← Prev</a><?php endif; ?>
          <?php for($p=1;$p<=($totalPages??1);$p++): ?><a href="?room=<?=$roomTypeId?>&sort=<?=clean($_GET['sort']??'recent')?>&page=<?=$p?>" class="pg-btn <?=$p===$page?'cur':''?>"><?=$p?></a><?php endfor; ?>
          <?php if ($page<($totalPages??1)): ?><a href="?room=<?=$roomTypeId?>&sort=<?=clean($_GET['sort']??'recent')?>&page=<?=$page+1?>" class="pg-btn">Next →</a><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Right: Write Review -->
      <div style="position:sticky;top:80px">
        <div class="lx-card">
          <div class="lx-card-hd">
            <div class="lx-card-title">Write a Review</div>
            <span class="badge badge-gold" style="font-size:11px">+50 pts</span>
          </div>
          <div class="lx-card-bd">
            <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> <a href="<?= $B ?>/login.php" style="color:var(--gold)">Log in</a> to write a review.</div>
            <?php endif; ?>
            <?php if ($submitMsg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($submitMsg) ?></div><?php endif; ?>
            <?php if ($submitErr): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($submitErr) ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="submit_review" value="1">

              <div class="form-group">
                <label class="form-label">Room Type *</label>
                <select class="form-control" name="room_type_id" required onchange="window.location='?room='+this.value">
                  <option value="">— Choose Room —</option>
                  <?php foreach ($roomTypes as $rt): ?>
                  <option value="<?=$rt['id']?>" <?=$roomTypeId===$rt['id']?'selected':''?>><?= htmlspecialchars($rt['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Your Rating *</label>
                <div class="star-pick" id="starPicker">
                  <?php for($s=5;$s>=1;$s--): ?>
                  <input type="radio" name="rating" id="s<?=$s?>" value="<?=$s?>" required>
                  <label for="s<?=$s?>" title="<?=$s?> star<?=$s>1?'s':''?>">★</label>
                  <?php endfor; ?>
                </div>
                <div id="rLabel" style="font-size:12px;color:var(--muted);margin-top:6px;min-height:18px"></div>
              </div>

              <?php if (isLoggedIn()): ?>
              <div class="form-group">
                <label class="form-label">Review Title</label>
                <input class="form-control" name="title" placeholder="Summarize your experience…" maxlength="200" <?= !isLoggedIn()?'disabled':'' ?>>
              </div>
              <?php endif; ?>

              <div class="form-group">
                <label class="form-label">Your Review * <span style="font-size:11px;color:var(--muted)">(min. 20 chars)</span></label>
                <textarea class="form-control" name="review" rows="5" placeholder="Tell us about your stay — room quality, service, food, atmosphere…" required minlength="20" <?= !isLoggedIn()?'disabled':'' ?> id="reviewTxt"></textarea>
                <div id="charCnt" style="font-size:11px;color:var(--muted);margin-top:4px;text-align:right">0 chars</div>
              </div>

              <!-- ── MEDIA UPLOAD ── -->
              <div class="form-group">
                <label class="form-label"><i class="fas fa-camera" style="color:var(--gold)"></i> Photos & Videos <span style="font-size:10px;color:var(--muted)">(optional, max 50MB each)</span></label>
                <div class="media-upload-zone" onclick="document.getElementById('mediaFileInput').click()" id="uploadZone">
                  <i class="fas fa-cloud-upload-alt"></i>
                  <div style="font-size:14px;font-weight:500">Drop photos / videos here</div>
                  <div style="font-size:12px;color:var(--muted);margin-top:4px">JPG, PNG, WebP, GIF, MP4, MOV, WebM</div>
                </div>
                <input type="file" name="media[]" id="mediaFileInput" multiple accept="image/*,video/*" style="display:none" onchange="previewMedia(this)" <?= !isLoggedIn()?'disabled':'' ?>>
                <div class="media-preview-row" id="mediaPreviewRow"></div>
              </div>

              <button type="submit" class="btn btn-gold btn-block" <?= !isLoggedIn()?'disabled':'' ?>>
                <i class="fas fa-star"></i> Submit Review
              </button>
              <?php if (!isLoggedIn()): ?>
              <a href="<?= $B ?>/login.php?after=<?= urlencode($_SERVER['REQUEST_URI'] ?? '') ?>" class="btn btn-outline btn-block" style="margin-top:8px"><i class="fas fa-sign-in-alt"></i> Login to Review</a>
              <?php endif; ?>
            </form>
          </div>
        </div>

        <!-- Guidelines -->
        <div style="background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);padding:18px 20px;margin-top:14px">
          <div style="font-family:var(--cinzel);font-size:9px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:12px">Review Guidelines</div>
          <?php foreach (['Be honest and specific about your experience','Verified stays get a special badge','Earn 50 loyalty points per review','Upload up to 5 photos or 1 video','Videos must be under 50MB (MP4, MOV)'] as $tip): ?>
          <div style="font-size:12px;color:var(--muted);margin-bottom:7px;display:flex;gap:7px"><i class="fas fa-check" style="color:var(--gold);margin-top:2px;flex-shrink:0"></i><?=$tip?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
const rLabels = ['','Poor','Fair','Good','Very Good','Excellent'];
document.querySelectorAll('.star-pick input').forEach(r=>{
  r.addEventListener('change',()=>{
    const el=document.getElementById('rLabel');
    el.textContent=rLabels[r.value]||'';el.style.color='var(--gold)';
    anime({targets:'.star-pick label',scale:[1.2,1],duration:200,easing:'easeOutBack',delay:anime.stagger(40)});
  });
});

document.getElementById('reviewTxt')?.addEventListener('input',function(){
  const l=this.value.length,el=document.getElementById('charCnt');
  el.textContent=l+' chars'+(l<20?' — need '+(20-l)+' more':'');
  el.style.color=l>=20?'var(--green)':'var(--muted)';
});

function previewMedia(input){
  const row=document.getElementById('mediaPreviewRow');
  row.innerHTML='';
  const files=[...input.files].slice(0,5); // max 5
  files.forEach((file,i)=>{
    const reader=new FileReader();
    reader.onload=e=>{
      const div=document.createElement('div');div.className='mp-thumb';
      const isVid=file.type.startsWith('video/');
      if(isVid){div.innerHTML=`<video src="${e.target.result}" muted preload="metadata" style="width:100%;height:100%;object-fit:cover"></video><div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.3);color:#fff;font-size:18px">▶</div>`;}
      else{div.innerHTML=`<img src="${e.target.result}" alt="" style="width:100%;height:100%;object-fit:cover">`;}
      const rm=document.createElement('button');rm.className='mp-rm';rm.innerHTML='×';rm.type='button';
      rm.onclick=()=>{div.remove();};
      div.appendChild(rm);row.appendChild(div);
      anime({targets:div,scale:[0,1],duration:300,easing:'easeOutBack'});
    };
    reader.readAsDataURL(file);
  });
}

// Drag and drop on upload zone
const uz=document.getElementById('uploadZone');
if(uz){
  uz.addEventListener('dragover',e=>{e.preventDefault();uz.style.borderColor='var(--gold)';uz.style.background='var(--gold-dim)';});
  uz.addEventListener('dragleave',()=>{uz.style.borderColor='';uz.style.background='';});
  uz.addEventListener('drop',e=>{
    e.preventDefault();uz.style.borderColor='';uz.style.background='';
    const fi=document.getElementById('mediaFileInput');
    if(fi){const dt=new DataTransfer();[...e.dataTransfer.files].forEach(f=>dt.items.add(f));fi.files=dt.files;previewMedia(fi);}
  });
}

async function markHelpful(id,btn){
  btn.disabled=true;btn.style.opacity='.5';
  try{
    const fd=new FormData();fd.append('action','helpful');fd.append('review_id',id);
    const res=await fetch('<?= $B ?>/api/reviews.php',{method:'POST',body:fd});
    const d=await res.json();
    if(d.success){const n=parseInt(btn.textContent.match(/\d+/)?.[0]||0)+1;btn.innerHTML='<i class="fas fa-thumbs-up"></i> Helpful ('+n+')';btn.style.color='var(--gold)';btn.style.borderColor='var(--gold)';}
  }catch(e){}
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
