<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="app-shell">
    <header class="navbar">
      <div class="navbar-inner">
        <RouterLink to="/" class="brand">
          <span class="brand-dot"></span>
          <span>ThreatScope</span>
        </RouterLink>

        <nav class="nav-links">
          <RouterLink to="/" class="nav-link">Dashboard</RouterLink>
          <RouterLink to="/assets" class="nav-link">Asset Watchlist</RouterLink>
        </nav>
      </div>
    </header>

    <main class="main-container">
      <RouterView />
    </main>
  </div>
</template>
