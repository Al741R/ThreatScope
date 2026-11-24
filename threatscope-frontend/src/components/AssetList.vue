<script setup>
defineProps({
  assets: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['edit', 'delete'])
</script>

<template>
  <section class="panel">
    <div class="panel-header">
      <h2>Tracked Software Assets</h2>
      <span class="count-pill">{{ assets.length }} total</span>
    </div>

    <div v-if="loading" class="state-box">Loading asset watchlist...</div>

    <div v-else-if="assets.length === 0" class="state-box">
      No software assets tracked yet. Add your first asset above.
    </div>

    <div v-else class="table-wrapper">
      <table class="asset-table">
        <thead>
          <tr>
            <th>Vendor</th>
            <th>Product</th>
            <th>Version</th>
            <th>Status</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="asset in assets" :key="asset.id">
            <td><code>{{ asset.vendor }}</code></td>
            <td><code>{{ asset.product }}</code></td>
            <td>{{ asset.version }}</td>
            <td>
              <span
                class="badge"
                :class="asset.status === 'Active' ? 'badge-active' : 'badge-retired'"
              >
                {{ asset.status }}
              </span>
            </td>
            <td class="actions-cell">
              <button type="button" class="btn btn-sm btn-secondary" @click="emit('edit', asset)">
                Edit
              </button>
              <button type="button" class="btn btn-sm btn-danger" @click="emit('delete', asset)">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
