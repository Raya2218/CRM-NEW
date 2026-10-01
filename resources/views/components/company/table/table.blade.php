@props(['companies', 'currentMenuId'])

<div class="fade-in" style="margin: 0; padding: 0;">
    <!-- Table ONLY -->
    <div class="overflow-x-auto" style="margin: 0; padding: 0;">
        <table id="companyTable" class="w-full" style="margin: 0; border-collapse: collapse;">
            <thead style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                <tr>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                        No</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                        Nama Perusahaan</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                        Jenis</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                        Tier</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                        Deskripsi</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                        Status</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody style="background-color: #ffffff; border-top: 1px solid #e5e7eb;">
                @forelse($companies as $index => $company)
                <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s;"
                    onmouseover="this.style.backgroundColor='#f9fafb'"
                    onmouseout="this.style.backgroundColor='#ffffff'">
                    <td style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: #111827; white-space: nowrap;">
                        <span style="font-weight: 500;">{{ $companies->firstItem() + $index }}</span>
                    </td>
                    <td style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; font-weight: 500; color: #111827;">
                        {{ $company->company_name }}
                    </td>
                    <td style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: #111827;">
                        {{ $company->companyType->type_name ?? '-' }}
                    </td>
                    <td style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: #111827;">
                        {{ $company->tier ?? '-' }}
                    </td>
                    <td style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: #111827;">
                        {{ $company->description ?? '-' }}
                    </td>
                    <td style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                        <span
                            class="company-status-badge {{ $company->status === 'active' ? 'is-active' : 'is-inactive' }}">
                            {{ ucfirst($company->status) }}
                        </span>
                    </td>
                    <td
                        style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; font-weight: 500; text-align: right; white-space: nowrap;">
                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.375rem;">
                            {{-- Show Detail Button --}}
                            @if(auth()->user()->canAccess($currentMenuId, 'view'))
                            <button onclick="showCompanyDetail('{{ $company->company_id }}')"
                                style="color: #059669; background: transparent; border: none; padding: 0.375rem; border-radius: 0.375rem; cursor: pointer; transition: all 0.15s; font-size: 0.875rem;"
                                onmouseover="this.style.backgroundColor='#d1fae5'; this.style.color='#047857';"
                                onmouseout="this.style.backgroundColor='transparent'; this.style.color='#059669';"
                                title="Show Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            @endif

                            {{-- Edit Button --}}
                            @if(auth()->user()->canAccess($currentMenuId, 'edit'))
                            <button class="js-edit-company" data-company-id="{{ $company->company_id }}"
                                data-company-name="{{ e($company->company_name) }}"
                                data-company-type-id="{{ $company->company_type_id }}"
                                data-company-tier="{{ e($company->tier) }}"
                                data-company-description="{{ e($company->description ?? '') }}"
                                data-company-status="{{ e($company->status) }}"
                                style="color: #2563eb; background: transparent; border: none; padding: 0.375rem; border-radius: 0.375rem; cursor: pointer; transition: all 0.15s; font-size: 0.875rem;"
                                onmouseover="this.style.backgroundColor='#dbeafe'; this.style.color='#1e40af';"
                                onmouseout="this.style.backgroundColor='transparent'; this.style.color='#2563eb';"
                                title="Edit Perusahaan">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endif

                            {{-- Delete Button --}}
                            @if(auth()->user()->canAccess($currentMenuId, 'delete'))
                            <form action="{{ route('company.destroy', $company->company_id) }}" method="POST"
                                style="display: inline; margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    style="color: #dc2626; background: transparent; border: none; padding: 0.375rem; border-radius: 0.375rem; cursor: pointer; transition: all 0.15s; font-size: 0.875rem;"
                                    onmouseover="this.style.backgroundColor='#fee2e2'; this.style.color='#991b1b';"
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#dc2626';"
                                    title="Hapus Perusahaan"
                                    onclick="return confirm('Yakin ingin menghapus perusahaan ini?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 3rem 1.5rem; text-align: center;">
                        <div
                            style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <div
                                style="width: 6rem; height: 6rem; border-radius: 9999px; background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db;"></i>
                            </div>
                            <h3 style="font-size: 1.125rem; font-weight: 500; color: #111827; margin: 0 0 0.25rem 0;">
                                Belum Ada Data</h3>
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Belum ada data perusahaan yang
                                tersedia</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Company Detail Modal - ROUNDED KONSISTEN --}}
