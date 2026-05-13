<script setup>
import AppSidebar from '@/components/AppSidebar.vue'

defineProps({
  title: String,
})
</script>

<template>
  <div class="app-layout">
    <AppSidebar mode="admin" />
    <main class="main-content">
      <div class="page-header">
        <h1 class="page-title">{{ title }}</h1>
        <slot name="header-actions" />
      </div>
      <div class="page-body">
        <slot />
      </div>
    </main>
  </div>
</template>
