/**
 * Eterea Gestionale
 * Reusable Components
 */

const LDEComponents = {
    /**
     * Create a select dropdown with search
     */
    createSelect(options) {
        const {
            element,
            data,
            valueField = 'id',
            textField = 'name',
            placeholder = 'Seleziona...',
            multiple = false,
            onChange = null
        } = options;

        const select = document.createElement('select');
        select.className = 'w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none';
        select.multiple = multiple;

        // Add placeholder option
        const placeholderOpt = document.createElement('option');
        placeholderOpt.textContent = placeholder;
        placeholderOpt.value = '';
        select.appendChild(placeholderOpt);

        // Add options
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item[valueField];
            opt.textContent = item[textField];
            select.appendChild(opt);
        });

        // Event listener
        if (onChange) {
            select.addEventListener('change', onChange);
        }

        if (element) {
            element.appendChild(select);
        }

        return select;
    },

    /**
     * Create a multi-select with chips
     */
    createMultiSelect(options) {
        const {
            element,
            data,
            valueField = 'id',
            textField = 'name',
            placeholder = 'Seleziona...',
            selected = [],
            onChange = null
        } = options;

        const container = document.createElement('div');
        container.className = 'relative';

        // Selected chips container
        const chipsContainer = document.createElement('div');
        chipsContainer.className = 'flex flex-wrap gap-2 mb-2';
        container.appendChild(chipsContainer);

        // Select dropdown
        const select = document.createElement('select');
        select.className = 'w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none';
        select.innerHTML = `<option value="">${placeholder}</option>`;
        
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item[valueField];
            opt.textContent = item[textField];
            select.appendChild(opt);
        });

        // Render selected chips
        const renderChips = () => {
            chipsContainer.innerHTML = selected.map(id => {
                const item = data.find(d => d[valueField] === id);
                if (!item) return '';
                return `
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-cyan-100 text-cyan-800 rounded-lg text-sm">
                        ${item[textField]}
                        <button type="button" onclick="this.parentElement.remove()" class="hover:text-cyan-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                `;
            }).join('');
        };

        select.addEventListener('change', (e) => {
            const value = e.target.value;
            if (value && !selected.includes(value)) {
                selected.push(value);
                renderChips();
                if (onChange) onChange(selected);
            }
            select.value = '';
        });

        container.appendChild(select);

        if (element) {
            element.appendChild(container);
        }

        renderChips();
        return { container, selected };
    },

    /**
     * Create a file upload zone
     */
    createFileUpload(options) {
        const {
            element,
            accept = '*',
            maxSize = 10 * 1024 * 1024,
            multiple = false,
            onSelect = null,
            onError = null
        } = options;

        const container = document.createElement('div');
        container.className = 'border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-cyan-500 hover:bg-cyan-50 transition-colors cursor-pointer';
        
        container.innerHTML = `
            <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <p class="text-slate-600 font-medium">Trascina i file qui o clicca per selezionare</p>
            <p class="text-sm text-slate-400 mt-1">Max ${(maxSize / 1024 / 1024).toFixed(0)}MB per file</p>
        `;

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = accept;
        input.multiple = multiple;
        input.className = 'hidden';

        // Click to select
        container.addEventListener('click', () => input.click());

        // Drag and drop
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            container.classList.add('border-cyan-500', 'bg-cyan-50');
        });

        container.addEventListener('dragleave', () => {
            container.classList.remove('border-cyan-500', 'bg-cyan-50');
        });

        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.classList.remove('border-cyan-500', 'bg-cyan-50');
            handleFiles(e.dataTransfer.files);
        });

        input.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        const handleFiles = (files) => {
            const validFiles = [];
            
            Array.from(files).forEach(file => {
                if (file.size > maxSize) {
                    if (onError) onError(`File "${file.name}" troppo grande`);
                    return;
                }
                validFiles.push(file);
            });

            if (onSelect && validFiles.length > 0) {
                onSelect(multiple ? validFiles : validFiles[0]);
            }
        };

        container.appendChild(input);

        if (element) {
            element.appendChild(container);
        }

        return { container, input };
    },

    /**
     * Create a progress bar
     */
    createProgressBar(options) {
        const {
            element,
            value = 0,
            max = 100,
            color = 'cyan',
            size = 'md'
        } = options;

        const container = document.createElement('div');
        const heightClass = size === 'sm' ? 'h-1' : size === 'lg' ? 'h-4' : 'h-2';
        
        container.className = `${heightClass} bg-slate-100 rounded-full overflow-hidden`;
        container.innerHTML = `
            <div class="h-full bg-${color}-500 rounded-full transition-all duration-300" style="width: ${(value / max) * 100}%"></div>
        `;

        if (element) {
            element.appendChild(container);
        }

        return {
            container,
            setValue: (newValue) => {
                const bar = container.querySelector('div');
                bar.style.width = `${(newValue / max) * 100}%`;
            }
        };
    },

    /**
     * Create a badge
     */
    createBadge(options) {
        const {
            text,
            color = 'gray',
            size = 'md'
        } = options;

        const sizeClasses = {
            sm: 'px-2 py-0.5 text-xs',
            md: 'px-2.5 py-1 text-sm',
            lg: 'px-3 py-1.5 text-base'
        };

        const badge = document.createElement('span');
        badge.className = `inline-flex items-center rounded-full font-medium bg-${color}-100 text-${color}-700 ${sizeClasses[size]}`;
        badge.textContent = text;

        return badge;
    },

    /**
     * Create an avatar
     */
    createAvatar(options) {
        const {
            name,
            image = null,
            color = '#3B82F6',
            size = 'md'
        } = options;

        const sizeClasses = {
            sm: 'w-8 h-8 text-xs',
            md: 'w-10 h-10 text-sm',
            lg: 'w-14 h-14 text-lg'
        };

        const initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

        const avatar = document.createElement('div');
        avatar.className = `${sizeClasses[size]} rounded-full flex items-center justify-center text-white font-medium`;
        
        if (image) {
            avatar.innerHTML = `<img src="${image}" alt="${name}" class="w-full h-full object-cover rounded-full">`;
        } else {
            avatar.style.backgroundColor = color;
            avatar.textContent = initials;
        }

        return avatar;
    },

    /**
     * Create a card
     */
    createCard(options) {
        const {
            title = null,
            subtitle = null,
            content,
            footer = null,
            elevated = true
        } = options;

        const card = document.createElement('div');
        card.className = elevated 
            ? 'bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden'
            : 'bg-white rounded-xl border border-slate-200 overflow-hidden';

        let html = '';

        if (title) {
            html += `
                <div class="p-5 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800">${title}</h3>
                    ${subtitle ? `<p class="text-sm text-slate-500 mt-1">${subtitle}</p>` : ''}
                </div>
            `;
        }

        html += `<div class="p-5">${content}</div>`;

        if (footer) {
            html += `<div class="px-5 py-3 bg-slate-50 border-t border-slate-100">${footer}</div>`;
        }

        card.innerHTML = html;

        return card;
    },

    /**
     * Create a data table
     */
    createTable(options) {
        const {
            columns,
            data,
            onRowClick = null
        } = options;

        const table = document.createElement('div');
        table.className = 'overflow-x-auto';

        let html = `
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        ${columns.map(col => `
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-200 ${col.class || ''}">
                                ${col.header}
                            </th>
                        `).join('')}
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
        `;

        data.forEach((row, index) => {
            html += `
                <tr class="hover:bg-slate-50 transition-colors ${onRowClick ? 'cursor-pointer' : ''}" 
                    ${onRowClick ? `onclick="${onRowClick(row, index)}"` : ''}>
                    ${columns.map(col => `
                        <td class="px-4 py-3 ${col.class || ''}">
                            ${col.render ? col.render(row[col.field], row) : row[col.field]}
                        </td>
                    `).join('')}
                </tr>
            `;
        });

        html += '</tbody></table>';
        table.innerHTML = html;

        return table;
    },

    /**
     * Create tabs
     */
    createTabs(options) {
        const {
            element,
            tabs,
            activeTab = 0
        } = options;

        const container = document.createElement('div');

        // Tab headers
        const header = document.createElement('div');
        header.className = 'flex border-b border-slate-200';

        tabs.forEach((tab, index) => {
            const btn = document.createElement('button');
            btn.className = `px-6 py-4 text-sm font-medium transition-colors ${
                index === activeTab 
                    ? 'text-cyan-600 border-b-2 border-cyan-600' 
                    : 'text-slate-500 hover:text-slate-700'
            }`;
            btn.textContent = tab.label;
            btn.onclick = () => switchTab(index);
            header.appendChild(btn);
        });

        // Tab content
        const content = document.createElement('div');
        content.className = 'p-6';

        const switchTab = (index) => {
            // Update header
            Array.from(header.children).forEach((btn, i) => {
                btn.className = `px-6 py-4 text-sm font-medium transition-colors ${
                    i === index 
                        ? 'text-cyan-600 border-b-2 border-cyan-600' 
                        : 'text-slate-500 hover:text-slate-700'
                }`;
            });

            // Update content
            content.innerHTML = '';
            if (typeof tabs[index].content === 'string') {
                content.innerHTML = tabs[index].content;
            } else if (tabs[index].content instanceof HTMLElement) {
                content.appendChild(tabs[index].content);
            }
        };

        container.appendChild(header);
        container.appendChild(content);

        // Set initial content
        switchTab(activeTab);

        if (element) {
            element.appendChild(container);
        }

        return { container, switchTab };
    }
};

// Expose globally
window.LDEComponents = LDEComponents;
