@extends('layouts.app')

@section('title', 'Create Club Event Booking')

@section('content')
<div class="booking-bg d-flex align-items-center justify-content-center">
    <div class="bg-dark text-white w-100 booking-card">
        <h2 class="text-center mb-4 booking-title">Create Club Event Booking</h2>
        <form method="POST" action="{{ url('/admin/bookings/block') }}">
            @csrf

            {{-- Hall Selector --}}
            <div class="mb-3">
                <label class="form-label fw-semibold booking-label">Select Hall</label>
                <select class="form-select booking-select" name="hall_id" required>
                    <option value="">-- Select Hall --</option>
                    @foreach($halls as $hall)
                        <option value="{{ $hall->id }}" {{ old('hall_id') == $hall->id ? 'selected' : '' }}>
                            {{ $hall->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Shift Selector --}}
            <div class="mb-3">
                <label class="form-label fw-semibold booking-label">Select Shift</label>
                <select class="form-select booking-select" name="shift" required>
                    <option value="FN" {{ old('shift') == 'FN' ? 'selected' : '' }}>Forenoon (FN)</option>
                    <option value="AN" {{ old('shift') == 'AN' ? 'selected' : '' }}>Afternoon (AN)</option>
                    <option value="FD" {{ old('shift') == 'FD' ? 'selected' : '' }}>Full Day (FD)</option>
                </select>
            </div>

            {{-- Date Picker --}}
            @php
                $currentYear = now()->year;
                $currentMonth = now()->month;
                $currentDay = now()->day;
                $selectedYear = old('year', $currentYear);
                $selectedMonth = old('month', $currentMonth);
                $selectedDay = old('day', $currentDay);
                $months = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                ];
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
            @endphp
            <div class="mb-3">
                <label class="form-label fw-semibold booking-label">Select Date</label>
                <div class="d-flex gap-2 flex-wrap">
                    {{-- Day --}}
                    <select id="day-select" class="form-select booking-select flex-fill" name="day" required style="min-width:80px;">
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            <option value="{{ $d }}" {{ $selectedDay == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endfor
                    </select>
                    {{-- Month --}}
                    <select id="month-select" class="form-select booking-select flex-fill" name="month" required style="min-width:120px;">
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    {{-- Year --}}
                    <select id="year-select" class="form-select booking-select flex-fill" name="year" required style="min-width:100px;">
                        @for($y = $currentYear; $y <= $currentYear + 10; $y++)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn booking-btn">
                    Book Date
                </button>
            </div>

            {{-- Success & Error Messages --}}
            @if(session('message'))
                <div class="text-success mt-3 fw-medium">{{ session('message') }}</div>
            @endif
            @if($errors->has('error'))
                <div class="text-danger mt-3 fw-medium">{{ $errors->first('error') }}</div>
            @endif
        </form>
    </div>
</div>

{{-- Dynamic Day Count Script --}}
<style>
    .booking-bg {
        font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        width: 100%;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .booking-card {
        max-width: 600px;
        margin: auto;
        padding: 2.5rem 2rem 2rem 2rem;
        border-radius: 1rem;
        border: 1.5px solid #B18E4E99;
        box-shadow: 0 8px 32px 0 #0004, 0 1.5px 0 #B18E4E44;
        background: rgba(30,30,30,0.98);
        transition: box-shadow 0.2s;
    }
    .booking-card:hover {
        box-shadow: 0 12px 40px 0 #B18E4E44, 0 2px 0 #B18E4E;
    }
    .booking-title {
        color: #B18E4E;
        font-weight: bold;
        letter-spacing: 1px;
        font-size: 2rem;
        margin-bottom: 2rem;
    }
    .booking-label {
        color: #e0c88b;
        font-size: 1.08rem;
        margin-bottom: 0.3rem;
    }
    .booking-select {
        background: #232323;
        color: #fff;
        border: 1.5px solid #B18E4E99;
        border-radius: 0.4rem;
        min-width: 80px;
        transition: border-color 0.2s, box-shadow 0.2s;
        font-size: 1rem;
    }
    .booking-select:focus {
        border-color: #B18E4E;
        box-shadow: 0 0 0 2px #B18E4E33;
        outline: none;
    }
    .booking-btn {
        background:  #B18E4E ;
        color: #232323;
        font-weight: bold;
        width: 100%;
        max-width: 220px;
        border: none;
        border-radius: 0.5rem;
        font-size: 1.1rem;
        box-shadow: 0 2px 8px #B18E4E22;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    }
    .booking-btn:hover, .booking-btn:focus {
        background:  #B18E4E ;
        color: #232323;
        box-shadow: 0 4px 16px #B18E4E44;
    }
    .text-success, .text-danger {
        font-size: 1.08rem;
        border-radius: 0.3rem;
        padding: 0.5rem 0.8rem;
        margin-top: 1rem;
        background: #23232344;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const daySelect = document.getElementById('day-select');
        const monthSelect = document.getElementById('month-select');
        const yearSelect = document.getElementById('year-select');
        function updateDays() {
            const month = parseInt(monthSelect.value);
            const year = parseInt(yearSelect.value);
            const daysInMonth = new Date(year, month, 0).getDate();
            const currentDay = parseInt(daySelect.value);
            daySelect.innerHTML = '';
            for (let d = 1; d <= daysInMonth; d++) {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                if (d === currentDay) opt.selected = true;
                daySelect.appendChild(opt);
            }
            if (currentDay > daysInMonth) {
                daySelect.value = daysInMonth;
            }
        }
        monthSelect.addEventListener('change', updateDays);
        yearSelect.addEventListener('change', updateDays);
    });
</script>
@endsection
