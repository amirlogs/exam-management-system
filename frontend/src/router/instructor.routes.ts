import DashboardView from '@/modules/instructor/pages/DashboardView.vue'
// import MyCoursesView from '@/modules/instructor/courses/pages/MyCoursesView.vue'
// import QuestionBankListView from '@/modules/instructor/question-bank/pages/QuestionBankList.vue'

// import ImportQuestionsView from '@/modules/instructor/question-bank/pages/ImportQuestions.vue'
// import QuestionListView from '@/modules/instructor/question-bank/pages/QuestionListView.vue'
// import ImportFlagsView from '@/modules/instructor/question-bank/pages/ImportFlagsView.vue'
// import ExamsListView from '@/modules/instructor/exams/pages/ExamsListView.vue'
// import ExamBuilderView from '@/modules/instructor/exams/pages/ExamBuilderView.vue'
// import GradingQueueView from '@/modules/instructor/grading/pages/GradingQueueView.vue'
// import GradingDetailView from '@/modules/instructor/grading/pages/GradingDetailView.vue'
// import FlagsView from '@/modules/instructor/pages/FlagsView.vue'

export default [
  {
    path: 'dashboard',
    name: 'instructor.dashboard',
    component: DashboardView,
  },
  {
    path: 'courses',
    name: 'instructor.courses.list',
    // component: MyCoursesView,
    meta: { permissions: ['course_offering.view'] },
  },

  // ---------------- Question Bank ----------------
  {
    path: 'question-bank',
    name: 'instructor.question-bank.list',
    // component: QuestionBankListView,
    meta: { permissions: ['question.view'] },
  },
  {
    path: 'courses/:courseId/question-bank',
    name: 'instructor.question-bank.list.byCourse',
    // component: QuestionBankListView,
    meta: { permissions: ['question.view'] },
  },
  {
    path: 'courses/:courseId/question-bank/import/:importId?',
    name: 'instructor.question-bank.import',
    // component: ImportQuestionsView,
    meta: { permissions: ['question.create'] },
  },
  {
    path: 'courses/:courseId/question-bank/imports/:importId/questions',
    name: 'instructor.import.questions',
    // component: QuestionListView,
    meta: { permissions: ['question.view'] },
  },
  {
    path: 'courses/:courseId/question-bank/imports/:importId/flags',
    name: 'instructor.import.flags',
    // component: ImportFlagsView,
    meta: { permissions: ['question.update'] },
  },

  // ---------------- Exams ----------------
  {
    path: 'exams',
    name: 'instructor.exams.list',
    // component: ExamsListView,
    meta: { permissions: ['exam.create', 'exam.update'] },
  },
  {
    path: 'exams/:examId/build',
    name: 'instructor.exams.build',
    // component: ExamBuilderView,
    meta: { permissions: ['exam.update'] },
  },

  // ---------------- Grading ----------------
  {
    path: 'grading',
    name: 'instructor.grading.list',
    // component: GradingQueueView,
    meta: { permissions: ['grade.create'] },
  },
  {
    path: 'grading/:examId',
    name: 'instructor.grading.detail',
    // component: GradingDetailView,
    meta: { permissions: ['grade.create'] },
  },

  // ---------------- Flags ----------------
  {
    path: 'flags',
    name: 'instructor.flags',
    // component: FlagsView,
  },
]
