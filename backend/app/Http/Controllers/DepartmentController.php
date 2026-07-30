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
        $department = Department::create($validated);

        return $this->success($department, 'Department created successfully', 201);
    }

    public function index()
    {
        $departments = Department::all();

        return $this->success(DepartmentResource::collection($departments), 'Departments fetched successfully', 200);
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $validated = $request->validated();

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
        $department->delete();

        return $this->success('', 'Department Archived successfully', 200);
    }

    public function restore(string $departmentId)
    {
        $department = Department::onlyTrashed()->find($departmentId);
        if (! $department) {
            return $this->error('', 'Program not found or is not in the trash', 404);
        }
        $department->restore();

        return $this->success(new DepartmentResource($department), 'Department Restored successfully', 200);
    }
}
