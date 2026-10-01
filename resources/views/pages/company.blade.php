@extends('layout.main')
@section('title','Company')

@section('content')
<div class="container-expanded mx-auto px-6 lg:px-8 py-8 pt-[60px] mt-0 fade-in">
    <!-- KPI Section -->
    <x-company.attribut.kpi
        :totalCompanies="$totalCompanies"
        :jenisCompanies="$jenisCompanies"
        :tierCompanies="$tierCompanies"
        :activeCompanies="$activeCompanies" />

    <!-- Company Card dengan Everything Inside -->
    <div style="background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; overflow: hidden; margin-top: 0.5rem;">

        <!-- Card Header dengan Title dan Action Button -->
        <div style="padding: 0.5rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">Company Management</h3>
                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">Kelola data perusahaan dan informasinya</p>
                </div>

                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    @if(auth()->user()->canAccess($currentMenuId, 'create'))
                    <button onclick="openAddCompanyModal()"
                        style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border: none; border-radius: 0.5rem; font-weight: 500; font-size: 0.875rem; cursor: pointer; box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2); transition: all 0.2s;">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Perusahaan</span>
                    </button>
                    @endif
                    <div style="position: relative;">
                        <button type="button" onclick="toggleCompanyExportMenu()" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; background: #059669; color: white; border: none; border-radius: 0.5rem; font-weight: 500; font-size: 0.875rem; cursor: pointer;"><i class="fas fa-download"></i><span>Export</span><i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i></button>
                        <div id="companyExportMenu" style="display: none; position: absolute; right: 0; top: calc(100% + 0.35rem); z-index: 20; min-width: 170px; padding: 0.35rem; background: white; border: 1px solid #d1d5db; border-radius: 0.5rem; box-shadow: 0 8px 18px rgba(0,0,0,0.15);">
                            <a href="{{ route('company.export', ['format' => 'xlsx']) }}" style="display: block; padding: 0.55rem 0.7rem; color: #374151; text-decoration: none; font-size: 0.875rem;"><i class="fas fa-file-excel" style="color: #059669; width: 1.25rem;"></i> Excel (.xlsx)</a>
                            <a href="{{ route('company.export', ['format' => 'csv']) }}" style="display: block; padding: 0.55rem 0.7rem; color: #374151; text-decoration: none; font-size: 0.875rem;"><i class="fas fa-file-csv" style="color: #2563eb; width: 1.25rem;"></i> CSV (.csv)</a>
                            <button type="button" onclick="openCompanyCustomExportModal()" style="display: block; width: 100%; padding: 0.55rem 0.7rem; border: 0; background: transparent; color: #374151; text-align: left; font-size: 0.875rem; cursor: pointer;"><i class="fas fa-sliders-h" style="color: #7c3aed; width: 1.25rem;"></i> Custom</button>
                        </div>
                    </div>
                    <button type="button" onclick="openCompanyImportModal()" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; background: #2563eb; color: white; border: none; border-radius: 0.5rem; font-weight: 500; font-size: 0.875rem; cursor: pointer;"><i class="fas fa-file-import"></i><span>Import</span></button>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div style="padding: 0.5rem 1.5rem; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
                <x-globals.filtersearch
                    tableId="companyTable"
                    :columns="[
                        'number',
                        'company_name', 
                        'company_type',
                        'tier',
                        'description', 
                        'status',
                        'actions'
                    ]"
                    :filters="[
                        'Type' => $types->pluck('type_name', 'company_type_id')->toArray(),
                        'Tier' => ['A', 'B', 'C', 'D'],
                        'Status' => ['Active', 'Inactive']
                    ]"
                    ajaxUrl="{{ route('company.search') }}"
                    placeholder="Cari nama perusahaan, deskripsi, atau tipe..." />
            </div>
        </div>

        <!-- Table Section - NO PADDING! -->
        <x-company.table.table :companies="$companies" :currentMenuId="$currentMenuId" />

        <!-- Pagination -->
        @if($companies->hasPages())
        <div style="border-top: 1px solid #e5e7eb; background-color: #f9fafb;">
            <x-globals.pagination :paginator="$companies" />
        </div>
        @endif
    </div>
