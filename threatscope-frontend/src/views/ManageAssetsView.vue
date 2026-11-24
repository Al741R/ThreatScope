<script setup>
import { ref, onMounted } from 'vue'
import { useAssetStore } from '@/stores/assetStore'
import AssetForm from '@/components/AssetForm.vue'
import AssetList from '@/components/AssetList.vue'

const assetStore = useAssetStore()
const editingAsset = ref(null)
const feedbackMessage = ref('')

onMounted(() => {
  assetStore.fetchAssets()
})

function showFeedback(msg) {
  feedbackMessage.value = msg
  setTimeout(() => {
    feedbackMessage.value = ''
  }, 3500)
}

function handleSaved(message) {
  editingAsset.value = null
  showFeedback(message || 'Asset saved.')
}

function handleEdit(asset) {
  editingAsset.value = { ...asset }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function handleDelete(asset) {
  const confirmed = window.confirm(
    `Remove ${asset.vendor} / ${asset.product} (${asset.version}) from your watchlist?`
  )
  if (!confirmed) return

  if (editingAsset.value?.id === asset.id) {
    editingAsset.value = null
  }

  const result = await assetStore.deleteAsset(asset.id)
  if (result.ok) {
    showFeedback(result.message || 'Asset deleted.')
  }
}
</script>

<template>
  <div class="page-stack">
    <div class="page-heading">
      <h1>Asset Watchlist</h1>
      <p>Manage internal software monitored by ThreatScope.</p>
    </div>

    <div v-if="feedbackMessage" class="alert alert-success">
      {{ feedbackMessage }}
    </div>

    <div v-if="assetStore.error" class="alert alert-error">
      {{ assetStore.error }}
    </div>

    <AssetForm
      :editing-asset="editingAsset"
      @saved="handleSaved"
      @cancel="editingAsset = null"
    />

    <AssetList
      :assets="assetStore.assets"
      :loading="assetStore.loading"
      @edit="handleEdit"
      @delete="handleDelete"
    />
  </div>
</template>
