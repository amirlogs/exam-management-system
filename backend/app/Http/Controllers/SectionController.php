<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectionRequest;
use App\Models\Section;
use App\Models\Semester;

class SectionController extends Controller
{
    public function store(StoreSectionRequest $request)
    {
        $semister = Semester::find($request->semester_id);
        if ($semister->status === 'completed') {
            return $this->error(null, 'Semister is completed', 400);
        }

        $validated = $request->validated();
        $section = Section::create($validated);

        return $this->success($section, 'Section created successfully', 201);
    }

    public function index()
    {
        $sections = Section::all();

        return $this->success($sections, 'Sections fetched Sucessfully');
    }
}
