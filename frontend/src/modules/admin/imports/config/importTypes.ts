import type { ImportType } from '../types/import';

export interface ContextField {
  key: string;
  label: string;
  kind: 'semester_select' | 'course_select' | 'number' | 'text';
  required: boolean;
}

export interface ImportTypeConfig {
  label: string;
  description: string;
  permission: string;
  contextFields: ContextField[];
  expectedColumns: string[];
  sampleFilename: string;
  sampleContent: string;
}

export const IMPORT_TYPE_CONFIG: Record<ImportType, ImportTypeConfig> = {
  students: {
    label: 'Students',
    description: 'Bulk-create student accounts and place them into their program section.',
    permission: 'student.import',
    contextFields: [
      {
        key: 'semester_id',
        label: 'Semester',
        kind: 'semester_select',
        required: true,
      },
      {
        key: 'year_level',
        label: 'Year level',
        kind: 'number',
        required: true,
      },
    ],
    expectedColumns: ['student_number', 'first_name', 'last_name', 'email', 'program_code', 'section_name', 'entry_year'],
    sampleFilename: 'students-sample.csv',
    sampleContent: `student_number,first_name,last_name,email,program_code,section_name,entry_year
UGR-2026-0101,Abel,Kebede,abel.sample@test.com,SE,A,2026
UGR-2026-0102,Mimi,Worku,mimi.sample@test.com,SE,B,2026
UGR-2026-0103,Dawit,Solomon,dawit.sample@test.com,CS,A,2026
UGR-2026-0104,Hana,Girma,hana.sample@test.com,EE,A,2026`,
  },

  instructors: {
    label: 'Instructors',
    description: 'Create instructor accounts and instructor profiles from a CSV file.',
    permission: 'instructor.import',
    contextFields: [],
    expectedColumns: ['first_name', 'last_name', 'email', 'department_id', 'employee_number', 'academic_rank'],
    sampleFilename: 'instructors-sample.csv',
    sampleContent: `first_name,last_name,email,department_id,employee_number,academic_rank
Bethel,Assefa,bethel.sample@test.com,1,SE-INS-101,Assistant Professor
Nathan,Worku,nathan.sample@test.com,2,CS-INS-101,Assistant Professor
Ruth,Haile,ruth.sample@test.com,3,EE-INS-101,Lecturer`,
  },

  sections: {
    label: 'Sections',
    description: 'Create program sections for a selected semester.',
    permission: 'section.import',
    contextFields: [
      {
        key: 'semester_id',
        label: 'Semester',
        kind: 'semester_select',
        required: true,
      },
    ],
    expectedColumns: ['program_code', 'year_level', 'name'],
    sampleFilename: 'sections-sample.csv',
    sampleContent: `program_code,year_level,name
SE,1,C
SE,3,B
CS,1,B`,
  },

  questions: {
    label: 'Questions',
    description: 'Import question bank entries for a course, optionally attaching them to an exam.',
    permission: 'question.import',
    contextFields: [
      {
        key: 'course_id',
        label: 'Course',
        kind: 'course_select',
        required: true,
      },
    ],
    expectedColumns: ['type', 'content', 'chapter', 'options', 'correct_answer', 'difficulty'],
    sampleFilename: 'questions-sample.csv',
    sampleContent: `type,content,chapter,options,correct_answer,difficulty
mcq,What is a primary key?,Database,"[""A unique identifier"",""A table"",""A database""]",A unique identifier,easy
true_false,A table can have only one primary key.,Keys,"[""True"",""False""]",True,easy
short_answer,What does SQL stand for?,SQL,,,easy
essay,Explain database normalization.,Normalization,,,hard`,
  },

  users: {
    label: 'Users',
    description: 'Create basic user accounts from a CSV file.',
    permission: 'user.import',
    contextFields: [],
    expectedColumns: ['first_name', 'last_name', 'email'],
    sampleFilename: 'users-sample.csv',
    sampleContent: `first_name,last_name,email
Abel,Kebede,abel.sample@test.com
Sara,Mekonnen,sara.sample@test.com
Liya,Desta,liya.sample@test.com`,
  },
};
