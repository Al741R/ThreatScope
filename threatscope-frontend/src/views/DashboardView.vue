<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAssetStore } from '@/stores/assetStore'
import { useScannerStore } from '@/stores/scannerStore'
import VulnerabilityCard from '@/components/VulnerabilityCard.vue'

const route = useRoute()
const router = useRouter()
const assetStore = useAssetStore()
const scannerStore = useScannerStore()

const lookupVendor = ref('')
const lookupProduct = ref('')
const severityFilter = ref('All')

const activeCount = computed(
  () => assetStore.assets.filter((a) => a.status === 'Active').length
)
const retiredCount = computed(
  () => assetStore.assets.filter((a) => a.status === 'Retired').length
)

const filteredVulnerabilities = computed(() => {
  const list = scannerStore.activeScan?.vulnerabilities ?? []
  if (severityFilter.value === 'All') {
    return list
  }
  return list.filter((v) => v.severity === severityFilter.value)
})

async function handleScanAsset(assetId, forceRefresh = false) {
  severityFilter.value = 'All'
  await scannerStore.scanAsset(assetId, forceRefresh)
}

async function handleQuickLookup() {
  severityFilter.value = 'All'
  await scannerStore.quickLookup(lookupVendor.value, lookupProduct.value)
}

function handleQueryScan(scanId) {
  if (!scanId) return
  const numericId = Number(scanId)
  if (!Number.isNaN(numericId)) {
    handleScanAsset(numericId)
    router.replace({ name: 'dashboard' })
  }
}

onMounted(async () => {
  if (assetStore.assets.length === 0) {
    await assetStore.fetchAssets()
  }
  handleQueryScan(route.query.scan)
})

watch(
  () => route.query.scan,
  (newScanId) => {
    handleQueryScan(newScanId)
  }
)
</script>

