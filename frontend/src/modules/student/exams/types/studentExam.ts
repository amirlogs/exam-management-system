export interface StudentExamAttemptInfo {
  id: number;
  status: 'in_progress' | 'completed' | 'submitted' | 'auto_submitted' | 'graded' | string;
  started_at: string | null;
  submitted_at: string | null;
  score: number | null;
}

export interface StudentExam {
  id: number;
  course_offering_id: number;
  title: string;
  type: string;
  duration_minutes: number;
  total_marks: number;
  total_questions: number;
  status: 'scheduled' | 'active' | 'completed' | string;
  grading_status?: 'not_started' | 'in_progress' | 'completed' | 'published' | string;
  scheduled_start: string | null;
  scheduled_end: string | null;
  course: {
    id: number;
    code: string;
    name: string;
  } | null;
  semester: {
    id: number;
    name: string;
  } | null;
  attempt: StudentExamAttemptInfo | null;
  created_at?: string;
}

export interface ReviewOptionItem {
  id: number;
  option_text: string;
  is_correct: boolean;
}

export interface ReviewQuestionItem {
  id: number;
  exam_question_id: number;
  question_id: number;
  order_number: number;
  marks: number;
  marks_awarded: number | null;
  is_correct: boolean;
  type: 'mcq' | 'true_false' | 'short_answer' | 'essay' | string;
  content: string;
  explanation: string | null;
  options?: ReviewOptionItem[];
  selected_answer_id?: number | null;
  answer_text?: string | null;
}

export interface ExamReviewData {
  attempt: {
    id: number;
    score: number | null;
    total_marks: number;
    status: string;
    submitted_at: string | null;
  };
  questions: ReviewQuestionItem[];
}

export interface QuestionOptionItem {
  id: number;
  option_text: string;
}

export interface StudentExamQuestion {
  id: number;
  exam_question_id: number;
  question_id: number;
  order_number: number;
  marks: number;
  type: 'mcq' | 'true_false' | 'short_answer' | 'essay';
  content: string;
  options?: QuestionOptionItem[];
  selected_answer_id?: number | null;
  answer_text?: string | null;
}

export interface SubmitAnswerPayload {
  exam_question_id: number;
  selected_option_id?: number | null;
  answer_text?: string | null;
}

export interface ExamAttempt {
  id: number;
  exam_id: number;
  student_id: number;
  status: 'in_progress' | 'completed' | 'graded';
  started_at: string;
  submitted_at: string | null;
  score: number | null;
}
