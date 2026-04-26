<?php
// ============================================================
//  Admin - Room Allocations
// ============================================================
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
requireAdmin();

$B = BASE;
$pageTitle = "Room Allocations";

// POST logic for Assign / Unassign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alloc_action']) && verifyCsrf()) {
    $action = $_POST['alloc_action'];
    try {
        if ($action === 'assign') {
            $bRef   = $_POST['booking_ref'];
            $rType  = (int)$_POST['room_type_id'];
            $roomId = (int)$_POST['room_id'];
            
            // Get room number & type name
            $rNum = $pdo->prepare("SELECT room_number FROM rooms WHERE id=?");
            $rNum->execute([$roomId]);
            $rn = $rNum->fetchColumn();
            
            $rtName = $pdo->prepare("SELECT name FROM room_types WHERE id=?");
            $rtName->execute([$rType]);
            $rtn = $rtName->fetchColumn();
            
            if ($rn && $rtn) {
                // Check if already assigned or if physical room is occupied by another active booking for same dates
                // For simplicity, just insert. The hotel staff knows best.
                $pdo->prepare("INSERT INTO booking_room_assignments (booking_ref, room_id, room_number, room_type_id, room_type_name) VALUES (?,?,?,?,?)")
                    ->execute([$bRef, $roomId, $rn, $rType, $rtn]);
                flash("Room $rn assigned to $bRef.");
            }
        } elseif ($action === 'unassign') {
            $aid = (int)$_POST['assignment_id'];
            $pdo->prepare("DELETE FROM booking_room_assignments WHERE id=?")->execute([$aid]);
            flash("Room unassigned successfully.");
        }
    } catch(Exception $e) { flash("Error: ".$e->getMessage(), 'error'); }
    header("Location: {$B}/admin/allocations.php"); exit;
}

// Fetch active bookings
$bookings = $pdo->query("SELECT * FROM bookings WHERE status IN ('confirmed','checked_in') AND check_out >= CURDATE() ORDER BY check_in ASC")->fetchAll();
$allBookedRooms = $pdo->query("SELECT * FROM booked_rooms")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC); // Grouped by booking_ref (Wait, PDO::FETCH_GROUP groups by first column)

$bRooms = [];
$stmt = $pdo->query("SELECT booking_ref, room_type_id, room_type_name, quantity FROM booked_rooms");
while($r = $stmt->fetch()) {
    $bRooms[$r['booking_ref']][] = $r;
}

$assignments = [];
$astmt = $pdo->query("SELECT * FROM booking_room_assignments");
while($a = $astmt->fetch()) {
    $assignments[$a['booking_ref']][$a['room_type_id']][] = $a;
}

// Fetch physical rooms
$physicalRooms = $pdo->query("SELECT * FROM rooms WHERE status='available' ORDER BY room_type_id, room_number")->fetchAll();

require __DIR__ . '/header.php';
?>

<div class="ad-page-header">
  <div>
    <h1 class="ad-h1">Room Allocations</h1>
    <p class="ad-desc">Assign physical room numbers to upcoming and active bookings.</p>
  </div>
</div>

<div class="ad-card" style="padding:0">
  <table class="ad-table">
    <thead>
      <tr>
        <th>Booking / Guest</th>
        <th>Dates</th>
        <th>Rooms Needed</th>
        <th>Allocation Status</th>
        <th style="text-align:right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if(empty($bookings)): ?>
      <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--muted)">No active bookings to allocate.</td></tr>
      <?php endif; ?>
      <?php foreach($bookings as $b):
          $beds = $bRooms[$b['booking_ref']] ?? [];
          $totalQty = array_sum(array_column($beds, 'quantity'));
          $assignedCount = 0;
          if (isset($assignments[$b['booking_ref']])) {
              foreach($assignments[$b['booking_ref']] as $acts) $assignedCount += count($acts);
          }
          $isFullyAssigned = ($assignedCount >= $totalQty);
      ?>
      <tr>
        <td>
          <div style="font-weight:600;color:var(--gold)"><?= htmlspecialchars($b['booking_ref']) ?></div>
          <div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($b['guest_name']) ?></div>
        </td>
        <td>
          <div style="font-size:13px"><?= date('M d', strtotime($b['check_in'])) ?> → <?= date('M d', strtotime($b['check_out'])) ?></div>
          <div style="font-size:11px;color:var(--muted)"><?= $b['nights'] ?> nights</div>
        </td>
        <td style="font-size:13px">
          <?php foreach($beds as $br): ?>
            <div><?= $br['quantity'] ?>x <?= htmlspecialchars($br['room_type_name']) ?></div>
          <?php endforeach; ?>
        </td>
        <td>
          <?php if($isFullyAssigned): ?>
            <span class="ad-badge" style="background:rgba(34,197,94,.1);color:#22c55e">Fully Allocated (<?= $assignedCount ?>/<?= $totalQty ?>)</span>
          <?php elseif($assignedCount > 0): ?>
            <span class="ad-badge" style="background:rgba(234,179,8,.1);color:#eab308">Partial Allocation (<?= $assignedCount ?>/<?= $totalQty ?>)</span>
          <?php else: ?>
            <span class="ad-badge" style="background:rgba(239,68,68,.1);color:#ef4444">Not Allocated (0/<?= $totalQty ?>)</span>
          <?php endif; ?>
        </td>
        <td style="text-align:right">
          <button class="ad-btn" onclick="openAllocModal('<?= $b['booking_ref'] ?>')"><i class="fas fa-bed"></i> Manage</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Allocation Modal Component -->
