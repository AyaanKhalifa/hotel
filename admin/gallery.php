<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
requireAdmin();

$B     = BASE;
$theme = getTheme();
$msg   = '';

// ── Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_upload'])) {
    $title    = clean($_POST['title'] ?? '');
    $category = clean($_POST['category'] ?? 'hotel');
    $imgUrl   = clean($_POST['img_url'] ?? '');
    $files    = $_FILES['images'] ?? [];
    $uploaded = 0; $errors = [];

    if ($imgUrl && filter_var($imgUrl, FILTER_VALIDATE_URL)) {
        // If it's a YouTube / Vimeo link, keep it as external URL since we embed those
        if (preg_match('/youtu\.be|youtube\.com|vimeo\.com/i', $imgUrl)) {
            $pdo->prepare("INSERT INTO gallery_images (title,category,image_url,is_local,media_type,uploaded_by) VALUES (?,?,?,0,'video',?)")
                ->execute([$title, $category, $imgUrl, $_SESSION['user_id']]);
            $uploaded++;
        } else {
            // Attempt to proactively download actual images/mp4 clips from the internet
            $ext = strtolower(pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (!$ext) $ext = 'jpg'; // fallback
            $isVid = in_array($ext, ['mp4','webm']) ? 1 : 0;
            $mType = $isVid ? 'video' : 'image';
            
            $fileData = @file_get_contents($imgUrl);
            if ($fileData) {
                $uploadDir = dirname(__DIR__) . '/uploads/gallery/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                
                $fname = 'gallery_remote_' . time() . '_' . rand(100,999) . '.' . $ext;
                $t = $title ?: 'Fetched URL Image';
                
                if (file_put_contents($uploadDir . $fname, $fileData)) {
                    $pdo->prepare("INSERT INTO gallery_images (title,category,image_url,is_local,filename,media_type,uploaded_by) VALUES (?,?,?,1,?,?,?)")
                        ->execute([$t, $category, '/uploads/gallery/'.$fname, $fname, $mType, $_SESSION['user_id']]);
                    $uploaded++;
                }
            } else {
                $errors[] = "Failed to fetch media from URL";
            }
        }
    }

    if (!empty($files['name'][0])) {
        $uploadDir = dirname(__DIR__) . '/uploads/gallery/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        foreach ($files['name'] as $k => $name) {
            if ($files['error'][$k] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp','mp4'])) { $errors[] = "$name: invalid type"; continue; }
            if ($files['size'][$k] > 20*1024*1024) { $errors[] = "$name: too large (max 20MB)"; continue; }
            $mType = ($ext === 'mp4') ? 'video' : 'image';
            $fname = 'gallery_' . time() . '_' . $k . '.' . $ext;
            if (move_uploaded_file($files['tmp_name'][$k], $uploadDir . $fname)) {
                $t = $title ?: pathinfo($name, PATHINFO_FILENAME);
                $pdo->prepare("INSERT INTO gallery_images (title,category,image_url,is_local,filename,media_type,uploaded_by) VALUES (?,?,?,1,?,?,?)")
                    ->execute([$t, $category, '/uploads/gallery/'.$fname, $fname, $mType, $_SESSION['user_id']]);
                $uploaded++;
            }
        }
    }
    $msg = $uploaded . ' image' . ($uploaded !== 1 ? 's' : '') . ' uploaded successfully.';
    if ($errors) $msg .= ' Errors: ' . implode(', ', $errors);
}

// ── Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_edit'])) {
    $editId  = (int)($_POST['edit_id'] ?? 0);
    $newTitle = clean($_POST['edit_title'] ?? '');
    $newCat   = clean($_POST['edit_cat'] ?? 'hotel');
    if ($editId) {
        $pdo->prepare("UPDATE gallery_images SET title=?, category=? WHERE id=?")->execute([$newTitle, $newCat, $editId]);
        $msg = 'Image updated.';
    }
}

// ── Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $imgRow = $pdo->prepare("SELECT * FROM gallery_images WHERE id=?");
    $imgRow->execute([(int)$_GET['delete']]);
    $imgData = $imgRow->fetch();
    if ($imgData) {
        if ($imgData['is_local'] && $imgData['filename'])
            @unlink(dirname(__DIR__) . '/uploads/gallery/' . $imgData['filename']);
        $pdo->prepare("DELETE FROM gallery_images WHERE id=?")->execute([(int)$_GET['delete']]);
        $msg = 'Image deleted.';
    }
}

