<script setup lang="ts">
import type { Component } from 'vue';
import { X } from 'lucide-vue-next';
import BaseButton from './ui/BaseButton.vue';

defineProps<{
  show: boolean;
  title: string;
  description: string;
  confirmText: string;
  variant?: 'danger' | 'accent';
  icon?: Component;
  loading?: boolean;
}>();

const emit = defineEmits<{ close: []; confirm: [] }>();
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="emit('close')">
      <div class="w-full max-w-md rounded-md border border-border bg-surface p-5" role="dialog" aria-modal="true">
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-start gap-3">
            <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-md', variant === 'danger' ? 'bg-error/10 text-error' : 'bg-accent/10 text-accent']">
              <component :is="icon" v-if="icon" class="h-4 w-4" />
            </div>
            <div>
              <h2 class="text-base font-semibold text-text">{{ title }}</h2>
              <p class="mt-1 text-sm leading-5 text-text/60">{{ description }}</p>
            </div>
          </div>
          <button class="shrink-0 rounded-md p-1 text-text/40 hover:bg-text/5 hover:text-text" @click="emit('close')">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <BaseButton variant="secondary" @click="emit('close')">Cancel</BaseButton>
          <BaseButton :variant="variant === 'danger' ? 'danger' : 'primary'" :loading="loading" @click="emit('confirm')">
            {{ confirmText }}
          </BaseButton>
        </div>
      </div>
    </div>
  </Teleport>
</template>
