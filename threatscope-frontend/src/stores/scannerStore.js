/** Manage live CIRCL API scans,
 * ad-hoc lookups, and client-side caching */

import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

export const useScannerStore = defineStore('scanner', () => {
  const activeScan = ref(null)
  const activeAssetId = ref(null)
  const scanning = ref(false)
  const error = ref(null)
  const cache = ref({})

  function clearError() {
    error.value = null
  }

  async function scanAsset(assetId, forceRefresh = false) {
    const cacheKey = `asset-${assetId}`
    activeAssetId.value = assetId
    clearError()

    if (!forceRefresh && cache.value[cacheKey]) {
      activeScan.value = cache.value[cacheKey]
      return { ok: true, cached: true }
    }

    scanning.value = true
    try {
      const { data } = await api.get(`/assets/${assetId}/scan`)
      activeScan.value = data
      cache.value[cacheKey] = data
      return { ok: true, cached: false }
    } catch (err) {
      activeScan.value = null
      error.value =
        err.response?.data?.message ||
        'Unable to reach the vulnerability scanner. Check that Laravel is running.'
      return { ok: false }
    } finally {
      scanning.value = false
    }
  }

  async function quickLookup(vendor, product) {
    const cleanVendor = vendor.trim().toLowerCase()
    const cleanProduct = product.trim().toLowerCase()

    if (!cleanVendor || !cleanProduct) {
      error.value = 'Both vendor and product are required for a quick lookup.'
      return { ok: false }
    }

    activeAssetId.value = null
    clearError()
    scanning.value = true

    try {
      const { data } = await api.get('/scan', {
        params: {
          vendor: cleanVendor,
          product: cleanProduct,
        },
      })
      activeScan.value = data
      return { ok: true }
    } catch (err) {
      activeScan.value = null
      error.value =
        err.response?.data?.message ||
        'Lookup failed. Verify the vendor/product names or try again.'
      return { ok: false }
    } finally {
      scanning.value = false
    }
  }

  function clearScan() {
    activeScan.value = null
    activeAssetId.value = null
    error.value = null
  }

  return {
    activeScan,
    activeAssetId,
    scanning,
    error,
    scanAsset,
    quickLookup,
    clearScan,
  }
})