// ── Handle Toggle Active
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE gallery_images SET is_active=NOT is_active WHERE id=?")->execute([(int)$_GET['toggle']]);
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// ── Fetch images
$catFilter = clean($_GET['cat'] ?? 'all');
$where     = $catFilter !== 'all' ? 'WHERE category=?' : '';
$params    = $catFilter !== 'all' ? [$catFilter] : [];
$stmt      = $pdo->prepare("SELECT * FROM gallery_images $where ORDER BY sort_order, created_at DESC");
$stmt->execute($params);
$allImages  = $stmt->fetchAll();
$categories = $pdo->query("SELECT category,COUNT(*) as cnt FROM gallery_images GROUP BY category ORDER BY cnt DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gallery Manager — Royale Vista Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= $B ?>/admin/css/admin.css">
<style>
.upload-zone{border:2px dashed var(--br);border-radius:var(--rlg);padding:40px;text-align:center;transition:all .2s;cursor:pointer}
.upload-zone:hover,.upload-zone.dragover{border-color:var(--gold);background:var(--golddim)}
.upload-zone i{font-size:36px;color:var(--gold);margin-bottom:12px;display:block}
.img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
.img-card{border-radius:var(--r);overflow:hidden;background:var(--card2);border:1px solid var(--br2);position:relative}
.img-card img{width:100%;height:140px;object-fit:cover;display:block}
.img-card-info{padding:10px}
.img-actions{display:flex;gap:6px}
.img-btn{flex:1;padding:6px;border-radius:6px;border:none;cursor:pointer;font-size:12px;font-family:var(--sans);transition:all .15s;text-align:center;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
.inactive-overlay{position:absolute;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;font-size:24px}
</style>
</head>
<body>
<?php include __DIR__ . '/partials/topbar.php'; ?>
<div class="adm-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="adm-main">

  <div class="adm-ph">
    <div><h1>Gallery Manager</h1><p class="sub"><?= count($allImages) ?> images</p></div>
    <a href="<?= $B ?>/gallery.php" target="_blank" class="btn btn-ghost btn-sm"><i class="fas fa-external-link-alt"></i> View Gallery</a>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-success" style="margin-bottom:20px"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="g2" style="gap:20px;margin-bottom:24px">
    <!-- Upload Card -->
    <div class="ac">
      <div class="ac-hd"><div class="ac-title"><i class="fas fa-cloud-upload-alt" style="color:var(--gold)"></i> Upload Images</div></div>
      <div class="ac-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <input type="hidden" name="do_upload" value="1">
          <div class="upload-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
            <i class="fas fa-images"></i>
            <div style="font-size:15px;font-weight:500;color:var(--tx)">Drop media here or click to browse</div>
            <div style="font-size:12px;color:var(--mu);margin-top:6px">JPG, PNG, WebP, GIF, MP4 — Max 20MB each — Multiple OK</div>
            <div id="filePreview" style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:14px"></div>
          </div>
          <input type="file" id="fileInput" name="images[]" multiple accept="image/*,video/mp4" style="display:none" onchange="previewFiles(this)">

          <div style="display:flex;align-items:center;gap:10px;margin:14px 0">
            <div style="flex:1;height:1px;background:var(--br2)"></div>
            <span style="font-size:12px;color:var(--mu)">OR</span>
            <div style="flex:1;height:1px;background:var(--br2)"></div>
          </div>
          <div class="fg">
            <label class="fl">Image URL</label>
            <input class="fc" name="img_url" type="url" placeholder="https://example.com/image.jpg">
          </div>
          <div class="fr">
            <div class="fg">
              <label class="fl">Title (optional)</label>
              <input class="fc" name="title" placeholder="Image title">
            </div>
            <div class="fg">
              <label class="fl">Category</label>
              <select class="fc" name="category">
                <?php foreach (['hotel','rooms','pool','restaurant','spa','bar','events','outdoor','facilities','lobby'] as $cat): ?>
                <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-gold btn-block"><i class="fas fa-upload"></i> Upload Images</button>
        </form>
      </div>
    </div>

    <!-- Stats Card -->
    <div class="ac">
      <div class="ac-hd"><div class="ac-title"><i class="fas fa-chart-pie" style="color:var(--gold)"></i> By Category</div></div>
      <div style="padding:12px 0">
        <?php
        $totalImages = count($allImages);
        foreach ($categories as $cat):
          $pct = $totalImages > 0 ? round($cat['cnt'] / $totalImages * 100) : 0; ?>
        <div style="padding:10px 22px;border-bottom:1px solid var(--br2)">
          <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px">
            <span style="font-weight:500;text-transform:capitalize"><?= htmlspecialchars($cat['category']) ?></span>
            <span style="color:var(--gold)"><?= $cat['cnt'] ?> photos</span>
          </div>
          <div style="height:4px;background:var(--card2);border-radius:2px;overflow:hidden">
            <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,var(--gold2),var(--gold));border-radius:2px"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <div style="padding:14px 22px;font-size:13px;color:var(--mu)">Total: <strong style="color:var(--tx)"><?= $totalImages ?></strong> images</div>
      </div>
    </div>
  </div>

  <!-- Image Grid -->
  <div class="ac">
    <div class="ac-hd">
      <div class="ac-title"><i class="fas fa-images" style="color:var(--gold)"></i> All Images (<?= count($allImages) ?>)</div>
      <select onchange="window.location='?cat='+this.value" class="fc" style="height:34px;font-size:12.5px;padding:5px 10px;width:140px">
        <option value="all" <?= $catFilter === 'all' ? 'selected' : '' ?>>All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['category'] ?>" <?= $catFilter === $cat['category'] ? 'selected' : '' ?>><?= ucfirst($cat['category']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="padding:20px">
      <?php if (empty($allImages)): ?>
      <div style="text-align:center;padding:60px;color:var(--mu)"><i class="fas fa-images" style="font-size:32px;margin-bottom:12px;display:block"></i>No images yet. Use the form above to add some!</div>
      <?php else: ?>
      <div class="img-grid">
        <?php foreach ($allImages as $img):
          $src = $img['is_local'] ? $B . '/uploads/gallery/' . $img['filename'] : $img['image_url']; ?>
          <div class="img-card">
          <div style="position:relative">
            <?php if ($img['media_type'] === 'video'): ?>
            <div style="width:100%;height:140px;background:#111;display:flex;align-items:center;justify-content:center;cursor:pointer" onclick="window.open('<?= htmlspecialchars($img['image_url']) ?>','_blank')">
              <i class="fas fa-play-circle" style="font-size:40px;color:var(--gold)"></i>
            </div>
            <?php else: ?>
            <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($img['title'] ?? '') ?>"
                 onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'180\' height=\'140\'><rect fill=\'%23333\' width=\'100%25\' height=\'100%25\'/><text x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%23666\' font-size=\'12\'>Error</text></svg>'">
            <?php endif; ?>
            <?php if (!$img['is_active']): ?>
            <div class="inactive-overlay">🚫</div>
            <?php endif; ?>
            <div style="position:absolute;top:8px;right:8px;display:flex;gap:4px">
              <span style="background:rgba(0,0,0,.7);color:var(--gold);font-size:10px;padding:2px 8px;border-radius:10px;text-transform:capitalize"><?= htmlspecialchars($img['category']) ?></span>
              <?php if ($img['media_type'] === 'video'): ?><span style="background:rgba(239,68,68,.8);color:#fff;font-size:9px;padding:2px 7px;border-radius:10px">🎥 VIDEO</span><?php endif; ?>
            </div>
          </div>
          <div class="img-card-info">
            <div style="font-size:12px;font-weight:500;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($img['title'] ?? 'Untitled') ?>"><?= htmlspecialchars($img['title'] ?? 'Untitled') ?></div>
            <div class="img-actions">
              <button class="img-btn" style="background:var(--goldbg);color:var(--gold)" onclick="openEdit(<?= $img['id'] ?>,'<?= addslashes($img['title']??'') ?>','<?= $img['category'] ?>')" title="Edit">
                <i class="fas fa-pencil-alt"></i>
              </button>
              <a href="?toggle=<?= $img['id'] ?>" class="img-btn" style="background:var(--<?= $img['is_active'] ? 'ambg' : 'grbg' ?>);color:var(--<?= $img['is_active'] ? 'am' : 'gr' ?>)" title="<?= $img['is_active'] ? 'Hide' : 'Show' ?>">
                <i class="fas fa-<?= $img['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
              </a>
              <a href="<?= htmlspecialchars($src) ?>" target="_blank" class="img-btn" style="background:var(--blbg);color:var(--bl)" title="View">
                <i class="fas fa-external-link-alt"></i>
              </a>
              <a href="?delete=<?= $img['id'] ?>" class="img-btn" style="background:var(--rdbg);color:var(--rd)" title="Delete" onclick="return confirm('Delete this image?')">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>
</div>

<script>
function toggleTheme(){const h=document.documentElement,n=h.getAttribute('data-theme')==='dark'?'light':'dark';h.setAttribute('data-theme',n);document.cookie='rv_theme='+n+';path=/;max-age=31536000';const i=document.getElementById('themeIcon');if(i)i.className='fas fa-'+(n==='dark'?'sun':'moon');}
function toggleSb(){const s=document.getElementById('sb');if(s)s.classList.toggle('open');}

function previewFiles(input) {
  const preview = document.getElementById('filePreview');
  preview.innerHTML = '';
  [...input.files].forEach(file => {
    const div = document.createElement('div');
    div.style.cssText = 'position:relative;width:70px';
    const isVid = file.type.startsWith('video/') || file.name.toLowerCase().endsWith('.mp4');
    
    if (isVid) {
      div.innerHTML = `<div style="width:70px;height:50px;background:#111;border-radius:6px;border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--gold)"><i class="fas fa-play-circle"></i></div><div style="font-size:9px;color:var(--mu);text-align:center;margin-top:3px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${file.name}</div>`;
      preview.appendChild(div);
    } else {
      const reader = new FileReader();
      reader.onload = e => {
        div.innerHTML = `<img src="${e.target.result}" style="width:70px;height:50px;object-fit:cover;border-radius:6px;border:2px solid var(--gold)"><div style="font-size:9px;color:var(--mu);text-align:center;margin-top:3px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${file.name}</div>`;
        preview.appendChild(div);
      };
      reader.readAsDataURL(file);
    }
  });
}
const dz = document.getElementById('dropZone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
dz.addEventListener('drop', e => {
  e.preventDefault(); dz.classList.remove('dragover');
  const fi = document.getElementById('fileInput');
  fi.files = e.dataTransfer.files;
  previewFiles(fi);
});
// Edit modal
function openEdit(id, title, cat) {
  document.getElementById('editId').value = id;
  document.getElementById('editTitle').value = title;
  document.getElementById('editCat').value = cat;
  document.getElementById('editModal').style.display = 'flex';
}
function closeEdit() { document.getElementById('editModal').style.display = 'none'; }
</script>

<!-- Edit Modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center" onclick="if(event.target===this)closeEdit()">
  <div style="background:var(--card);border:1px solid var(--br);border-radius:var(--rlg);padding:28px;width:380px;max-width:95vw">
    <h3 style="font-family:var(--cinzel);font-size:15px;margin-bottom:20px;color:var(--gold)">✏ Edit Media</h3>
    <form method="POST">
      <input type="hidden" name="do_edit" value="1">
      <input type="hidden" name="edit_id" id="editId">
      <div class="fg" style="margin-bottom:14px">
        <label class="fl">Title</label>
        <input class="fc" name="edit_title" id="editTitle" placeholder="Image/video title">
      </div>
      <div class="fg" style="margin-bottom:20px">
        <label class="fl">Category</label>
        <select class="fc" name="edit_cat" id="editCat">
          <?php foreach (['hotel','rooms','pool','restaurant','spa','bar','events','outdoor','facilities','lobby'] as $cat): ?>
          <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-gold" style="flex:1">Save Changes</button>
        <button type="button" class="btn btn-ghost" onclick="closeEdit()">Cancel</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>

