<template>
  <aside class="left-navigation">
    <div class="sidebar-section">
      <nav class="navigation-menu">
        <ul class="menu-list">
          <li class="menu-item">
            <router-link to="/" class="menu-link">
              <i class="icon fas fa-tachometer-alt"></i>
              <span class="link-text">ダッシュボード</span>
            </router-link>
          </li>
          <li class="menu-item">
            <router-link to="/events" class="menu-link">
              <i class="icon fas fa-calendar-alt"></i>
              <span class="link-text">イベント</span>
            </router-link>
          </li>
          <!-- <li class="menu-item">
            <router-link to="/settings" class="menu-link">
              <i class="icon fas fa-cogs"></i>
              <span class="link-text">設定</span>
            </router-link>
          </li> -->
        </ul>
      </nav>
    </div>
  </aside>
</template>

<script>
export default {
  name: 'LeftNavigation',
};
</script>