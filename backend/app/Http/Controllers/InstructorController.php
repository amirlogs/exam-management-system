<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = GetRequestsValidator::validate($request);
        $query = Instructor::query();
        RequestFilters::apply($query, $request, ['department_id', 'status']);
        $instructors = $query->with(['user', 'department'])->paginate($perPage);

        return $this->paginate($instructors, InstructorResource::class, 'Instructors fetched successfully');
    }
}
