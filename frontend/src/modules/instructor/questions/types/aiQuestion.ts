export type AiQuestionType = 'mcq' | 'true_false' | 'short_answer' | 'essay';
export type AiDifficulty = 'easy' | 'medium' | 'hard';
export type AiGenerationStatus = 'pending' | 'ready_for_review' | 'confirmed' | 'failed';

export interface GenerateQuestionsPayload {
  input: string;
  count?: number;
  is_note?: boolean;
  type?: AiQuestionType;
  difficulty?: AiDifficulty;
  context?: {
    course_id?: number | null;
    exam_id?: number | null;
  };
}

export interface GeneratedQuestionItemData {
  type: AiQuestionType;
  content: string;
  chapter?: string;
  options?: string[];
  correct_answer?: string | boolean;
  difficulty?: AiDifficulty;
}

export interface GeneratedQuestionItem {
  index?: number;
  data?: GeneratedQuestionItemData;
  type?: AiQuestionType;
  content?: string;
  chapter?: string;
  options?: string[];
  correct_answer?: string | boolean;
  difficulty?: AiDifficulty;
  status?: string;
  errors?: string[];
}

export interface GeneratedQuestionsHistory {
  id: number;
  input: string;
  count: number;
  is_note: boolean;
  type: AiQuestionType;
  difficulty: AiDifficulty;
  status: AiGenerationStatus;
  context?: {
    course_id?: number;
    exam_id?: number;
  };
  total_rows: number;
  valid_count: number;
  error_count: number;
  questions: (GeneratedQuestionItem | GeneratedQuestionItemData)[];
  uploaded_by?: {
    id: number;
    name: string;
    email: string;
  };
  created_at: string;
  updated_at: string;
}
