import RolesView from '@/modules/admin/access/pages/RolesView.vue'
import UsersView from '@/modules/admin/access/pages/UsersView.vue'
import CollegesView from '@/modules/admin/colleges/pages/CollegesView.vue'
import CourseOfferingsBoard from '@/modules/admin/course-offerings/pages/CourseOfferingsBoard.vue'
import CoursesView from '@/modules/admin/courses/pages/CoursesView.vue'
import CurriculumPlannerView from '@/modules/admin/curriculum/pages/CurriculumPlannerView.vue'
import DepartmentsView from '@/modules/admin/departments/pages/DepartmentsView.vue'
import ImportDetailView from '@/modules/admin/imports/pages/ImportDetailView.vue'
import ImportsListView from '@/modules/admin/imports/pages/ImportsListView.vue'
import NewImportView from '@/modules/admin/imports/pages/NewImportView.vue'
import DashboardView from '@/modules/admin/pages/DashboardView.vue'
import ProgramsView from '@/modules/admin/programs/pages/ProgramsView.vue'
import SectionsView from '@/modules/admin/sections/pages/SectionsView.vue'
import SemestersView from '@/modules/admin/semesters/pages/SemestersView.vue'
import UniversitiesView from '@/modules/admin/universities/pages/UniversitiesView.vue'

export default [
  {
    path: 'dashboard',
    name: 'admin.dashboard',
    component: DashboardView,
  },

  // // ---------------- Academic Structure ----------------
  {
    path: 'universities',
    name: 'admin.universities.list',
    component: UniversitiesView,
  },
  {
    path: 'colleges',
    name: 'admin-colleges',
    component: CollegesView,
  },
  {
    path: 'departments',
    name: 'admin-departments',
    component: DepartmentsView,
  },
  {
    path: 'programs',
    name: 'admin-programs',
    component: ProgramsView,
  },
  {
    path: 'courses',
    name: 'admin.courses.list',
    component: CoursesView,
  },

  // // ---------------- Curriculum ----------------
  { path: 'curriculum', name: 'admin-curriculum', component: CurriculumPlannerView },
  { path: 'semesters', name: 'admin-semesters', component: SemestersView },
  { path: 'sections', name: 'admin-sections', component: SectionsView },

  // // ---------------- Data Import ----------------
  { path: 'imports', name: 'admin-imports', component: ImportsListView },
  { path: 'imports/new', name: 'admin-import-new', component: NewImportView },
  { path: 'imports/:id(\\d+)', name: 'admin-import-detail', component: ImportDetailView },
  // // ---------------- Course Offerings ----------------
  { path: 'course-offerings', name: 'admin-course-offerings', component: CourseOfferingsBoard },

  // // ---------------- Users & Roles ----------------
  { path: 'users', name: 'admin-users', component: UsersView },
  { path: 'roles', name: 'admin-roles', component: RolesView },

  // // ---------------- Results ----------------
  // {
  //   path: 'results',
  //   name: 'admin.results.list',
  //   component: () => import('@/modules/admin/pages/results/ResultsView.vue'),
  // },
]
