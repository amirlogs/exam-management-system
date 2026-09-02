<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function myTeaching(Request $request)
    {
        $instructors = $request->user()->instructor;

        if (! $instructors) {
            return $this->error(null, "You are not a teacher");
        }

        $offering = $instructors->courseOfferings()->with('course')->with('semester')->with('sections')->get();

        return $offering;
    }
}
