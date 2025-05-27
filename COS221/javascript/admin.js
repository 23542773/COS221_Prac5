//client side local schema
const allowedTables = {
    admin: ['K', 'Privilege'],
    categories: ['RID', 'Category'],
    listings: ['ProductID', 'RID', 'quantity', 'price', 'remaining'],
    productimgs: ['PID', 'URL'],
    products: ['ProductID', 'Name', 'Description', 'Brand', 'Category', 'Thumbnail'],
    retailers: ['RetailerID', 'Name', 'URL']
};

const actionSelect = document.getElementById('action');
const tableSelect = document.getElementById('table');
const formFieldsDiv = document.getElementById('formFields');
const responseDiv = document.getElementById('response');
const submitBtn = document.getElementById('submitBtn');

// Populate table dropdown
for (const table in allowedTables) {
    const option = document.createElement('option');
    option.value = table;
    option.textContent = table.charAt(0).toUpperCase() + table.slice(1);
    tableSelect.appendChild(option);
}

actionSelect.addEventListener('change', updateForm);
tableSelect.addEventListener('change', updateForm);

// update form for admin to input values
function updateForm() {
    const action = actionSelect.value;
    const table = tableSelect.value;
    formFieldsDiv.innerHTML = '';
    formFieldsDiv.style.display = 'none';

    if (!action) return;

    formFieldsDiv.style.display = 'block';

    //create
    if (action === 'create') {
        formFieldsDiv.innerHTML = '<h4>Record Values</h4>';
        allowedTables[table].forEach(field => {
            createFieldInput('values_' + field, field, getFieldPlaceholder(table, field));
        });
        //update
    } else if (action === 'update') {
        formFieldsDiv.innerHTML = '<div class="field-group"><h4>Updates (new values)</h4></div>';
        allowedTables[table].forEach(field => {
            createFieldInput('updates_' + field, field, `New ${field}`);
        });

        const whereDiv = document.createElement('div');
        whereDiv.className = 'field-group';
        whereDiv.innerHTML = '<h4>Where Conditions (criteria to match)</h4>';
        formFieldsDiv.appendChild(whereDiv);

        allowedTables[table].forEach(field => {
            createFieldInput('where_' + field, field, `Match ${field}`);
        });
        //delete
    } else if (action === 'delete' && allowedTables[table]) {
        formFieldsDiv.innerHTML = '<h4>Delete Conditions (records matching these will be deleted)</h4>';
        allowedTables[table].forEach(field => {
            createFieldInput('where_' + field, field, `Match ${field}`);
        });
    }
}

function createFieldInput(id, label, placeholder) {
    const div = document.createElement('div');
    div.style.marginBottom = '10px';

    const labelEl = document.createElement('label');
    labelEl.textContent = label + ':';
    labelEl.style.fontSize = '12px';
    labelEl.style.color = '#666';
    labelEl.style.marginBottom = '3px';

    const input = document.createElement('input');
    input.type = getInputType(label);
    input.id = id;
    input.placeholder = placeholder;
    input.style.width = '100%';
    input.style.padding = '8px';
    input.style.fontSize = '12px';

    div.appendChild(labelEl);
    div.appendChild(input);
    formFieldsDiv.appendChild(div);
}

function getInputType(fieldName) {
    if (fieldName.toLowerCase().includes('password')) return 'password';
    if (fieldName.toLowerCase().includes('email')) return 'email';
    if (fieldName.toLowerCase().includes('phone')) return 'tel';
    if (fieldName.toLowerCase().includes('url')) return 'url';
    return 'text';
}

function getFieldPlaceholder(table, field) {
    const placeholders = {
        admin: {
            'K': 'User API Key',
            'Privilege': 'Super Admin, Listings Admin, or User Admin'
        },
        products: {
            'ProductID': 'Unique product identifier',
            'Category': 'Product category'
        }
    };

    return placeholders[table]?.[field] || `Enter ${field}`;
}

async function sendRequest() {
    const adminKey = localStorage.getItem('apikey');

    if (!adminKey) {
        showResponse('Admin key is required', 'error');
        return;
    }

    const action = actionSelect.value;
    if (!action) {
        showResponse('Please select an action', 'error');
        return;
    }

    // Set loading state
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    document.body.classList.add('loading');

    const payload = {
        api: 'admin',
        operation: action,
        apikey: adminKey
    };

    console.log(payload);

    try {
        // Build payload based on action
        if (action === 'create') {
            const table = tableSelect.value;
            if (!table) {
                throw new Error('Please select a table');
            }
            payload.table = table;
            payload.values = {};

            let hasValues = false;
            allowedTables[table].forEach(field => {
                const value = document.getElementById('values_' + field)?.value?.trim();
                if (value) {
                    payload.values[field] = value;
                    hasValues = true;
                }
            });

            if (!hasValues) {
                throw new Error('Please provide at least one value');
            }
        }
        else if (action === 'update') {
            const table = tableSelect.value;
            if (!table) {
                throw new Error('Please select a table');
            }
            payload.table = table;
            payload.updates = {};
            payload.where = {};

            let hasUpdates = false;
            let hasWhere = false;

            allowedTables[table].forEach(field => {
                const updateValue = document.getElementById('updates_' + field)?.value?.trim();
                const whereValue = document.getElementById('where_' + field)?.value?.trim();

                if (updateValue) {
                    payload.updates[field] = updateValue;
                    hasUpdates = true;
                }
                if (whereValue) {
                    payload.where[field] = whereValue;
                    hasWhere = true;
                }
            });

            if (!hasUpdates) throw new Error('Please provide at least one update value');
            if (!hasWhere) throw new Error('Please provide at least one where condition');
        }
        else if (action === 'delete') {
            const table = tableSelect.value;
            if (!table) {
                throw new Error('Please select a table');
            }
            payload.table = table;
            payload.where = {};

            let hasWhere = false;
            allowedTables[table].forEach(field => {
                const value = document.getElementById('where_' + field)?.value?.trim();
                if (value) {
                    payload.where[field] = value;
                    hasWhere = true;
                }
            });

            if (!hasWhere) {
                throw new Error('Please provide at least one condition for deletion');
            }

            if (!confirm('Are you sure you want to delete records matching these conditions? This action cannot be undone.')) {
                return;
            }
        }

        console.log('Sending payload:', payload);

        const response = await fetch('../api_cos221.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            throw new Error(`error: ${response.status}`);
        }

        const result = await response.json();
        console.log('Response:', result);

        if (result.status === 'success') {
            showResponse(JSON.stringify(result.data, null, 2), 'success');
        } else {
            showResponse(result.data || result.message || 'Unknown error occurred', 'error');
        }

    } catch (error) {

        console.error('Request failed:', error);
        showResponse(`Error: ${error.message}`, 'error');

    } finally {
        // Reset loading state
        submitBtn.disabled = false;
        submitBtn.textContent = 'Execute Action';
        document.body.classList.remove('loading');
    }
}

function showResponse(message, type = 'info') {
    responseDiv.textContent = message;
    responseDiv.className = `response ${type}`;
}