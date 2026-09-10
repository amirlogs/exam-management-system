export default [
  {
    path: '',
    redirect: { name: 'student.dashboard' },
  },
  {
    path: 'dashboard',
    name: 'student.dashboard',
    component: () => import('@/modules/student/dashboard/pages/StudentDashboardView.vue'),
  },
  {
    path: 'profile',
    name: 'student.profile',
    component: () => import('@/modules/profile/pages/ProfileView.vue'),
  },
  {
    path: 'exams',
    name: 'student.exams.list',
    component: () => import('@/modules/student/exams/pages/StudentExamsListView.vue'),
    meta: {
      permissions: ['student.exam.take'],
      permissionMode: 'any',
    },
  },
  {
    path: 'exams/:examId',
    name: 'student.exams.overview',
    component: () => import('@/modules/student/exams/pages/StudentExamOverviewView.vue'),
    meta: {
      permissions: ['student.exam.take'],
      permissionMode: 'any',
    },
  },
  {
    path: 'exams/:examId/take',
    name: 'student.exams.take',
    component: () => import('@/modules/student/exams/pages/StudentExamTakingView.vue'),
    meta: {
      permissions: ['student.exam.take'],
      permissionMode: 'any',
    },
  },
  {
    path: 'results',
    name: 'student.results.list',
    component: () => import('@/modules/student/results/pages/StudentResultsListView.vue'),
    meta: {
      permissions: ['result.view_own'],
      permissionMode: 'any',
    },
  },
];
