<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyType;
use App\Models\CompanyPic;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CompanyController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $companiesQuery = Company::with('companyType', 'user');

        // Role-based filtering
        if ($user->role_id == 1) {
            // superadmin - all data
        } elseif (in_array($user->role_id, [7, 11])) {
            $companiesQuery->whereHas('user', function ($q) {
                $q->where('role_id', 12);
            });
        } elseif ($user->role_id == 12) {
            $companiesQuery->where('user_id', $user->user_id);
        } else {
            $companiesQuery->whereNull('company_id');
        }

        // KPI calculations
        $totalCompanies = (clone $companiesQuery)->count();
        $jenisCompanies = (clone $companiesQuery)->distinct('company_type_id')->count('company_type_id');
        $tierCompanies = (clone $companiesQuery)->distinct('tier')->count('tier');
        $activeCompanies = (clone $companiesQuery)->where('status', 'active')->count();

        $companies = $this->applyCompanyOrdering($companiesQuery)->paginate(10);
        $types = CompanyType::where('is_active', true)->get();
        $provinces = Province::all();

        return view('pages.company', compact(
            'companies',
            'types',
            'totalCompanies',
            'jenisCompanies',
            'tierCompanies',
            'activeCompanies',
            'provinces'
        ));
    }

    public function getRegencies($province_id)
    {
        try {
            $regencies = Regency::where('province_id', $province_id)
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $regencies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching regencies: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get districts by regency ID
     */
    public function getDistricts($regency_id)
    {
        try {
            $districts = District::where('regency_id', $regency_id)
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $districts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching districts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get villages by district ID
     */
    public function getVillages($district_id)
    {
        try {
            $villages = Village::where('district_id', $district_id)
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $villages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching villages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PICs for a company (for edit modal)
     */
    public function getCompanyPics($id)
    {
        try {
            $pics = CompanyPIC::where('company_id', $id)
                ->orderBy('pic_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'pics' => $pics->map(function ($pic) {
                    return [
                        'pic_id' => $pic->pic_id,
                        'pic_name' => $pic->pic_name,
                        'position' => $pic->position ?? '-',
                        'phone' => $pic->phone ?? '-',
                        'email' => $pic->email ?? '-'
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading PICs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading PICs: ' . $e->getMessage()
            ], 500);
        }
    }


    public function search(Request $request)
    {
        $query = Company::with('companyType');
        $search = $request->input('search') ?? $request->input('query');

        if ($search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(company_name) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereHas('companyType', function ($qt) use ($searchLower) {
                        $qt->whereRaw('LOWER(type_name) LIKE ?', ["%{$searchLower}%"]);
                    });
            });
        }

        if ($request->filled('type')) {
            $type = $request->type;
            if (is_numeric($type)) {
                $query->where('company_type_id', (int) $type);
            } else {
                $query->whereHas('companyType', function ($q) use ($type) {
                    $q->whereRaw('LOWER(type_name) LIKE ?', ['%' . strtolower($type) . '%']);
                });
            }
        }

        if ($request->filled('tier')) {
            $query->whereRaw('LOWER(tier) = ?', [strtolower($request->tier)]);
        }

        if ($request->filled('status')) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)]);
        }

        $companies = $this->applyCompanyOrdering($query)->paginate(10);

        return response()->json([
            'items' => $companies->map(function ($company, $index) use ($companies) {
                return [
                    'number' => $companies->firstItem() + $index,
                    'company_id' => $company->company_id,
                    'company_name' => $company->company_name ?? '-',
                    'company_type' => $company->companyType->type_name ?? '-',
                    'company_type_code' => $company->company_type_code,
                    'company_code' => $company->company_code,
                    'company_full_code' => $company->company_full_code,
                    'tier' => $company->tier ?? '-',
                    'description' => $company->description ?? '-',
                    'status' => ucfirst($company->status ?? 'inactive'),
                    'actions' => $this->getCompanyActions($company)
                ];
            })->toArray(),
            'pagination' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'from' => $companies->firstItem(),
                'to' => $companies->lastItem(),
                'total' => $companies->total()
            ]
        ]);
    }

    // 🔥 UPDATED: Show with ALL fields
    public function show($id)
    {
        try {
            $company = Company::with([
                'companyType',
                'user',
                'province',
                'regency',
                'district',
                'village'
            ])->findOrFail($id);

            $pics = CompanyPic::where('company_id', $id)
                ->orderBy('pic_name')
                ->get();

            return response()->json([
                'success' => true,
                'company' => [
                    'company_id' => $company->company_id,
                    'company_name' => $company->company_name,
                    'company_type' => $company->companyType->type_name ?? '-',
                    'company_type_id' => $company->company_type_id,
                    'company_type_code' => $company->company_type_code,
                    'company_code' => $company->company_code,
                    'company_full_code' => $company->company_full_code,
                    'tier' => $company->tier ?? '-',
                    'description' => $company->description ?? '-',
                    'status' => $company->status,
                    'created_by' => $company->user->name ?? '-',

                    // 🔥 Address IDs (for edit)
                    'province_id' => $company->province_id,
                    'regency_id' => $company->regency_id,
                    'district_id' => $company->district_id,
                    'village_id' => $company->village_id,

                    // 🔥 Address Names (for display)
                    'province' => $company->province->name ?? '-',
                    'regency' => $company->regency->name ?? '-',
                    'district' => $company->district->name ?? '-',
                    'village' => $company->village->name ?? '-',
                    'full_address' => $company->address ?? '-',

                    // 🔥 Contact & Media
                    'company_phone' => $company->phone ?? '-',
                    'company_email' => $company->email ?? '-',
                    'company_website' => $company->website ?? null,
                    'company_linkedin' => $company->linkedin ?? null,
                    'company_instagram' => $company->instagram ?? null,
                    'mobile_phone' => $company->mobile_phone ?? null,
                    'owner' => $company->owner ?? null,
                    'kemkes_link' => $company->kemkes_link ?? null,
                    'sp_number' => $company->sp_number ?? null,
                    'latitude' => $company->latitude,
                    'longitude' => $company->longitude,
                    'pic' => $company->pic,
                    'company_logo' => $company->logo ? url('/storage/' . ltrim($company->logo, '/')) : null,
                ],
                'pics' => $pics->map(function ($pic) {
                    return [
                        'pic_id' => $pic->pic_id,
                        'pic_name' => $pic->pic_name,
                        'position' => $pic->position ?? '-',
                        'phone' => $pic->phone ?? '-',
                        'email' => $pic->email ?? '-'
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching company detail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail perusahaan'
            ], 500);
        }
    }

    // 🔥 UPDATED: Store with ALL fields
    public function store(Request $request)
    {
        Log::info('📥 Store Request:', $request->all());

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_type_id' => 'required|exists:company_type,company_type_id',
            'tier' => 'nullable|string|in:A,B,C,D',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',

            // 🔥 Address validation
            'province_id' => 'nullable|string|max:255',
            'regency_id' => 'nullable|string|max:255',
            'district_id' => 'nullable|string|max:255',
            'village_id' => 'nullable|string|max:255',
            'address' => 'nullable|string',

            // 🔥 Contact & Media validation
            'company_phone' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_linkedin' => 'nullable|url|max:255',
            'company_instagram' => 'nullable|string|max:255',
            'mobile_phone' => 'nullable|string|max:255',
            'owner' => 'nullable|string|max:255',
            'kemkes_link' => 'nullable|url|max:500',
            'sp_number' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',

            // 🔥 PICs validation
            'pics' => 'nullable|array',
            'pics.*.pic_name' => 'required_with:pics|string|max:255',
            'pics.*.position' => 'nullable|string|max:255',
            'pics.*.phone' => 'nullable|string|max:20',
            'pics.*.email' => 'nullable|email|max:255',
        ]);

        try {
            DB::beginTransaction();

            $companyCode = $this->nextCompanyCode($validated['company_type_id']);

            // 🔥 Handle logo upload
            $logoPath = null;
            if ($request->hasFile('company_logo')) {
                $logoPath = $this->storeCompressedLogo(
                    $request->file('company_logo'),
                    $this->formatCompanyNumber($validated['company_type_id']) . $this->formatCompanyNumber($companyCode)
                );
            }

            // Create company
            $company = Company::create([
                'company_name' => $validated['company_name'],
                'company_type_id' => $validated['company_type_id'],
                'company_code' => $companyCode,
                'tier' => $validated['tier'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'user_id' => Auth::id(),

                // 🔥 Address fields
                'address' => $validated['address'] ?? null,
                'province_id' => $validated['province_id'] ?? null,
                'regency_id' => $validated['regency_id'] ?? null,
                'district_id' => $validated['district_id'] ?? null,
                'village_id' => $validated['village_id'] ?? null,

                // 🔥 Contact & Media fields
                'phone' => $validated['company_phone'] ?? null,
                'mobile_phone' => $validated['mobile_phone'] ?? null,
                'email' => $validated['company_email'] ?? null,
                'website' => $validated['company_website'] ?? null,
                'linkedin' => $validated['company_linkedin'] ?? null,
                'instagram' => $validated['company_instagram'] ?? null,
                'logo' => $logoPath,
                'owner' => $validated['owner'] ?? null,
                'kemkes_link' => $validated['kemkes_link'] ?? null,
                'sp_number' => $validated['sp_number'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);

            Log::info('✅ Company created:', ['company_id' => $company->company_id]);

            // 🔥 Create PICs
            if ($request->has('pics') && is_array($request->pics)) {
                foreach ($request->pics as $index => $picData) {
                    if (empty($picData['pic_name'])) {
                        Log::warning("⚠️ Skipping empty PIC at index {$index}");
                        continue;
                    }

                    CompanyPic::create([
                        'company_id' => $company->company_id,
                        'pic_name' => $picData['pic_name'],
                        'position' => $picData['position'] ?? null,
                        'phone' => $picData['phone'] ?? null,
                        'email' => $picData['email'] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('✅ Transaction committed');

            return redirect()->back()->with('success', 'Company berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error storing company: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Gagal menambahkan company: ' . $e->getMessage())
                ->withInput();
        }
    }

    // 🔥 UPDATED: Update with ALL fields
    public function update(Request $request, $id)
    {
        Log::info('📝 Update Request:', $request->all());

        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_type_id' => 'required|exists:company_type,company_type_id',
            'tier' => 'nullable|string|in:A,B,C,D',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',

            // 🔥 Address validation
            'province_id' => 'nullable|string|max:255',
            'regency_id' => 'nullable|string|max:255',
            'district_id' => 'nullable|string|max:255',
            'village_id' => 'nullable|string|max:255',
            'address' => 'nullable|string',

            // 🔥 Contact & Media validation
            'company_phone' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_linkedin' => 'nullable|url|max:255',
            'company_instagram' => 'nullable|string|max:255',
            'mobile_phone' => 'nullable|string|max:255',
            'owner' => 'nullable|string|max:255',
            'kemkes_link' => 'nullable|url|max:500',
            'sp_number' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',

            // 🔥 PICs validation
            'pics' => 'nullable|array',
            'pics.*.pic_name' => 'required_with:pics|string|max:255',
            'pics.*.position' => 'nullable|string|max:255',
            'pics.*.phone' => 'nullable|string|max:20',
            'pics.*.email' => 'nullable|email|max:255',
        ]);

        try {
            DB::beginTransaction();

            $companyTypeChanged = (int) $company->company_type_id !== (int) $validated['company_type_id'];
            $companyCode = $companyTypeChanged
                ? $this->nextCompanyCode($validated['company_type_id'], $company->company_id)
                : $company->company_code;

            // 🔥 Handle logo upload
            $logoPath = $company->logo;
            if ($request->hasFile('company_logo')) {
                // Delete old logo
                if ($company->logo) {
                    Storage::disk('public')->delete($company->logo);
                }
                $logoPath = $this->storeCompressedLogo(
                    $request->file('company_logo'),
                    $this->formatCompanyNumber($validated['company_type_id']) . $this->formatCompanyNumber($companyCode)
                );
            }

            // Update company
            $company->update([
                'company_name' => $validated['company_name'],
                'company_type_id' => $validated['company_type_id'],
                'company_code' => $companyCode,
                'tier' => $validated['tier'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],

                // 🔥 Address fields
                'address' => $validated['address'] ?? null,
                'province_id' => $validated['province_id'] ?? null,
                'regency_id' => $validated['regency_id'] ?? null,
                'district_id' => $validated['district_id'] ?? null,
                'village_id' => $validated['village_id'] ?? null,

                // 🔥 Contact & Media fields
                'phone' => $validated['company_phone'] ?? null,
                'mobile_phone' => $validated['mobile_phone'] ?? null,
                'email' => $validated['company_email'] ?? null,
                'website' => $validated['company_website'] ?? null,
                'linkedin' => $validated['company_linkedin'] ?? null,
                'instagram' => $validated['company_instagram'] ?? null,
                'logo' => $logoPath,
                'owner' => $validated['owner'] ?? null,
                'kemkes_link' => $validated['kemkes_link'] ?? null,
                'sp_number' => $validated['sp_number'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);

            Log::info('✅ Company updated:', ['company_id' => $company->company_id]);

            // 🔥 Delete old PICs and create new
            CompanyPic::where('company_id', $company->company_id)->delete();

            if ($request->has('pics') && is_array($request->pics)) {
                foreach ($request->pics as $index => $picData) {
                    if (empty($picData['pic_name'])) continue;

                    CompanyPic::create([
                        'company_id' => $company->company_id,
                        'pic_name' => $picData['pic_name'],
                        'position' => $picData['position'] ?? null,
                        'phone' => $picData['phone'] ?? null,
                        'email' => $picData['email'] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('✅ Update committed');

            return redirect()->back()->with('success', 'Company berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error updating company: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Gagal mengupdate company: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $company = Company::findOrFail($id);
            $logoPath = $company->logo;

            CompanyPic::where('company_id', $company->company_id)->delete();
            $company->delete();

            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Perusahaan berhasil dihapus');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error deleting company: ' . $e->getMessage(), [
                'company_id' => $id,
            ]);

            return redirect()->back()
                ->with('error', 'Gagal menghapus perusahaan: ' . $e->getMessage());
        }
    }

    public function getPICsByCompany($companyId)
    {
        try {
            $pics = CompanyPic::where('company_id', $companyId)
                ->select('pic_id as id', 'pic_name as name', 'position', 'phone', 'email')
                ->orderBy('pic_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'pics' => $pics
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching PICs: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load PICs'
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
            'company_type_id' => 'nullable|exists:company_type,company_type_id',
        ]);
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'xlsx', 'xls'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'File harus berformat CSV, XLS, atau XLSX.',
            ], 422);
        }
        $user = Auth::user();
        $path = $request->file('file')->getRealPath();

        try {
            $reader = IOFactory::createReaderForFile($path);
            $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, true, false);
            $header = array_map(fn($value) => preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $value))), array_shift($rows));
            $headerAliases = [
                'namaperusahaan' => 'company_name',
                'companyname' => 'company_name',
                'rumahsakit' => 'company_name',
                'jenis' => 'company_type',
                'companytype' => 'company_type',
                'provinsi' => 'province',
                'kabkota' => 'regency',
                'alamat' => 'address',
                'telepon' => 'phone',
                'hpwa' => 'mobile_phone',
                'pemilik' => 'owner',
                'kelas' => 'tier',
                'linkdetailkemkes' => 'kemkes_link',
                'nosp' => 'sp_number',
                'lat' => 'latitude',
                'long' => 'longitude',
                'pic' => 'pic',
                'picname' => 'pic',
                'picposition' => 'pic_position',
                'picphone' => 'pic_phone',
                'picemail' => 'pic_email',
                'tier' => 'tier',
                'deskripsi' => 'description',
                'description' => 'description',
                'status' => 'status',
            ];
            $header = array_map(fn($column) => $headerAliases[$column] ?? $column, $header);
            if (!in_array('company_name', $header, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kolom nama perusahaan atau Rumah Sakit tidak ditemukan.',
                ], 422);
            }
            $imported = 0;
            $created = 0;
            $updated = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                if (!array_filter($row, fn($value) => $value !== null && $value !== '')) continue;
                $row = array_pad($row, count($header), null);
                $data = array_combine($header, array_slice($row, 0, count($header)));

                try {
                    $type = !empty($data['company_type'])
                        ? CompanyType::whereRaw('LOWER(type_name) = ?', [strtolower(trim($data['company_type']))])->first()
                        : CompanyType::find($request->input('company_type_id'));
                    $tier = strtoupper(trim((string) ($data['tier'] ?? '')));
                    $status = strtolower(trim((string) ($data['status'] ?? 'active')));
                    if (!$type || empty(trim($data['company_name'] ?? ''))) throw new \RuntimeException('Nama perusahaan atau Jenis tidak valid');
                    if (!in_array($tier, ['A', 'B', 'C', 'D'], true)) throw new \RuntimeException('Tier harus A, B, C, atau D');
                    if (!in_array($status, ['active', 'inactive'], true)) throw new \RuntimeException('Status harus active atau inactive');

                    $provinceId = $this->findRegionId(Province::class, $data['province'] ?? null);
                    $regencyId = $this->findRegionId(Regency::class, $data['regency'] ?? null);
                    $kemkesLink = trim((string) ($data['kemkes_link'] ?? '')) ?: null;
                    $companyName = trim($data['company_name']);
                    $company = $kemkesLink
                        ? Company::where('kemkes_link', $kemkesLink)->orderBy('company_id')->first()
                        : Company::where('company_type_id', $type->company_type_id)
                        ->whereRaw('LOWER(company_name) = ?', [strtolower($companyName)])
                        ->where('province_id', $provinceId)
                        ->where('regency_id', $regencyId)
                        ->orderBy('company_id')
                        ->first();

                    $companyData = [
                        'company_name' => $companyName,
                        'company_type_id' => $type->company_type_id,
                        'tier' => $tier,
                        'description' => $data['description'] ?? null,
                        'status' => $status,
                        'user_id' => $user->user_id,
                        'address' => $data['address'] ?? null,
                        'province_id' => $provinceId,
                        'regency_id' => $regencyId,
                        'district_id' => $this->findRegionId(District::class, $data['district'] ?? null),
                        'village_id' => $this->findRegionId(Village::class, $data['village'] ?? null),
                        'phone' => $data['phone'] ?? null,
                        'mobile_phone' => $data['mobile_phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'website' => $data['website'] ?? null,
                        'linkedin' => $data['linkedin'] ?? null,
                        'instagram' => $data['instagram'] ?? null,
                        'owner' => $data['owner'] ?? null,
                        'kemkes_link' => $kemkesLink,
                        'sp_number' => $data['sp_number'] ?? null,
                        'latitude' => $this->normalizeCoordinate($data['latitude'] ?? null),
                        'longitude' => $this->normalizeCoordinate($data['longitude'] ?? null),
                        'pic' => trim((string) ($data['pic'] ?? '')) ?: null,
                    ];

                    if ($company) {
                        $fillEmpty = [];
                        foreach ($companyData as $field => $value) {
                            if (($company->{$field} === null || $company->{$field} === '') && $value !== null && $value !== '') {
                                $fillEmpty[$field] = $value;
                            }
                        }
                        if ($fillEmpty !== []) {
                            $company->update($fillEmpty);
                        }
                        $updated++;
                    } else {
                        $company = Company::create([
                            ...$companyData,
                            'company_code' => $this->nextCompanyCode($type->company_type_id),
                        ]);
                        $created++;
                    }
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = 'Baris ' . ($index + 2) . ': ' . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "$imported data diproses: $created data baru, $updated data diperbarui",
                'imported' => $imported,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'File tidak dapat dibaca: ' . $e->getMessage()], 422);
        }
    }

    private function findRegionId(string $model, mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') return null;

        $value = trim((string) $value);

        return $model::where('id', $value)->value('id')
            ?? $model::whereRaw('LOWER(name) = ?', [strtolower($value)])->value('id');
    }

    private function normalizeCoordinate(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') return null;

        return is_numeric($value) ? (float) $value : null;
    }

    private function applyCompanyOrdering($query)
    {
        return $query
            ->orderByRaw('(SELECT COUNT(*) FROM company AS type_counts WHERE type_counts.company_type_id = company.company_type_id) DESC')
            ->orderByRaw("CASE company.tier WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 WHEN 'D' THEN 4 ELSE 5 END")
            ->orderByRaw('LOWER(company.company_name) ASC')
            ->orderByRaw("CASE WHEN LOWER(company.status) = 'active' THEN 1 ELSE 2 END");
    }

    private function nextCompanyCode(int|string $companyTypeId, ?int $ignoreCompanyId = null): int
    {
        $query = Company::query()
            ->where('company_type_id', $companyTypeId)
            ->when($ignoreCompanyId !== null, fn($builder) => $builder->where('company_id', '!=', $ignoreCompanyId));

        $nextCode = $query->lockForUpdate()->pluck('company_code')
            ->map(fn($code) => (int) $code)
            ->max() + 1;

        return $nextCode;
    }

    private function formatCompanyNumber(int|string $value): string
    {
        return str_pad((string) $value, 5, '0', STR_PAD_LEFT);
    }

    private function storeCompressedLogo($file, string $filenameBase): string
    {
        $originalSize = $file->getSize();
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (!$image) {
            throw new \RuntimeException('File logo tidak dapat diproses.');
        }

        $mime = $file->getMimeType();
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        ob_start();
        $encoded = match ($extension) {
            'png' => imagepng($image, null, 6),
            'gif' => imagegif($image),
            default => imagejpeg($image, null, 78),
        };
        $compressedContent = ob_get_clean();
        imagedestroy($image);

        if (!$encoded || $compressedContent === false) {
            throw new \RuntimeException('File logo gagal dikompres.');
        }

        $filename = $filenameBase . '.' . $extension;
        $path = 'company_logos/' . $filename;
        Storage::disk('public')->put($path, $compressedContent);

        $compressedSize = strlen($compressedContent);
        $reduction = $originalSize > 0
            ? round((1 - ($compressedSize / $originalSize)) * 100, 2)
            : 0;

        Log::info('✅ Logo compressed and stored:', [
            'path' => $path,
            'original_bytes' => $originalSize,
            'compressed_bytes' => $compressedSize,
            'original_mb' => round($originalSize / 1024 / 1024, 2),
            'compressed_mb' => round($compressedSize / 1024 / 1024, 2),
            'reduction_percent' => $reduction,
        ]);

        return $path;
    }

    public function export(Request $request)
    {
        $format = strtolower(request('format', 'xlsx'));
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            abort(400, 'Format export tidak valid. Gunakan CSV atau XLSX.');
        }

        $roleId = Auth::user()->role_id;
        $companies = Company::with(['companyType', 'province', 'regency', 'district', 'village', 'pics'])
            ->when($roleId == 12, fn($query) => $query->where('user_id', Auth::id()))
            ->when(in_array($roleId, [7, 11]), fn($query) => $query->whereHas('user', fn($q) => $q->where('role_id', 12)))
            ->when(!in_array($roleId, [1, 7, 11, 12]), fn($query) => $query->whereNull('company_id'))
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('company_type_id', $request->input('type'));
            })
            ->when($request->filled('tier'), function ($query) use ($request) {
                $query->whereRaw('LOWER(tier) = ?', [strtolower($request->input('tier'))]);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->whereRaw('LOWER(status) = ?', [strtolower($request->input('status'))]);
            })
            ->orderBy('company_name')->get();

        $headers = ['Company Name', 'Company Code', 'Company Type', 'Tier', 'Description', 'Status', 'Address', 'Province', 'Regency', 'District', 'Village', 'Phone', 'Email', 'Website', 'LinkedIn', 'Instagram', 'Latitude', 'Longitude', 'PIC'];
        $rows = [];
        foreach ($companies as $company) {
            $rows[] = [
                $company->company_name,
                $company->company_full_code,
                $company->companyType->type_name ?? '',
                $company->tier,
                $company->description,
                $company->status,
                $company->address,
                $company->province->name ?? '',
                $company->regency->name ?? '',
                $company->district->name ?? '',
                $company->village->name ?? '',
                $company->phone,
                $company->email,
                $company->website,
                $company->linkedin,
                $company->instagram,
                $company->latitude,
                $company->longitude,
                $company->pic,
            ];
        }

        $filename = 'companies-export-' . date('Y-m-d-His') . '.' . $format;
        $tempPath = storage_path('app/' . $filename);

        if ($format === 'csv') {
            $handle = fopen($tempPath, 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($headers, null, 'A1');
            if ($rows !== []) {
                $sheet->fromArray($rows, null, 'A2');
            }
            (new Xlsx($spreadsheet))->save($tempPath);
        }

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function getCompaniesForDropdown()
    {
        try {
            $user = Auth::user();
            $companiesQuery = Company::query();

            if ($user->role_id == 1) {
                // superadmin
            } elseif (in_array($user->role_id, [7, 11])) {
                $companiesQuery->whereHas('user', function ($q) {
                    $q->where('role_id', 12);
                });
            } elseif ($user->role_id == 12) {
                $companiesQuery->where('user_id', $user->user_id);
            } else {
                $companiesQuery->whereNull('company_id');
            }

            $companies = $companiesQuery
                ->where('status', 'active')
                ->select('company_id as id', 'company_name as name')
                ->orderBy('company_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'companies' => $companies
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching companies: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load companies'
            ], 500);
        }
    }

    private function getCompanyActions($company)
    {
        $currentMenuId = View::shared('currentMenuId', null);
        /** @var \App\Models\User|null $authenticatedUser */
        $authenticatedUser = Auth::user();

        $canEdit = $authenticatedUser !== null && $authenticatedUser->canAccess($currentMenuId ?? 1, 'edit');
        $canDelete = $authenticatedUser !== null && $authenticatedUser->canAccess($currentMenuId ?? 1, 'delete');
        $canView = $authenticatedUser !== null && $authenticatedUser->canAccess($currentMenuId ?? 1, 'view');

        $actions = [];

        if ($canView) {
            $actions[] = [
                'type' => 'view',
                'onclick' => "showCompanyDetail('{$company->company_id}')",
                'title' => 'Show Detail'
            ];
        }

        if ($canEdit) {
            $actions[] = [
                'type' => 'edit',
                'onclick' => "openEditCompanyModal('{$company->company_id}', '" . addslashes($company->company_name) . "', '{$company->company_type_id}', '{$company->tier}', '" . addslashes($company->description ?? '') . "', '{$company->status}')",
                'title' => 'Edit Company'
            ];
        }

        if ($canDelete) {
            $csrfToken = csrf_token();
            $deleteRoute = route('company.destroy', $company->company_id);

            $actions[] = [
                'type' => 'delete',
                'onclick' => "deleteCompany('{$company->company_id}', '{$deleteRoute}', '{$csrfToken}')",
                'title' => 'Delete Company'
            ];
        }

        return $actions;
    }



    public function storeCompanyAjax(Request $request)
    {
        // ✅ Force JSON response
        $request->headers->set('Accept', 'application/json');

        Log::info('🔥 AJAX Company Store Request:', $request->all());

        try {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'company_type_id' => 'required|exists:company_type,company_type_id',
                'tier' => 'nullable|in:A,B,C,D',
                'status' => 'nullable|in:active,inactive',
                'description' => 'nullable|string',
            ]);

            $company = Company::create([
                'company_name' => $validated['company_name'],
                'company_type_id' => $validated['company_type_id'],
                'company_code' => $this->nextCompanyCode($validated['company_type_id']),
                'tier' => $validated['tier'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'description' => $validated['description'] ?? null,
                'user_id' => Auth::id(),
            ]);

            Log::info('✅ Company created via AJAX:', ['company_id' => $company->company_id]);

            // ✅ EXPLICIT JSON RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Company berhasil ditambahkan',
                'company' => [
                    'id' => $company->company_id,
                    'name' => $company->company_name
                ]
            ], 200, ['Content-Type' => 'application/json']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation error:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            Log::error('❌ Error creating company via AJAX:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500, ['Content-Type' => 'application/json']);
        }
    }
}