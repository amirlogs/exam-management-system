import type { ZodSchema } from 'zod'

export function zodToVeeValidate<T>(schema: ZodSchema<T>) {
  return (values: T) => {
    const result = schema.safeParse(values)
    console.log('validating', values, result.success, result.success ? null : result.error.issues)

    if (result.success) return {}

    const errors: Record<string, string> = {}
    result.error.issues.forEach((issue) => {
      const path = issue.path.join('.')
      if (!errors[path]) errors[path] = issue.message
    })
    return errors
  }
}
