<script setup>
import { reactive, watch, computed } from 'vue'
import { useAssetStore } from '@/stores/assetStore'

const props = defineProps({
  editingAsset: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['saved', 'cancel'])
const assetStore = useAssetStore()

const form = reactive({
  vendor: '',
  product: '',
  version: '',
  status: 'Active',
})

const isEditing = computed(() => Boolean(props.editingAsset))

function resetForm() {
  form.vendor = ''
  form.product = ''
  form.version = ''
  form.status = 'Active'
}

watch(
  () => props.editingAsset,
  (asset) => {
    assetStore.clearErrors()
    if (asset) {
      form.vendor = asset.vendor
      form.product = asset.product
      form.version = asset.version
      form.status = asset.status
    } else {
      resetForm()
    }
  },
  { immediate: true }
)

async function handleSubmit() {
  const payload = {
    vendor: form.vendor,
    product: form.product,
    version: form.version,
    status: form.status,
  }

  const result = isEditing.value
    ? await assetStore.updateAsset(props.editingAsset.id, payload)
    : await assetStore.createAsset(payload)

  if (result.ok) {
    resetForm()
    emit('saved', result.message)
  }
}

function handleCancel() {
  resetForm()
  assetStore.clearErrors()
  emit('cancel')
}
</script>

<template>
  <section class="panel">
    <div class="panel-header">
      <h2>{{ isEditing ? 'Update Software Asset' : 'Add Software Asset' }}</h2>
      <span v-if="isEditing" class="badge badge-info">Editing #{{ editingAsset.id }}</span>
    </div>

    <form class="asset-form" @submit.prevent="handleSubmit">
      <div class="form-grid">
        <div class="form-group">
          <label for="vendor">Vendor</label>
          <input
            id="vendor"
            v-model="form.vendor"
            type="text"
            placeholder="e.g. microsoft, apache, php"
            required
          />
          <small v-if="assetStore.validationErrors.vendor" class="field-error">
            {{ assetStore.validationErrors.vendor[0] }}
          </small>
        </div>

        <div class="form-group">
          <label for="product">Product</label>
          <input
            id="product"
            v-model="form.product"
            type="text"
            placeholder="e.g. windows_10, http_server"
            required
          />
          <small v-if="assetStore.validationErrors.product" class="field-error">
            {{ assetStore.validationErrors.product[0] }}
          </small>
        </div>

        <div class="form-group">
          <label for="version">Version</label>
          <input
            id="version"
            v-model="form.version"
            type="text"
            placeholder="e.g. 2.4.59"
            required
          />
          <small v-if="assetStore.validationErrors.version" class="field-error">
            {{ assetStore.validationErrors.version[0] }}
          </small>
        </div>

        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" v-model="form.status">
            <option value="Active">Active</option>
            <option value="Retired">Retired</option>
          </select>
          <small v-if="assetStore.validationErrors.status" class="field-error">
            {{ assetStore.validationErrors.status[0] }}
          </small>
        </div>
      </div>

      <p class="form-hint">
        Tip: Use lowercase names with underscores for multi-word products (e.g. <code>windows_10</code>) to match CIRCL lookups.
      </p>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary" :disabled="assetStore.submitting">
          {{ assetStore.submitting ? 'Saving...' : isEditing ? 'Save Changes' : 'Add to Watchlist' }}
        </button>
        <button
          v-if="isEditing"
          type="button"
          class="btn btn-secondary"
          @click="handleCancel"
        >
          Cancel
        </button>
      </div>
    </form>
  </section>
</template>
