<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectionRequest;
use App\Models\Section;
use App\Models\Semester;

class SectionController extends Controller
{
    public function store(StoreSectionRequest $request)
    {
        $validated = $request->validated();
        $semister = Semester::find($validated['semester_id']);
        
        if (! $semister) {
            return $this->error(null, 'semester not found', 404);
        }
        if ($semister->status === 'completed') {
            return $this->error(null, 'section cannot be created for a semester that is completed', 400);
        }
        $section = Section::create($validated);

        return $this->success($section, 'section created successfully', 201);
    }

    public function index()
    {
        $sections = Section::all();

        return $this->success($sections, 'sections fetched sucessfully');
    }
}
