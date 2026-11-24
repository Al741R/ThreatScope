/* Handles all local CRUD state
(GET, POST, PUT, DELETE)
and Laravel validation errors*/

import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAssetStore = defineStore('assets', () => {
  const assets = ref([])
  const loading = ref(false)
  const submitting = ref(false)
  const error = ref(null)
  const validationErrors = ref({})

  function clearErrors() {
    error.value = null
    validationErrors.value = {}
  }

  async function fetchAssets() {
    loading.value = true
    clearErrors()
    try {
      const { data } = await api.get('/assets')
      assets.value = data.data ?? []
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load assets from server.'
    } finally {
      loading.value = false
    }
  }

  async function createAsset(payload) {
    submitting.value = true
    clearErrors()
    try {
      const { data } = await api.post('/assets', payload)
      assets.value.unshift(data.data)
      return { ok: true, message: data.message }
    } catch (err) {
      if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors || {}
      }
      error.value = err.response?.data?.message || 'Failed to add asset.'
      return { ok: false }
    } finally {
      submitting.value = false
    }
  }

  async function updateAsset(id, payload) {
    submitting.value = true
    clearErrors()
    try {
      const { data } = await api.put(`/assets/${id}`, payload)
      const index = assets.value.findIndex((item) => item.id === id)
      if (index !== -1) {
        assets.value[index] = data.data
      }
      return { ok: true, message: data.message }
    } catch (err) {
      if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors || {}
      }
      error.value = err.response?.data?.message || 'Failed to update asset.'
      return { ok: false }
    } finally {
      submitting.value = false
    }
  }

  async function deleteAsset(id) {
    clearErrors()
    try {
      const { data } = await api.delete(`/assets/${id}`)
      assets.value = assets.value.filter((item) => item.id !== id)
      return { ok: true, message: data.message }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete asset.'
      return { ok: false }
    }
  }

  return {
    assets,
    loading,
    submitting,
    error,
    validationErrors,
    clearErrors,
    fetchAssets,
    createAsset,
    updateAsset,
    deleteAsset,
  }
})
