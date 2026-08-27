type Rule = (value: string) => string | null;

export const required =
  (label: string): Rule =>
  (v) =>
    !v || !v.trim() ? `${label} is required` : null;

export const minLength =
  (n: number, label: string): Rule =>
  (v) =>
    v && v.trim().length < n ? `${label} must be at least ${n} characters` : null;

export const maxLength =
  (n: number, label: string): Rule =>
  (v) =>
    v && v.trim().length > n ? `${label} must be at most ${n} characters` : null;

export function runValidators(value: string, rules: Rule[]): string {
  for (const rule of rules) {
    const err = rule(value);
    if (err) return err;
  }
  return '';
}
