import { z } from 'zod'

export const questionSchema = z.object({
  type: z.enum(['mcq', 'true_false', 'short_answer', 'essay']),
  content: z.string().min(5, 'Content must be at least 5 characters'),
  chapter: z.string().optional(),
  difficulty: z.enum(['easy', 'medium', 'hard']),
  options: z.array(z.string()).optional(),
  correct_answer: z.string().optional(),
})
