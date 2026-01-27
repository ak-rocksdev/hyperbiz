<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function list(Request $request)
    {
        $search = $request->get('search', null);
        $perPage = $request->get('per_page', 5);

        $companiesQuery = Company::query();
        if ($search) {
            $companiesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        $totalSearchResults = $companiesQuery->count();
        $companies = $companiesQuery->paginate($perPage);

        $data = $companies->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'address' => $company->address,
                'phone' => $company->phone,
                'register_at' => Carbon::parse($company->created_at)->format('d M Y - H:i'),
            ];
        });

        $totalCompanies = Company::count();

        return Inertia::render('Company/List', [
            'companies' => $data,
            'pagination' => [
                'total' => $companies->total(),
                'per_page' => $companies->perPage(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
            ],
            'totalSearchResults' => $totalSearchResults,
            'totalCompanies' => $totalCompanies,
        ]);
    }

    public function detail($id)
    {
        $company = Company::findOrFail($id);

        $data = [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'address' => $company->address,
            'phone' => $company->phone,
            'website' => $company->website,
            'logo' => $company->logo,
            'created_at' => Carbon::parse($company->created_at)->format('d M Y'),
            'created_at_time' => Carbon::parse($company->created_at)->format('H:i'),
            'updated_at' => Carbon::parse($company->updated_at)->format('d M Y'),
            'updated_at_time' => Carbon::parse($company->updated_at)->format('H:i'),
        ];

        return Inertia::render('Company/Detail', [
            'company' => $data
        ]);
    }

    public function detailApi($id)
    {
        $company = Company::findOrFail($id);

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'website' => $company->website,
                'logo' => $company->logo,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'name.required' => 'The company name is required.',
            'email.required' => 'A valid email address is required.',
            'phone.required' => 'The company phone number is required.',
            'address.required' => 'The company address is required.',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
                $validatedData['logo'] = $logoPath;
            }

            $validatedData['created_by'] = auth()->id();
            $company = Company::create($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company created successfully.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create company.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);

        $data = [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'website' => $company->website,
            'logo' => $company->logo,
        ];

        return Inertia::render('Company/Edit', [
            'company' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'remove_logo' => 'nullable|boolean',
        ], [
            'name.required' => 'Company name is required.',
            'name.max' => 'Company name cannot exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email cannot exceed 255 characters.',
            'phone.required' => 'Phone number is required.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'address.required' => 'Address is required.',
            'address.max' => 'Address cannot exceed 500 characters.',
            'website.url' => 'Please enter a valid website URL.',
            'website.max' => 'Website URL cannot exceed 255 characters.',
            'logo.image' => 'Logo must be an image file.',
            'logo.mimes' => 'Logo must be a JPEG, PNG, or JPG file.',
            'logo.max' => 'Logo file size cannot exceed 2MB.',
        ]);

        DB::beginTransaction();
        try {
            $company = Company::findOrFail($id);

            // Handle logo removal
            if ($request->boolean('remove_logo') && $company->logo) {
                Storage::disk('public')->delete($company->logo);
                $validatedData['logo'] = null;
            }
            // Handle new logo upload
            elseif ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($company->logo) {
                    Storage::disk('public')->delete($company->logo);
                }
                $logoPath = $request->file('logo')->store('company', 'public');
                $validatedData['logo'] = $logoPath;
            }

            // Remove remove_logo from validated data as it's not a column
            unset($validatedData['remove_logo']);

            $company->fill($validatedData);
            $hasChanges = $company->isDirty();

            if ($hasChanges) {
                $company->save();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Company updated successfully.',
                ], 200);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'No changes were made.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update company. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
