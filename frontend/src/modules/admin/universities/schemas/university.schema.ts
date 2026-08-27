import { z } from 'zod';

export const universitySchema = z.object({
  name: z.string().min(3, 'Name must be at least 3 characters').max(255),
  code: z.string().min(2, 'Code must be at least 2 characters').max(255),
  address: z.string().min(3, 'Address must be at least 3 characters').max(255),
});
