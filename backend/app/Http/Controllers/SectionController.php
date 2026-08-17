<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
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
        $query = Section::query();
        RequestFilters::apply($query, $request, ['semester_id', 'program_id', 'year_level']);
        $sections = $query->paginate($per_page);

        return $this->paginate($sections, SectionResource::class, 'sections fetched successfully');
    }

    public function update(UpdateSectionRequest $request, Section $section)
    {
        $validated = $request->validated();
        if (isset($validated['semester_id'])) {
            $semister = Semester::find($validated['semester_id']);

            if ($semister->status === 'completed') {
                return $this->error(null, 'section cannot be updated for a semester that is completed', 400);
            }
        }
        $section->update($validated);

        return $this->success(new SectionResource($section->refresh()), 'section created successfully', 201);
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return $this->success(null, 'section deleted successfully');
    }

    public function restore(string $section)
    {
        $section = Section::onlyTrashed()->find($section);
        if (! $section) {
            return $this->error(null, 'section no found in archived', 404);
        }
        $section->restore();

        return $this->success(new SectionResource($section), 'section restored successfully');
    }

    public function archived(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Section::query();
        RequestFilters::apply($query, $request, ['semester_id', 'program_id', 'year_level']);
        $sections = $query->onlyTrashed()->paginate($per_page);

        return $this->paginate($sections, SectionResource::class, 'archived sections fetched successfully');
    }
}
