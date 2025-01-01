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
        $company = Company::find($id);

        $data = [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'address' => $company->address,
            'phone' => $company->phone,
            'website' => $company->website,
            'logo' => $company->logo,
            'register_at' => Carbon::parse($company->created_at)->format('d M Y - H:i')
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
        $company = Company::findOrFail(1);

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
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'logo' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->hasFile('logo') && !in_array($request->file('logo')->getClientOriginalExtension(), ['jpeg', 'png', 'jpg'])) {
                        $fail('The ' . $attribute . ' must be a file of type: jpeg, png, jpg.');
                    }
                },
                'max:2048',
            ],
        ]);

        $company = Company::findOrFail($id);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $logoPath = $request->file('logo')->store('company', 'public');
            $validatedData['logo'] = $logoPath;
        }

        $companyDirty = $company->fill($validatedData)->isDirty();
        if ($companyDirty) {
            $company->save();
        }

        if (!$companyDirty) {
            return response()->json([
                'success' => true,
                'message' => 'No changes detected.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully.',
        ], 200);
    }
}
