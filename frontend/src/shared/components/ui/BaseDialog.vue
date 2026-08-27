<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    title?: string;
    description?: string;
    maxWidth?: string;
    closeOnBackdrop?: boolean;
    closeOnEscape?: boolean;
  }>(),
  {
    title: '',
    description: '',
    maxWidth: 'max-w-lg',
    closeOnBackdrop: true,
    closeOnEscape: true,
  },
);

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

function close() {
  emit('update:modelValue', false);
}

function handleBackdropClick() {
  if (props.closeOnBackdrop) {
    close();
  }
}

function handleKeydown(event: KeyboardEvent) {
  if (props.closeOnEscape && event.key === 'Escape') {
    close();
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      document.addEventListener('keydown', handleKeydown);
    } else {
      document.removeEventListener('keydown', handleKeydown);
    }
  },
);

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="title || 'Dialog'">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/40" @click="handleBackdropClick" />

      <!-- Dialog -->
      <div class="relative flex max-h-[calc(100vh-2rem)] w-full flex-col overflow-hidden rounded-md border border-border bg-surface shadow-sm" :class="maxWidth" @click.stop>
        <!-- Header -->
        <div v-if="title || $slots.header" class="flex shrink-0 items-start justify-between gap-4 border-b border-border px-5 py-4">
          <slot name="header">
            <div class="min-w-0">
              <h2 class="text-base font-semibold text-text">
                {{ title }}
              </h2>

              <p v-if="description" class="mt-1 text-sm text-text/60">
                {{ description }}
              </p>
            </div>
          </slot>

          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-text/50 transition-colors hover:bg-bg hover:text-text focus:outline-none focus:ring-2 focus:ring-accent/30"
            aria-label="Close dialog"
            @click="close">
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Body -->
        <div class="min-h-0 overflow-y-auto p-5">
          <slot />
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer" class="shrink-0 border-t border-border px-5 py-4">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>
