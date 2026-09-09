import type { QuestionOption, QuestionType } from '../../exams/types/exam';

export interface StudentUser {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  full_name?: string;
}

export interface StudentInfo {
  id: number;
  student_number: string;
  user?: StudentUser;
}

export interface AnswerItem {
  id: number;
  exam_attempt_id: number;
  exam_question_id: number;
  question_id: number;
  selected_option_id: number | null;
  answer_text: string | null;
  is_correct: boolean | null;
  marks_awarded: number | null;
  graded_by: number | null;
  graded_at: string | null;
  question?: {
    id: number;
    type: QuestionType;
    content: string;
    options?: QuestionOption[];
  };
  exam_question?: {
    id: number;
    order_number: number;
    marks: number;
  };
}

export interface ExamAttemptSubmission {
  id: number;
  exam_id: number;
  student_id: number;
  status: 'in_progress' | 'completed' | 'auto_submitted' | 'graded';
  score: number | null;
  started_at: string | null;
  submitted_at: string | null;
  student?: StudentInfo;
  answers_count?: number;
  graded_answers_count?: number;
  pending_grading_count?: number;
  answers?: AnswerItem[];
}

export interface GradeItem {
  id: number;
  student_id: number;
  course_offering_id: number;
  exam_id: number;
  score: number;
  status: 'pending_verification' | 'verified' | 'published' | 'rejected';
  graded_by: number;
  student?: StudentInfo;
}

export interface GradeAnswerPayload {
  marks_awarded: number;
}
