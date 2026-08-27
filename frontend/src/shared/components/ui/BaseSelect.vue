<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { ChevronDown, Check } from 'lucide-vue-next';

const props = defineProps<{
  modelValue: string;
  options: { value: string; label: string }[];
  placeholder?: string;
  disabled?: boolean;
}>();
const emit = defineEmits<{ 'update:modelValue': [string] }>();

const triggerRef = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const menuStyle = ref({ top: '0px', left: '0px', width: '0px' });

const selectedLabel = computed(() => props.options.find((o) => o.value === props.modelValue)?.label ?? props.placeholder ?? 'Select…');

async function open() {
  if (props.disabled) return;
  isOpen.value = true;
  await nextTick();
  updatePosition();
}

function updatePosition() {
  if (!triggerRef.value) return;
  const rect = triggerRef.value.getBoundingClientRect();
  const menuHeight = 240;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUpward = spaceBelow < menuHeight && rect.top > menuHeight;

  menuStyle.value = {
    top: (openUpward ? rect.top - 4 - menuHeight : rect.bottom + 4) + 'px',
    left: rect.left + 'px',
    width: rect.width + 'px',
  };
}

function select(value: string) {
  emit('update:modelValue', value);
  isOpen.value = false;
}

function close() {
  isOpen.value = false;
}

function handleScroll() {
  // Recompute position on scroll instead of losing the menu — this is the
  // actual fix for "disappears when you scroll" (previously it likely just
  // stayed at a stale position or got clipped by an ancestor's overflow)
  if (isOpen.value) updatePosition();
}

function handleClickOutside(e: MouseEvent) {
  const target = e.target as Node;
  if (triggerRef.value?.contains(target)) return;
  const menu = document.getElementById('base-select-menu');
  if (menu?.contains(target)) return;
  close();
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, true); // capture phase — catches scroll on any ancestor, including modal bodies
  window.addEventListener('resize', handleScroll);
  document.addEventListener('click', handleClickOutside);
});
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll, true);
  window.removeEventListener('resize', handleScroll);
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div ref="triggerRef" class="relative">
    <button
      type="button"
      :disabled="disabled"
      class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-md border border-border bg-bg text-sm text-left transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      :class="isOpen ? 'border-accent' : 'hover:border-accent/50'"
      @click.stop="open">
      <span :class="modelValue ? 'text-text' : 'text-text/40'">{{ selectedLabel }}</span>
      <ChevronDown class="w-4 h-4 text-text/40 shrink-0 transition-transform" :class="isOpen ? 'rotate-180' : ''" />
    </button>

    <Teleport to="body">
      <div
        v-if="isOpen"
        id="base-select-menu"
        class="fixed z-[100] max-h-60 overflow-y-auto rounded-md border border-border bg-surface shadow-lg py-1"
        :style="menuStyle"
        @click.stop>
        <button
          v-for="opt in options"
          :key="opt.value"
          type="button"
          class="w-full flex items-center justify-between px-3 py-2 text-sm text-left transition-colors"
          :class="opt.value === modelValue ? 'text-accent font-semibold bg-accent/5' : 'text-text hover:bg-bg'"
          @click="select(opt.value)">
          {{ opt.label }}
          <Check v-if="opt.value === modelValue" class="w-3.5 h-3.5" />
        </button>
        <p v-if="!options.length" class="px-3 py-2 text-sm text-text/40">No options available</p>
      </div>
    </Teleport>
  </div>
</template>
