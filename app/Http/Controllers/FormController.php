<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\User;
use Illuminate\Http\Request;
use Str;

class FormController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'string|required',
            'slug' => 'string|required||unique:forms,slug|regex:/^[a-zA-Z0-9,-]+$/',
            'description' => 'string|nullable',
            'allowed_domains' => 'present|array',
            'allowed_domains.*' => 'string',
            'limit_one_response' => 'required|boolean'
        ]);
        $form = $request->user()->forms()->create($data, ['creator_id' => $request->user()->id]);

        return response()->json([
            'message' => 'Create form success',
            'form' => $form
        ], 201);
    }

    public function index(Request $request)
    {
        $forms = Form::where('creator_id', $request->user()->id)->get();
        return response()->json([
            'message' => 'Get all forms success',
            'forms' => $forms
        ], 200);
    }

    public function show(Request $request, string $form_slug)
    {
        $form = Form::with('questions')->where('slug', $form_slug)->first();
        if (!$form) {
            return response()->json([
                'message' => 'Form not found'
            ], 404);
        }

        $user = $request->user();
        $allowedDomains = $form->allowed_domains ?? [];

        if (!empty($allowedDomains)) {
            $emailDomain = Str::afterLast($user->email, '@');

            if (!in_array($emailDomain, $allowedDomains)) {
                return response()->json([
                    'message' => 'Forbidden access',
                ], 403);
            }
        }

        return response()->json([
            'message' => 'Get form success',
            'form' => $form,
        ], 200);
    }
}
