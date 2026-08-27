<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{ (e: 'files-selected', files: FileList): void }>();
const isDragging = ref(false);

function onDrop(e: DragEvent) {
  isDragging.value = false;
  if (e.dataTransfer?.files.length) emit('files-selected', e.dataTransfer.files);
}
function onBrowse(e: Event) {
  const files = (e.target as HTMLInputElement).files;
  if (files?.length) emit('files-selected', files);
}
</script>

<template>
  <div
    class="border-2 border-dashed rounded-lg py-16 flex flex-col items-center justify-center text-center transition-colors"
    :class="isDragging ? 'border-accent bg-accent/5' : 'border-border'"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="onDrop">
    <div class="w-16 h-16 rounded-full bg-bg flex items-center justify-center mb-4">
      <slot name="icon" />
    </div>
    <h3 class="text-lg font-bold text-text mb-1">Ready to upload</h3>
    <p class="text-sm text-text/60 mb-4">
      Drag and drop your file here, or
      <label class="text-accent font-semibold cursor-pointer underline">
        click to browse
        <input type="file" class="hidden" @change="onBrowse" />
      </label>
    </p>
    <slot name="footer" />
  </div>
</template>
