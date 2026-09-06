<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Clock, FileText, CheckCircle2, Calendar, ArrowRight, ShieldCheck, Lock, Laptop, HelpCircle, AlertCircle, Check } from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';

import * as api from '../../exams/api/studentExams';
import type { StudentExam } from '../../exams/types/studentExam';

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();

const exams = ref<StudentExam[]>([]);
const loading = ref(true);

const studentName = computed(() => {
  const user = authStore.user;
  if (!user) return 'Student';
  return user.first_name || user.username || 'Student';
});

const activeExams = computed(() => {
  return exams.value.filter((e) => e.status === 'active' && e.attempt?.status !== 'completed' && e.attempt?.status !== 'graded');
});

const featuredActiveExam = computed(() => {
  return activeExams.value[0] || null;
});

const upcomingExams = computed(() => {
  return exams.value.filter((e) => e.status === 'scheduled');
});

const completedExams = computed(() => {
  return exams.value.filter((e) => e.attempt?.status === 'completed' || e.attempt?.status === 'graded');
});

async function loadDashboardData() {
  loading.value = true;
  try {
    const res = await api.getStudentExams(1, 50);
    exams.value = res.data || [];
  } catch (err: any) {
    handleApiError(err, uiStore, undefined, 'Failed to load dashboard data.');
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadDashboardData();
});
</script>

