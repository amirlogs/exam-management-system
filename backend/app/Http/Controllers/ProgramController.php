<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Http\Search\RequestSearch;
use App\Models\Program;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function store(StoreProgramRequest $request)
    {
        $validated = $request->validated();
        $program = Program::create($validated);

        return $this->success(new ProgramResource($program), 'Program created successfully', 201);
    }

    public function index(Request $request)
    {

        $per_page = GetRequestsValidator::validate($request);
        $query = Program::query();
        RequestSearch::apply($query, $request, ['name']);
        RequestFilters::apply($query, $request, ['department_id']);
        $programs = $query->paginate($per_page);

        return $this->paginate($programs, ProgramResource::class, 'Programs retrieved successfully');
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

        return $this->success('', 'Program archived successfully', 200);
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

    public function archived(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Program::query();
        RequestSearch::apply($query, $request, ['name']);
        RequestFilters::apply($query, $request, ['department_id']);
        $programs = $query->onlyTrashed()->paginate($per_page);

        return $this->paginate($programs, ProgramResource::class, 'Archived programs retrieved successfully');
    }
}
