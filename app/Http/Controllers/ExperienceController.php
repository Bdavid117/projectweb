<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function index()
    {
        $experiences = Experience::latest('updated_at')->get();

        return view('experiences.index', [
            'experiences' => $experiences,
        ]);
    }
}