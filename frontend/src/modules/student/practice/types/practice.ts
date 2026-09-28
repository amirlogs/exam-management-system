export type PracticeExamStatus = 'draft' | 'active' | 'completed';
export type PracticeQuestionType = 'mcq' | 'true_false' | 'short_answer' | 'essay';
export type PracticeDifficulty = 'easy' | 'medium' | 'hard';

export interface PracticeQuestionOption {
  id: number;
  option_text: string;
  is_correct?: boolean;
}

export interface PracticeQuestion {
  id: number;
  type: PracticeQuestionType;
  content: string;
  difficulty: PracticeDifficulty;
  status: string;
  options: PracticeQuestionOption[];
  practice_exam_id?: number;
  practice_question_histories_id?: number;
  explanation?: string;
  user_answer?: {
    id: number;
    selected_option_id: number;
    is_correct: boolean;
    answer_text?: string | null;
  } | null;
  created_at?: string;
  updated_at?: string;
}

export interface PracticeExam {
  id: number;
  title: string;
  duration_minutes: number;
  composition: Record<string, any>;
  total_marks: number;
  total_questions: number;
  status: PracticeExamStatus;
  owner?: {
    id: number;
    name: string;
    email: string;
  };
  created_at: string;
  updated_at: string;
}

export interface PracticeQuestionHistory {
  id: number;
  owned_by: number;
  input: string;
  count: number;
  isNote: boolean;
  type: string;
  difficulty: string;
  practice_exam_id: number;
  total_rows: number;
  valid_count: number;
  error_count: number;
  validated_question: any[] | null;
  status: 'pending' | 'processing' | 'ready_for_review' | 'confirmed' | 'failed';
  created_at: string;
  updated_at: string;
}

export interface CreatePracticeExamPayload {
  title: string;
  duration_minutes: number;
  composition: Record<string, { marks_each?: number; count?: number }>;
}

export interface GeneratePracticeQuestionsPayload {
  input: string;
  count: number;
  is_note: boolean;
  type: string;
  difficulty: string;
}

export interface SubmitPracticeAnswerPayload {
  option_id: number;
}

export interface SubmitPracticeAnswerResponse {
  answer: {
    id: number;
    practice_exam_id: number;
    practice_question_id: number;
    selected_option_id: number;
    is_correct: boolean;
    user_id: number;
  };
  is_correct: boolean;
  selected_option_id: number;
  correct_option_id: number;
}

export interface QuestionGuidance {
  id: number;
  user_id: number;
  student_id: number;
  practice_exam_id: number;
  practice_question_id: number;
  prompt: string;
  response: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface AskGuidancePayload {
  prompt: string;
}
