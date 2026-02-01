<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding welcome page.
     */
    public function welcome()
    {
        $user = auth()->user();

        // If user already has a company, redirect to dashboard
        if ($user->hasCompany()) {
            return redirect()->route('dashboard');
        }

        // If user is platform admin, redirect to admin dashboard
        if ($user->isPlatformAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Onboarding/Welcome', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Show the company setup form.
     */
    public function companySetup()
    {
        $user = auth()->user();

        // If user already has a company, redirect to dashboard
        if ($user->hasCompany()) {
            return redirect()->route('dashboard');
        }

        // If user is platform admin, redirect to admin dashboard
        if ($user->isPlatformAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Onboarding/CompanySetup', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Store the new company and assign user to it.
     */
    public function storeCompany(Request $request)
    {
        $user = auth()->user();

        // If user already has a company, redirect to dashboard
        if ($user->hasCompany()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Create the company with trial status
            $company = Company::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? $user->email,
                'phone' => $validated['phone'] ?? '',
                'address' => $validated['address'] ?? '',
                'website' => $validated['website'] ?? null,
                'industry' => $validated['industry'] ?? null,
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays((int) config('app.trial_days', 14)),
            ]);

            // Assign user to the company
            $user->company_id = $company->id;
            $user->save();

            // Assign superadmin role to the user (first user of company)
            if (!$user->hasRole('superadmin')) {
                $user->assignRole('superadmin');
            }

            DB::commit();

            return redirect()->route('onboarding.complete')->with('success', 'Company created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Onboarding company creation failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'data' => $validated,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'name' => 'Failed to create company: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the onboarding complete page.
     */
    public function complete()
    {
        $user = auth()->user();

        // If user doesn't have a company, redirect back to setup
        if (!$user->hasCompany()) {
            return redirect()->route('onboarding.company-setup');
        }

        $company = $user->company;

        return Inertia::render('Onboarding/Complete', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'trial_ends_at' => $company->trial_ends_at?->format('d M Y'),
                'trial_days_remaining' => $company->trialDaysRemaining(),
            ],
            'trial_days' => config('app.trial_days', 14),
        ]);
    }
}
