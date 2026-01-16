@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Hall Management</h1>

    {{-- Create Hall --}}
    @php
        $admin = Auth::guard('admin')->user();
        // $isAdmin is true only for admins, but all users (including non-admins) should see everything
        $isAdmin = $admin && $admin->role === 'admin';
        // $canEdit is true only for admins, false for non-admins
        $canEdit = $isAdmin;
    @endphp
    <div class="card mb-4">
        <div class="card-header">Add New Hall</div>
        <div class="card-body">
            <form id="create-hall-form">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required @if(!$canEdit) disabled @endif>
                </div>
                <button type="submit" class="btn btn-primary" @if(!$canEdit) disabled @endif>Create Hall</button>
            </form>
        </div>
    </div>

    {{-- List Halls --}}
    <div id="halls-list"
        data-can-edit="{{ $canEdit ? '1' : '0' }}"
        data-is-admin="{{ $isAdmin ? '1' : '0' }}"
    ></div>
</div>

<script>
const apiBase = '/api/halls';

// Get canEdit and isAdmin from blade
const hallsListDiv = document.getElementById('halls-list');
const canEdit = hallsListDiv.dataset.canEdit === '1';
const isAdmin = hallsListDiv.dataset.isAdmin === '1';

// Fetch and render all halls
async function fetchHalls() {
    const res = await fetch(apiBase);
    const halls = await res.json();
    renderHalls(halls);
}

