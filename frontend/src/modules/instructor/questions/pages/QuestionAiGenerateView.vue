<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Sparkles } from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import AiQuestionGeneratorModal from '../components/AiQuestionGeneratorModal.vue';
import type { GeneratedQuestionsHistory } from '../types/aiQuestion';

const route = useRoute();
const router = useRouter();

const examId = route.params.examId ? Number(route.params.examId) : null;
const isOpen = ref(true);

function handleConfirmed(session: GeneratedQuestionsHistory) {
  if (examId) {
    router.push({
      name: 'instructor.exams.questions',
      params: { examId },
    });
  } else {
    router.push({
      name: 'instructor.questions.list',
    });
  }
}

function handleClose() {
  isOpen.value = false;
  if (examId) {
    router.push({
      name: 'instructor.exams.questions',
      params: { examId },
    });
  } else {
    router.push({
      name: 'instructor.questions.list',
    });
  }
}
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
    <ResourceToolbar
      title="AI Question Synthesis"
      description="Generate high-quality exam questions using curriculum concepts or lecture text."
      :show-search="false"
      :show-refresh="false"
      :show-fullscreen="false"
    >
      <template #actions>
        <BaseButton variant="secondary" @click="handleClose">
          <template #icon>
            <ArrowLeft class="h-4 w-4" />
          </template>
          Back
        </BaseButton>
      </template>
    </ResourceToolbar>

    <div class="rounded-2xl border border-dashed border-border bg-surface/50 p-12 text-center space-y-4">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-accent/10 text-accent ring-8 ring-accent/5">
        <Sparkles class="h-7 w-7 animate-pulse" />
      </div>
      <div class="space-y-1">
        <h3 class="font-bold text-text text-lg">AI Question Generation Session</h3>
        <p class="text-xs text-text/60 max-w-md mx-auto">
          The generation modal is active. You can generate questions by topic or paste course notes, review the generated deck, and commit them.
        </p>
      </div>
      <BaseButton variant="primary" @click="isOpen = true">
        Open Generator
      </BaseButton>
    </div>

    <!-- Active Generator Modal -->
    <AiQuestionGeneratorModal
      v-model="isOpen"
      :exam-id="examId"
      scope="instructor"
      @confirmed="handleConfirmed"
      @update:model-value="(val) => { if (!val) handleClose(); }"
    />
  </div>
</template>
