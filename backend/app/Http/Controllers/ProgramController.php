<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Department;
use App\Models\Program;

class ProgramController extends Controller
{
    public function store(StoreProgramRequest $request)
    {

        $validated = $request->validated();
        $department = Department::find($validated['department_id']);

        if (! $department) {
            return $this->error('', 'Department not found', 404);
        }

        $program = $department->programs()->create($validated);

        return $this->success(new ProgramResource($program), 'Program created successfully', 201);
    }

    public function index()
    {
        $programs = Program::all();

        if ($programs->isEmpty()) {
            return $this->error('', 'No programs found', 404);
        }

        // return $programs;
        return $this->success(ProgramResource::collection($programs), 'Program created successfully', 201);
    }

    public function update(UpdateProgramRequest $request, string $programId)
    {
        $validated = $request->validated();

        $program = Program::find($programId);

        if (! $program) {
            return $this->error('', 'Program not found', 404);
        }
        if ($request->has('department_id')) {
            
            $department = Department::find($validated['department_id']);

            if (! $department) {
                return $this->error('', 'Department not found', 404);
            }
        }

        $program->update($validated);

        return $this->success(new ProgramResource($program), 'Program updated successfully', 200);
    }

    public function destroy(string $programId)
    {
        $program = Program::find($programId);

        if (! $program) {
            return $this->error('', 'Program not found', 404);
        }

        $program->delete();

        return $this->success('', 'Program deleted successfully', 200);
    }

    public function restore(string $programId)
    {
        $program = Program::withTrashed()->find($programId);

        if (! $program) {
            return $this->error('', 'Program not found', 404);
        }

        $program->restore();

        return $this->success(new ProgramResource($program), 'Program restored successfully', 200);
    } 
}
