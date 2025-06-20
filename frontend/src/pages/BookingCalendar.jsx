import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate, useParams } from 'react-router-dom';
import { useHalls } from '../context/HallsContext';
import Footer from '../components/Footer';
const API_BASE = import.meta.env.VITE_API_URL;

const BookingCalendar = () => {
  const { authData } = useAuth();
  const navigate = useNavigate();
  const { halls } = useHalls();
  const { hallId } = useParams();
  
  const [selectedMonth, setSelectedMonth] = useState('');
  const [selectedYear, setSelectedYear] = useState('');
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showResults, setShowResults] = useState(false);
  const [selectedDate, setSelectedDate] = useState('');
  const [selectedShift, setSelectedShift] = useState('');
  const [showConfirmModal, setShowConfirmModal] = useState(false);

  const currentHall = halls.find(hall => hall.id === parseInt(hallId));

  const months = [
    { value: '01', label: 'January' },
    { value: '02', label: 'February' },
    { value: '03', label: 'March' },
    { value: '04', label: 'April' },
    { value: '05', label: 'May' },
    { value: '06', label: 'June' },
    { value: '07', label: 'July' },
    { value: '08', label: 'August' },
    { value: '09', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' }
  ];

  const shifts = [
    { value: 'FN', label: 'Forenoon' },
    { value: 'AN', label: 'Afternoon' },
    { value: 'FD', label: 'Full Day' }
  ];

  /*useEffect(() => {
    if (!authData?.member) {
      navigate("/login");
    }
  }, [authData, navigate]);*/

  const handleSearch = async () => {
    if (!selectedMonth || !selectedYear) {
      alert('Please select both month and year');
      return;
    }

    setLoading(true);
    try {
      const response = await fetch(
        `${API_BASE}/api/bookings/hall/${hallId}?month=${selectedYear}-${selectedMonth}`,
        /*{
          headers: {
            'Authorization': `Bearer ${authData.token}`
          }
        }*/
      );

      if (response.ok) {
        const data = await response.json();
        setBookings(data);
        setShowResults(true);
      } else {
        throw new Error('Failed to fetch bookings');
      }
    } catch (error) {
      console.error('Error fetching bookings:', error);
      alert('Error fetching bookings');
    } finally {
      setLoading(false);
    }
  };

  const getDaysInMonth = (year, month) => {
    return new Date(year, month, 0).getDate();
  };

  const getFirstDayOfMonth = (year, month) => {
    return new Date(year, month - 1, 1).getDay();
  };

  const getBookingStatus = (date, shift) => {
    const booking = bookings.find(b => 
      b.booking_date === `${selectedYear}-${selectedMonth}-${date.toString().padStart(2, '0')}` && 
      b.shift === shift
    );
    return booking;
  };

  // Helper: Is FD on this date blocking all shifts?
  const isFDBlockingDay = (date) => {
    const fdBooking = getBookingStatus(date, 'FD');
    return (
      fdBooking &&
      ['Confirmed', 'Pre-Booked', 'Unpaid'].includes(fdBooking.status)
    );
  };

  // Helper: Is FN or AN on this date blocking FD?
  const isFNorANBlockingFD = (date) => {
    const fnBooking = getBookingStatus(date, 'FN');
    const anBooking = getBookingStatus(date, 'AN');
    return (
      (fnBooking && ['Confirmed', 'Pre-Booked', 'Unpaid'].includes(fnBooking.status)) ||
      (anBooking && ['Confirmed', 'Pre-Booked', 'Unpaid'].includes(anBooking.status))
    );
  };

  // Updated isShiftAvailable
  const isShiftAvailable = (date, shift) => {
    // If FD is blocking, all shifts except FD itself are unavailable
    if (isFDBlockingDay(date) && shift !== 'FD') return false;
    // If FD is being checked and FN/AN are blocking, FD is unavailable
    if (shift === 'FD' && isFNorANBlockingFD(date)) return false;

    const booking = getBookingStatus(date, shift);
    if (!booking || booking.status === 'Cancelled') return true;
    if (
      booking.status === 'Unpaid' ||
      booking.status === 'Unavailable' ||
      booking.status === 'Pre-Booked' ||
      booking.status === 'Confirmed'
    ) return false;
    return true;
  };

  // Update getShiftStyle
  const getShiftStyle = (date, shift) => {
    // If FD is blocking, all shifts except FD itself look unavailable
    if (isFDBlockingDay(date) && shift !== 'FD') {
      return { backgroundColor: 'transparent', color: '#c5c1c1', borderColor: '#484545' };
    }
    // If FD is being checked and FN/AN are blocking, FD looks unavailable
    if (shift === 'FD' && isFNorANBlockingFD(date)) {
      return { backgroundColor: 'transparent', color: '#c5c1c1', borderColor: '#484545' };
    }

    const booking = getBookingStatus(date, shift);
    if (!booking || booking.status === 'Cancelled') {
      // Available or Cancelled
      return { backgroundColor: '#00B34C', color: 'white', borderColor: '#BFA46540' };
    }
    if (booking.status === 'Unpaid' || booking.status === 'Pre-Booked') {
      // Prebooked (Unpaid)
      return { backgroundColor: '#F4B083', color: '#232323', borderColor: '#BFA46540' };
    }
    if (booking.status === 'Unavailable') {
      // Unavailable
      return { backgroundColor: 'transparent', color: '#c5c1c1', borderColor: '#484545' };
    }
    if (booking.status === 'Confirmed') {
      // Booked or Confirmed
      return { backgroundColor: '#FE0000', color: 'white', borderColor: '#BFA46540' };
    }
    // Default
    return { backgroundColor: '#00B34C', color: 'white', borderColor: '#BFA46540' };
  };

  const isShiftDisabled = (shift) => {
    if (!selectedDate) return false;
    
    if (shift === 'FD') {
      // FD is disabled if FN or AN is selected
      return selectedShift === 'FN' || selectedShift === 'AN';
    } else {
      // FN and AN are disabled if FD is selected
      return selectedShift === 'FD';
    }
  };

  const handleDateShiftSelect = (date, shift) => {
    if (!isShiftAvailable(date, shift)) return;
    
    const dateStr = `${selectedYear}-${selectedMonth}-${date.toString().padStart(2, '0')}`;
    setSelectedDate(dateStr);
    setSelectedShift(shift);
  };

  const handleConfirmBooking = async () => {
    if (!selectedDate || !selectedShift) {
      alert('Please select a date and shift');
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/api/bookings`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authData.token}`
        },
        body: JSON.stringify({
          hall_id: parseInt(hallId),
          booking_date: selectedDate,
          shift: selectedShift
        })
      });

      if (response.ok) {
        alert('Booking confirmed successfully!');
        setShowConfirmModal(false);
        setSelectedDate('');
        setSelectedShift('');
        // Refresh the calendar data
        handleSearch();
      } else {
        throw new Error('Failed to create booking');
      }
    } catch (error) {
      console.error('Error creating booking:', error);
      alert('Error creating booking');
    }
  };

  const renderCalendar = () => {
    if (!showResults) return null;

    const year = parseInt(selectedYear);
    const month = parseInt(selectedMonth);
    const daysInMonth = getDaysInMonth(year, month);
    const firstDay = getFirstDayOfMonth(year, month);

    const days = [];
    const dayNames = [
      { full: 'Sunday', short: 'Sun' },
      { full: 'Monday', short: 'Mon' },
      { full: 'Tuesday', short: 'Tue' },
      { full: 'Wednesday', short: 'Wed' },
      { full: 'Thursday', short: 'Thu' },
      { full: 'Friday', short: 'Fri' },
      { full: 'Saturday', short: 'Sat' },
    ];

    // Add empty cells for days before the first day of the month
    for (let i = 0; i < firstDay; i++) {
      days.push(
        <div
          key={`empty-${i}`}
          className="p-2 border"
          style={{ borderColor: '#BFA46540' }}
        ></div>
      );
    }

    // Add days of the month
    for (let date = 1; date <= daysInMonth; date++) {
      days.push(
        <div
          key={date}
          className="p-1 border"
          style={{ borderColor: '#BFA46540' }}
        >
          <div className="text-center text-white mb-1">{date}</div>
          <div className="space-y-1">
            {shifts.map(shift => {
              const dateStr = `${selectedYear}-${selectedMonth}-${date.toString().padStart(2, '0')}`;
              const isSelected = selectedDate === dateStr && selectedShift === shift.value;
              const booking = getBookingStatus(date, shift.value);
              const isAvailable = isShiftAvailable(date, shift.value);

              return (
                <button
                  key={shift.value}
                  onClick={() => isAvailable && handleDateShiftSelect(date, shift.value)}
                  disabled={!isAvailable}
                  style={{
                    ...getShiftStyle(date, shift.value),
                    borderWidth: '1px',
                    borderStyle: 'solid',
                    borderRadius: '4px',
                    marginBottom: '2px',
                    width: '100%',
                    fontSize: '12px',
                    padding: '4px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    opacity: isAvailable ? 1 : 0.6,
                    cursor: isAvailable ? 'pointer' : 'not-allowed',
                    position: 'relative',
                  }}
                >
                  <span>{shift.value}</span>
                  {isSelected && (
                    <span
                      style={{
                        marginLeft: '4px',
                        color: 'white',
                        fontWeight: 'bold',
                        fontSize: '14px',
                        lineHeight: 1,
                        display: 'inline-block',
                      }}
                    >
                      ✓
                    </span>
                  )}
                </button>
              );
            })}
          </div>
        </div>
      );
    }

    return (
      <div className="mt-8">
        <div className="text-center bg-[#4D4D4D] grid-cols-2 py-4 mb-4">
          <h3 className="text-[#BFA465] bg-[#4D4D4D] font-semibold text-lg mb-4">
            Result of
          </h3>
          <h3 className="text-white text-md mb-4">
            Month: {months.find(m => m.value === selectedMonth)?.label}{' '}
            {selectedYear} Hall: {currentHall?.name}
          </h3>
        </div>
        <div className="bg-[#333333] py-4">
          <div
            className="py-2 text-[#c5c1c1]"
            style={{
              borderTop: '1px solid #4D4D4D',
              borderBottom: '1px solid #4D4D4D',
            }}
          >
            FN: Forenoon (11am to 3pm), An: Afternoon (3pm to 11pm), FD: Full Day
            (11am to 11pm)
          </div>
        </div>

        {/* Day names row with interconnected borders and no background */}
        <div className="grid grid-cols-7 gap-0 mb-0">
          {dayNames.map(day => (
            <div
              key={day.full}
              className="text-center text-white font-semibold border p-2"
              style={{ borderColor: '#BFA46540', background: 'transparent' }}
            >
              <span className="hidden md:inline">{day.full}</span>
              <span className="inline md:hidden">{day.short}</span>
            </div>
          ))}
        </div>

        {/* Calendar days with interconnected borders */}
        <div className="grid grid-cols-7 gap-0 mb-4">
          {days}
        </div>

        {selectedDate && selectedShift && (
          <div className="text-center">
            <button
              onClick={() => setShowConfirmModal(true)}
              className="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded-lg"
            >
              Confirm Booking
            </button>
          </div>
        )}
      </div>
    );
  };

  return (
    <div className="absolute top-116 left-0 right-0 min-h-10 overflow-y-scroll bg-[#232323] p-7">
      <div className="max-w-4xl mx-auto">
        <div className="bg-[#333333] rounded-lg p-6">
          <h2 className="text-white text-xl font-semibold mb-6 text-center">Booking Calendar</h2>
          <p className="text-white text-center mb-6">Check Hall Availability: {currentHall?.name}</p>

          <div className="space-y-4 mb-6">
            <div className="grid grid-cols-2 gap-4 px-2 md:px-30">
              <div>
                <label className="block text-white text-sm mb-2">Select Month</label>
                <select
                  value={selectedMonth}
                  onChange={(e) => setSelectedMonth(e.target.value)}
                  className="w-full p-1 md:p-2 bg-[#232323] text-white rounded border border-gray-600"
                >
                  <option value="">Select Month</option>
                  {months.map(month => (
                    <option key={month.value} value={month.value}>
                      {month.label}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-white text-sm mb-2">Select Year</label>
                <input
                  type="number"
                  value={selectedYear}
                  onChange={(e) => setSelectedYear(e.target.value)}
                  placeholder="Enter Year"
                  min="2024"
                  max="2030"
                  className="w-full p-1 md:p-2 bg-[#232323] text-white rounded border border-gray-600"
                />
              </div>
            </div>

            <div className="text-center">
              <button
                onClick={handleSearch}
                disabled={loading}
                style={{ backgroundColor: '#BFA465',  color: '#FFFFFF', padding: '8px 24px', borderRadius: '0.375rem', border:'#B18E4E', rounded: '4px', fontSize: '16px', fontWeight: 'semibold' }}
              >
                {loading ? 'Searching...' : 'Search'}
              </button>
            </div>
          </div>

          {/* Color Code Legend - Always Visible */}
          <div className="mb-6 py-4" style={{ borderTop: '1px solid #4D4D4D', borderBottom: '1px solid #4D4D4D' }}>
            <div className="flex justify-center space-x-6">
              <div className="flex items-center space-x-2">
                <div style={{ width: '16px', height: '16px', backgroundColor: '#00B34C', borderRadius: '0.2rem' }}></div>
                <span className="text-white text-sm">Available</span>
              </div>
              <div className="flex items-center space-x-2">
                <div style={{ width: '16px', height: '16px', backgroundColor: '#FE0000', borderRadius: '0.2rem' }}></div>
                <span className="text-white text-sm">Booked</span>
              </div>
              <div className="flex items-center space-x-2">
                <div style={{ width: '16px', height: '16px', backgroundColor: '#F4B083', borderRadius: '0.2rem' }}></div>
                <span className="text-white text-sm">Prebooked</span>
              </div>

            </div>
          </div >
          <div className="bg-[#4D4D4D] py-4">

          {renderCalendar()}</div>
        </div>
      </div>

      {/* Confirmation Modal */}
      {showConfirmModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-gray-800 p-6 rounded-lg max-w-md w-full mx-4">
            <h3 className="text-white text-lg mb-4">Confirm Booking</h3>
            <p className="text-gray-300 mb-2">Hall: {currentHall?.name}</p>
            <p className="text-gray-300 mb-2">Date: {selectedDate}</p>
            <p className="text-gray-300 mb-6">Shift: {selectedShift}</p>
            <div className="flex space-x-4">
              <button
                onClick={handleConfirmBooking}
                className="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded"
              >
                Confirm
              </button>
              <button
                onClick={() => setShowConfirmModal(false)}
                className="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 rounded"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}
      <div className='py-7'>
      <Footer />
      </div>
    </div>
  );
};

export default BookingCalendar;