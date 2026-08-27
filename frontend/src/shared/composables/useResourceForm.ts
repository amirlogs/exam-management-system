import { reactive } from 'vue';
import type { ZodSchema } from 'zod';

export function useResourceForm<T extends Record<string, any>>(schema: ZodSchema<T>, initial: T) {
  const form = reactive({ ...initial }) as T;
  const errors = reactive<Record<string, string>>({});

  function resetErrors() {
    Object.keys(errors).forEach((k) => delete errors[k]);
  }

  function reset(values: T) {
    Object.assign(form, values);
    resetErrors();
  }

  function validate(): boolean {
    resetErrors();
    const result = schema.safeParse(form);
    if (!result.success) {
      for (const issue of result.error.issues) {
        const field = issue.path[0] as string;
        if (!errors[field]) errors[field] = issue.message;
      }
      return false;
    }
    return true;
  }

  // Maps Laravel's 422 { errors: { field: ["msg"] } } onto the same inline errors object
  function applyServerErrors(serverErrors: Record<string, string[] | string> | null | undefined) {
    if (!serverErrors) return;
    for (const [field, msg] of Object.entries(serverErrors)) {
      errors[field] = Array.isArray(msg) ? msg[0] : msg;
    }
  }

  return { form, errors, validate, reset, applyServerErrors };
}