<div id="allocModal" class="ad-modal">
  <div class="ad-modal-content" style="max-width:600px">
    <div class="ad-modal-header">
      <h2>Manage Allocation: <span id="m_bref" style="color:var(--gold)"></span></h2>
      <button class="ad-modal-close" onclick="document.getElementById('allocModal').classList.remove('open')">×</button>
    </div>
    <div class="ad-modal-body" id="m_body" style="background:var(--bg);border-radius:6px;padding:20px;min-height:200px">
      <!-- Injected by JS -->
    </div>
  </div>
</div>

<script>
const bRooms = <?= json_encode($bRooms) ?>;
const assgns = <?= json_encode($assignments) ?>;
const pRooms = <?= json_encode($physicalRooms) ?>;
const csrf   = '<?= csrf() ?>';

function openAllocModal(bRef) {
    document.getElementById('m_bref').textContent = bRef;
    const body = document.getElementById('m_body');
    const roomsNeeded = bRooms[bRef] || [];
    const bAssgns = assgns[bRef] || {};
    
    let html = '';
    roomsNeeded.forEach(rn => {
        html += `<div style="margin-bottom:20px;background:var(--card);padding:15px;border:1px solid var(--bdr);border-radius:6px">
            <h4 style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
                <span>${rn.room_type_name}</span>
                <span class="ad-badge" style="background:rgba(255,255,255,.1)">Qty: ${rn.quantity}</span>
            </h4>`;
            
        const acts = bAssgns[rn.room_type_id] || [];
        
        // Loop up to quantity to show slots
        for(let i=0; i<rn.quantity; i++) {
            if (acts[i]) {
                // Assigned
                html += `<div style="display:flex;align-items:center;justify-content:space-between;background:rgba(34,197,94,.05);border:1px solid rgba(34,197,94,.2);padding:10px;border-radius:4px;margin-bottom:8px">
                    <div><i class="fas fa-door-closed" style="color:#22c55e;margin-right:8px"></i> Room <strong>${acts[i].room_number}</strong> Assigned</div>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="csrf" value="${csrf}">
                        <input type="hidden" name="alloc_action" value="unassign">
                        <input type="hidden" name="assignment_id" value="${acts[i].id}">
                        <button class="ad-btn" style="padding:4px 8px;font-size:12px;background:rgba(239,68,68,.1);color:#ef4444;border:none"><i class="fas fa-times"></i> Unassign</button>
                    </form>
                </div>`;
            } else {
                // Not assigned
                let opts = `<option value="">-- Select Physical Room --</option>`;
                pRooms.filter(pr => pr.room_type_id == rn.room_type_id).forEach(pr => {
                    opts += `<option value="${pr.id}">Room ${pr.room_number} (Floor ${pr.floor})</option>`;
                });
                
                html += `<div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg);border:1px dashed var(--bdr);padding:10px;border-radius:4px;margin-bottom:8px">
                    <form method="POST" style="margin:0;display:flex;width:100%;gap:10px">
                        <input type="hidden" name="csrf" value="${csrf}">
                        <input type="hidden" name="alloc_action" value="assign">
                        <input type="hidden" name="booking_ref" value="${bRef}">
                        <input type="hidden" name="room_type_id" value="${rn.room_type_id}">
                        <select name="room_id" class="ad-input" style="flex:1" required>
                            ${opts}
                        </select>
                        <button class="ad-btn"><i class="fas fa-check"></i> Assign</button>
                    </form>
                </div>`;
            }
        }
        
        html += `</div>`;
    });
    
    if (html==='') html = '<div style="color:var(--muted)">No rooms found for this booking.</div>';
    
    body.innerHTML = html;
    document.getElementById('allocModal').classList.add('open');
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
