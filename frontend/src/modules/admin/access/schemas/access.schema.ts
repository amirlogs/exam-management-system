import { z } from 'zod';

export const userSchema = z.object({
  first_name: z.string().min(3, 'First name must be at least 3 characters').max(255),
  last_name: z.string().min(3, 'Last name must be at least 3 characters').max(255),
  email: z.string().email('Enter a valid email'),
  password: z.string().min(8, 'Password must be at least 8 characters'),
});
export const editUserSchema = userSchema.pick({ first_name: true, last_name: true });

export const roleSchema = z.object({
  name: z.string().min(3, 'Name must be at least 3 characters').max(255),
  description: z.string().min(3, 'Description must be at least 3 characters').max(1000),
});
