// frontend/src/router/instructor.routes.ts
import CourseOfferingHubView from '@/modules/instructor/course-offerings/pages/CourseOfferingHubView.vue';
import CourseOfferingsListView from '@/modules/instructor/course-offerings/pages/CourseOfferingsListView.vue';
import ExamCreateView from '@/modules/instructor/exams/pages/ExamCreateView.vue';
import ExamDetailView from '@/modules/instructor/exams/pages/ExamDetailView.vue';
import ExamQuestionImportView from '@/modules/instructor/exams/pages/ExamQuestionImportView.vue';
import ExamQuestionsView from '@/modules/instructor/exams/pages/ExamQuestionsView.vue';
import ExamsView from '@/modules/instructor/exams/pages/ExamsView.vue';
// import GradingQueueView from '@/modules/instructor/grading/pages/GradingQueueView.vue'
import DashboardView from '@/modules/instructor/pages/DashboardView.vue';
import MyCoursesView from '@/modules/instructor/pages/MyCoursesView.vue';
import TeachingDetailView from '@/modules/instructor/teaching/pages/TeachingDetailView.vue';
import TeachingView from '@/modules/instructor/teaching/pages/TeachingView.vue';
import QuestionAddView from '@/modules/instructor/questions/pages/QuestionAddView.vue';
import QuestionCreateView from '@/modules/instructor/questions/pages/QuestionCreateView.vue';
import QuestionDetailView from '@/modules/instructor/questions/pages/QuestionDetailView.vue';
import QuestionEditView from '@/modules/instructor/questions/pages/QuestionEditView.vue';
import QuestionImportView from '@/modules/instructor/questions/pages/QuestionImportView.vue';
import QuestionsView from '@/modules/instructor/questions/pages/QuestionsView.vue';

export default [
  {
    path: 'dashboard',
    name: 'instructor.dashboard',
    component: DashboardView,
  },
  {
    path: 'profile',
    name: 'instructor.profile',
    component: () => import('@/modules/profile/pages/ProfileView.vue'),
  },
  {
    path: 'teaching',
    name: 'instructor.teaching.list',
    component: TeachingView,
    meta: { permissions: ['course_offering.view'], permissionMode: 'any' },
  },
  {
    path: 'teaching/:courseOfferingId',
    name: 'instructor.teaching.detail',
    component: TeachingDetailView,
    meta: { permissions: ['course_offering.view'], permissionMode: 'any' },
  },
  {
    path: 'courses',
    name: 'instructor.courses.list',
    component: MyCoursesView,
    meta: { permissions: ['course_offering.view'], permissionMode: 'any' },
  },

  // ==================== QUESTION BANK ====================
  {
    path: 'questions',
    name: 'instructor.questions.list',
    component: QuestionsView,
    meta: { permissions: ['question.create', 'question.view'], permissionMode: 'any' },
  },
  {
    path: 'questions/add',
    name: 'instructor.questions.add',
    component: QuestionAddView,
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'questions/create',
    name: 'instructor.questions.create',
    component: QuestionCreateView,
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'questions/import',
    name: 'instructor.questions.import',
    component: QuestionImportView,
    meta: { permissions: ['question.import'], permissionMode: 'any' },
  },
  {
    path: 'questions/ai-generate',
    name: 'instructor.questions.ai-generate',
    component: () => import('@/modules/instructor/questions/pages/QuestionAiGenerateView.vue'),
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'questions/:questionId/edit',
    name: 'instructor.questions.edit',
    component: QuestionEditView,
    meta: { permissions: ['question.update'], permissionMode: 'any' },
  },
  {
    path: 'questions/:questionId',
    name: 'instructor.questions.detail',
    component: QuestionDetailView,
    meta: { permissions: ['question.view'], permissionMode: 'any' },
  },

  // ==================== COURSE OFFERINGS / EXAMS ====================
  {
    path: 'course-offerings',
    name: 'instructor.course-offerings.list',
    component: CourseOfferingsListView,
    meta: { permissions: ['exam.create', 'exam.update', 'exam.view'], permissionMode: 'any' },
  },
  {
    path: 'course-offerings/:courseOfferingId',
    name: 'instructor.course-offerings.hub',
    component: CourseOfferingHubView,
    meta: { permissions: ['course_offering.view'], permissionMode: 'any' },
  },
  {
    path: 'exams',
    name: 'instructor.exams.list',
    component: ExamsView,
    meta: { permissions: ['exam.view'], permissionMode: 'any' },
  },
  {
    path: 'exams/create',
    name: 'instructor.exams.create',
    component: ExamCreateView,
    meta: { permissions: ['exam.create'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId',
    name: 'instructor.exams.detail',
    component: ExamDetailView,
    meta: { permissions: ['exam.view'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId/questions',
    name: 'instructor.exams.questions',
    component: ExamQuestionsView,
    meta: { permissions: ['exam.view', 'exam.update'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId/questions/import',
    name: 'instructor.exams.questions.import',
    component: ExamQuestionImportView,
    meta: { permissions: ['exam.update'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId/questions/ai-generate',
    name: 'instructor.exams.questions.ai-generate',
    component: () => import('@/modules/instructor/questions/pages/QuestionAiGenerateView.vue'),
    meta: { permissions: ['exam.update'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId/build',
    name: 'instructor.exams.build',
    redirect: (to) => ({ name: 'instructor.exams.questions', params: { examId: to.params.examId } }),
  },

  // ==================== GRADING ====================
  {
    path: 'grading',
    name: 'instructor.grading.list',
    component: () => import('@/modules/instructor/grading/pages/GradingQueueView.vue'),
    meta: { permissions: ['grade.create'], permissionMode: 'any' },
  },
  {
    path: 'grading/exams/:examId',
    name: 'instructor.grading.submissions',
    component: () => import('@/modules/instructor/grading/pages/ExamSubmissionsView.vue'),
    meta: { permissions: ['grade.create'], permissionMode: 'any' },
  },
];
