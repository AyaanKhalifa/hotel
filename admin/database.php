<?php
error_reporting(E_ALL); ini_set('display_errors', '1');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
requireAdmin();
require_once __DIR__ . '/_helper.php';

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) ?: [];
$table = clean($_GET['table'] ?? ($tables[0] ?? ''));
if (!in_array($table, $tables, true)) {
    $table = $tables[0] ?? '';
}

$msg = '';
$pk = null;
if ($table) {
    $pkStmt = $pdo->query("SHOW KEYS FROM `{$table}` WHERE Key_name='PRIMARY'");
    $pkRow = $pkStmt->fetch();
    $pk = $pkRow['Column_name'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_row']) && $table && $pk) {
    $id = $_POST['row_id'] ?? null;
    if ($id !== null && $id !== '') {
        $pdo->prepare("DELETE FROM `{$table}` WHERE `{$pk}` = ?")->execute([$id]);
        $msg = "Row deleted from {$table}.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_row']) && $table) {
    $editId = $_POST['edit_id'] ?? '';
    $metaCols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
    $insertCols = []; $insertVals = []; $insertParams = [];
    $updateSets = []; $updateParams = [];
    foreach ($metaCols as $c) {
        $field = $c['Field'];
        $isPk = ($pk && $field === $pk);
        $isAuto = strpos(strtolower($c['Extra'] ?? ''), 'auto_increment') !== false;
        if ($isPk && $isAuto && $editId === '') { continue; }
        if ($isPk && $editId !== '') { continue; }
        $val = $_POST['col'][$field] ?? null;
        $val = ($val === '') ? null : $val;
        if ($editId === '') {
            $insertCols[] = "`{$field}`";
            $insertVals[] = '?';
            $insertParams[] = $val;
        } else {
            $updateSets[] = "`{$field}`=?";
            $updateParams[] = $val;
        }
    }
    if ($editId === '') {
        if (!empty($insertCols)) {
            $sql = "INSERT INTO `{$table}` (".implode(',', $insertCols).") VALUES (".implode(',', $insertVals).")";
            $pdo->prepare($sql)->execute($insertParams);
            $msg = "Row inserted into {$table}.";
        }
    } else if ($pk) {
        if (!empty($updateSets)) {
            $sql = "UPDATE `{$table}` SET ".implode(',', $updateSets)." WHERE `{$pk}`=?";
            $updateParams[] = $editId;
            $pdo->prepare($sql)->execute($updateParams);
            $msg = "Row updated in {$table}.";
        }
    }
}

$tableStats = [];
foreach ($tables as $tb) {
    try {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$tb}`")->fetchColumn();
    } catch (Exception $e) {
        $count = 0;
    }
    $tableStats[$tb] = $count;
}

$columns = [];
$rows = [];
$total = 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;
$q = trim((string)($_GET['q'] ?? ''));

if ($table) {
    $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
    $where = '';
    $params = [];
    if ($q !== '' && !empty($columns)) {
        $searchParts = [];
        foreach ($columns as $col) {
            $searchParts[] = "CAST(`{$col['Field']}` AS CHAR) LIKE ?";
            $params[] = "%{$q}%";
        }
        $where = "WHERE (" . implode(' OR ', $searchParts) . ")";
    }
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` {$where}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT * FROM `{$table}` {$where} LIMIT {$limit} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

$totalPages = max(1, (int)ceil($total / $limit));

ob_start(); ?>
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="adm-ph">
  <div><h1>Database Explorer</h1><p class="sub">All tables · all columns · all rows (paginated)</p></div>
</div>

<div class="mc-grid mc-3" style="margin-bottom:18px">
  <div class="mc"><div class="mc-ico"><i class="fas fa-database"></i></div><div><div class="mc-v"><?= count($tables) ?></div><div class="mc-l">Tables</div></div></div>
  <div class="mc"><div class="mc-ico"><i class="fas fa-table"></i></div><div><div class="mc-v"><?= $table ? ($tableStats[$table] ?? 0) : 0 ?></div><div class="mc-l">Rows In Selected</div></div></div>
  <div class="mc"><div class="mc-ico"><i class="fas fa-columns"></i></div><div><div class="mc-v"><?= count($columns) ?></div><div class="mc-l">Columns</div></div></div>
</div>

<div class="ac" style="margin-bottom:16px">
  <div class="ac-body" style="padding:14px 18px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <select class="fc" name="table" style="min-width:240px" onchange="this.form.submit()">
        <?php foreach ($tables as $tb): ?>
          <option value="<?= htmlspecialchars($tb) ?>" <?= $tb === $table ? 'selected' : '' ?>><?= htmlspecialchars($tb) ?> (<?= (int)$tableStats[$tb] ?>)</option>
        <?php endforeach; ?>
      </select>
      <input class="fc" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search selected table..." style="min-width:280px">
      <button class="btn btn-gold btn-sm" type="submit"><i class="fas fa-search"></i> Search</button>
      <?php if ($table): ?><button type="button" class="btn btn-ghost btn-sm" onclick="openAddRow()"><i class="fas fa-plus"></i> Add Row</button><?php endif; ?>
    </form>
  </div>
</div>

<?php if ($table): ?>
<div class="ac" style="margin-bottom:16px">
  <div class="ac-body" style="padding:14px 18px">
    <div style="font-size:14px;margin-bottom:8px"><strong>Table:</strong> <code><?= htmlspecialchars($table) ?></code></div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <?php foreach ($columns as $c): ?>
        <span class="badge <?= $c['Key'] === 'PRI' ? 'bgold' : 'bb' ?>" title="<?= htmlspecialchars($c['Type']) ?>">
          <?= htmlspecialchars($c['Field']) ?><?= $c['Key'] === 'PRI' ? ' (PK)' : '' ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="ac">
  <div class="tw"><table>
    <thead>
      <tr>
        <?php foreach ($columns as $c): ?><th><?= htmlspecialchars($c['Field']) ?></th><?php endforeach; ?>
        <?php if ($pk): ?><th>Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="<?= count($columns) + ($pk ? 1 : 0) ?>" style="text-align:center;padding:40px;color:var(--mu)">No rows found.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <?php foreach ($columns as $c): $v = $r[$c['Field']] ?? ''; ?>
            <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars((string)$v) ?>">
              <?= htmlspecialchars((string)$v) ?>
            </td>
          <?php endforeach; ?>
          <?php if ($pk): ?>
          <td>
            <div style="display:flex;gap:4px">
              <button class="btn btn-ghost btn-sm" type="button" onclick='openEditRow(<?= json_encode($r) ?>)'><i class="fas fa-edit"></i></button>
              <form method="POST" onsubmit="return confirm('Delete this row?')">
                <input type="hidden" name="delete_row" value="1">
                <input type="hidden" name="row_id" value="<?= htmlspecialchars((string)$r[$pk]) ?>">
                <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>

<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:16px">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a class="btn <?= $p === $page ? 'btn-gold' : 'btn-ghost' ?> btn-sm" href="?table=<?= urlencode($table) ?>&q=<?= urlencode($q) ?>&page=<?= $p ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<script>
const DB_TABLE = <?= json_encode($table) ?>;
const DB_PK = <?= json_encode($pk) ?>;
const DB_COLS = <?= json_encode($columns) ?>;

function openAddRow(){ renderRowForm(null); }
function openEditRow(row){ renderRowForm(row); }
function esc(v){
  return String(v ?? '')
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}
function renderRowForm(row){
  const fields = DB_COLS.map(c => {
    const isPk = DB_PK && c.Field === DB_PK;
    const isAuto = (c.Extra || '').toLowerCase().includes('auto_increment');
    const type = String(c.Type || '').toLowerCase();
    const isText = type.includes('text');
    const isDate = type.startsWith('date') || type.startsWith('timestamp') || type.startsWith('datetime');
    const isNum = type.includes('int') || type.includes('decimal') || type.includes('float') || type.includes('double');
    if (!row && isPk && isAuto) return '';
    const v = row ? (row[c.Field] ?? '') : '';
    const dis = (row && isPk) ? 'readonly' : '';
    if (isText) {
      return `<div class="fg" style="grid-column:1/-1"><label class="fl">${esc(c.Field)}${isPk?' (PK)':''}</label><textarea class="fc" name="col[${esc(c.Field)}]" ${dis} rows="4">${esc(v)}</textarea></div>`;
    }
    const inputType = isNum ? 'number' : (isDate ? 'text' : 'text');
    return `<div class="fg"><label class="fl">${esc(c.Field)}${isPk?' (PK)':''}</label><input class="fc" type="${inputType}" name="col[${esc(c.Field)}]" value="${esc(v)}" ${dis}></div>`;
  }).join('');
  const editId = row && DB_PK ? row[DB_PK] : '';
  openModal(`<div class="adm-modal adm-modal-lg"><div class="adm-modal-hd"><div class="adm-modal-title">${row?'Edit':'Add'} Row · ${DB_TABLE}</div><button class="adm-modal-x" onclick="closeModal()">×</button></div><form method="POST"><input type="hidden" name="save_row" value="1"><input type="hidden" name="edit_id" value="${editId}"><div class="adm-modal-bd" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">${fields}</div><div class="adm-modal-ft"><button type="button" class="btn btn-ghost btn-sm" onclick="closeModal()">Cancel</button><button class="btn btn-gold btn-sm" type="submit">Save</button></div></form></div>`);
}
</script>
<?php
$body = ob_get_clean();
adminPage('Database Explorer', $body);

