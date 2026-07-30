<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeRequest;
use App\Http\Requests\UpdateCollegeRequest;
use App\Http\Resources\CollegeResource;
use App\Models\College;

class CollegeController extends Controller
{
    public function store(StoreCollegeRequest $request)
    {
        $validated = $request->validated();
        $college = College::create($validated)->load('university:id,name,code,address');

        return $this->success($college, 'College created successfully', 201);
    }

    public function index()
    {
        $colleges = College::all();

        return $this->success(CollegeResource::collection($colleges), 'Colleges retrieved successfully');
    }

    public function update(UpdateCollegeRequest $request, College $college)
    {
        $validated = $request->validated();
        $college->update($validated);

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
