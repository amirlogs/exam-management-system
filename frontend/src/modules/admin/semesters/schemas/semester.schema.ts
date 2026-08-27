import { z } from 'zod';

export const semesterSchema = z
  .object({
    academic_year: z.coerce.number().int().min(2016, 'Year must be 2016 or later').max(2030, 'Year must be 2030 or earlier'),
    name: z.coerce.number().int().min(1).max(2),
    start_date: z.string().min(1, 'Start date is required'),
    end_date: z.string().min(1, 'End date is required'),
  })
  .refine((d) => new Date(d.end_date) > new Date(d.start_date), {
    message: 'End date must be after start date',
    path: ['end_date'],
  });
