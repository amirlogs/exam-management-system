<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\College;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function store(StoreDepartmentRequest $request)
    {
        $validated = $request->validated();
        $college = College::find($validated['college_id']);

        if (! $college) {
            return $this->error('', 'College not found', 404);
        }
        $department = $college->departments()->create($validated);

        return $this->success($department, 'Department created successfully', 201);

    }

    public function index()
    {
        $departments = Department::all();

        if ($departments->isEmpty()) {
            $this->error('', 'No Departments Found', 404);
        }

        return $this->success(DepartmentResource::collection($departments), 'Departments fetched successfully', 200);
    }

    public function update(UpdateDepartmentRequest $request, string $id)
    {
        $validated = $request->validated();
        $department = Department::find($id);
        if (! $department) {
            return $this->error('', 'Department not found', 404);

        }
        if ($request->has('college_id')) {
            $college = College::find($validated['college_id']);
            if (! $college) {
                return $this->error('', 'College not found', 404);
            }
        }
        $department->update($validated);

        return $this->success(new DepartmentResource($department), 'Department updated successfully', 200);

    }

    public function destroy(Department $department)
    {
        if (! $department) {
            return $this->error('', 'Department not found', 404);
        }

        $department->delete();

        return $this->success('', 'Department Archived successfully', 200);
    }

    public function restore(string $departmentId)
    {
        $department = Department::withTrashed()->find($departmentId);

        if (! $department) {
            return $this->error('', 'Department not found', 404);
        }

        $department->restore();

        return $this->success(new DepartmentResource($department), 'Department Restored successfully', 200);
    }
}
