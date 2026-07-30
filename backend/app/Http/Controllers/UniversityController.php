<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\UpdateUniversityRequest;
use App\Models\University;

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

    public function index()
    {
        $universities = University::all();

        return $this->success($universities, 'Universities retrieved successfully');
    }

    public function show(University $university)
    {
        return $this->success($university, 'University retrieved successfully');
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
}
