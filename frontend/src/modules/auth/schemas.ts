import { z } from 'zod';

export const loginSchema = z.object({
  email: z.string().min(1, 'Email is required').email('Enter a valid email address'),
  password: z.string().min(1, 'Password is required').min(8, 'Password must be at least 8 characters'),
});

export const firstTimePasswordSchema = z
  .object({
    new_password: z.string().min(8, 'Password must be at least 8 characters'),
    new_password_confirmation: z.string().min(8, 'Confirm password is required'),
  })
  .refine((data) => data.new_password === data.new_password_confirmation, {
    message: "Passwords don't match",
    path: ['new_password_confirmation'],
  });

export type LoginForm = z.infer<typeof loginSchema>;
export type FirstTimePasswordForm = z.infer<typeof firstTimePasswordSchema>;
