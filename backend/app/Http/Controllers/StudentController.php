<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = GetRequestsValidator::validate($request);
        $query = Student::query();
        RequestFilters::apply($query, $request, ['section_id', 'status', 'program_id']);

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->whereHas('user', function ($query) use ($search) {
                $query->where('first_name', 'ILIKE', "%{$search}%")
                      ->orWhere('last_name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }
        $students = $query->with(['user', 'section'])->paginate($perPage);

        return $this->paginate($students, StudentResource::class, 'Students fetched successfully');
    }
}
