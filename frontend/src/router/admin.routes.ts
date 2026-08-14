import DashboardView from "@/modules/admin/pages/DashboardView.vue";
import UniversitiesView from "@/modules/admin/universities/pages/UniversitiesView.vue";

export default [
  {
    path: 'dashboard',
    name: 'admin.dashboard',
    component: DashboardView
  },

  // // ---------------- Academic Structure ----------------
  {
    path: 'universities',
    name: 'admin.universities.list',
    component: UniversitiesView
  },
  // {
  //   path: 'colleges',
  //   name: 'admin.colleges.list',
  //   component: () => import('@/modules/admin/pages/academic-structure/CollegesView.vue'),
  // },
  // {
  //   path: 'departments',
  //   name: 'admin.departments.list',
  //   component: () => import('@/modules/admin/pages/academic-structure/DepartmentsView.vue'),
  // },
  // {
  //   path: 'programs',
  //   name: 'admin.programs.list',
  //   component: () => import('@/modules/admin/pages/academic-structure/ProgramsView.vue'),
  // },
  // {
  //   path: 'courses',
  //   name: 'admin.courses.list',
  //   component: () => import('@/modules/admin/pages/academic-structure/CoursesView.vue'),
  // },

  // // ---------------- Curriculum ----------------
  // {
  //   path: 'curriculum',
  //   name: 'admin.curriculum.list',
  //   component: () => import('@/modules/admin/pages/curriculum/CurriculumListView.vue'),
  // },
  // {
  //   path: 'curriculum/:curriculumId',
  //   name: 'admin.curriculum.detail',
  //   component: () => import('@/modules/admin/pages/curriculum/CurriculumDetailView.vue'),
  // },

  // // ---------------- Semesters & Sections ----------------
  // {
  //   path: 'semesters',
  //   name: 'admin.semesters.list',
  //   component: () => import('@/modules/admin/pages/semesters/SemestersView.vue'),
  // },
  // {
  //   path: 'sections',
  //   name: 'admin.sections.list',
  //   component: () => import('@/modules/admin/pages/semesters/SectionsView.vue'),
  // },

  // // ---------------- Data Import ----------------
  // {
  //   path: 'imports',
  //   name: 'admin.imports.hub',
  //   component: () => import('@/modules/admin/pages/imports/ImportHubView.vue'),
  // },
  // {
  //   path: 'imports/new/:type',
  //   name: 'admin.imports.new',
  //   component: () => import('@/modules/admin/pages/imports/ImportUploadView.vue'),
  // },
  // {
  //   path: 'imports/:importId/review',
  //   name: 'admin.imports.review',
  //   component: () => import('@/modules/admin/pages/imports/ImportReviewView.vue'),
  // },

  // // ---------------- Course Offerings ----------------
  // {
  //   path: 'course-offerings',
  //   name: 'admin.course-offerings.list',
  //   component: () => import('@/modules/admin/pages/course-offerings/CourseOfferingsView.vue'),
  // },
  // {
  //   path: 'course-offerings/generate',
  //   name: 'admin.course-offerings.generate',
  //   component: () => import('@/modules/admin/pages/course-offerings/GenerateSuggestionsView.vue'),
  // },
  // {
  //   path: 'course-offerings/new',
  //   name: 'admin.course-offerings.new',
  //   component: () => import('@/modules/admin/pages/course-offerings/CreateCourseOfferingView.vue'),
  // },
  // {
  //   path: 'course-offerings/:courseOfferingId',
  //   name: 'admin.course-offerings.detail',
  //   component: () => import('@/modules/admin/pages/course-offerings/CourseOfferingDetailView.vue'),
  // },

  // // ---------------- Users & Roles ----------------
  // {
  //   path: 'users',
  //   name: 'admin.users.list',
  //   component: () => import('@/modules/admin/pages/users/UsersView.vue'),
  // },
  // {
  //   path: 'roles',
  //   name: 'admin.roles.list',
  //   component: () => import('@/modules/admin/pages/users/RolesView.vue'),
  // },
  // {
  //   path: 'roles/:roleId/permissions',
  //   name: 'admin.roles.permissions',
  //   component: () => import('@/modules/admin/pages/users/RolePermissionsView.vue'),
  // },

  // // ---------------- Results ----------------
  // {
  //   path: 'results',
  //   name: 'admin.results.list',
  //   component: () => import('@/modules/admin/pages/results/ResultsView.vue'),
  // },
]
