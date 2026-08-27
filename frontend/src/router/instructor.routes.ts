import CourseOfferingHubView from '@/modules/instructor/course-offerings/pages/CourseOfferingHubView.vue';
import CourseOfferingsListView from '@/modules/instructor/course-offerings/pages/CourseOfferingsListView.vue';
import ExamComposerView from '@/modules/instructor/exams/pages/ExamComposerView.vue';
import ExamDetailView from '@/modules/instructor/exams/pages/ExamDetailView.vue';
import ExamsListView from '@/modules/instructor/exams/pages/ExamsListView.vue';
// import GradingQueueView from '@/modules/instructor/grading/pages/GradingQueueView.vue'
import GenericImportReviewView from '@/modules/instructor/imports/pages/GenericImportReviewView.vue';
import InstructorQuestionImportDetailView from '@/modules/instructor/imports/views/InstructorQuestionImportDetailView.vue';
import InstructorQuestionImportView from '@/modules/instructor/imports/views/InstructorQuestionImportView.vue';
import DashboardView from '@/modules/instructor/pages/DashboardView.vue';
import MyCoursesView from '@/modules/instructor/pages/MyCoursesView.vue';
import QuestionBankListView from '@/modules/instructor/questions/pages/QuestionBankListView.vue';
import { Component } from 'lucide-vue-next';

export default [
  {
    path: 'question-bank/import',
    name: 'instructor-question-import',
    component: InstructorQuestionImportView,
    meta: {
      permissions: ['question.import'],
    },
  },
  {
    path: 'question-bank/import/:id(\\d+)',
    name: 'instructor-question-import-detail',
    component: InstructorQuestionImportDetailView,
    meta: {
      permissions: ['question.import'],
    },
  },
  {
    path: 'dashboard',
    name: 'instructor.dashboard',
    component: DashboardView,
  },
  {
    path: 'courses',
    name: 'instructor.courses.list',
    component: MyCoursesView,
    meta: { permissions: ['course_offering.view'], permissionMode: 'any' },
  },
  {
    path: 'courses/:courseId/questions',
    name: 'instructor.questions.list',
    component: QuestionBankListView,
    meta: { permissions: ['question.create', 'question.view'], permissionMode: 'any' },
  },
  {
    path: 'courses/:courseId/questions/import',
    name: 'instructor.questions.import',
    component: GenericImportReviewView,
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'imports/new',
    name: 'instructor.imports.new',
    component: GenericImportReviewView,
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'imports/:importId',
    name: 'instructor.imports.review',
    component: GenericImportReviewView,
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'course-offerings',
    name: 'instructor.course-offerings.list',
    component: CourseOfferingsListView,
    meta: { permissions: ['exam.create', 'exam.update', 'exam.view'], permissionMode: 'any' },
  },
  {
    path: 'course-offerings/:courseOfferingId/exams',
    name: 'instructor.exams.list',
    component: ExamsListView,
    meta: { permissions: ['exam.view'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId',
    name: 'instructor.exams.detail',
    component: ExamDetailView,
    meta: { permissions: ['exam.view'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId/build',
    name: 'instructor.exams.build',
    component: ExamComposerView,
    meta: { permissions: ['exam.update'], permissionMode: 'any' },
  },
  {
    path: 'grading',
    name: 'instructor.grading.list',
    // component: GradingQueueView,
    meta: { permissions: ['grade.create'], permissionMode: 'any' },
  },
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
    path: 'dashboard',
    name: 'instructor.dashboard',
    component: DashboardView,
  },
  {
    path: 'courses',
    name: 'instructor.courses.list',
    component: MyCoursesView,
    meta: { permissions: ['course_offering.view'], permissionMode: 'any' },
  },
  {
    path: 'courses/:courseId/questions',
    name: 'instructor.questions.list',
    component: QuestionBankListView,
    meta: { permissions: ['question.create', 'question.view'], permissionMode: 'any' },
  },
  {
    path: 'courses/:courseId/questions/import',
    name: 'instructor.questions.import',
    component: GenericImportReviewView,
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'imports/new',
    name: 'instructor.imports.new',
    component: GenericImportReviewView,
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'imports/:importId',
    name: 'instructor.imports.review',
    component: GenericImportReviewView,
    meta: { permissions: ['question.create'], permissionMode: 'any' },
  },
  {
    path: 'course-offerings',
    name: 'instructor.course-offerings.list',
    component: CourseOfferingsListView,
    meta: { permissions: ['exam.create', 'exam.update', 'exam.view'], permissionMode: 'any' },
  },
  {
    path: 'course-offerings/:courseOfferingId/exams',
    name: 'instructor.exams.list',
    component: ExamsListView,
    meta: { permissions: ['exam.view'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId',
    name: 'instructor.exams.detail',
    component: ExamDetailView,
    meta: { permissions: ['exam.view'], permissionMode: 'any' },
  },
  {
    path: 'exams/:examId/build',
    name: 'instructor.exams.build',
    component: ExamComposerView,
    meta: { permissions: ['exam.update'], permissionMode: 'any' },
  },
  {
    path: 'grading',
    name: 'instructor.grading.list',
    // component: GradingQueueView,
    meta: { permissions: ['grade.create'], permissionMode: 'any' },
  },
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
];
