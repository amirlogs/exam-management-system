import type { ImportType } from '../types/import'

export interface ContextField {
  key: string
  label: string
  kind: 'semester_select' | 'program_select' | 'course_select' | 'number' | 'text'
  required: boolean
}

export interface ImportTypeConfig {
  label: string
  description: string
  contextFields: ContextField[]
  // Pulled directly from each Validator's $rules — keeps the UI honest about what the CSV needs
  expectedColumns: string[]
}

export const IMPORT_TYPE_CONFIG: Record<ImportType, ImportTypeConfig> = {
  students: {
    label: 'Students',
    description: 'Bulk-enroll students into a program and section for a given semester.',
    contextFields: [
      { key: 'semester_id', label: 'Semester', kind: 'semester_select', required: true },
      { key: 'year_level', label: 'Year level', kind: 'number', required: true },
    ],
    expectedColumns: ['student_number', 'first_name', 'last_name', 'email', 'program_code', 'section_name', 'entry_year'],
  },
  instructors: {
    label: 'Instructors',
    description: 'Create instructor accounts. No additional context needed.',
    contextFields: [],
    expectedColumns: ['first_name', 'last_name', 'email'],
  },
  sections: {
    label: 'Sections',
    description: 'Bulk-create class sections for a semester.',
    contextFields: [{ key: 'semester_id', label: 'Semester', kind: 'semester_select', required: true }],
    expectedColumns: ['program_code', 'year_level', 'name'],
  },
  questions: {
    label: 'Questions',
    description: 'Bulk-import question bank entries for a course, optionally attached to an exam.',
    contextFields: [
      { key: 'course_id', label: 'Course', kind: 'course_select', required: true },
      { key: 'exam_id', label: 'Exam (optional)', kind: 'number', required: false },
    ],
    expectedColumns: ['type', 'content', 'chapter', 'options', 'correct_answer', 'difficulty'],
  },
  enrollments: {
    label: 'Enrollments',
    description: 'Bulk-enroll students into course offerings.',
    // ASSUMPTION — context rules for enrollments weren't provided, same flag as before
    contextFields: [],
    expectedColumns: [],
  },
}
