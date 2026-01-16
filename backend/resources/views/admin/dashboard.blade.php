@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<style>
  .bell-circle {
    position: fixed;
    bottom: 30px;
    right: 40px;
    width: 48px;
    height: 48px;
    background: #007bff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1051;
  }
  .bell-badge {
    position: absolute;
    top: 8px;
    right: 6px;
    background: #dc3545;
    color: #fff;
    border-radius: 8px;
    padding: 0 6px;
    font-size: 13px;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
    line-height: 18px;
    height: 18px;
    box-shadow: 0 0 2px #0002;
  }
</style>

<!-- Bell Icon with badge -->
<div class="bell-circle" id="review-bell" style="display:none">
    <span style="color:white;font-size:22px;position:relative;">
        <i class="fa fa-bell"></i>
        <span class="bell-badge" id="review-badge" style="display:none"></span>
    </span>
</div>

<!-- Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reviewModalLabel">Bookings Need Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="review-modal-body">
        <!-- Content set by JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a id="open-bookings-link" class="btn btn-primary" href="/admin/bookings?status=Review">Open</a>
      </div>
    </div>
  </div>
</div>

<!-- Font Awesome for bell icon (fallback to Unicode if not loaded) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="container py-5">
    <h1 class="mb-4 fw-bold text-dark">Welcome, <span class="text-primary">Admin</span></h1>

    <div class="row g-4">
        <x-stat-card color="primary" icon="person" label="Total Users" :value="$data['total_users']" />
        <x-stat-card color="success" icon="calendar" label="Total Bookings" :value="$data['total_bookings']" />
        <x-stat-card color="warning" icon="building" label="Total Halls" :value="$data['total_halls']" />
        <x-stat-card color="info" icon="currency-dollar" label="Total Revenue" :value="'৳' . $data['total_revenue']" />
    </div>
</div>

<script>
const bookingsApi = '/api/bookings';
let reviewCount = 0;


async function fetchReviewCount() {
    try {
        const res = await fetch(bookingsApi);
        const bookingsRaw = await res.json();
        let bookingsArr = Array.isArray(bookingsRaw) ? bookingsRaw : (bookingsRaw.data ? bookingsRaw.data : []);
        // Filter for status Review
        reviewCount = bookingsArr.filter(b => b.status === 'Review').length;
        const bell = document.getElementById('review-bell');
        const badge = document.getElementById('review-badge');
        if (reviewCount > 0) {
            bell.style.display = '';
            badge.textContent = reviewCount;
            badge.style.display = '';
        } else {
            bell.style.display = 'none';
        }
    } catch (e) {
        document.getElementById('review-bell').style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const bell = document.getElementById('review-bell');
    bell.addEventListener('click', function() {
        document.getElementById('review-modal-body').textContent = `${reviewCount} booking${reviewCount !== 1 ? 's' : ''} need${reviewCount !== 1 ? '' : 's'} to be reviewed.`;
        var modal = new bootstrap.Modal(document.getElementById('reviewModal'));
        modal.show();
    });
    fetchReviewCount();
    setInterval(fetchReviewCount, 60000);
});
</script>
@endsection
