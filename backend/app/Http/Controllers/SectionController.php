<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Models\Semester;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function store(StoreSectionRequest $request)
    {
        $validated = $request->validated();
        $semister = Semester::find($validated['semester_id']);

        if ($semister->status === 'completed') {
            return $this->error(null, 'section cannot be created for a semester that is completed', 400);
        }

        $section = Section::create($validated);

        return $this->success(new SectionResource($section), 'section created successfully', 201);
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $sections = Section::paginate($per_page);

        return $this->paginate($sections, SectionResource::class, 'sections fetched successfully');
    }
}
