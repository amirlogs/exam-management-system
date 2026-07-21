import AuthLayout from '@/app/layouts/AuthLayout.vue'
import LoginView from '@/modules/auth/pages/LoginView.vue'
import WorkspaceSelectView from '@/modules/workspace/pages/WorkspaceSelectView.vue'
import NotFoundView from '@/shared/pages/NotFoundView.vue'
import UnauthorizedView from '@/shared/pages/UnauthorizedView.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { authGuard } from './guards'

const routes = [
  {
    path: '/',
    component: AuthLayout,
    children: [
      {
        path: 'login',
        name: 'login',
        component: LoginView,
      },
      {
        path: 'select-workspace',
        name: 'select-workspace',
        component: WorkspaceSelectView,
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundView,
  },
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: UnauthorizedView,
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(authGuard)
// router.beforeEach(permissionGuard)

export default router

// {
//   path: '/admin',
//   meta: {
//     requiresAuth: true,
//     workspace: 'admin',
//   },
//   Component: adminlayout ,
//   children: adminRoutes,
// },
// {
//   path: '/teaching',
//   component: TeachingLayout,
//   meta: {
//     requiresAuth: true,
//     workspace: 'teaching',
//   },
//   children: teachingRoutes,
// },
// {
//   path: '/student',
//   component: StudentLayout,
//   meta: { requiresAuth: true, workspace: 'student' },
//   children: studentRoutes,
// },
