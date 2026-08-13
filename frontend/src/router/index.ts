import AdminLayout from '@/layouts/AdminLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import InstructorLayout from '@/layouts/InstructorLayout.vue'
import StudentLayout from '@/layouts/StudentLayout.vue'
import LoginView from '@/modules/auth/pages/LoginView.vue'
import NoAccessView from '@/modules/workspace/pages/NoAccessView.vue'
import WorkspaceSelectView from '@/modules/workspace/pages/WorkspaceSelectView.vue'
import NotFoundView from '@/shared/pages/NotFoundView.vue'
import UnauthorizedView from '@/shared/pages/UnauthorizedView.vue'
import { createRouter, createWebHistory } from 'vue-router'
import adminRoutes from './admin.routes'
import { authGuard } from './guards'
import teachingRoutes from './instructor.routes'
import studentRoutes from './student.routes'
const routes = [
  {
    path: '/',
    component: AuthLayout,
    children: [
      {
        path: 'login',
        name: 'login',
        component: LoginView,
        meta: { guestOnly: true },
      },
      {
        path: 'select-workspace',
        name: 'select-workspace',
        component: WorkspaceSelectView,
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'no-access',
        name: 'no-access',
        component: NoAccessView,
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: '/',
        redirect: {
          name: 'select-workspace',
        },
      },
    ],
  },
  {
    path: '/instructor',
    component: InstructorLayout,
    meta: {
      requiresAuth: true,
      workspace: 'instructor',
    },
    children: teachingRoutes,
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: {
      requiresAuth: true,
      workspace: 'admin',
    },
    children: adminRoutes,
  },
  {
    path: '/student',
    component: StudentLayout,
    meta: {
      requiresAuth: true,
      workspace: 'student',
    },
    children: studentRoutes,
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

// router.beforeEach(authGuard)
// router.beforeEach(permissionGuard)
export default router
