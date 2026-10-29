<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Form;
use App\Models\Response;
use DB;
use Illuminate\Http\Request;
use Str;

class ResponseController extends Controller
{
    public function store(Request $request, string $form_slug)
    {
        $form = Form::where('slug', $form_slug)->first();
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
        if (
            $form->limit_one_response &&
            Response::where('form_id', $form->id)
                ->where('user_id', $user->id)
                ->exists()
        ) {
            return response()->json([
                'message' => 'You can not submit form twice',
            ], 422);
        }

        $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer',
            'answers.*.value' => 'present'
        ]);
        $answers = $request->input('answers');

        foreach ($form->questions as $question) {
            $submittedAnswer = collect($answers)->firstWhere(
                'question_id',
                $question->id
            );

            $value = $submittedAnswer['value'] ?? null;
            if ($question->is_required && ($value === null || $value === '')) {
                return response()->json([
                    'message' => 'Invalid field',
                    'errors' => [
                        'answers' => [
                            "The {$question->name} field is required."
                        ],
                    ],
                ], 422);
            }
            if (in_array($question->choice_type, ['multiple choice', 'dropdown']) && $value !== null && $value !== '') {
                $choices = $question->choices ? explode(',', $question->choices) : [];
                if (!in_array($value, $choices)) {
                    return response()->json([
                        'message' => 'Invalid field',
                        'errors' => [
                            'answers' => [
                                "Invalid choice for {$question->name}."
                            ],
                        ],
                    ], 422);
                }
            }

            if ($question->choice_type === 'checkboxes' && $value !== null && $value !== '') {
                $choices = $question->choices ? explode(',', $question->choices) : [];
                if (!is_array($value) || count(array_diff($value, $choices)) > 0) {
                    return response()->json([
                        'message' => 'Invalid field',
                        'errors' => [
                            'answers' => [
                                "Invalid choices for {$question->name}."
                            ],
                        ],
                    ], 422);
                }
            }
        }

        $questionIds = $form->questions->pluck('id')->map(fn($id) => (int) $id)->all();
        foreach ($answers as $answer) {
            if (
                !in_array(
                    (int) $answer['question_id'],
                    $questionIds,
                    true
                )
            ) {
                return response()->json([
                    'message' => 'Invalid field',
                    'errors' => [
                        'answers' => [
                            'Question does not belong to this form.'
                        ],
                    ],
                ], 422);
            }
        }

        DB::transaction(function () use ($form, $user, $answers) {
            $formResponse = Response::create([
                'form_id' => $form->id,
                'user_id' => $user->id,
            ]);
            foreach ($answers as $answer) {
                Answer::create([
                    'form_response_id' => $formResponse->id,
                    'question_id' => $answer['question_id'],
                    'value' => is_array($answer['value'])
                        ? json_encode($answer['value'])
                        : $answer['value'],
                ]);
            }
        });
        return response()->json([
            'message' => 'Submit response success',
        ], 200);
    }

    public function index(Request $request, string $form_slug)
    {
        $form = Form::with('questions')->where('slug', $form_slug)->first();
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        if ($form->creator_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden access',
            ], 403);
        }

        $responses = Response::with([
            'user:id,name,email,email_verified_at',
            'answers.question',
        ])->where('form_id', $form->id)->latest()->get()
            ->map(function ($response) {
                return [
                    'date' => $response->created_at->format('Y-m-d H:i:s'),
                    'user' => $response->user,
                    'answers' => $response->answers
                        ->mapWithKeys(function ($answer) {
                            return [$answer->question->name => $answer->value];
                        }),
                ];
            });
        return response()->json([
            'message' => 'Get responses success',
            'responses' => $responses,
        ], 200);
    }
}
