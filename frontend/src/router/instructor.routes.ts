import DashboardView from '@/modules/teaching/pages/DashboardView.vue'

export default [
  {
    path: 'dashboard',
    name: 'teaching-dashboard',
    component: DashboardView,
  },
  {
    path: 'courses/:courseId/question-bank/import',
    name: 'teaching.question-bank.import',
    component: () => import('@/modules/teaching/pages/question-bank/ImportQuestions.vue'),
  },
  {
    path: 'courses/:courseId/question-bank/imports/:importId',
    name: 'teaching.question-bank.import.show',
    component: () => import('@/modules/teaching/pages/question-bank/ImportQuestions.vue'),
  },
]
