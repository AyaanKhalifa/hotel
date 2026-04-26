<?php
$unread = 0; $pending = 0;
try { $unread  = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(); } catch(Exception $e){}
try { $pending = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed' AND pay_status='pending'")->fetchColumn(); } catch(Exception $e){}
$currFile = basename($_SERVER['PHP_SELF']);
?>
<aside class="adm-sb" id="sb">
  <div class="sb-logo">
    <div class="adm-emblem">RV</div>
    <span class="adm-brand-name">Admin</span>
  </div>
  <nav class="sb-nav">
    <div class="sb-group">Overview</div>
    <a href="<?= BASE ?>/admin/index.php"     class="sb-link <?= $currFile === 'index.php'     ? 'on' : '' ?>"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
    <a href="<?= BASE ?>/admin/analytics.php" class="sb-link <?= $currFile === 'analytics.php' ? 'on' : '' ?>"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
    <a href="<?= BASE ?>/admin/reports.php"   class="sb-link <?= $currFile === 'reports.php'   ? 'on' : '' ?>"><i class="fas fa-file-chart-column"></i><span>Reports</span></a>

    <div class="sb-group">Operations</div>
    <a href="<?= BASE ?>/admin/bookings.php"      class="sb-link <?= $currFile === 'bookings.php'     ? 'on' : '' ?>"><i class="fas fa-calendar-check"></i><span>Bookings</span><?php if ($pending): ?><b class="sb-badge"><?= $pending ?></b><?php endif; ?></a>
    <a href="<?= BASE ?>/admin/allocations.php"   class="sb-link <?= $currFile === 'allocations.php'  ? 'on' : '' ?>"><i class="fas fa-door-open"></i><span>Allocations</span></a>
    <a href="<?= BASE ?>/admin/cancellations.php" class="sb-link <?= $currFile === 'cancellations.php'? 'on' : '' ?>"><i class="fas fa-ban"></i><span>Cancellations</span></a>
    <a href="<?= BASE ?>/admin/payments.php"      class="sb-link <?= $currFile === 'payments.php'     ? 'on' : '' ?>"><i class="fas fa-credit-card"></i><span>Payments</span></a>
    <a href="<?= BASE ?>/admin/rooms.php"         class="sb-link <?= $currFile === 'rooms.php'        ? 'on' : '' ?>"><i class="fas fa-bed"></i><span>Rooms</span></a>
    <a href="<?= BASE ?>/admin/room-facilities.php" class="sb-link <?= $currFile === 'room-facilities.php' ? 'on' : '' ?>"><i class="fas fa-list-check"></i><span>Room Facilities</span></a>
    <a href="<?= BASE ?>/admin/properties.php"    class="sb-link <?= $currFile === 'properties.php'   ? 'on' : '' ?>"><i class="fas fa-globe"></i><span>Properties</span></a>
    <a href="<?= BASE ?>/admin/hotel-properties.php" class="sb-link <?= $currFile === 'hotel-properties.php' ? 'on' : '' ?>"><i class="fas fa-map-marked-alt"></i><span>Map Properties</span></a>
    <a href="<?= BASE ?>/admin/event-catalog.php" class="sb-link <?= $currFile === 'event-catalog.php' ? 'on' : '' ?>"><i class="fas fa-calendar-plus"></i><span>Event Catalog</span></a>
    <a href="<?= BASE ?>/admin/pricing.php"       class="sb-link <?= $currFile === 'pricing.php'      ? 'on' : '' ?>"><i class="fas fa-tags"></i><span>Pricing</span></a>
    <a href="<?= BASE ?>/admin/dining.php"        class="sb-link <?= $currFile === 'dining.php'       ? 'on' : '' ?>"><i class="fas fa-utensils"></i><span>Dining Requests</span></a>
    <a href="<?= BASE ?>/admin/spa.php"           class="sb-link <?= $currFile === 'spa.php'          ? 'on' : '' ?>"><i class="fas fa-spa"></i><span>Spa Requests</span></a>
    <a href="<?= BASE ?>/admin/events.php"        class="sb-link <?= $currFile === 'events.php'       ? 'on' : '' ?>"><i class="fas fa-calendar-days"></i><span>Event Requests</span></a>
    <a href="<?= BASE ?>/admin/concierge.php"     class="sb-link <?= $currFile === 'concierge.php'    ? 'on' : '' ?>"><i class="fas fa-concierge-bell"></i><span>Concierge Requests</span></a>
    <a href="<?= BASE ?>/admin/services.php"      class="sb-link <?= $currFile === 'services.php'     ? 'on' : '' ?>"><i class="fas fa-bell-concierge"></i><span>Services Catalog</span></a>

    <div class="sb-group">Guests</div>
    <a href="<?= BASE ?>/admin/users.php"       class="sb-link <?= $currFile === 'users.php'       ? 'on' : '' ?>"><i class="fas fa-users"></i><span>Guests</span></a>
    <a href="<?= BASE ?>/admin/memberships.php" class="sb-link <?= $currFile === 'memberships.php' ? 'on' : '' ?>"><i class="fas fa-crown"></i><span>Memberships</span></a>
    <a href="<?= BASE ?>/admin/loyalty.php"     class="sb-link <?= $currFile === 'loyalty.php'     ? 'on' : '' ?>"><i class="fas fa-coins"></i><span>Loyalty</span></a>
    <?php $newJobs=0; try{$newJobs=(int)$pdo->query("SELECT COUNT(*) FROM job_applications WHERE status='new'")->fetchColumn();}catch(\Exception $e){} ?>
    <a href="<?= BASE ?>/admin/careers.php"     class="sb-link <?= $currFile === 'careers.php'     ? 'on' : '' ?>"><i class="fas fa-briefcase"></i><span>Job Applications</span><?php if($newJobs):?><b class="sb-badge"><?=$newJobs?></b><?php endif;?></a>


    <div class="sb-group">Marketing</div>
    <a href="<?= BASE ?>/admin/offers.php"   class="sb-link <?= $currFile === 'offers.php'   ? 'on' : '' ?>"><i class="fas fa-tag"></i><span>Offer Codes</span></a>
    <a href="<?= BASE ?>/admin/reviews.php"  class="sb-link <?= $currFile === 'reviews.php'  ? 'on' : '' ?>"><i class="fas fa-star"></i><span>Reviews</span></a>
    <a href="<?= BASE ?>/admin/messages.php" class="sb-link <?= $currFile === 'messages.php' ? 'on' : '' ?>"><i class="fas fa-envelope"></i><span>Messages</span><?php if ($unread): ?><b class="sb-badge"><?= $unread ?></b><?php endif; ?></a>
    <a href="<?= BASE ?>/admin/newsletter.php" class="sb-link <?= $currFile === 'newsletter.php' ? 'on' : '' ?>"><i class="fas fa-at"></i><span>Newsletter</span></a>
    <a href="<?= BASE ?>/admin/gallery.php"  class="sb-link <?= $currFile === 'gallery.php'  ? 'on' : '' ?>"><i class="fas fa-images"></i><span>Gallery</span></a>
    <a href="<?= BASE ?>/admin/experiences.php" class="sb-link <?= $currFile === 'experiences.php' ? 'on' : '' ?>"><i class="fas fa-mountain-sun"></i><span>Experiences</span></a>

    <div class="sb-group">System</div>
    <a href="<?= BASE ?>/admin/notifications.php" class="sb-link <?= $currFile === 'notifications.php' ? 'on' : '' ?>"><i class="fas fa-bell"></i><span>Notifications</span></a>
    <a href="<?= BASE ?>/admin/database.php"      class="sb-link <?= $currFile === 'database.php'      ? 'on' : '' ?>"><i class="fas fa-database"></i><span>Database Explorer</span></a>
    <a href="<?= BASE ?>/admin/coverage.php"      class="sb-link <?= $currFile === 'coverage.php'      ? 'on' : '' ?>"><i class="fas fa-diagram-project"></i><span>Coverage Dashboard</span></a>
    <a href="<?= BASE ?>/admin/settings.php"      class="sb-link <?= $currFile === 'settings.php'      ? 'on' : '' ?>"><i class="fas fa-gear"></i><span>Settings</span></a>

    <div class="sb-sep"></div>
    <a href="<?= BASE ?>/" class="sb-link" target="_blank"><i class="fas fa-external-link-alt"></i><span>View Hotel</span></a>
    <a href="<?= BASE ?>/admin/logout.php" class="sb-link sb-logout"><i class="fas fa-sign-out-alt"></i><span>Sign Out</span></a>
  </nav>
</aside>
