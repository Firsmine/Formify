<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function store(Request $request, string $form_slug)
    {
        $form = Form::where('slug', $form_slug)->first();
        if (!$form) {
            return response()->json([
                'message' => 'Form not found'
            ], 404);
        }
        if ($form->creator_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden access',
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'choice_type' => [
                'required',
                'in:short answer,paragraph,date,multiple choice,dropdown,checkboxes',
            ],
            'choices' => [
                'required_if:choice_type,multiple choice,dropdown,checkboxes',
                'array',
            ],
            'choices.*' => 'string',
            'is_required' => 'required|boolean',
        ]);

        $choices = $request->input('choices');

        $question = Question::create([
            'form_id' => $form->id,
            'name' => $request->name,
            'choice_type' => $request->choice_type,
            'choices' => $choices !== null
                ? implode(',', $choices)
                : null,
            'is_required' => $request->is_required,
        ]);
        return response()->json([
            'message' => 'Add question success',
            'question' => $question,
        ], 200);
    }

    public function destroy(Request $request, string $form_slug, int $question_id)
    {
        $form = Form::where('slug', $form_slug)->first();
        if (!$form) {
            return response()->json([
                'message' => 'Form not found'
            ], 404);
        }
        if ($form->creator_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden access',
            ], 403);
        }

        $question = Question::where('form_id', $form->id)->where('id', $question_id)->first();
        if (!$question) {
            return response()->json([
                'message' => 'Question not found',
            ], 404);
        }

        $question->delete();
        return response()->json([
            'message' => 'Remove question success',
        ], 200);
    }
}
