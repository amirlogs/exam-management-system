export default [
  { path: 'login', name: 'login', component: () => import('@/modules/auth/pages/LoginView.vue') },
  {
    path: 'forgot-password',
    name: 'forgot-password',
    // component: () => import('@/modules/auth/pages/ForgotPasswordView.vue'),
  },
  {
    path: 'select-workspace',
    name: 'select-workspace',
    component: () => import('@/modules/workspace/pages/WorkspaceSelectView.vue'),
    meta: { requiresAuth: true },
  },
]
