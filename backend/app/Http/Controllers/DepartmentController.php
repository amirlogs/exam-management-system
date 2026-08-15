<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\College;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function store(StoreDepartmentRequest $request)
    {
        $validated = $request->validated();
        $department = Department::create($validated);

        return $this->success(new DepartmentResource($department), 'Department created successfully', 201);
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $per_page = min($request->per_page ?? 12, 100);
        $departments = Department::with('college')->paginate($per_page);

        return $this->paginate($departments, DepartmentResource::class, 'Departments retrieved successfully');
    }

    public function archived(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $per_page = min($request->per_page ?? 12, 100);
        $departments = Department::onlyTrashed()->with('college')->paginate($per_page);

        return $this->paginate($departments, DepartmentResource::class, 'Archived Departments retrieved successfully');
    }

    public function show(Department $department)
    {
        return $this->success(new DepartmentResource($department->load('college')), 'Department retrieved successfully', 200);
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
