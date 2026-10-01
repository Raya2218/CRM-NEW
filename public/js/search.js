/**
 * Reusable Table Search and Filter Handler
 * Bisa digunakan untuk semua table dengan konfigurasi berbeda
 * 
 * Usage:
 * const handler = new TableHandler({
 *     tableId: 'userTable',
 *     ajaxUrl: '/users/search',
 *     filters: ['role', 'status'],
 *     columns: ['number', 'visit_date', 'company', 'pic', 'location', 'purpose', 'sales', 'follow_up', 'actions']
 *     searchParam: 'q' // ✅ BARU: nama parameter search (default: 'search')
 * });
 */

class TableHandler {
    constructor(config) {
        console.log('🔧 TableHandler constructor called with config:', config);

        this.config = {
            tableId: null,
            ajaxUrl: null,
            filters: [],
            columns: ['number', 'visit_date', 'company', 'pic', 'location', 'purpose', 'sales', 'follow_up', 'actions'],
            columnMapping: {}, // ✅ NEW: Map backend keys to display keys
            debounceDelay: 400,
            pageSize: 10,
            searchParam: 'search',
            ...config
        };

        console.log('📋 Final config:', this.config);

        // DOM elements
        this.searchInput = document.getElementById('searchInput') ||
            document.getElementById(`${this.config.tableId}SearchInput`) ||
            document.querySelector('input[type="text"][placeholder*="Cari"]');

        this.tbody = document.querySelector(`#${this.config.tableId} tbody`);

        this.paginationContainer = document.querySelector('.pagination-container') ||
            document.querySelector(`#${this.config.tableId}-pagination`);

        // State
        this.debounceTimer = null;
        this.currentPage = 1;
        this.lastParams = {};

        console.log('📍 DOM Elements found:', {
            searchInput: !!this.searchInput,
            tbody: !!this.tbody,
            paginationContainer: !!this.paginationContainer
        });

        if (!this.tbody || !this.config.ajaxUrl) {
            console.error('❌ TableHandler: Missing required elements or config');
            return;
        }

        this.init();
    }

    init() {
        console.log('🚀 Initializing TableHandler...');
        this.attachSearchListener();
        this.attachFilterListeners();
        this.attachPaginationListener();
        console.log('✅ TableHandler initialized');
    }

