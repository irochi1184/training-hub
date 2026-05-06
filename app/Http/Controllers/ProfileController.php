<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->studentProfile?->load('skills');

        return Inertia::render('Profile/Show', [
            'profile' => $profile,
        ]);
    }

    public function edit(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->studentProfile?->load('skills');

        return Inertia::render('Profile/Edit', [
            'profile' => $profile,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $profile = StudentProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => $validated['bio'] ?? null,
                'learning_goal' => $validated['learning_goal'] ?? null,
            ],
        );

        // スキルを全件置換
        $profile->skills()->delete();

        if (!empty($validated['skills'])) {
            $profile->skills()->createMany(
                collect($validated['skills'])->map(fn ($skill) => [
                    'skill_name' => $skill['skill_name'],
                    'level' => $skill['level'],
                ])->all()
            );
        }

        return redirect()->route('profile.show')
            ->with('success', 'プロフィールを更新しました');
    }
}
