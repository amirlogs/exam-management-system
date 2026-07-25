export default [
  {
    path: 'dashboard',
    name: 'instructor-dashboard',
    component: () => import('@/modules/instructor/pages/DashboardView.vue'),
  },
  {
    path: 'courses',
    name: 'instructor.courses',
    component: () => import('@/modules/instructor/pages/MyCoursesView.vue'),
  },
  {
    path: 'question-bank',
    name: 'instructor.question-bank.list',
    component: () => import('@/modules/instructor/pages/question-bank/QuestionBankList.vue'),
  },
  {
    path: 'courses/:courseId/question-bank/import/:importId?',
    name: 'instructor.question-bank.import',
    component: () => import('@/modules/instructor/pages/question-bank/ImportQuestions.vue'),
  },
  {
    path: 'courses/:courseId/question-bank/imports/:importId/questions',
    name: 'instructor.import.questions',
    component: () => import('@/modules/instructor/pages/QuestionListView.vue'),
  },
  {
    path: 'questions/:questionId/flags',
    name: 'instructor.question.flags',
    component: () => import('@/modules/instructor/pages/QuestionFlagsView.vue'),
  },
  {
    path: 'courses/:courseId/question-bank',
    name: 'instructor.question-bank.list.byCourse',
    component: () => import('@/modules/instructor/pages/question-bank/QuestionBankList.vue'),
  },
  {
    path: 'courses/:courseId',
    name: 'instructor.course.overview',
    component: () => import('@/modules/instructor/pages/CourseOverview.vue'),
  },
  {
    path: 'courses/:courseId/question-bank/imports/:importId/flags',
    name: 'instructor.import.flags',
    component: () => import('@/modules/instructor/pages/ImportFlagsView.vue'),
  },
]
