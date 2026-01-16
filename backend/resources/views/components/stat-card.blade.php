@props(['label', 'value', 'icon', 'color'])

<div class="col-md-6 col-lg-3">
    <div class="card text-white bg-{{ $color }} shadow-sm h-100">
        <div class="card-body text-center">
            <div class="mb-2">
                <i class="bi bi-{{ $icon }} display-6"></i>
            </div>
            <h5 class="card-title">{{ $label }}</h5>
            <p class="card-text fs-3 fw-bold">{{ $value }}</p>
        </div>
    </div>
</div>
