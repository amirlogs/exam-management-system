<script setup lang="ts">
import ToastContainer from '@/shared/components/ui/ToastContainer.vue'
import GlobalLoadingBar from '@/shared/components/ui/GlobalLoadingBar.vue'
import { useUiStore } from '@/stores/ui'


const uiStore = useUiStore()
</script>
<template>
    <div id="app">
        <router-view />
        <ToastContainer />
        <GlobalLoadingBar v-if="uiStore.isLoading" />
    </div>
<div v-if="authStore.isInitializing" class="min-h-screen flex items-center justify-center">
</template>