</div>

<!-- Modals -->
<x-company.action.action :types="$types" :provinces="$provinces" />
<x-company.action.edit :types="$types" :provinces="$provinces" />

<div id="companyImportModal" class="company-import-overlay" aria-hidden="true">
    <div class="company-import-card" role="dialog" aria-modal="true" aria-labelledby="companyImportTitle">
        <div class="company-import-header">
            <div class="company-import-title-wrap">
                <div class="company-import-icon"><i class="fas fa-file-import"></i></div>
                <h2 id="companyImportTitle">Import Company</h2>
            </div>
            <button type="button" class="company-import-close" onclick="closeCompanyImportModal()" aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>
        <form id="companyImportForm" action="{{ route('company.import') }}" method="POST" enctype="multipart/form-data" onsubmit="importCompanyFile(event)">
            @csrf
            <div class="company-import-body">
                <label class="company-import-label" for="companyImportFile"><i class="fas fa-file-excel"></i> Upload File (CSV/Excel)</label>
                <label class="company-file-picker" for="companyImportFile">
                    <span class="company-choose-file"><i class="fas fa-folder-open"></i> Choose File</span>
                    <span id="companyFileName" class="company-file-name">No file chosen</span>
                </label>
                <input id="companyImportFile" type="file" name="file" accept=".csv,.xlsx,.xls" required hidden onchange="showCompanyFileName(this)">
                <label class="company-export-label" for="companyImportType">Jenis Perusahaan</label>
                <select id="companyImportType" name="company_type_id" class="company-export-select" required>
                    <option value="">Pilih jenis perusahaan</option>
                    @foreach($types as $type)
                    <option value="{{ $type->company_type_id }}">{{ $type->type_name }}</option>
                    @endforeach
                </select>
                <div class="company-import-format"><i class="fas fa-info-circle"></i><span><strong>Format rumah sakit:</strong> Rumah Sakit, Provinsi, Kab/Kota, Alamat, Telepon, HP/WA, Pemilik, Kelas, Link Detail Kemkes, Website, Email, No SP, PIC, Instagram, Lat, Long</span></div>
            </div>
            <div class="company-import-footer">
                <button type="button" class="company-cancel-button" onclick="closeCompanyImportModal()"><i class="fas fa-times"></i> Batal</button>
                <button type="submit" class="company-upload-button"><i class="fas fa-upload"></i> Upload</button>
            </div>
        </form>
    </div>
</div>

<div id="companyCustomExportModal" class="company-import-overlay" aria-hidden="true">
    <div class="company-import-card" role="dialog" aria-modal="true" aria-labelledby="companyCustomExportTitle">
        <div class="company-import-header">
            <div class="company-import-title-wrap">
                <div class="company-import-icon"><i class="fas fa-sliders-h"></i></div>
                <h2 id="companyCustomExportTitle">Custom Export</h2>
            </div>
            <button type="button" class="company-import-close" onclick="closeCompanyCustomExportModal()" aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>
        <form id="companyCustomExportForm" onsubmit="return false">
            <div class="company-import-body">
                <p class="company-import-note" style="margin-top: 0; margin-bottom: 1rem;">Pilih filter data yang ingin diexport.</p>
                <label class="company-export-label" for="companyExportType">Type</label>
                <select id="companyExportType" class="company-export-select">
                    <option value="">Semua Type</option>
                    @foreach($types as $type)
                    <option value="{{ $type->company_type_id }}">{{ $type->type_name }}</option>
                    @endforeach
                </select>
                <label class="company-export-label" for="companyExportTier">Tier</label>
                <select id="companyExportTier" class="company-export-select">
                    <option value="">Semua Tier</option>
                    <option value="A">Tier A</option>
                    <option value="B">Tier B</option>
                    <option value="C">Tier C</option>
                    <option value="D">Tier D</option>
                </select>
                <label class="company-export-label" for="companyExportStatus">Status</label>
                <select id="companyExportStatus" class="company-export-select">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
            <div class="company-import-footer">
                <button type="button" class="company-cancel-button" onclick="closeCompanyCustomExportModal()"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="company-upload-button company-export-format-button" onclick="exportCustomCompanies('csv')"><i class="fas fa-file-csv"></i> CSV</button>
                <button type="button" class="company-upload-button company-export-format-button" onclick="exportCustomCompanies('xlsx')"><i class="fas fa-file-excel"></i> Excel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/address-cascade.js') }}"></script>
