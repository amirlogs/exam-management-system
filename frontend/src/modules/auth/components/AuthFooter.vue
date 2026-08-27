<script setup lang="ts">
withDefaults(defineProps<{ orgName?: string; divider?: boolean }>(), {
  orgName: 'State University',
  divider: false,
});
const year = new Date().getFullYear();
</script>

<template>
  <div class="w-full">
    <hr v-if="divider" class="border-border mb-6" />
    <div class="text-center text-sm text-text/50">
      <p>{{ orgName }} © {{ year }}. All rights reserved.</p>
      <div class="flex items-center justify-center gap-2 mt-1">
        <router-link to="/privacy" class="font-semibold text-text/70 hover:text-accent transition-colors"> Privacy Policy </router-link>
        <span class="w-1 h-1 rounded-full bg-text/30" />
        <router-link to="/terms" class="font-semibold text-text/70 hover:text-accent transition-colors"> Terms of Service </router-link>
        <template v-if="$slots.default">
          <span class="w-1 h-1 rounded-full bg-text/30" />
          <slot />
        </template>
      </div>
    </div>
  </div>
</template>