<div id="companyDetailModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div
        style="background-color: white; border-radius: 1.5rem; width: 95%; max-width: 700px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;">

        {{-- Modal Header - FIXED --}}
        <div
            style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; background: linear-gradient(to right, #4f46e5, #7c3aed); flex-shrink: 0;">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: white; margin: 0;">Detail Perusahaan</h3>
            <button onclick="closeCompanyDetailModal()"
                style="color: white; background: transparent; border: none; font-size: 1.5rem; cursor: pointer; padding: 0;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Modal Body - SCROLLABLE WITH BORDER RADIUS AT BOTTOM --}}
        <div
            style="overflow-y: auto; flex: 1; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem; border-radius: 0 0 1.5rem 1.5rem;">

            {{-- Logo --}}
            <div style="text-align: center;">
                <div id="detailLogoContainer"
                    style="display: flex; justify-content: center; align-items: center; min-height: 120px;">
                    <p style="color: #9ca3af; font-size: 0.875rem;">Loading...</p>
                </div>
            </div>

            {{-- Info Grid --}}
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <div>
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.375rem;">Nama</label>
                    <p id="detailCompanyName"
                        style="font-size: 0.9375rem; font-weight: 500; color: #111827; margin: 0;">-</p>
                </div>
                <div>
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.375rem;">Jenis</label>
                    <p id="detailCompanyType" style="font-size: 0.9375rem; color: #111827; margin: 0;">-</p>
                </div>
                <div>
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.375rem;">Tier</label>
                    <p id="detailCompanyTier" style="font-size: 0.9375rem; color: #111827; margin: 0;">-</p>
                </div>
                <div>
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.375rem;">Status</label>
                    <span id="detailCompanyStatus"
                        style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">-</span>
                </div>
            </div>

            {{-- Deskripsi --}}
            <div>
                <label
                    style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 0.375rem;">Deskripsi</label>
                <p id="detailCompanyDescription"
                    style="font-size: 0.9375rem; color: #111827; margin: 0; line-height: 1.5;">-</p>
            </div>

            {{-- Address --}}
            <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0 0 0.75rem 0;">Lokasi</h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Provinsi</label>
                        <p id="detailProvince" style="font-size: 0.875rem; color: #111827; margin: 0;">-</p>
                    </div>
                    <div>
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Kabupaten</label>
                        <p id="detailRegency" style="font-size: 0.875rem; color: #111827; margin: 0;">-</p>
                    </div>
                    <div>
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Kecamatan</label>
                        <p id="detailDistrict" style="font-size: 0.875rem; color: #111827; margin: 0;">-</p>
                    </div>
                    <div>
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Kelurahan</label>
                        <p id="detailVillage" style="font-size: 0.875rem; color: #111827; margin: 0;">-</p>
                    </div>
                </div>
                <div style="margin-top: 0.75rem;">
                    <label style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Alamat
                        Lengkap</label>
                    <p id="detailFullAddress" style="font-size: 0.875rem; color: #111827; margin: 0; line-height: 1.5;">
                        -</p>
                </div>
            </div>

            {{-- Contact Info --}}
            <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0 0 0.75rem 0;">Kontak & Media
                </h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Telepon</label>
                        <p id="detailCompanyPhone" style="font-size: 0.875rem; color: #111827; margin: 0;">-</p>
                    </div>
                    <div>
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Email</label>
                        <p id="detailCompanyEmail" style="font-size: 0.875rem; color: #111827; margin: 0;">-</p>
                    </div>
                    <div>
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Website</label>
                        <p id="detailCompanyWebsite" style="font-size: 0.875rem; margin: 0;"><a
                                id="detailCompanyWebsiteLink" href="#" target="_blank"
                                style="color: #3b82f6; text-decoration: none;">-</a></p>
                    </div>
                    <div>
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">LinkedIn</label>
                        <p id="detailCompanyLinkedin" style="font-size: 0.875rem; margin: 0;"><a
                                id="detailCompanyLinkedinLink" href="#" target="_blank"
                                style="color: #3b82f6; text-decoration: none;">-</a></p>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label
                            style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Instagram</label>
                        <p id="detailCompanyInstagram" style="font-size: 0.875rem; margin: 0;"><a
                                id="detailCompanyInstagramLink" href="#" target="_blank"
                                style="color: #3b82f6; text-decoration: none;">-</a></p>
                    </div>
                </div>
            </div>

            {{-- PICs - COMPACT & ROUNDED --}}
            <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;">PIC</h4>
                    <span id="picCount"
                        style="font-size: 0.75rem; color: #ffffff; background-color: #4f46e5; padding: 0.25rem 0.625rem; border-radius: 9999px; font-weight: 600;">0</span>
                </div>
                <div id="picsContainer"
                    style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 300px; overflow-y: auto;">
                    {{-- PICs will be loaded here --}}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function closeCompanyDetailModal() {
        const modal = document.getElementById('companyDetailModal');

        if (!modal) {
            return;
        }

        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function showCompanyDetail(companyId) {
        const modal = document.getElementById('companyDetailModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        document.getElementById('picsContainer').innerHTML = `
        <div style="text-align: center; padding: 1rem; color: #6b7280;">
            <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>
    `;

        fetch(`/company/${companyId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Company data:', data); // Tambahkan ini untuk debugging

                if (data.success) {
                    const c = data.company;

                    // Tambahkan ini untuk debugging logo
                    console.log('Logo data:', c.company_logo);
                    console.log('Full company object:', c);

                    // Basic info
                    document.getElementById('detailCompanyName').textContent = c.company_name || '-';
                    document.getElementById('detailCompanyType').textContent = c.company_type || '-';
                    document.getElementById('detailCompanyTier').textContent = c.tier || '-';
                    document.getElementById('detailCompanyDescription').textContent = c.description || '-';

                    // Status
                    const statusBadge = document.getElementById('detailCompanyStatus');
                    statusBadge.textContent = c.status;
                    if (c.status.toLowerCase() === 'active') {
                        statusBadge.style.backgroundColor = '#d1fae5';
                        statusBadge.style.color = '#065f46';
                    } else {
                        statusBadge.style.backgroundColor = '#fee2e2';
                        statusBadge.style.color = '#991b1b';
                    }

                    // Address
                    document.getElementById('detailProvince').textContent = c.province || '-';
                    document.getElementById('detailRegency').textContent = c.regency || '-';
                    document.getElementById('detailDistrict').textContent = c.district || '-';
                    document.getElementById('detailVillage').textContent = c.village || '-';
                    document.getElementById('detailFullAddress').textContent = c.full_address || '-';

                    // Contact
                    document.getElementById('detailCompanyPhone').textContent = c.company_phone || '-';
                    document.getElementById('detailCompanyEmail').textContent = c.company_email || '-';

                    if (c.company_website) {
                        document.getElementById('detailCompanyWebsiteLink').href = c.company_website;
                        document.getElementById('detailCompanyWebsiteLink').textContent = c.company_website;
                    } else {
                        document.getElementById('detailCompanyWebsite').innerHTML =
                            '<span style="color: #111827;">-</span>';
                    }

                    if (c.company_linkedin) {
                        document.getElementById('detailCompanyLinkedinLink').href = c.company_linkedin;
                        document.getElementById('detailCompanyLinkedinLink').textContent = c.company_linkedin;
                    } else {
                        document.getElementById('detailCompanyLinkedin').innerHTML =
                            '<span style="color: #111827;">-</span>';
                    }

                    if (c.company_instagram) {
                        document.getElementById('detailCompanyInstagramLink').href =
                            `https://instagram.com/${c.company_instagram.replace('@', '')}`;
                        document.getElementById('detailCompanyInstagramLink').textContent = c.company_instagram;
                    } else {
                        document.getElementById('detailCompanyInstagram').innerHTML =
                            '<span style="color: #111827;">-</span>';
                    }

                    // Logo - Diperbaiki dengan lebih banyak debugging
                    const logoContainer = document.getElementById('detailLogoContainer');
                    console.log('Checking logo:', c.company_logo);

                    if (c.company_logo && c.company_logo !== null && c.company_logo !== '') {
                        logoContainer.innerHTML =
                            `<img src="${c.company_logo}" style="max-width: 100%; max-height: 150px; border-radius: 0.75rem;" onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\\'width: 120px; height: 120px; background-color: #e5e7eb; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center;\\'><i class=\\'fas fa-image\\' style=\\'font-size: 2.5rem; color: #9ca3af;\\'></i></div>';">`;
                    } else {
                        logoContainer.innerHTML =
                            `<div style="width: 120px; height: 120px; background-color: #e5e7eb; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image" style="font-size: 2.5rem; color: #9ca3af;"></i></div>`;
                    }

                    // PICs - COMPACT & ROUNDED
                    const picsContainer = document.getElementById('picsContainer');
                    const picCount = document.getElementById('picCount');

                    if (data.pics && data.pics.length > 0) {
                        picCount.textContent = data.pics.length;
                        picsContainer.innerHTML = data.pics.map(pic => `
                        <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 0.5rem; background-color: #f9fafb; display: flex; gap: 0.5rem; align-items: flex-start;">
                            <div style="flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 0.75rem; background: linear-gradient(135deg, #4f46e5, #7c3aed); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.875rem;">
                                ${pic.pic_name.charAt(0).toUpperCase()}
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <p style="font-size: 0.8125rem; font-weight: 600; color: #111827; margin: 0 0 0.125rem 0;">${pic.pic_name}</p>
                                <p style="font-size: 0.75rem; color: #6b7280; margin: 0 0 0.25rem 0;">${pic.position || '-'}</p>
                                <div style="display: flex; gap: 0.5rem; font-size: 0.7rem;">
                                    <a href="tel:${pic.phone}" style="color: #3b82f6; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${pic.phone || '-'}</a>
                                    <span style="color: #d1d5db;">•</span>
                                    <a href="mailto:${pic.email}" style="color: #3b82f6; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${pic.email || '-'}</a>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    } else {
                        picCount.textContent = '0';
                        picsContainer.innerHTML =
                            `<p style="text-align: center; color: #9ca3af; font-size: 0.875rem; margin: 0;">Belum ada PIC</p>`;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('picsContainer').innerHTML =
                    `<div style="text-align: center; color: #dc2626; font-size: 0.875rem;">Gagal memuat data</div>`;
            });
    }

    document.getElementById('companyDetailModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeCompanyDetailModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeCompanyDetailModal();
        }
    });

    document.querySelectorAll('.js-edit-company').forEach(function(button) {
        button.addEventListener('click', function() {
            openEditCompanyModal(
                this.dataset.companyId,
                this.dataset.companyName,
                this.dataset.companyTypeId,
                this.dataset.companyTier,
                this.dataset.companyDescription,
                this.dataset.companyStatus
            );
        });
    });
</script>

<style>
    .company-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 500;
    }

    .company-status-badge.is-active {
        background-color: #d1fae5;
        color: #065f46;
    }

    .company-status-badge.is-inactive {
        background-color: #fee2e2;
        color: #991b1b;
    }

    @media (max-width: 1024px) {
        #companyTable {
            font-size: 0.8125rem;
        }

        #companyTable th,
        #companyTable td {
            padding: 0.4rem 0.65rem;
        }
    }

    @media (max-width: 768px) {
        #companyTable {
            font-size: 0.75rem;
        }

        #companyTable th,
        #companyTable td {
            padding: 0.375rem 0.5rem;
        }

        #companyDetailModal>div {
            width: 95%;
        }
    }
</style>