<script src="{{ asset('js/company-modal.js') }}"></script>
<script src="{{ asset('js/search.js') }}"></script>
@endpush

<style>
    /* Hover effects for buttons */
    button:hover,
    a[href]:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Focus styles for inputs */
    #searchInput:focus,
    #filterType:focus,
    #filterTier:focus,
    #filterStatus:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {

        button span,
        a span {
            display: none;
        }
    }

    .company-import-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, 0.58);
    }

    .company-import-overlay.is-open {
        display: flex;
    }

    .company-import-card {
        width: min(100%, 515px);
        overflow: hidden;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    }

    .company-import-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        color: #fff;
        background: linear-gradient(120deg, #6366f1, #8b5cf6 58%, #3b82f6);
    }

    .company-import-title-wrap {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .company-import-title-wrap h2 {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
    }

    .company-import-icon {
        display: grid;
        width: 2.9rem;
        height: 2.9rem;
        place-items: center;
        border-radius: 0.7rem;
        background: rgba(255, 255, 255, .2);
        font-size: 1.25rem;
    }

    .company-import-close {
        border: 0;
        background: transparent;
        color: #fff;
        font-size: 1.55rem;
        cursor: pointer;
    }

    .company-import-body {
        padding: 1.5rem;
    }

    .company-import-label {
        display: block;
        margin-bottom: .8rem;
        color: #374151;
        font-size: 1rem;
        font-weight: 600;
    }

    .company-import-label i {
        margin-right: .65rem;
        color: #16a34a;
    }

    .company-file-picker {
        display: flex;
        min-height: 5rem;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border: 3px dashed #d1d5db;
        border-radius: .7rem;
        cursor: pointer;
    }

    .company-choose-file {
        padding: .7rem 1rem;
        border-radius: .6rem;
        color: #15803d;
        background: #f0fdf4;
        font-weight: 600;
        font-size: .9rem;
        white-space: nowrap;
    }

    .company-file-name {
        overflow: hidden;
        color: #111827;
        font-size: .95rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .company-import-format {
        display: flex;
        gap: .6rem;
        margin-top: .9rem;
        padding: .75rem .85rem;
        border: 1px solid #bfdbfe;
        border-radius: .65rem;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: .8rem;
        line-height: 1.45;
    }

    .company-import-format i {
        margin-top: .2rem;
    }

    .company-import-note {
        margin: .85rem 0 0;
        color: #6b7280;
        font-size: .85rem;
    }

    .company-export-label {
        display: block;
        margin: .85rem 0 .35rem;
        color: #374151;
        font-size: .9rem;
        font-weight: 600;
    }

    .company-export-select {
        width: 100%;
        padding: .7rem .8rem;
        border: 1px solid #d1d5db;
        border-radius: .6rem;
        color: #111827;
        background: #fff;
        font-size: .9rem;
    }

    .company-import-footer {
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
        padding: 0 1.5rem 1.5rem;
    }

    .company-cancel-button,
    .company-upload-button {
        min-width: 7rem;
        padding: .7rem 1rem;
        border-radius: .6rem;
        font-size: .9rem;
        font-weight: 600;
        cursor: pointer;
    }

    .company-cancel-button {
        border: 1px solid #d1d5db;
        color: #374151;
        background: #fff;
    }

    .company-upload-button {
        border: 0;
        color: #fff;
        background: #16a34a;
        box-shadow: 0 8px 18px rgba(22, 163, 74, .22);
    }

    .company-export-format-button {
        min-width: auto;
        background: #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .2);
    }

    @media (max-width: 640px) {

        .company-import-header,
        .company-import-body {
            padding: 1.25rem;
        }

        .company-import-footer {
            padding: 0 1.25rem 1.25rem;
        }

        .company-import-title-wrap h2 {
            font-size: 1.35rem;
        }

        .company-file-picker {
            align-items: flex-start;
            flex-direction: column;
            gap: .8rem;
        }

        .company-import-footer {
            flex-direction: column-reverse;
        }

        .company-cancel-button,
        .company-upload-button {
            width: 100%;
        }
    }
</style>

<script>
    // ==================== DELETE FUNCTION ====================
    function deleteCompany(companyId, deleteRoute, csrfToken) {
        console.log('🗑️ Delete company:', {
            companyId,
            deleteRoute,
            csrfToken
        });

        deleteRecord(companyId, deleteRoute, csrfToken, (data) => {
            console.log('✅ Delete success:', data);
            if (window.companyTableHandler) {
                console.log('🔄 Refreshing table...');
                window.companyTableHandler.refresh();
            } else {
                console.warn('⚠️ companyTableHandler not found, reloading page');
                location.reload();
            }
        });
    }

    // ==================== TABLE HANDLER INITIALIZATION ====================
    document.addEventListener('DOMContentLoaded', () => {
        console.log('📋 Company page loaded');

        if (typeof TableHandler === 'undefined') {
            console.error('❌ TableHandler class not found. search.js may not be loaded.');
            return;
        }

        console.log('🎯 Creating TableHandler instance...');

        try {
            window.companyTableHandler = new TableHandler({
                tableId: 'companyTable',
                ajaxUrl: '{{ route("company.search") }}',
                filters: ['type', 'tier', 'status'],
                columns: ['number', 'company_name', 'company_type', 'tier', 'description', 'status', 'actions']
            });

            console.log('✅ TableHandler initialized:', window.companyTableHandler);
        } catch (error) {
            console.error('❌ Error initializing TableHandler:', error);
        }
    });

    async function importCompanyFile(event) {
        event.preventDefault();
        const form = event.target;
        const uploadButton = form.querySelector('.company-upload-button');
        uploadButton.disabled = true;
        uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json'
                }
            });
            const result = await response.json().catch(() => ({
                success: false,
                message: 'Server mengembalikan respons yang tidak valid.'
            }));
            const detail = result.errors?.length ? `\n\n${result.errors.join('\n')}` : '';
            alert((result.message || 'Import selesai') + detail);
            if (result.success && result.imported > 0) window.location.reload();
        } catch (error) {
            alert('Import gagal. Periksa format file dan coba lagi.');
        } finally {
            uploadButton.disabled = false;
            uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload';
        }
    }

    function openCompanyImportModal() {
        const modal = document.getElementById('companyImportModal');
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeCompanyImportModal() {
        const modal = document.getElementById('companyImportModal');
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.getElementById('companyImportForm').reset();
        document.getElementById('companyFileName').textContent = 'No file chosen';
    }

    function openCompanyCustomExportModal() {
        document.getElementById('companyExportMenu').style.display = 'none';
        const modal = document.getElementById('companyCustomExportModal');
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeCompanyCustomExportModal() {
        const modal = document.getElementById('companyCustomExportModal');
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function exportCustomCompanies(format) {
        const params = new URLSearchParams({
            format
        });
        const filters = {
            type: document.getElementById('companyExportType').value,
            tier: document.getElementById('companyExportTier').value,
            status: document.getElementById('companyExportStatus').value
        };

        Object.entries(filters).forEach(([key, value]) => {
            if (value) params.set(key, value);
        });

        window.location.href = `{{ route('company.export') }}?${params.toString()}`;
        closeCompanyCustomExportModal();
    }

    function showCompanyFileName(input) {
        document.getElementById('companyFileName').textContent = input.files[0]?.name || 'No file chosen';
    }

    function toggleCompanyExportMenu() {
        const menu = document.getElementById('companyExportMenu');
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    document.addEventListener('click', (event) => {
        const menu = document.getElementById('companyExportMenu');
        if (menu && !menu.parentElement.contains(event.target)) menu.style.display = 'none';
    });

    document.getElementById('companyImportModal').addEventListener('click', (event) => {
        if (event.target.id === 'companyImportModal') closeCompanyImportModal();
    });

    document.getElementById('companyCustomExportModal').addEventListener('click', (event) => {
        if (event.target.id === 'companyCustomExportModal') closeCompanyCustomExportModal();
    });
</script>
@endsection