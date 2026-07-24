import { defineStore } from 'pinia'
import { ref } from 'vue'

export const usePermissionsStore = defineStore('permissions', () => {
  const permissions = ref<string[]>([])
  const workspaces = ref<string[]>([])

  const setPermissions = (newPermissions: string[]) => {
    permissions.value = [...newPermissions]
  }
  function can(permission: string) {
    return permissions.value.includes(permission)
  }

  function clear() {
    permissions.value = []
  }

  const admin = [
    'create_departments',
    'create_users',
    'manage_user_roles',
    'manage_role_permissions',
    'view_audit_logs',
    'view_anticheat_logs',
    'escalate_anticheat_alerts',
    'restore_deleted_data',
    'manage_enrollment_exceptions',
    'approve_question_import',
    'approve_question_group',
    'reopen_question_group',
    'adjust_session_timing',
    'revise_grades_after_window',
  ]
  const teaching = [
    'upload_question_bank',
    'confirm_question_import',
    'flag_questions',
    'create_question_group',
    'add_questions_to_group',
    'submit_group_for_approval',
    'create_exam',
    'schedule_exam_session',
    'view_grading_queue',
    'grade_essay_questions',
    'release_results',
    'revise_grades_within_window',
    'view_course_analytics',
    'view_student_analytics',
    'view_per_question_metrics',
  ]

  const student = [
    'view_eligible_exams',
    'start_exam_attempt',
    'submit_exam_answers',
    'finalize_exam_attempt',
  ]
  const getVisibleWorkspaces = () => {
    for (const permission of admin) {
      if (permissions.value.includes(permission)) {
        console.log('admin')
        workspaces.value.push('admin')
        break
      }
    }
    for (const permission of teaching) {
      if (permissions.value.includes(permission)) {
        console.log('teaching')
        workspaces.value.push('teaching')
        break
      }
    }
    for (const permission of student) {
      if (permissions.value.includes(permission)) {
        console.log('student')
        workspaces.value.push('student')
        break
      }
    }
    return workspaces.value
  }
  return {
    permissions,
    setPermissions,
    can,
    clear,
    getVisibleWorkspaces,
  }
})
