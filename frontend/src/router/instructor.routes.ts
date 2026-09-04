import TeachingView from '@/modules/instructor/teaching/pages/TeachingView.vue';
import TeachingDetailView from '@/modules/instructor/teaching/pages/TeachingDetailView.vue';
import DashboardView from '@/modules/instructor/pages/DashboardView.vue';
import QuestionsView from '@/modules/instructor/questions/pages/QuestionsView.vue';
import QuestionDetailView from '@/modules/instructor/questions/pages/QuestionDetailView.vue';
import QuestionFormView from '@/modules/instructor/questions/pages/QuestionFormView.vue';
import QuestionAddView from '@/modules/instructor/questions/pages/QuestionAddView.vue';
import QuestionImportView from '@/modules/instructor/questions/pages/QuestionImportView.vue';

export default [
  {
    path: 'dashboard',
    name: 'instructor.dashboard',
    component: DashboardView,
  },
  // ---------------- Teaching ----------------
  {
    path: 'teaching',
    name: 'instructor.teaching.list',
    component: TeachingView,
    meta: {
      permissions: ['course_offering.view'],
    },
  },

  {
    path: 'teaching/:courseOfferingId',
    name: 'instructor.teaching.detail',
    component: TeachingDetailView,
    meta: {
      permissions: ['course_offering.view'],
    },
  },
  // ---------------- Questions ----------------
  {
    path: 'questions',
    name: 'instructor.questions.list',
    component: QuestionsView,
    meta: {
      permissions: ['question.view'],
    },
  },

  {
    path: 'questions/add',
    name: 'instructor.questions.add',
    component: QuestionAddView,
    meta: {
      permissions: ['question.create'],
    },
  },

  {
    path: 'questions/create',
    name: 'instructor.questions.create',
    component: QuestionFormView,
    meta: {
      permissions: ['question.create'],
    },
  },

  {
    path: 'questions/import',
    name: 'instructor.questions.import',
    component: QuestionImportView,
    meta: {
      permissions: ['question.import'],
    },
  },

  {
    path: 'questions/:questionId(\\d+)',
    name: 'instructor.questions.detail',
    component: QuestionDetailView,
    meta: {
      permissions: ['question.view'],
    },
  },

  {
    path: 'questions/:questionId(\\d+)/edit',
    name: 'instructor.questions.edit',
    component: QuestionFormView,
    meta: {
      permissions: ['question.update'],
    },
  },
  //----------------- Exam---------------
  {
    path: 'exams',
    name: 'instructor.exams.list',
    component: () => import('@/modules/instructor/exams/pages/ExamsView.vue'),
    meta: {
      permission: 'exam.view',
    },
  },
  {
    path: 'exams/create',
    name: 'instructor.exams.create',
    component: () => import('@/modules/instructor/exams/pages/ExamCreateView.vue'),
    meta: {
      permission: 'exam.create',
    },
  },
  {
    path: 'exams/:examId(\\d+)',
    name: 'instructor.exams.detail',
    component: () => import('@/modules/instructor/exams/pages/ExamDetailView.vue'),
    meta: {
      permission: 'exam.view',
    },
  },
  {
    path: 'exams/:examId(\\d+)/questions',
    name: 'instructor.exams.questions',
    component: () => import('@/modules/instructor/exams/pages/ExamQuestionsView.vue'),
    meta: {
      permission: 'exam.view',
    },
  },
  {
    path: 'exams/:examId(\\d+)/questions/import',
    name: 'instructor.exams.questions.import',
    component: () => import('@/modules/instructor/exams/pages/ExamQuestionImportView.vue'),
    meta: {
      permission: 'question.import',
    },
  },
];
