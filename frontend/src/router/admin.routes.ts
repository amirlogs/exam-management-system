import RolesView from '@/modules/admin/access/pages/RolesView.vue';
import UsersView from '@/modules/admin/access/pages/UsersView.vue';
import CollegesView from '@/modules/admin/colleges/pages/CollegesView.vue';
import CourseOfferingsView from '@/modules/admin/course-offerings/pages/CourseOfferingsView.vue';
import CoursesView from '@/modules/admin/courses/pages/CoursesView.vue';
import CurriculumPlannerView from '@/modules/admin/curriculum/pages/CurriculumPlannerView.vue';
import DepartmentsView from '@/modules/admin/departments/pages/DepartmentsView.vue';
import ImportDetailView from '@/modules/admin/imports/pages/ImportDetailView.vue';
import ImportsListView from '@/modules/admin/imports/pages/ImportsListView.vue';
import NewImportView from '@/modules/admin/imports/pages/NewImportView.vue';
import InstructorsView from '@/modules/admin/instructors/pages/InstructorsView.vue';
import DashboardView from '@/modules/admin/pages/DashboardView.vue';
import ProgramsView from '@/modules/admin/programs/pages/ProgramsView.vue';
import QuestionBankView from '@/modules/admin/questions/pages/QuestionBankView.vue';
import SectionsView from '@/modules/admin/sections/pages/SectionsView.vue';
import SemestersView from '@/modules/admin/semesters/pages/SemestersView.vue';
import StudentsView from '@/modules/admin/students/pages/StudentsView.vue';
import UniversitiesView from '@/modules/admin/universities/pages/UniversitiesView.vue';

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
    meta: {
      permissions: ['university.view'],
    },
  },
  {
    path: 'colleges',
    name: 'admin-colleges',
    component: CollegesView,
    meta: {
      permissions: ['college.view'],
    },
  },
  {
    path: 'departments',
    name: 'admin-departments',
    component: DepartmentsView,
    meta: {
      permissions: ['department.view'],
    },
  },
  {
    path: 'programs',
    name: 'admin-programs',
    component: ProgramsView,
    meta: {
      permissions: ['program.view'],
    },
  },
  {
    path: 'courses',
    name: 'admin.courses.list',
    component: CoursesView,
    meta: {
      permissions: ['course.view'],
    },
  },

  // // ---------------- Curriculum ----------------
  {
    path: 'curriculum',
    name: 'admin-curriculum',
    component: CurriculumPlannerView,
    meta: {
      permissions: ['curriculum.view'],
    },
  },
  {
    path: 'semesters',
    name: 'admin-semesters',
    component: SemestersView,
    meta: {
      permissions: ['semester.view'],
    },
  },
  {
    path: 'sections',
    name: 'admin-sections',
    component: SectionsView,
    meta: {
      permissions: ['section.view'],
    },
  },

  // // ---------------- Data Import ----------------
  {
    path: 'imports',
    name: 'admin-imports',
    component: ImportsListView,
    meta: {
      permissions: ['import.view'],
    },
  },
  {
    path: 'imports/new',
    name: 'admin-import-new',
    component: NewImportView,
    meta: {
      permissions: ['student.import', 'instructor.import', 'section.import', 'question.import', 'user.import'],
      permission_mode: 'any',
    },
  },
  {
    path: 'imports/:id(\\d+)',
    name: 'admin-import-detail',
    component: ImportDetailView,
    meta: {
      permissions: ['import.view'],
    },
  },
  // // ---------------- Course Offerings ----------------
  {
    path: 'course-offerings',
    name: 'admin-course-offerings',
    component: CourseOfferingsView,
    meta: {
      permissions: ['course_offering.view'],
    },
  },

  // // ---------------- Users  ----------------
  {
    path: 'users',
    name: 'admin-users',
    component: UsersView,
  },
  {
    path: 'instructors',
    name: 'admin-instructors',
    component: InstructorsView,
    meta: {
      permissions: ['instructor.view'],
    },
  },
  {
    path: 'students',
    name: 'admin-students',
    component: StudentsView,
    meta: {
      permissions: ['student.view'],
    },
  },
  {
    path: 'questions',
    name: 'admin-questions',
    component: QuestionBankView,
    meta: {
      permissions: ['question.view_all'],
    },
  },
  // // ---------------- Roles  ----------------
  {
    path: 'roles',
    name: 'admin-roles',
    component: RolesView,
    meta: {
      permissions: ['role.view'],
    },
  },

  // // ---------------- Results ----------------
  // {
  //   path: 'results',
  //   name: 'admin.results.list',
  //   component: () => import('@/modules/admin/pages/results/ResultsView.vue'),
  // },
];
