<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAssetStore } from '@/stores/assetStore'

const assetStore = useAssetStore()

onMounted(() => {
  if (assetStore.assets.length === 0) {
    assetStore.fetchAssets()
  }
})

const activeCount = computed(
  () => assetStore.assets.filter((a) => a.status === 'Active').length
)
const retiredCount = computed(
  () => assetStore.assets.filter((a) => a.status === 'Retired').length
)
</script>

<template>
  <div class="page-stack">
    <div class="page-heading">
      <h1>Threat Dashboard</h1>
      <p>Overview of tracked software assets and live CIRCL vulnerability scans.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-label">Total Tracked Assets</span>
        <strong class="stat-value">{{ assetStore.assets.length }}</strong>
      </div>
      <div class="stat-card">
        <span class="stat-label">Active Assets</span>
        <strong class="stat-value text-green">{{ activeCount }}</strong>
      </div>
      <div class="stat-card">
        <span class="stat-label">Retired Assets</span>
        <strong class="stat-value text-muted">{{ retiredCount }}</strong>
      </div>
    </div>

    <section class="panel">
      <div class="panel-header">
        <h2>Vulnerability Scanner</h2>
        <RouterLink to="/assets" class="btn btn-sm btn-primary">Manage Watchlist</RouterLink>
      </div>
      <div class="state-box">
        Asset CRUD is connected to Laravel. Will mount live CIRCL scanner and color-coded CVSS vulnerability cards right here. (Phase 4)
      </div>
    </section>
  </div>
</template>
