<script setup lang="ts">
import { FileSpreadsheet, PenLine, Sparkles } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';

const router = useRouter();

function goManual() {
  router.push({
    name: 'instructor.questions.create',
  });
}

function goImport() {
  router.push({
    name: 'instructor.questions.import',
  });
}

function goAi() {
  router.push({
    name: 'instructor.questions.ai-generate',
  });
}

function back() {
  router.push({
    name: 'instructor.questions.list',
  });
}
</script>

<template>
  <div class="mx-auto w-full max-w-300 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="Add Questions"
      description="Choose how you want to add questions to your question bank."
      :show-search="false"
      :show-refresh="false"
      :show-fullscreen="false">
      <template #actions>
        <BaseButton variant="secondary" @click="back"> Back </BaseButton>
      </template>
    </ResourceToolbar>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <!-- AI Generation Card -->
      <button
        type="button"
        class="group relative rounded-xl border border-accent/40 bg-accent/5 p-6 text-left transition-all hover:border-accent hover:shadow-lg hover:shadow-accent/5 cursor-pointer"
        @click="goAi"
      >
        <div class="absolute top-4 right-4">
          <span class="inline-flex items-center gap-1 rounded-full bg-accent/15 px-2 py-0.5 text-[10px] font-bold text-accent">
            <Sparkles class="h-3 w-3" />
            AI Powered
          </span>
        </div>

        <div class="flex items-start justify-between">
          <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-accent/15 text-accent ring-4 ring-accent/10">
            <Sparkles class="h-5 w-5" />
          </div>
        </div>

        <h2 class="mt-5 text-base font-semibold text-text group-hover:text-accent transition-colors">
          Generate with AI
        </h2>

        <p class="mt-1.5 text-sm leading-relaxed text-text/60">
          Synthesize questions from course syllabus concepts or paste raw lecture slide notes for automatic generation.
        </p>
      </button>

      <!-- Manual Card -->
      <button type="button" class="group rounded-xl border border-border bg-surface p-6 text-left transition-all hover:border-accent/40 hover:shadow-md cursor-pointer" @click="goManual">
        <div class="flex items-start justify-between">
          <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-accent/10 text-accent">
            <PenLine class="h-5 w-5" />
          </div>

          <span class="text-text/25 transition-colors group-hover:text-accent"> → </span>
        </div>

        <h2 class="mt-5 text-base font-semibold text-text">Create manually</h2>

        <p class="mt-1.5 text-sm leading-relaxed text-text/50">Create one question at a time and configure its type, difficulty, chapter, and answers.</p>
      </button>

      <!-- CSV Card -->
      <button type="button" class="group rounded-xl border border-border bg-surface p-6 text-left transition-all hover:border-accent/40 hover:shadow-md cursor-pointer" @click="goImport">
        <div class="flex items-start justify-between">
          <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-accent/10 text-accent">
            <FileSpreadsheet class="h-5 w-5" />
          </div>

          <span class="text-text/25 transition-colors group-hover:text-accent"> → </span>
        </div>

        <h2 class="mt-5 text-base font-semibold text-text">Upload CSV</h2>

        <p class="mt-1.5 text-sm leading-relaxed text-text/50">Upload multiple questions using the existing question import validation and review workflow.</p>
      </button>
    </div>
  </div>
</template>
