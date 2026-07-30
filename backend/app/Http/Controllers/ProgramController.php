<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;

class ProgramController extends Controller
{
    public function store(StoreProgramRequest $request)
    {
        $validated = $request->validated();
        $program = Program::create($validated);

        return $this->success(new ProgramResource($program), 'Program created successfully', 201);
    }

    public function index()
    {
        $programs = Program::all();

        return $this->success(ProgramResource::collection($programs), 'Program created successfully', 201);
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        $validated = $request->validated();
        $program->update($validated);

        return $this->success(new ProgramResource($program), 'Program updated successfully', 200);
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return $this->success('', 'Program deleted successfully', 200);
    }

    public function restore(string $programId)
    {
        $program = Program::onlyTrashed()->find($programId);
        if (! $program) {
            return $this->error('', 'Program not found or is not in the trash', 404);
        }
        $program->restore();

        return $this->success(new ProgramResource($program), 'Program restored successfully', 200);
    }
}