function renderHalls(halls) {
    const container = document.getElementById('halls-list');
    container.innerHTML = '';
    halls.forEach(hall => {
        const div = document.createElement('div');
        div.className = 'card mb-3';
        div.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><b>${hall.name}</b> (ID: ${hall.id})</span>
                ${canEdit
                    ? `<button class="btn btn-danger btn-sm" onclick="deleteHall(${hall.id})">Delete</button>`
                    : `<button class="btn btn-danger btn-sm" disabled style="pointer-events:none;opacity:0.5;">Delete</button>`
                }
            </div>
            <div class="card-body">
                <form onsubmit="event.preventDefault(); updateHall(${hall.id}, this);">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>Name</label>
                            <input type="text" class="form-control" name="name" value="${hall.name}" ${!canEdit ? 'disabled' : ''}>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Description</label>
                            <input type="text" class="form-control" name="description" value="${hall.description || ''}" ${!canEdit ? 'disabled' : ''}>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Capacity</label>
                            <input type="number" class="form-control" name="capacity" value="${hall.capacity || ''}" ${!canEdit ? 'disabled' : ''}>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Active</label>
                            <input type="checkbox" name="is_active" ${hall.is_active ? 'checked' : ''} ${!canEdit ? 'disabled' : ''}>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm mt-2" ${!canEdit ? 'disabled' : ''}>Update Basic Info</button>
                </form>

                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Charges</h5>
                        <div id="charges-${hall.id}"></div>
                        <form onsubmit="event.preventDefault(); addCharge(${hall.id}, this);">
                            <input type="text" name="name" placeholder="Charge Name" required ${!canEdit ? 'disabled' : ''}>
                            <input type="number" name="value" placeholder="Value" required ${!canEdit ? 'disabled' : ''}>
                            <button type="submit" class="btn btn-primary btn-sm" ${!canEdit ? 'disabled' : ''}>Add</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h5>Policy Content</h5>
                        <div class="mb-2">
                            <input type="text" id="policy-search-${hall.id}" placeholder="Search policy key...">
                            <button class="btn btn-secondary btn-sm" onclick="searchPolicyContent(${hall.id})">Search</button>
                        </div>
                        <div id="policy-content-${hall.id}"></div>
                        <form onsubmit="event.preventDefault(); addPolicyContent(${hall.id}, this);">
                            <input type="text" name="header" placeholder="Header" required ${!canEdit ? 'disabled' : ''}>
                            <input type="text" name="description" placeholder="Description" required ${!canEdit ? 'disabled' : ''}>
                            <button type="submit" class="btn btn-primary btn-sm" ${!canEdit ? 'disabled' : ''}>Add</button>
                        </form>
                    </div>
                </div>

                <hr>
                <div>
                    <h5>Images</h5>
                    <div id="images-${hall.id}"></div>
                    <form onsubmit="event.preventDefault(); addImages(${hall.id}, this);" enctype="multipart/form-data">
                        <input type="file" name="images[]" multiple required>
                        <button type="submit" class="btn btn-primary btn-sm" ${!canEdit ? 'disabled' : ''}>Upload Images</button>
                    </form>
                </div>

                <hr>
                <div>
                    <h5>Policy PDF</h5>
                    <div id="policy-pdf-${hall.id}"></div>
                    <form onsubmit="event.preventDefault(); addPolicyPdf(${hall.id}, this);" enctype="multipart/form-data">
                        <input type="file" name="policy_pdf" accept="application/pdf" required>
                        <button type="submit" class="btn btn-primary btn-sm" ${!canEdit ? 'disabled' : ''}>Upload PDF</button>
                    </form>
                </div>
            </div>
        `;
        container.appendChild(div);
        renderCharges(hall);
        renderPolicyContent(hall);
        renderImages(hall);
        renderPolicyPdf(hall);
    });
}

// CRUD for Hall

document.getElementById('create-hall-form').onsubmit = async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    // Add empty arrays for images, charges, and policy_content
    formData.append('images', JSON.stringify([]));
    formData.append('charges', JSON.stringify({}));
    formData.append('policy_content', JSON.stringify({}));
    await fetch(apiBase, {
        method: 'POST',
        body: formData
    });
    this.reset();
    fetchHalls();
};

async function updateHall(id, form) {
    const data = {
        name: form.name.value,
        description: form.description.value,
        capacity: form.capacity.value,
        is_active: form.is_active.checked
    };
    await fetch(`${apiBase}/${id}/basic`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    fetchHalls();
}

async function deleteHall(id) {
    if (!confirm('Delete this hall?')) return;
    await fetch(`${apiBase}/${id}`, { method: 'DELETE' });
    fetchHalls();
}

// Charges
function renderCharges(hall) {
    const el = document.getElementById(`charges-${hall.id}`);
    el.innerHTML = '';
    const charges = hall.charges || {};
    Object.entries(charges).forEach(([name, value]) => {
        el.innerHTML += `
            <div class="d-flex align-items-center mb-1">
                <b>${name}</b>: <input type="number" value="${value}" style="width:80px;" onchange="updateCharge(${hall.id}, '${name}', this.value)" ${!canEdit ? 'disabled' : ''}>
                ${canEdit
                    ? `<button class="btn btn-danger btn-sm ms-2" onclick="deleteCharge(${hall.id}, '${name}')">Delete</button>`
                    : `<button class="btn btn-danger btn-sm ms-2" disabled style="pointer-events:none;opacity:0.5;">Delete</button>`
                }
            </div>
        `;
    });
}
async function addCharge(id, form) {
    const formData = new FormData();
    formData.append('name', form.name.value);
    formData.append('value', form.value.value);
    await fetch(`${apiBase}/${id}/charge`, {
        method: 'POST',
        body: formData
    });
    fetchHalls();
}
async function updateCharge(id, name, value) {
    const formData = new FormData();
    formData.append('name', name);
    formData.append('value', value);
    await fetch(`${apiBase}/${id}/charge`, {
        method: 'POST', // Use POST for update with FormData
        body: formData
    });
    fetchHalls();
}
async function deleteCharge(id, name) {
    const formData = new FormData();
    formData.append('name', name);
    await fetch(`${apiBase}/${id}/charge`, {
        method: 'POST', // Use POST for delete with FormData
        body: formData,
        headers: { 'X-HTTP-Method-Override': 'DELETE' }
    });
    fetchHalls();
}

// Policy Content
function renderPolicyContent(hall) {
    const el = document.getElementById(`policy-content-${hall.id}`);
    el.innerHTML = '';
    const policy = hall.policy_content || {};
    Object.entries(policy).forEach(([header, desc]) => {
        el.innerHTML += `
            <div class="mb-1">
                <b>${header}</b>: <input type="text" value="${desc}" style="width:60%" onchange="updatePolicyContent(${hall.id}, '${header}', this.value)" ${!canEdit ? 'disabled' : ''}>
                ${canEdit
                    ? `<button class="btn btn-danger btn-sm ms-2" onclick="deletePolicyContent(${hall.id}, '${header}')">Delete</button>`
                    : `<button class="btn btn-danger btn-sm ms-2" disabled style="pointer-events:none;opacity:0.5;">Delete</button>`
                }
            </div>
        `;
    });
}
async function addPolicyContent(id, form) {
    const formData = new FormData();
    formData.append('header', form.header.value);
    formData.append('description', form.description.value);
    await fetch(`${apiBase}/${id}/policy-content`, {
        method: 'POST',
        body: formData
    });
    fetchHalls();
}
async function updatePolicyContent(id, header, description) {
    const formData = new FormData();
    formData.append('header', header);
    formData.append('description', description);
    await fetch(`${apiBase}/${id}/policy-content`, {
        method: 'POST', // Use POST for update with FormData
        body: formData
    });
    fetchHalls();
}
async function deletePolicyContent(id, header) {
    const formData = new FormData();
    formData.append('header', header);
    await fetch(`${apiBase}/${id}/policy-content`, {
        method: 'POST', // Use POST for delete with FormData
        body: formData,
        headers: { 'X-HTTP-Method-Override': 'DELETE' }
    });
    fetchHalls();
}
async function searchPolicyContent(id) {
    const query = document.getElementById(`policy-search-${id}`).value;
    if (!query) return;
    const formData = new FormData();
    formData.append('query', query);
    const res = await fetch(`${apiBase}/${id}/policy-content/search`, {
        method: 'POST',
        body: formData
    });
    const { results } = await res.json();
    const el = document.getElementById(`policy-content-${id}`);
    el.innerHTML = '';
    Object.entries(results).forEach(([header, desc]) => {
        el.innerHTML += `<div class="mb-1"><b>${header}</b>: ${desc}</div>`;
    });
}

// Images
function renderImages(hall) {
    const el = document.getElementById(`images-${hall.id}`);
    el.innerHTML = '';
    (hall.images || []).forEach(url => {
        el.innerHTML += `<div class="d-inline-block me-2 mb-2"><img src="${url}" width="100">${canEdit
            ? `<button class=\"btn btn-danger btn-sm d-block mt-1\" onclick=\"deleteImage(${hall.id}, '${url}')\">Delete</button>`
            : `<button class=\"btn btn-danger btn-sm d-block mt-1\" disabled style=\"pointer-events:none;opacity:0.5;\">Delete</button>`
        }</div>`;
    });
}
async function addImages(id, form) {
    const formData = new FormData(form);
    await fetch(`${apiBase}/${id}/images`, {
        method: 'POST',
        body: formData
    });
    fetchHalls();
}
async function deleteImage(id, url) {
    const formData = new FormData();
    formData.append('image_url', url);
    await fetch(`${apiBase}/${id}/images`, {
        method: 'POST',
        body: formData,
        headers: { 'X-HTTP-Method-Override': 'DELETE' }
    });
    fetchHalls();
}

// Policy PDF
function renderPolicyPdf(hall) {
    const el = document.getElementById(`policy-pdf-${hall.id}`);
    el.innerHTML = hall.policy_pdf
        ? `<a href="${hall.policy_pdf}" target="_blank">View PDF</a> ${canEdit
            ? `<button class=\"btn btn-danger btn-sm\" onclick=\"deletePolicyPdf(${hall.id})\">Delete</button>`
            : `<button class=\"btn btn-danger btn-sm\" disabled style=\"pointer-events:none;opacity:0.5;\">Delete</button>`
        }`
        : 'No PDF uploaded.';
}
async function addPolicyPdf(id, form) {
    const formData = new FormData(form);
    await fetch(`${apiBase}/${id}/policy-pdf`, {
        method: 'POST',
        body: formData
    });
    fetchHalls();
}
async function deletePolicyPdf(id) {
    await fetch(`${apiBase}/${id}/policy-pdf`, { method: 'DELETE' });
    fetchHalls();
}

// Initial load
fetchHalls();
</script>
@endsection
