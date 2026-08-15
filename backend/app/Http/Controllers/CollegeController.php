<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeRequest;
use App\Http\Requests\UpdateCollegeRequest;
use App\Http\Resources\CollegeResource;
use App\Models\College;
use App\Services\CurrentUniversity;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function store(StoreCollegeRequest $request)
    {
        $validated = $request->validated();
        $college = College::create([
            ...$validated,
            'university_id' => CurrentUniversity::id(),
        ])->load('university:id,name,code,address');

        return $this->success(new CollegeResource($college), 'College created successfully', 201);
    }

    public function show(College $college)
    {
        return $this->success(new CollegeResource($college), 'College retrieved successfully');
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $per_page = min($request->per_page ?? 12, 100);
        $colleges = College::paginate($per_page);

        return $this->paginate($colleges, CollegeResource::class, 'Colleges retrieved successfully');

    }

    public function archived(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $per_page = min($request->per_page ?? 12, 100);
        $colleges = College::onlyTrashed()->paginate($per_page);

        return $this->paginate($colleges, CollegeResource::class, 'Archived colleges retrieved successfully');

    }

    public function update(UpdateCollegeRequest $request, College $college)
    {
        $validated = $request->validated();
        $college->update([
            ...$validated,
            'university_id' => CurrentUniversity::id(),
        ]);

        return $this->success(new CollegeResource($college), 'College updated successfully');
    }

    public function destroy(College $college)
    {
        $college->delete();

        return $this->success(null, 'College deleted successfully');
    }

    public function restore(string $collegeId)
    {
        $college = College::onlyTrashed()->find($collegeId);
        if (! $college) {
            return $this->error('Program not found or is not in the trash', 404);
        }
        $college->restore();

        return $this->success(new CollegeResource($college), 'College restored successfully');

    }
}
