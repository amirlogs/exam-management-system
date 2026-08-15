<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\UpdateUniversityRequest;
use App\Http\Resources\UniversityResource;
use App\Models\University;
use Illuminate\Http\Request;

class UniversityController extends Controller
{
    public function store(StoreUniversityRequest $request)
    {
        $data = $request->validated();
        $university = University::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'address' => $data['address'],
        ]);

        return $this->success($university, 'University created successfully', 201);
    }

    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10), 25);
        $universities = University::paginate($perPage);

        return $this->paginate($universities, UniversityResource::class, 'Universities retrieved successfully');
    }

    public function show(University $university)
    {
        return $this->success(new UniversityResource($university), 'University retrieved successfully');
    }

    public function update(University $university, UpdateUniversityRequest $request)
    {
        $data = $request->validated();
        $university->update($data);

        return $this->success($university, 'University updated successfully');

    }

    public function destroy(University $university)
    {
        $university->delete();

        return $this->success('', 'University achieved successfully');
    }

    public function restore(string $id)
    {
        $university = University::onlyTrashed()->find($id);

        if (! $university) {
            return $this->error('', 'Program not found or is not in the trash', 404);
        }

        $university->restore();

        return $this->success($university, 'University restored successfully');
    }

    public function archived(Request $request)
    {
        $perPage = min($request->integer('per_page', 10), 25);
        $universities = University::onlyTrashed()->paginate($perPage);

        return $this->paginate($universities, UniversityResource::class, 'Archived university retrieved successfully');
    }
}
