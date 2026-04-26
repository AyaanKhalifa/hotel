<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$map = [
  ['client'=>'rooms.php',         'tables'=>'room_types, rooms, room_images, room_facilities',                 'admin'=>'rooms.php, room-facilities.php, database.php',   'status'=>'covered'],
  ['client'=>'reviews.php',       'tables'=>'room_ratings, review_media',                                      'admin'=>'reviews.php, database.php',                       'status'=>'covered'],
  ['client'=>'bookings.php',      'tables'=>'bookings, booked_rooms, booking_room_assignments',               'admin'=>'bookings.php, allocations.php, payments.php',     'status'=>'covered'],
  ['client'=>'events.php',        'tables'=>'events, event_bookings',                                           'admin'=>'event-catalog.php, events.php',                   'status'=>'covered'],
  ['client'=>'dining.php',        'tables'=>'dining_reservations',                                               'admin'=>'dining.php, database.php',                        'status'=>'covered'],
  ['client'=>'spa.php',           'tables'=>'spa_appointments',                                                  'admin'=>'spa.php, database.php',                           'status'=>'covered'],
  ['client'=>'concierge.php',     'tables'=>'concierge_requests',                                                'admin'=>'concierge.php, database.php',                     'status'=>'covered'],
  ['client'=>'experiences.php',   'tables'=>'experiences',                                                       'admin'=>'experiences.php, database.php',                   'status'=>'covered'],
  ['client'=>'locations.php',     'tables'=>'hotel_properties',                                                  'admin'=>'hotel-properties.php, database.php',              'status'=>'covered'],
  ['client'=>'properties.php',    'tables'=>'properties, property_images, property_amenities',                 'admin'=>'properties.php, database.php',                    'status'=>'partial'],
  ['client'=>'gallery.php',       'tables'=>'gallery_images',                                                    'admin'=>'gallery.php, database.php',                       'status'=>'covered'],
  ['client'=>'offers.php',        'tables'=>'offers',                                                            'admin'=>'offers.php, database.php',                        'status'=>'covered'],
  ['client'=>'gift-cards.php',    'tables'=>'gift_cards, gift_card_usage',                                      'admin'=>'gift-cards.php, database.php',                    'status'=>'covered'],
  ['client'=>'my-gift-cards.php', 'tables'=>'gift_cards, gift_card_usage',                                      'admin'=>'gift-cards.php, database.php',                    'status'=>'covered'],
  ['client'=>'membership.php',    'tables'=>'memberships, membership_features, user_memberships',              'admin'=>'memberships.php, database.php',                   'status'=>'covered'],
  ['client'=>'loyalty.php',       'tables'=>'loyalty_points, loyalty_transactions',                             'admin'=>'loyalty.php, database.php',                       'status'=>'covered'],
  ['client'=>'notifications.php', 'tables'=>'notifications',                                                     'admin'=>'notifications.php, database.php',                 'status'=>'covered'],
  ['client'=>'contact.php',       'tables'=>'contact_messages',                                                  'admin'=>'messages.php, database.php',                      'status'=>'covered'],
  ['client'=>'services.php',      'tables'=>'services_catalog',                                                  'admin'=>'services.php, database.php',                      'status'=>'covered'],
];

$tot = count($map);
$covered = count(array_filter($map, fn($m)=>$m['status']==='covered'));
$partial = count(array_filter($map, fn($m)=>$m['status']==='partial'));
$notdb = count(array_filter($map, fn($m)=>$m['status']==='not_db'));

ob_start(); ?>
<div class="adm-ph">
  <div><h1>Coverage Dashboard</h1><p class="sub">Client page to admin management mapping</p></div>
  <a class="btn btn-gold btn-sm" href="<?= BASE ?>/admin/database.php"><i class="fas fa-database"></i> Open DB Explorer</a>
</div>

<div class="mc-grid mc-3" style="margin-bottom:16px">
  <div class="mc"><div class="mc-ico"><i class="fas fa-sitemap"></i></div><div><div class="mc-v"><?= $tot ?></div><div class="mc-l">Client Modules</div></div></div>
  <div class="mc"><div class="mc-ico"><i class="fas fa-check-circle"></i></div><div><div class="mc-v"><?= $covered ?></div><div class="mc-l">Fully Covered</div></div></div>
  <div class="mc"><div class="mc-ico"><i class="fas fa-exclamation-triangle"></i></div><div><div class="mc-v"><?= $partial ?></div><div class="mc-l">Partial Coverage</div></div></div>
</div>

<div class="ac"><div class="tw"><table>
  <thead><tr><th>Client Page</th><th>DB Tables</th><th>Admin Management</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach($map as $row):
    $badge = $row['status']==='covered' ? 'bg' : ($row['status']==='partial' ? 'ba' : 'bb');
    $label = $row['status']==='covered' ? 'Covered' : ($row['status']==='partial' ? 'Partial' : 'Not DB');
  ?>
  <tr>
    <td><a href="<?= BASE ?>/<?= htmlspecialchars($row['client']) ?>" target="_blank"><code><?= htmlspecialchars($row['client']) ?></code></a></td>
    <td style="font-size:12.5px"><?= htmlspecialchars($row['tables']) ?></td>
    <td style="font-size:12.5px"><?= htmlspecialchars($row['admin']) ?></td>
    <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>

<?php if($notdb>0): ?>
<div class="ac" style="margin-top:12px">
  <div class="ac-body" style="padding:14px 18px;font-size:13px;color:var(--mu)">
    <strong>Note:</strong> some pages are static/content-only and are marked as not DB-backed.
  </div>
</div>
<?php endif; ?>
<?php
$body = ob_get_clean();
adminPage('Coverage Dashboard — Admin', $body);

