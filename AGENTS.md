# jAGENTS.md — Exam Management System

This file is the entry point for any AI agent (Gemini CLI, Antigravity, Claude, etc.)
working on this repo. Read this fully before touching any code, and before writing
anything, restate your understanding of the relevant module back to the human for
confirmation.

## What this project is

A university exam management system. Universities → Colleges → Departments →
Programs → Curricula/Courses → Semesters/Sections → Course Offerings → Enrollments.
On top of that: Questions (bank, approval, flags), Exams (composition, approval,
scheduling, activate/publish/end), student ExamAttempts + Answers, Grading +
verification, Results, Roles/Permissions, audit logs, CSV imports.

Three user workspaces: **admin**, **instructor**, **student**. Each has its own
frontend module tree and its own route prefix on the backend.

## Stack

- Backend: Laravel 13, PHP 8.3+, Sanctum auth, spatie-style permission middleware,
  maatwebsite/excel for CSV imports, Pest for tests.
- Frontend: Vue 3 (Composition API only, `<script setup lang="ts">`), TypeScript,
  Vite, Pinia, vue-router, Tailwind v4, vee-validate + zod for forms.

## Hard rules

1. **Do not modify anything under `backend/` unless the task explicitly says to
   change the backend.** Most sessions are frontend-only, consuming an existing
   API. If you believe the backend needs a change to make a task possible, STOP
   and say so instead of making the change yourself.
2. **Do not invent new UI primitives.** Reuse what's in
   `frontend/src/shared/components/` and `shared/components/ui/`
   (`BaseButton`, `BaseCard`, `BaseInput`, `BaseSelect`, `BaseBadge`,
   `ResourceToolbar`, `ConfirmModal`, etc.) instead of writing raw `<button>`/
   ad-hoc markup styling.
3. **Follow the existing module pattern exactly** (see below) — don't restructure
   folders or rename existing files as a side effect of a feature task.
4. **Before writing any code, list every existing file you read and every
   assumption you're making**, so the human can catch a wrong assumption before
   code gets written, not after.
5. **One module/feature per session.** Don't touch files outside the module you
   were asked to build unless a bug there is blocking the task — and if so, call
   it out explicitly rather than silently fixing it.
6. Never fetch/guess API shapes from memory — read the actual controller/route/
   resource file for the endpoint you're integrating with.

## Backend conventions

- Controllers extend `App\Http\Controllers\Controller`, which provides three
  response helpers (from `App\Traits\ApiResponse`) — always use these, never
  return raw `response()->json()`:
  - `$this->success($data, $message = null, $code = 200)`
  - `$this->paginate($paginator, ResourceClass::class, $message)` — wraps a
    paginator, returns `data` (via the Resource) + a `pagination` block
    (`current_page`, `last_page`, `per_page`, `total`, `from`, `to`).
  - `$this->error($errors, $message = 'An error occurred', $code = 400)`
  - Every JSON response has the shape:
    `{ success, message, data, errors }` (plus `pagination` when paginated).
- Route model binding parameter names in `routes/api.php` **must match** the
  controller method's parameter name exactly (implicit binding is by name, not
  position) — this codebase has had bugs from this mismatch before, double check it.
- Permissions are enforced via `permission:` route middleware on the backend
  and mirrored as `meta: { permissions: [...], permissionMode: 'any'|'all' }`
  on the matching frontend route. Look up real permission names in
  `database/seeders/RolePermissionSeeder.php` — don't guess a permission name.
- Authorization for student/instructor-scoped resources goes through Policies
  in `app/Policies/` (e.g. `ExamPolicy@viewAsStudent`,
  `ExamAttemptPolicy@canSubmitAnswer`), called via `$this->authorize(...)`.
- List/filter endpoints use `App\Http\Filters\RequestFilters::apply($query,
$request, [...fields])` for simple `where` filters, plus
  `App\Validation\GetRequestsValidator::validate($request)` to resolve
  `per_page`.
- API Resources in `app/Http/Resources/` control the exact JSON shape per
  model — always check the Resource file, the raw model's `$fillable` is not
  what gets sent to the frontend.

## Frontend conventions

- Every workspace (`admin`, `instructor`, `student`) has its own route file:
  `frontend/src/router/{workspace}.routes.ts`.
- Feature modules live at
  `frontend/src/modules/{workspace}/{feature}/` with subfolders:
  - `pages/` — one component per route, wired directly into the router.
  - `components/` — reusable pieces used by pages in this feature (forms,
    table rows, wizard steps). Not routed directly.
  - `api/{feature}.ts` — thin wrapper functions around axios calls for this
    feature only.
  - `types/{feature}.ts` — TypeScript interfaces for this feature's data.
- Route names are dot-namespaced: `{workspace}.{feature}.{action}`
  (e.g. `instructor.questions.detail`). Every route a page's script actually
  calls `router.push({ name: ... })` for MUST exist in the routes file —
  cross check this both ways before finishing a task.
- List pages generally follow: `ResourceToolbar` header (search/refresh/
  fullscreen), a filter bar, a data table or card grid, pagination footer
  wired to the `pagination` block the backend returns.
- Toasts/errors go through `useUiStore().showToast(message, 'success'|'error')`
  and the `handleApiError(error, uiStore, undefined, fallbackMessage)` helper
  (`shared/utils/apiError.ts`) — don't roll your own error handling.
- API payload keys sent to the backend must be **snake_case**, matching what
  the Laravel controller reads off `$request` — even when the Vue-side
  form/emit uses camelCase internally, convert at the page level before
  calling the API function.

## Known landmines already fixed once (don't reintroduce)

- Don't duplicate route arrays or leave `import` statements pointing at
  component files that don't exist — a single unresolvable import in a routes
  file breaks the entire workspace's build, not just one page.
- The question-bank "add manually" and "import" flows depend on courses
  scoped to _this user's_ teaching assignments (`getTeaching()`), not the
  admin-only `/courses` endpoint (`course.view` permission) — instructors
  don't have that permission, so using the admin course list silently 403s.
- `ImportUploadStep.vue` (admin/imports/components) is shared across
  workspaces — extend it with optional props rather than forking it per
  workspace.

## How to start any session

1. Read this file.
2. Read the specific existing sibling module you're told to pattern-match
   (e.g. if building something for `student`, read the equivalent
   `instructor` module's `pages/`, `api/`, `types/` first).
3. Read the actual backend controller/routes/resources for every endpoint
   you'll call — don't assume the payload shape.
4. Write a short plan (files you'll create/edit, one line each) and wait for
   confirmation before generating code, unless told to just go ahead.
