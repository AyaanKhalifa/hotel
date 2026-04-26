<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Gallery — Royale Vista';
$category  = clean($_GET['cat'] ?? 'all');

$where  = $category !== 'all' ? 'AND gi.category=?' : '';
$params = $category !== 'all' ? [$category] : [];
$stmt = $pdo->prepare("SELECT gi.* FROM gallery_images gi WHERE gi.is_active=1 $where ORDER BY gi.sort_order, gi.created_at DESC");
$stmt->execute($params);
$images = $stmt->fetchAll();
$cats = $pdo->query("SELECT category, COUNT(*) as cnt FROM gallery_images WHERE is_active=1 GROUP BY category ORDER BY cnt DESC")->fetchAll();

require __DIR__ . '/header.php';
?>
<style>
.gallery-page{padding-top:88px;min-height:100vh}
.gallery-hero{padding:48px 0 36px;background:linear-gradient(135deg,var(--bg2),var(--bg));border-bottom:1px solid var(--bdr2)}
.cat-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px}
.cat-btn{padding:8px 20px;border-radius:24px;font-size:13px;border:1px solid var(--border);color:var(--text2);background:transparent;cursor:pointer;font-family:var(--sans);transition:all var(--t);text-decoration:none}
.cat-btn:hover,.cat-btn.active{background:var(--gold);color:#000;border-color:var(--gold);font-weight:600}
.gallery-masonry{columns:4 200px;gap:12px}
.g-item{break-inside:avoid;margin-bottom:12px;border-radius:var(--radius);overflow:hidden;position:relative;cursor:zoom-in}
.g-item img{width:100%;display:block;transition:transform .5s var(--ease),filter .3s}
.g-item:hover img{transform:scale(1.06);filter:brightness(.8)}
.g-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7),transparent 50%);opacity:0;transition:opacity .3s;display:flex;align-items:flex-end;padding:14px}
.g-item:hover .g-overlay{opacity:1}
#lbox{position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.96);display:none;align-items:center;justify-content:center}
#lbox.open{display:flex;animation:fadeIn .2s}
#lbImg{max-width:90vw;max-height:85vh;object-fit:contain;border-radius:8px;transition:opacity .3s}
.lb-close{position:absolute;top:20px;right:20px;background:rgba(255,255,255,.12);border:none;color:#fff;font-size:24px;cursor:pointer;width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.lb-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.1);border:none;color:#fff;font-size:22px;cursor:pointer;width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:background .2s}
.lb-nav:hover{background:rgba(255,255,255,.2)}
#lbPrev{left:20px}#lbNext{right:20px}
#lbInfo{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);text-align:center;color:rgba(255,255,255,.8)}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@media(max-width:768px){.gallery-masonry{columns:2 140px}}
@media(max-width:480px){.gallery-masonry{columns:2 100px;gap:6px}}
</style>
<div class="gallery-page">
  <div class="gallery-hero"><div class="container">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px">
      <div>
        <div class="section-label">Visual Story</div>
        <h1 style="font-family:var(--serif);font-size:clamp(28px,4vw,44px);font-weight:400;margin-bottom:10px">Gallery</h1>
        <p style="color:var(--text2);font-size:15px"><?= count($images) ?> photos · <?= count($cats) ?> categories</p>
      </div>
      <?php if(isAdmin()): ?><a href="<?= $B ?>/admin/gallery.php" class="btn btn-gold"><i class="fas fa-cloud-upload-alt"></i> Manage Gallery</a><?php endif; ?>
    </div>
  </div></div>
  <div class="container" style="padding-top:28px;padding-bottom:60px">
    <div class="cat-filters">
      <a href="?cat=all" class="cat-btn <?= $category==='all'?'active':'' ?>">All (<?= count($images) ?>)</a>
      <?php foreach($cats as $cat): ?><a href="?cat=<?= urlencode($cat['category']) ?>" class="cat-btn <?= $category===$cat['category']?'active':'' ?>"><?= ucfirst(htmlspecialchars($cat['category'])) ?> (<?= $cat['cnt'] ?>)</a><?php endforeach; ?>
    </div>
    <?php if(empty($images)): ?>
    <div style="text-align:center;padding:80px;color:var(--muted)"><div style="font-size:48px;margin-bottom:16px">🖼️</div><h3 style="font-family:var(--serif);font-size:22px">No photos here yet</h3></div>
    <?php else: ?>
    <div class="gallery-masonry">
      <?php foreach($images as $i=>$img): $src=$img['is_local']?$B.'/uploads/gallery/'.$img['filename']:$img['image_url']; ?>
      <div class="g-item" onclick="openLB(<?= $i ?>)">
        <?php if($img['media_type']==='video'): ?>
        <div style="width:100%;aspect-ratio:16/9;background:#111;display:flex;align-items:center;justify-content:center;min-height:120px">
          <i class="fas fa-play-circle" style="font-size:44px;color:var(--gold);opacity:.9"></i>
        </div>
        <?php else: ?>
        <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($img['title']??'') ?>" loading="lazy">
        <?php endif; ?>
        <div class="g-overlay">
          <div>
            <div style="color:#fff;font-size:13px;font-weight:500"><?= htmlspecialchars($img['title']??'') ?></div>
            <div style="color:rgba(255,255,255,.7);font-size:11px;text-transform:capitalize"><?= htmlspecialchars($img['category']??'') ?><?= $img['media_type']==='video'?' 🎥':'' ?></div>
          </div>
          <div style="margin-left:auto;color:rgba(255,255,255,.7)"><i class="fas fa-<?= $img['media_type']==='video'?'play':'expand-alt' ?>"></i></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<div id="lbox">
  <button class="lb-close" onclick="closeLB()">×</button>
  <button class="lb-nav" id="lbPrev" onclick="navLB(-1)"><i class="fas fa-chevron-left"></i></button>
  <img id="lbImg" src="" alt="">
  <div id="lbVideo" style="display:none;max-width:90vw;max-height:75vh;width:900px;aspect-ratio:16/9"></div>
  <button class="lb-nav" id="lbNext" onclick="navLB(1)"><i class="fas fa-chevron-right"></i></button>
  <div id="lbInfo">
    <div id="lbTitle" style="font-family:var(--serif);font-size:18px"></div>
    <div id="lbCounter" style="font-size:12px;opacity:.5;margin-top:4px"></div>
  </div>
</div>
<script>
const galleryData=<?= json_encode(array_map(fn($img)=>['url'=>$img['is_local']?BASE.'/uploads/gallery/'.$img['filename']:$img['image_url'],'title'=>$img['title']??'','cat'=>$img['category']??'','type'=>$img['media_type']??'image'],$images)) ?>;
let lbI=0;
function openLB(i){lbI=i;updLB();document.getElementById('lbox').classList.add('open');document.body.style.overflow='hidden'}
function closeLB(){
  const lbv=document.getElementById('lbVideo');
  lbv.innerHTML=''; lbv.style.display='none';
  document.getElementById('lbImg').style.display='';
  document.getElementById('lbox').classList.remove('open');document.body.style.overflow=''
}
function navLB(d){lbI=(lbI+d+galleryData.length)%galleryData.length;updLB()}
function getYTEmbed(url){ const m=url.match(/(?:youtu\.be\/|v=)([\w-]{11})/); return m?`https://www.youtube.com/embed/${m[1]}?autoplay=1`:null; }
function getVimeoEmbed(url){ const m=url.match(/vimeo\.com\/([0-9]+)/); return m?`https://player.vimeo.com/video/${m[1]}?autoplay=1`:null; }
function updLB(){
  const g=galleryData[lbI];if(!g)return;
  const lbv=document.getElementById('lbVideo');
  const lbImg=document.getElementById('lbImg');
  document.getElementById('lbTitle').textContent=g.title;
  document.getElementById('lbCounter').textContent=(lbI+1)+' / '+galleryData.length;
  if(g.type==='video'){
    lbImg.style.display='none'; lbv.style.display='block';
    const ytSrc=getYTEmbed(g.url), vmSrc=getVimeoEmbed(g.url);
    if(ytSrc||vmSrc){
      lbv.innerHTML=`<iframe src="${ytSrc||vmSrc}" style="width:100%;height:100%;border:none;border-radius:12px" allow="autoplay;fullscreen" allowfullscreen></iframe>`;
    } else {
      lbv.innerHTML=`<video src="${g.url}" controls autoplay style="width:100%;height:100%;border-radius:12px"></video>`;
    }
  } else {
    lbv.innerHTML=''; lbv.style.display='none'; lbImg.style.display='';
    lbImg.style.opacity=0; lbImg.src=g.url; lbImg.onload=()=>{lbImg.style.opacity=1};
  }
}
document.addEventListener('keydown',e=>{if(!document.getElementById('lbox').classList.contains('open'))return;if(e.key==='ArrowRight')navLB(1);if(e.key==='ArrowLeft')navLB(-1);if(e.key==='Escape')closeLB()});
document.getElementById('lbox').addEventListener('click',e=>{if(e.target===e.currentTarget)closeLB()});
</script>
<?php require __DIR__ . '/footer.php'; ?>