<template>
  <div class="space-y-8 max-w-7xl mx-auto">
    <!-- ── Page Header ─────────────────────────────────────────────── -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <div class="text-xs font-mono uppercase tracking-wider text-text/60 flex items-center gap-2 mb-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-accent inline-block" />
          <span>Candidate Portal · Examination Period</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-text font-display">Welcome back, {{ studentName }}.</h1>
      </div>

      <div class="flex items-center gap-2.5 bg-surface border border-border px-3.5 py-1.5 rounded-lg shadow-2xs self-start md:self-auto">
        <span class="w-2 h-2 rounded-full bg-success animate-ping" />
        <span class="text-xs font-medium text-text">Session Verified · Proctoring Protocol Active</span>
      </div>
    </div>

    <!-- ── Loading Skeleton ────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="h-60 bg-surface rounded-2xl border border-border" />
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="h-24 bg-surface rounded-xl border border-border" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="i in 3" :key="i" class="h-56 bg-surface rounded-xl border border-border" />
      </div>
    </div>

    <template v-else>
      <!-- ── 1. Active Exam Hero Card (Stitch Reference Design) ─────── -->
      <div v-if="featuredActiveExam" class="relative w-full rounded-2xl bg-surface border border-border shadow-sm overflow-hidden transition-all hover:shadow-md">
        <!-- Gradient accent bar across top -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-accent via-accent/70 to-border" />
        <!-- Left color status indicator bar -->
        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-accent" />

        <!-- Soft ambient background glow -->
        <div class="absolute -right-16 -top-16 w-80 h-80 bg-accent/10 rounded-full blur-3xl pointer-events-none" />
        <div class="absolute -left-12 -bottom-12 w-64 h-64 bg-success/10 rounded-full blur-2xl pointer-events-none" />

        <div class="relative z-10 p-6 sm:p-8 lg:p-10 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
          <!-- Info side -->
          <div class="flex flex-col gap-3 max-w-2xl">
            <div class="flex flex-wrap items-center gap-2.5">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-success/15 text-success font-mono text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-success animate-pulse" />
                ACTIVE NOW — Ready to Sit
              </span>
              <span class="font-mono text-xs bg-bg border border-border px-2.5 py-0.5 rounded text-text/70"> EXAM #{{ featuredActiveExam.id }} </span>
            </div>

            <div>
              <span class="font-mono text-xs font-semibold text-accent uppercase tracking-wide block mb-1">
                {{ featuredActiveExam.course?.code }}: {{ featuredActiveExam.course?.name }}
              </span>
              <h2 class="text-2xl sm:text-3xl font-bold text-text font-display tracking-tight">
                {{ featuredActiveExam.title }}
              </h2>
            </div>

            <!-- Contextual metadata -->
            <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-text/60 pt-1">
              <div class="flex items-center gap-1.5">
                <Clock class="w-4 h-4 text-accent" />
                <span>{{ featuredActiveExam.duration_minutes }} mins duration</span>
              </div>
              <span class="text-border">•</span>
              <div class="flex items-center gap-1.5">
                <FileText class="w-4 h-4 text-text/50" />
                <span>{{ featuredActiveExam.total_questions }} Questions</span>
              </div>
              <span class="text-border">•</span>
              <div class="flex items-center gap-1.5">
                <CheckCircle2 class="w-4 h-4 text-text/50" />
                <span>{{ featuredActiveExam.total_marks }} Points</span>
              </div>
              <span class="text-border">•</span>
              <div class="flex items-center gap-1.5 text-success font-medium">
                <ShieldCheck class="w-4 h-4" />
                <span>Proctored Session</span>
              </div>
            </div>
          </div>

          <!-- Action CTA -->
          <div class="flex flex-col items-start lg:items-end gap-2 shrink-0">
            <BaseButton
              variant="primary"
              class="w-full sm:w-auto lg:w-56 py-3.5 px-6 font-semibold shadow-md flex items-center justify-center gap-2 group"
              @click="router.push({ name: 'student.exams.overview', params: { examId: featuredActiveExam.id } })">
              <span>{{ featuredActiveExam.attempt?.status === 'in_progress' ? 'Resume Examination' : 'Enter Examination' }}</span>
              <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
            </BaseButton>
            <span class="text-[11px] text-text/50 text-right"> Browser locking will activate on launch </span>
          </div>
        </div>
      </div>

      <!-- No active exam state card -->
      <div v-else class="bg-surface rounded-2xl border border-border p-6 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center shrink-0">
            <CheckCircle2 class="w-5 h-5 text-success" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-text">No active examinations at this moment</h3>
            <p class="text-xs text-text/60 mt-0.5">You are all caught up. Check your scheduled examination timetable below.</p>
          </div>
        </div>
        <BaseButton variant="secondary" @click="router.push({ name: 'student.exams.list' })"> View Full Schedule </BaseButton>
      </div>

      <!-- ── 2. Quick Assessment Metrics Row ────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-surface rounded-xl border border-border p-4 sm:p-5 flex flex-col gap-1 shadow-2xs">
          <span class="text-xs text-text/60 font-medium">Total Registered</span>
          <span class="text-2xl sm:text-3xl font-bold font-display text-text">{{ exams.length }}</span>
          <span class="text-[11px] font-mono text-text/50">Assessments assigned to you</span>
        </div>
        <div class="bg-surface rounded-xl border border-border p-4 sm:p-5 flex flex-col gap-1 shadow-2xs">
          <span class="text-xs text-text/60 font-medium">Upcoming Schedule</span>
          <span class="text-2xl sm:text-3xl font-bold font-display text-text">{{ upcomingExams.length }}</span>
          <span class="text-[11px] font-mono text-text/50">Scheduled on timetable</span>
        </div>
        <div class="bg-surface rounded-xl border border-border p-4 sm:p-5 flex flex-col gap-1 shadow-2xs">
          <span class="text-xs text-text/60 font-medium">Completed Submissions</span>
          <span class="text-2xl sm:text-3xl font-bold font-display text-text">{{ completedExams.length }}</span>
          <span class="text-[11px] font-mono text-text/50">Papers submitted for grading</span>
        </div>
      </div>
      <!-- ── 4. Candidate Briefing & Examination Protocols ───────────── -->
      <section class="space-y-4">
        <div class="flex items-baseline justify-between">
          <div>
            <h2 class="text-xl font-bold tracking-tight text-text font-display">Candidate Examination Protocol</h2>
            <p class="text-xs text-text/60 mt-0.5">Essential regulations and system safeguards governing online assessments.</p>
          </div>
          <span class="font-mono text-xs text-text/50 bg-bg border border-border px-2 py-0.5 rounded"> CODE: REG-2025 </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <!-- Card 1: Academic Integrity -->
          <div class="bg-surface rounded-xl border border-border p-5 shadow-2xs space-y-3.5 flex flex-col justify-between">
            <div class="space-y-3">
              <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <h3 class="text-sm font-bold text-text font-display">Academic Integrity & Conduct</h3>
              <ul class="space-y-2 text-xs text-text/70">
                <li class="flex items-start gap-2">
                  <Check class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" />
                  <span>Single-candidate verification active during session.</span>
                </li>
                <li class="flex items-start gap-2">
                  <Check class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" />
                  <span>Full-screen proctored chamber required on launch.</span>
                </li>
                <li class="flex items-start gap-2">
                  <Check class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" />
                  <span>Zero-tolerance policy for unauthorized aid or tabs.</span>
                </li>
              </ul>
            </div>
            <div class="pt-3 border-t border-border text-[11px] font-mono text-text/50">Integrity Level: Strict Proctored</div>
          </div>

          <!-- Card 2: Technical Safeguards -->
          <div class="bg-surface rounded-xl border border-border p-5 shadow-2xs space-y-3.5 flex flex-col justify-between">
            <div class="space-y-3">
              <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
                <Laptop class="w-5 h-5" />
              </div>
              <h3 class="text-sm font-bold text-text font-display">Session Safeguards & Autosave</h3>
              <ul class="space-y-2 text-xs text-text/70">
                <li class="flex items-start gap-2">
                  <Check class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" />
                  <span>Responses sync to university assessment servers.</span>
                </li>
                <li class="flex items-start gap-2">
                  <Check class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" />
                  <span>Disconnections permit reconnection during active window.</span>
                </li>
                <li class="flex items-start gap-2">
                  <Check class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" />
                  <span>Timed auto-submission triggers when countdown ends.</span>
                </li>
              </ul>
            </div>
            <div class="pt-3 border-t border-border text-[11px] font-mono text-text/50">Server Autosync: Enabled</div>
          </div>

          <!-- Card 3: Support & Invigilation Assistance -->
          <div class="bg-surface rounded-xl border border-border p-5 shadow-2xs space-y-3.5 flex flex-col justify-between">
            <div class="space-y-3">
              <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
                <HelpCircle class="w-5 h-5" />
              </div>
              <h3 class="text-sm font-bold text-text font-display">Invigilation & Technical Support</h3>
              <p class="text-xs text-text/70 leading-relaxed">
                If you encounter connectivity or hardware disruption during an examination, report immediately to your course invigilator or academic faculty helpdesk.
              </p>
              <div class="p-2.5 rounded-lg bg-bg border border-border/80 text-xs text-text/70 space-y-1">
                <span class="font-semibold block text-text text-[11px]">Assessment Helpdesk</span>
                <span class="font-mono text-[11px] block text-text/60">support@university.edu</span>
              </div>
            </div>
            <div class="pt-3 border-t border-border text-[11px] font-mono text-text/50">Response Time: Immediate During Sitting</div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