    attachSearchListener() {
        if (!this.searchInput) {
            console.warn('⚠️ Search input not found');
            return;
        }

        console.log('✅ Attaching search listener to:', this.searchInput.id);

        this.searchInput.addEventListener('input', (e) => {
            console.log('🔍 Search input event:', e.target.value);
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                console.log('⏱️ Search debounce executed');
                this.fetchData(1);
            }, this.config.debounceDelay);
        });
    }

    attachFilterListeners() {
        console.log('📌 Attaching filter listeners for:', this.config.filters);

        this.config.filters.forEach(filterName => {
            let filterElement = document.querySelector(`[data-filter="${filterName}"]`);

            if (!filterElement) {
                const possibleIds = [
                    `filter${filterName.charAt(0).toUpperCase() + filterName.slice(1)}`,
                    `${this.config.tableId}_${this.slugify(filterName)}Filter`,
                    filterName
                ];

                for (const id of possibleIds) {
                    filterElement = document.getElementById(id);
                    if (filterElement) {
                        console.log(`✅ Filter found by ID: ${id}`);
                        break;
                    }
                }
            } else {
                console.log(`✅ Filter found by data-filter: ${filterName}`);
            }

            if (filterElement) {
                filterElement.addEventListener('change', (e) => {
                    console.log(`🔄 Filter changed: ${filterName} = ${e.target.value}`);
                    this.fetchData(1);
                });
            } else {
                console.warn(`⚠️ Filter element NOT found for: ${filterName}`);
            }
        });
    }

    attachPaginationListener() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.pagination-link');
            if (link) {
                e.preventDefault();
                const page = link.dataset.page;
                if (page) {
                    console.log('📄 Pagination clicked, page:', page);
                    this.fetchData(page);
                }
            }
        });
    }

    slugify(str) {
        return str.toLowerCase().replaceAll(' ', '_');
    }

    buildParams(page = 1) {
        const params = new URLSearchParams();
        params.append('page', page);

        if (this.searchInput?.value && this.searchInput.value.trim() !== '') {
            params.append(this.config.searchParam, this.searchInput.value.trim());
            console.log(`✅ Search param: ${this.config.searchParam} = ${this.searchInput.value.trim()}`);
        }

        this.config.filters.forEach(filterName => {
            let filterElement = document.querySelector(`[data-filter="${filterName}"]`);

            if (!filterElement) {
                const possibleIds = [
                    `filter${filterName.charAt(0).toUpperCase() + filterName.slice(1)}`,
                    `${this.config.tableId}_${this.slugify(filterName)}Filter`,
                    filterName
                ];

                for (const id of possibleIds) {
                    filterElement = document.getElementById(id);
                    if (filterElement) break;
                }
            }

            if (filterElement && filterElement.value &&
                filterElement.value !== '' && filterElement.value !== 'all') {
                params.append(filterName.toLowerCase(), filterElement.value);
                console.log(`✅ Filter param: ${filterName.toLowerCase()} = ${filterElement.value}`);
            }
        });

        return params;
    }

    async fetchData(page = 1) {
        if (!this.tbody) {
            console.error('❌ tbody not found');
            return;
        }

        console.log('📡 Fetching data for page:', page);
        this.showLoading();

        try {
            const params = this.buildParams(page);
            this.lastParams = Object.fromEntries(params);

            const url = `${this.config.ajaxUrl}?${params.toString()}`;
            console.log('🌐 Fetch URL:', url);
            console.log('📤 Params:', this.lastParams);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            console.log('📥 Response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ Data received:', {
                items: data.items?.length || 0,
                total: data.pagination?.total || 0,
                sampleItem: data.items?.[0] // ✅ Log first item to see structure
            });

            if (data.debug) {
                console.log('🐛 Backend debug:', data.debug);
            }

            this.renderTable(data.items || []);
            this.renderPagination(data.pagination || {});
            this.currentPage = page;

        } catch (error) {
            console.error('❌ Fetch error:', error);
            this.showError(error.message);
        }
    }

    renderTable(items) {
        if (!items?.length) {
            console.log('ℹ️ No items to render');
            this.tbody.innerHTML = `
                <tr>
                    <td colspan="${this.config.columns.length}" class="text-center py-12">
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <div style="width: 6rem; height: 6rem; border-radius: 9999px; background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db;"></i>
                            </div>
                            <h3 style="font-size: 1.125rem; font-weight: 500; color: #111827; margin: 0 0 0.25rem 0;">Tidak Ada Data</h3>
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Tidak ada data yang sesuai dengan pencarian atau filter Anda</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        console.log(`✅ Rendering ${items.length} items`);
        this.tbody.innerHTML = items.map(item => this.renderRow(item)).join('');
    }

    // ✅ FIXED: Get value with column mapping support
    getColumnValue(item, col) {
        // Check if there's a mapping for this column
        const mappedKey = this.config.columnMapping[col] || col;

        let value = item[mappedKey];

        // Handle nested values
        if (value === undefined && mappedKey.includes('.')) {
            value = this.getNestedValue(item, mappedKey);
        }

        return value;
    }

    renderRow(item) {
        let row = `<tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s;" 
                       onmouseover="this.style.backgroundColor='#f9fafb'" 
                       onmouseout="this.style.backgroundColor='#ffffff'">`;

        this.config.columns.forEach(col => {
            if (col === 'actions') return;

            // ✅ Use mapped column value
            let value = this.getColumnValue(item, col);

            // Default to '-' if empty
            if (value === undefined || value === null || value === '') {
                value = '-';
            }

            if (col === 'status') {
                const isActive = String(value).toLowerCase() === 'active';
                row += `
                    <td style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: ${isActive ? '#d1fae5' : '#fee2e2'}; color: ${isActive ? '#065f46' : '#991b1b'};">
                            ${this.escapeHTML(String(value))}
                        </span>
                    </td>`;
                return;
            }

            // ✅ NUMBER column (rownum)
            if (col === 'number') {
                row += `
                    <td style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: #111827; white-space: nowrap;">
                        <span style="font-weight: 500;">${this.escapeHTML(String(value))}</span>
                    </td>`;
                return;
            }

            // ✅ VISIT_DATE column
            if (col === 'visit_date') {
                row += `
                    <td style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                        <div style="font-size: 0.8125rem; color: #111827;">
                            ${value !== '-' ? `
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    <i class="fas fa-calendar" style="color: #9ca3af; font-size: 0.6875rem;"></i>
                                    <span>${this.escapeHTML(String(value))}</span>
                                </div>
                            ` : '<span style="color: #9ca3af;">-</span>'}
                        </div>
                    </td>`;
                return;
            }

            // ✅ COMPANY column
            if (col === 'company') {
                row += `
                    <td style="padding: 0.5rem 0.75rem;">
                        <div style="font-size: 0.8125rem; color: #111827;">
                            ${value !== '-' ? `
                                <span style="display: flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-building" style="color: #9ca3af; font-size: 0.6875rem;"></i>
                                    ${this.escapeHTML(String(value))}
                                </span>
                            ` : '<span style="color: #9ca3af;">-</span>'}
                        </div>
                    </td>`;
                return;
            }

            // ✅ PIC column
            if (col === 'pic' || col === 'pic_name') {
                row += `
                    <td style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                        <div style="font-size: 0.8125rem; font-weight: 500; color: #111827;">
                            ${this.escapeHTML(String(value))}
                        </div>
                    </td>`;
                return;
            }

            // ✅ LOCATION column - dengan format 2 baris
            if (col === 'location') {
                let mainLocation = '-';
                let subLocation = '';

                if (value !== '-' && String(value).includes('|')) {
                    // Format: "Province | Regency, District, Village"
                    const parts = String(value).split('|').map(s => s.trim());
                    mainLocation = parts[0] || '-';
                    subLocation = parts[1] || '';
                } else if (value !== '-') {
                    // Fallback: jika tidak ada separator, tampilkan semua
                    mainLocation = String(value);
                }

                row += `
                    <td style="padding: 0.5rem 0.75rem;">
                        <div style="font-size: 0.8125rem; color: #111827;">
                            <div style="display: flex; align-items: center; gap: 0.25rem;">
                                <i class="fas fa-map-marker-alt" style="color: #9ca3af; font-size: 0.6875rem;"></i>
                                <span>${this.escapeHTML(mainLocation)}</span>
                            </div>
                            ${subLocation ? `
                                <div style="font-size: 0.6875rem; color: #6b7280; margin-top: 0.125rem;">
                                    ${this.escapeHTML(subLocation)}
                                </div>
                            ` : ''}
                        </div>
                    </td>`;
                return;
            }

            // ✅ PURPOSE column
            if (col === 'purpose') {
                row += `
                    <td style="padding: 0.5rem 0.75rem;">
                        <div style="font-size: 0.8125rem; color: #374151; max-width: 16rem;">
                            ${value !== '-' ? `
                                <span style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="${this.escapeHTML(String(value))}">
                                    ${this.escapeHTML(String(value))}
                                </span>
                            ` : '<span style="color: #9ca3af;">-</span>'}
                        </div>
                    </td>`;
                return;
            }

            // ✅ SALES column (object with username & email)
            if (col === 'sales') {
                const salesData = typeof value === 'object' && value !== null ? value : { username: value, email: '' };
                const username = salesData.username || String(value) || '-';
                const email = salesData.email || 'No email';

                row += `
                    <td style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 2rem; height: 2rem; flex-shrink: 0;">
                                <div style="width: 2rem; height: 2rem; border-radius: 9999px; background-color: #e0e7ff; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user-tie" style="color: #6366f1; font-size: 0.75rem;"></i>
                                </div>
                            </div>
                            <div style="margin-left: 0.5rem;">
                                <div style="font-size: 0.8125rem; font-weight: 500; color: #111827;">${this.escapeHTML(username)}</div>
                                <div style="font-size: 0.6875rem; color: #6b7280;">${this.escapeHTML(email)}</div>
                            </div>
                        </div>
                    </td>`;
                return;
            }

            // ✅ FOLLOW_UP column
            if (col === 'follow_up') {
                const isYes = String(value).toLowerCase() === 'ya' ||
                    String(value).toLowerCase() === 'yes' ||
                    value === true ||
                    value === 1;

                row += `
                    <td style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                        ${isYes ? `
                            <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 500; background-color: #d1fae5; color: #065f46;">
                                <i class="fas fa-check-circle" style="margin-right: 0.25rem; font-size: 0.625rem;"></i>
                                Ya
                            </span>
                        ` : `
                            <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 500; background-color: #f3f4f6; color: #374151;">
                                <i class="fas fa-times-circle" style="margin-right: 0.25rem; font-size: 0.625rem;"></i>
                                Tidak
                            </span>
                        `}
                    </td>`;
                return;
            }

            // Default column
            row += `<td style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: #111827;">
                ${this.escapeHTML(String(value))}
            </td>`;
        });

        // Actions column
        if (this.config.columns.includes('actions')) {
            row += this.renderActions(item.actions || []);
        }

        row += `</tr>`;
        return row;
    }

    renderActions(actions) {
        if (!Array.isArray(actions)) actions = [];

        let html = `<td style="padding: 0.5rem 0.75rem; text-align: right; white-space: nowrap;">
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.375rem;">`;

        actions.forEach(action => {
            const iconMap = {
                edit: 'edit',
                delete: 'trash',
                view: 'eye'
            };

            const icon = iconMap[action.type] || 'circle';

            if (action.type === 'edit') {
                html += `
                    <button 
                        onclick="${action.onclick || ''}"
                        style="color: #2563eb; background: transparent; border: none; padding: 0.375rem; border-radius: 0.375rem; cursor: pointer; transition: all 0.15s; font-size: 0.875rem;"
                        onmouseover="this.style.backgroundColor='#dbeafe'; this.style.color='#1e40af';"
                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#2563eb';"
                        title="${action.title || 'Edit'}">
                        <i class="fas fa-${icon}"></i>
                    </button>`;
            } else if (action.type === 'view') {
                html += `
                    <button
                        onclick="${action.onclick || ''}"
                        style="color: #059669; background: transparent; border: none; padding: 0.375rem; border-radius: 0.375rem; cursor: pointer; transition: all 0.15s; font-size: 0.875rem;"
                        onmouseover="this.style.backgroundColor='#d1fae5'; this.style.color='#047857';"
                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#059669';"
                        title="${action.title || 'Show Detail'}">
                        <i class="fas fa-${icon}"></i>
                    </button>`;
            } else if (action.type === 'delete') {
                html += `
                    <button 
                        onclick="${action.onclick || ''}"
                        style="color: #dc2626; background: transparent; border: none; padding: 0.375rem; border-radius: 0.375rem; cursor: pointer; transition: all 0.15s; font-size: 0.875rem;"
                        onmouseover="this.style.backgroundColor='#fee2e2'; this.style.color='#991b1b';"
                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#dc2626';"
                        title="${action.title || 'Delete'}">
                        <i class="fas fa-${icon}"></i>
                    </button>`;
            }
        });

        html += `</div></td>`;
        return html;
    }

    renderPagination(pagination) {
        if (!this.paginationContainer) {
            console.warn('⚠️ Pagination container not found');
            return;
        }

        const { current_page = 1, last_page = 1, from = 0, to = 0, total = 0 } = pagination;

        if (last_page <= 1) {
            this.paginationContainer.style.display = 'none';
            return;
        }

        this.paginationContainer.style.display = 'block';

        let html = `
            <div style="padding: 0.625rem 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div style="font-size: 0.75rem; color: #374151;">
                    Menampilkan <span style="font-weight: 500;">${from}</span> sampai <span style="font-weight: 500;">${to}</span>
                    dari <span style="font-weight: 500;">${total}</span> hasil
                </div>
                <div style="display: flex; gap: 0.25rem;">`;

        const btn = (page, text, active = false) => `
            <button class="pagination-link" data-page="${page}"
                style="min-width: 1.75rem; padding: 0.375rem 0.625rem; border: 1px solid ${active ? '#6366f1' : '#d1d5db'}; border-radius: 0.375rem; background: ${active ? '#6366f1' : 'white'}; color: ${active ? 'white' : '#374151'}; cursor: pointer; transition: all 0.2s; font-size: 0.75rem; font-weight: ${active ? '500' : '400'};"
                ${!active ? `onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='white'"` : ''}>
                ${text}
            </button>`;

        if (current_page > 1) {
            html += btn(current_page - 1, '<i class="fas fa-chevron-left"></i>');
        }

        const max = 5;
        const start = Math.max(1, current_page - Math.floor(max / 2));
        const end = Math.min(last_page, start + max - 1);

        if (start > 1) {
            html += btn(1, '1') + (start > 2 ? '<span style="padding: 0 0.5rem; color: #9ca3af;">...</span>' : '');
        }

        for (let i = start; i <= end; i++) {
            html += btn(i, i, i === current_page);
        }

        if (end < last_page) {
            html += (end < last_page - 1 ? '<span style="padding: 0 0.5rem; color: #9ca3af;">...</span>' : '') + btn(last_page, last_page);
        }

        if (current_page < last_page) {
            html += btn(current_page + 1, '<i class="fas fa-chevron-right"></i>');
        }

        html += '</div></div>';
        this.paginationContainer.innerHTML = html;
    }

    escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    getNestedValue(obj, path) {
        try {
            return path.split('.').reduce((val, key) =>
                val && val[key] !== undefined ? val[key] : undefined, obj);
        } catch {
            return undefined;
        }
    }

    showLoading() {
        if (this.tbody) {
            this.tbody.innerHTML = `
                <tr>
                    <td colspan="${this.config.columns.length}" class="text-center py-8">
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2.5rem; color: #9ca3af; margin-bottom: 0.75rem;"></i>
                            <p style="color: #6b7280; font-size: 0.875rem;">Loading...</p>
                        </div>
                    </td>
                </tr>`;
        }
    }

    showError(message = 'Error loading data') {
        if (this.tbody) {
            this.tbody.innerHTML = `
                <tr>
                    <td colspan="${this.config.columns.length}" class="text-center py-8">
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2.5rem; color: #ef4444; margin-bottom: 0.75rem;"></i>
                            <p style="color: #dc2626; font-weight: 500;">${this.escapeHTML(message)}</p>
                        </div>
                    </td>
                </tr>`;
        }
    }

    refresh(page = 1) {
        this.fetchData(page);
    }

    getLastParams() {
        return this.lastParams;
    }

    search(keyword) {
        if (this.searchInput) {
            this.searchInput.value = keyword;
            this.fetchData(1);
        }
    }
}

console.log('✅ Enhanced TableHandler loaded');