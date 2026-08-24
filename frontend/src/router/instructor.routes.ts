// import CourseDetailsView from '@/modules/instructor/courses/pages/CourseDetailsView.vue'
// import MyCoursesView from '@/modules/instructor/courses/pages/MyCoursesView.vue'
// import ExamBuilderView from '@/modules/instructor/exams/pages/ExamBuilderView.vue'
// import ExamsView from '@/modules/instructor/exams/pages/ExamsView.vue'
// import EvaluateAnswersView from '@/modules/instructor/grading/pages/EvaluateAnswersView.vue'
// import GradingView from '@/modules/instructor/grading/pages/GradingView.vue'
// import DashboardView from '@/modules/instructor/pages/DashboardView.vue'
// import QuestionBankView from '@/modules/instructor/questions/pages/QuestionBankView.vue'
// import QuestionFlagsView from '@/modules/instructor/questions/pages/QuestionFlagsView.vue'
import { BookOpen, CheckSquare, FileText, HelpCircle, LayoutGrid } from 'lucide-vue-next'

export default [
  {
    path: 'dashboard',
    name: 'instructor.dashboard',
    // component: DashboardView,
  },

  // ---------------- Course Offerings ----------------
  {
    path: 'courses',
    name: 'instructor-courses',
    // component: MyCoursesView,
    meta: {
      permissions: ['course_offering.view'],
    },
  },
  {
    path: 'courses/:id',
    name: 'instructor-course-details',
    // component: CourseDetailsView,
    meta: {
      permissions: ['course_offering.view'],
    },
  },

  // ---------------- Question Bank ----------------
  {
    path: 'question-bank',
    name: 'instructor-question-bank',
    // component: QuestionBankView,
    meta: {
      permissions: ['question.view'],
    },
  },
  {
    path: 'question-bank/flags',
    name: 'instructor-question-flags',
    // component: QuestionFlagsView,
    meta: {
      permissions: ['question.view'],
    },
  },

  // ---------------- Exams Management ----------------
  {
    path: 'exams',
    name: 'instructor-exams',
    // component: ExamsView,
    meta: {
      permissions: ['exam.view'],
    },
  },
  {
    path: 'exams/:id/builder',
    name: 'instructor-exam-builder',
    // component: ExamBuilderView,
    meta: {
      permissions: ['exam.update'],
    },
  },

  // ---------------- Grading & Submissions ----------------
  {
    path: 'grading',
    name: 'instructor-grading',
    // component: GradingView,
    meta: {
      permissions: ['grade.update'],
    },
  },
  {
    path: 'grading/exams/:examId',
    name: 'instructor-evaluate-answers',
    // component: EvaluateAnswersView,
    meta: {
      permissions: ['grade.update'],
    },
  },
]

