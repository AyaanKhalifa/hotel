<?php $theme = getTheme(); $adminName = htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Admin'); ?>
<header class="adm-top">
  <div class="adm-top-left">
    <button class="adm-toggle" onclick="toggleSb()" id="sidebarToggle" title="Toggle sidebar">
      <i class="fas fa-bars"></i>
    </button>
    <a href="<?= BASE ?>/admin/index.php" class="adm-brand">
      <div class="adm-emblem">RV</div>
      <span class="adm-brand-name hide-sm">Royale Vista</span>
      <span class="adm-brand-badge hide-sm">Admin</span>
    </a>
  </div>

  <div class="adm-top-right">
    <a href="<?= BASE ?>/" class="adm-tbtn" target="_blank" title="View live site">
      <i class="fas fa-external-link-alt"></i>
      <span class="hide-sm">View Site</span>
    </a>
    <button class="adm-tbtn" onclick="toggleTheme()" title="Toggle theme">
      <i class="fas fa-<?= $theme === 'dark' ? 'sun' : 'moon' ?>" id="themeIcon"></i>
    </button>

    <!-- Admin profile dropdown -->
    <div class="adm-profile-drop" id="admProfileDrop">
      <button class="adm-profile-btn" onclick="toggleAdmProfile()">
        <?= userAvatar($_SESSION['profile_img'] ?? null, $adminName, 32) ?>
        <span class="hide-sm adm-profile-name"><?= htmlspecialchars(explode(' ', $adminName)[0]) ?></span>
        <i class="fas fa-chevron-down adm-profile-chevron hide-sm"></i>
      </button>
      <div class="adm-profile-panel" id="admProfilePanel">
        <div class="adm-profile-hd">
          <?= userAvatar($_SESSION['profile_img'] ?? null, $adminName, 46) ?>
          <div>
            <div class="adm-profile-hd-name"><?= $adminName ?></div>
            <div class="adm-profile-hd-role"><i class="fas fa-shield-alt" style="font-size:9px"></i> Administrator</div>
          </div>
        </div>
        <div class="adm-profile-divider"></div>
        <a href="<?= BASE ?>/profile.php" class="adm-profile-link" target="_blank">
          <i class="fas fa-user-circle"></i> My Profile
        </a>
        <a href="<?= BASE ?>/admin/settings.php" class="adm-profile-link">
          <i class="fas fa-gear"></i> Settings
        </a>
        <div class="adm-profile-divider"></div>
        <a href="<?= BASE ?>/admin/logout.php" class="adm-profile-link adm-profile-link-danger">
          <i class="fas fa-sign-out-alt"></i> Sign Out
        </a>
      </div>
    </div>
  </div>
</header>

<script>
function toggleAdmProfile(){
  const panel = document.getElementById('admProfilePanel');
  const isOpen = panel.classList.toggle('open');
  if(isOpen){
    document.addEventListener('click', function closeAdm(e){
      if(!e.target.closest('#admProfileDrop')){
        panel.classList.remove('open');
        document.removeEventListener('click', closeAdm);
      }
    });
  }
}
</script>
