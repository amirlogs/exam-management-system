export default [
  {
    path: 'dashboard',
    name: 'admin-dashboard',
    // component: () => import('@/modules/admin/pages/DashboardView.vue'),
  },
  {
    path: 'student-records',
    name: 'admin-student-records',
    // component: () => import('@/modules/admin/pages/StudentRecordsView.vue'),
    meta: { permission: 'admin.students.view' },
  },
  {
    path: 'student-records/import',
    name: 'admin-import',
    // component: () => import('@/modules/admin/pages/ImportStudentsView.vue'),
    meta: { permission: 'admin.students.import' },
  },
  {
    path: 'user-management',
    name: 'admin-users',
    // component: () => import('@/modules/admin/pages/UserManagementView.vue'),
    meta: { permission: 'admin.users.manage' },
  },
]
