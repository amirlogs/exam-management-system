<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreSemesterRequest;
use App\Http\Requests\UpdateSemesterRequest;
use App\Http\Resources\SemesterResource;
use App\Http\Search\RequestSearch;
use App\Models\Semester;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function store(StoreSemesterRequest $request)
    {
        $validated = $request->validated();

        if (Semester::where('name', $validated['name'])->where('academic_year', $validated['academic_year'])->exists()) {
            return $this->error(null, 'Semester already exists', 409);
        }

        $semester = Semester::with('courses')->create($validated);

        // return response()->json($semester, 201);
        return $this->success(new SemesterResource($semester), 'Semester created successfully', 201);
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Semester::query();
        RequestSearch::apply($query, $request, ['academic_year', 'status']);
        RequestFilters::apply($query, $request, ['academic_year', 'status']);
        $semesters = $query->paginate($per_page);

        return $this->paginate($semesters, SemesterResource::class, 'Semesters retrieved successfully');
    }

    public function update(Semester $semester, UpdateSemesterRequest $request)
    {
        $validated = $request->validated();
        if ($semester->state() === 'completed') {
            $this->error(null, 'Semester is completed', 409);
        }

        if (Semester::where('name', $validated['name'])->where('academic_year', $validated['academic_year'])->where('id', '!=', $semester->id)->exists()) {
            return $this->error(null, 'Semester already exists', 409);
        }

        $semester->update($validated);

        return $this->success(new SemesterResource($semester), 'Semester updated successfully');
    }

    public function open(Semester $semester)
    {
        if ($semester->state() === 'active') {
            return $this->error(null, 'Semester is already active', 409);
        }
        if (Semester::where('status', 'active')->exists()) {
            return $this->error(null, 'Another semester is already active please update it first', 409);
        }
        $semester->activate();

        return $this->success(new SemesterResource($semester->refresh()), 'Semester opened successfully');

    }

    public function close(Semester $semester)
    {
        if ($semester->state() === 'completed') {
            return $this->error(null, 'Semester is already closed', 409);
        }

        $semester->close();

        return $this->success(new SemesterResource($semester->refresh()), 'Semester closed successfully');
    }

    public function archive(Semester $semester)
    {
        if ($semester->state() !== 'completed') {
            return $this->error(null, 'Semester has to be completed first');
        }
        $semester->delete();

        return $this->success(new SemesterResource($semester), 'Semester archived successfully');
    }

    public function restore(string $semesterId)
    {
        $semester = Semester::onlyTrashed()->find($semesterId);
        if (! $semester) {
            return $this->error(null, 'Semester not found or not archived', 404);
        }
        $semester->restore();

        return $this->success(new SemesterResource($semester), 'Semester restored successfully');
    }

    public function archived(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Semester::query();
        RequestSearch::apply($query, $request, ['name', 'academic_year']);
        RequestFilters::apply($query, $request, ['academic_year', 'status']);
        $semesters = $query->onlyTrashed()->paginate($per_page);

        return $this->paginate($semesters, SemesterResource::class, 'Semesters retrieved successfully');
    }
}