<template>
  <div class="page-stack">
    <div class="page-heading">
      <h1>Threat Dashboard</h1>
      <p>Scan your tracked software watchlist or run ad-hoc lookups against the CIRCL Vulnerability-Lookup API.</p>
    </div>

    <!-- Top Watchlist Stats -->
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

    <!-- Main Two-Column Scanner Workspace -->
    <div class="dashboard-grid">
      <!-- Left Column: Watchlist Targets & Quick Lookup -->
      <div class="scanner-sidebar">
        <section class="panel">
          <div class="panel-header">
            <h2>Watchlist Targets</h2>
            <RouterLink to="/assets" class="btn btn-sm btn-secondary">Manage</RouterLink>
          </div>

          <div v-if="assetStore.loading" class="state-box">Loading watchlist...</div>

          <div v-else-if="assetStore.assets.length === 0" class="state-box">
            No assets saved yet.
            <div class="empty-action">
              <RouterLink to="/assets" class="btn btn-sm btn-primary">Add Assets</RouterLink>
            </div>
          </div>

          <ul v-else class="target-list">
            <li
              v-for="asset in assetStore.assets"
              :key="asset.id"
              class="target-item"
              :class="{ 'target-active': scannerStore.activeAssetId === asset.id }"
            >
              <div class="target-meta">
                <div class="target-name">
                  <strong>{{ asset.vendor }}</strong> / {{ asset.product }}
                </div>
                <div class="target-sub">
                  <span>v{{ asset.version }}</span>
                  <span
                    class="badge"
                    :class="asset.status === 'Active' ? 'badge-active' : 'badge-retired'"
                  >
                    {{ asset.status }}
                  </span>
                </div>
              </div>

              <button
                type="button"
                class="btn btn-sm btn-primary"
                :disabled="scannerStore.scanning"
                @click="handleScanAsset(asset.id)"
              >
                {{
                  scannerStore.scanning && scannerStore.activeAssetId === asset.id
                    ? 'Scanning...'
                    : 'Scan'
                }}
              </button>
            </li>
          </ul>
        </section>

        <!-- Ad-Hoc Lookup Panel -->
        <section class="panel">
          <div class="panel-header">
            <h2>Quick Lookup</h2>
            <span class="count-pill">Live API</span>
          </div>

          <form class="quick-lookup-form" @submit.prevent="handleQuickLookup">
            <div class="form-group">
              <label for="lookup-vendor">Vendor</label>
              <input
                id="lookup-vendor"
                v-model="lookupVendor"
                type="text"
                placeholder="e.g. wordpress"
                required
              />
            </div>

            <div class="form-group">
              <label for="lookup-product">Product</label>
              <input
                id="lookup-product"
                v-model="lookupProduct"
                type="text"
                placeholder="e.g. wordpress"
                required
              />
            </div>

            <button
              type="submit"
              class="btn btn-primary"
              :disabled="scannerStore.scanning"
            >
              {{ scannerStore.scanning && !scannerStore.activeAssetId ? 'Searching...' : 'Search CIRCL' }}
            </button>
          </form>
        </section>
      </div>

      <!-- Right Column: Live Vulnerability Results -->
      <section class="panel results-panel">
        <div class="panel-header">
          <div>
            <h2>Scan Results</h2>
            <p v-if="scannerStore.activeScan" class="results-subtitle">
              Target:
              <code>{{ scannerStore.activeScan.query.vendor }}/{{ scannerStore.activeScan.query.product }}</code>
              <span v-if="scannerStore.activeScan.asset">
                (Tracked v{{ scannerStore.activeScan.asset.version }})
              </span>
            </p>
          </div>

          <div v-if="scannerStore.activeScan" class="results-actions">
            <button
              v-if="scannerStore.activeAssetId"
              type="button"
              class="btn btn-sm btn-secondary"
              :disabled="scannerStore.scanning"
              @click="handleScanAsset(scannerStore.activeAssetId, true)"
            >
              Rescan Live
            </button>
            <button
              type="button"
              class="btn btn-sm btn-secondary"
              @click="scannerStore.clearScan()"
            >
              Clear
            </button>
          </div>
        </div>

        <!-- Error State -->
        <div v-if="scannerStore.error" class="alert alert-error">
          {{ scannerStore.error }}
        </div>

        <!-- Loading State -->
        <div v-else-if="scannerStore.scanning" class="state-box loading-box">
          <div class="spinner"></div>
          <p>Querying CIRCL Vulnerability-Lookup API and parsing CVSS scores...</p>
        </div>

        <!-- Empty Initial State -->
        <div v-else-if="!scannerStore.activeScan" class="state-box">
          Select any software asset from your watchlist or run a quick lookup on the left to inspect live vulnerabilities.
        </div>

        <!-- Loaded Results -->
        <div v-else class="results-body">
          <!-- Queried Endpoint Metadata -->
          <div class="endpoint-bar">
            <span class="endpoint-label">CIRCL Endpoint:</span>
            <code class="endpoint-url">{{ scannerStore.activeScan.query.endpoint }}</code>
          </div>

          <!-- Interactive Severity Summary & Filter Pills -->
          <div class="severity-bar">
            <button
              type="button"
              class="filter-pill"
              :class="{ active: severityFilter === 'All' }"
              @click="severityFilter = 'All'"
            >
              All ({{ scannerStore.activeScan.summary.total }})
            </button>
            <button
              type="button"
              class="filter-pill filter-critical"
              :class="{ active: severityFilter === 'Critical' }"
              @click="severityFilter = 'Critical'"
            >
              Critical ({{ scannerStore.activeScan.summary.critical }})
            </button>
            <button
              type="button"
              class="filter-pill filter-high"
              :class="{ active: severityFilter === 'High' }"
              @click="severityFilter = 'High'"
            >
              High ({{ scannerStore.activeScan.summary.high }})
            </button>
            <button
              type="button"
              class="filter-pill filter-medium"
              :class="{ active: severityFilter === 'Medium' }"
              @click="severityFilter = 'Medium'"
            >
              Medium ({{ scannerStore.activeScan.summary.medium }})
            </button>
            <button
              type="button"
              class="filter-pill filter-low"
              :class="{ active: severityFilter === 'Low' }"
              @click="severityFilter = 'Low'"
            >
              Low ({{ scannerStore.activeScan.summary.low }})
            </button>
            <button
              v-if="scannerStore.activeScan.summary.unknown > 0"
              type="button"
              class="filter-pill filter-unknown"
              :class="{ active: severityFilter === 'Unknown' }"
              @click="severityFilter = 'Unknown'"
            >
              Unknown ({{ scannerStore.activeScan.summary.unknown }})
            </button>
          </div>

          <!-- Zero Vulnerabilities State -->
          <div
            v-if="scannerStore.activeScan.vulnerabilities.length === 0"
            class="state-box state-clean"
          >
            No known vulnerabilities were returned by CIRCL for
            <code>{{ scannerStore.activeScan.query.vendor }}/{{ scannerStore.activeScan.query.product }}</code>.
          </div>

          <!-- Filter Returned Empty -->
          <div v-else-if="filteredVulnerabilities.length === 0" class="state-box">
            No <strong>{{ severityFilter }}</strong> vulnerabilities found in the top results for this target.
          </div>

          <!-- Color-Coded Vulnerability Cards -->
          <div v-else class="vuln-list">
            <p class="results-note">
              Showing {{ filteredVulnerabilities.length }}
              {{ severityFilter === 'All' ? 'highest-severity' : severityFilter.toLowerCase() }}
              {{ filteredVulnerabilities.length === 1 ? 'record' : 'records' }}
              (sorted by CVSS score).
            </p>
            <VulnerabilityCard
              v-for="vuln in filteredVulnerabilities"
              :key="vuln.cve_id"
              :vulnerability="vuln"
            />
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
