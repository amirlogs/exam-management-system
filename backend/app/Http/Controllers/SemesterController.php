<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSemesterRequest;
use App\Http\Requests\UpdateSemesterRequest;
use App\Models\Semester;

class SemesterController extends Controller
{
    public function store(StoreSemesterRequest $request)
    {
        $validated = $request->validated();

        if (Semester::where('name', $validated['name'])->where('academic_year', $validated['academic_year'])->exists()) {
            return $this->error(null, 'Semester already exists', 409);
        }

        $semester = Semester::create($validated);

        return response()->json($semester, 201);
    }

    public function index()
    {
        $semesters = Semester::all();

        return $this->success($semesters, 'Semesters retrieved successfully');
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

        return $this->success($semester, 'Semester updated successfully');
    }

    // complted others and make this activate
    public function open(Semester $semester)
    {
        $semester->activate();

        return $this->success($semester->refresh(), 'Semester opened successfully');

    }

    public function close(Semester $semester)
    {
        $semester->close();

        return $this->success($semester->refresh(), 'Semester closed successfully');
    }

    public function archive(Semester $semester)
    {
        if ($semester->state() !== 'completed') {
            return $this->error(null, 'Semester has to be completed first');
        }
        $semester->delete();

        return $this->success(null, 'Semester archived successfully');
    }

    public function restore(string $semesterId)
    {
        $semester = Semester::onlyTrashed()->find($semesterId);
        if (! $semester) {
            return $this->error(null, 'Semester not found or not archived', 404);
        }
        $semester->restore();

        return $this->success($semester, 'Semester restored successfully');
    }
}
