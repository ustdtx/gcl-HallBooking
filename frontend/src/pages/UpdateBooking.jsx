import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { useHalls } from '../context/HallsContext';
import Footer from '../components/Footer';
const API_BASE = import.meta.env.VITE_API_URL;

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 11 }, (_, i) => currentYear + i);

const UpdateBooking = () => {
  const { authData } = useAuth();
  const navigate = useNavigate();
  const { halls } = useHalls();
  const { bookingId } = useParams();
  
  const [selectedMonth, setSelectedMonth] = useState('');
  const [selectedYear, setSelectedYear] = useState('');
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showResults, setShowResults] = useState(false);
  const [selectedDate, setSelectedDate] = useState('');
  const [selectedShift, setSelectedShift] = useState('');
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [chargeInfo, setChargeInfo] = useState(null);
  const [agreedTerms, setAgreedTerms] = useState(false);
  const [currentBooking, setCurrentBooking] = useState(null);
  const [initialLoading, setInitialLoading] = useState(true);

  const currentHall = currentBooking ? halls.find(hall => hall.id === currentBooking.hall_id) : null;

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

  // Fetch current booking details
  useEffect(() => {
    const fetchBookingDetails = async () => {
      if (!authData?.token) {
        navigate('/login');
        return;
      }

      try {
        const response = await fetch(`${API_BASE}/api/bookings/${bookingId}`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${authData.token}`
          }
        });

        if (response.ok) {
          const booking = await response.json();
          setCurrentBooking(booking);
          
          // Set initial values from the booking
          const bookingDate = new Date(booking.booking_date);
          const month = (bookingDate.getMonth() + 1).toString().padStart(2, '0');
          const year = bookingDate.getFullYear().toString();
          
          setSelectedMonth(month);
          setSelectedYear(year);
          setSelectedDate(booking.booking_date);
          setSelectedShift(booking.shift);
          
          // Automatically search for the month of the booking
          await handleSearch(year, month, booking.hall_id);
        } else {
          throw new Error('Failed to fetch booking details');
        }
      } catch (error) {
        console.error('Error fetching booking details:', error);
        alert('Error fetching booking details');
        navigate('/bookings'); // Redirect to bookings list or appropriate page
      } finally {
        setInitialLoading(false);
      }
    };

    fetchBookingDetails();
  }, [bookingId, authData, navigate]);

  const handleSearch = async (year = selectedYear, month = selectedMonth, hallId = currentBooking?.hall_id) => {
    if (!month || !year || !hallId) {
      if (!initialLoading) {
        alert('Please select both month and year');
      }
      return;
    }

    setLoading(true);
    try {
      const response = await fetch(
        `${API_BASE}/api/bookings/hall/${hallId}?month=${year}-${month}`,
        {
          method: 'GET',
        }
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
      if (!initialLoading) {
        alert('Error fetching bookings');
      }
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
      b.shift === shift &&
      b.id !== parseInt(bookingId) // Ignore current booking being updated
    );
    return booking;
  };

  // Helper: Is FD on this date blocking all shifts?
  const isFDBlockingDay = (date) => {
    const fdBooking = getBookingStatus(date, 'FD');
    return (
      fdBooking &&
      ['Confirmed', 'Pre-Booked', 'Unpaid', 'Review'].includes(fdBooking.status)
    );
  };

  // Helper: Is FN or AN on this date blocking FD?
  const isFNorANBlockingFD = (date) => {
    const fnBooking = getBookingStatus(date, 'FN');
    const anBooking = getBookingStatus(date, 'AN');
    return (
      (fnBooking && ['Confirmed', 'Pre-Booked', 'Unpaid', 'Review'].includes(fnBooking.status)) ||
      (anBooking && ['Confirmed', 'Pre-Booked', 'Unpaid', 'Review'].includes(anBooking.status))
    );
  };

  // Helper: Check if a date is before today
  const isPastDate = (date) => {
    const today = new Date();
    const checkDate = new Date(`${selectedYear}-${selectedMonth}-${date.toString().padStart(2, '0')}`);
    // Remove time part for accurate comparison
    today.setHours(0,0,0,0);
    checkDate.setHours(0,0,0,0);
    return checkDate < today;
  };

  // Updated isShiftAvailable
  const isShiftAvailable = (date, shift) => {
    if (isPastDate(date)) return false; // Disable past dates

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
      booking.status === 'Confirmed' ||
      booking.status === 'Review'
    ) return false;
    return true;
  };

  // Update getShiftStyle
  const getShiftStyle = (date, shift) => {
    if (isPastDate(date)) {
      // Style as unavailable for past dates
      return { backgroundColor: 'transparent', color: '#c5c1c1', borderColor: '#484545' };
    }
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
    if (
      booking.status === 'Unpaid' ||
      booking.status === 'Pre-Booked' ||
      booking.status === 'Review'
    ) {
      // Prebooked (Unpaid or Review)
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
    const dateStr = `${selectedYear}-${selectedMonth}-${date.toString().padStart(2, '0')}`;
    // If already selected, unselect
    if (selectedDate === dateStr && selectedShift === shift) {
      setSelectedDate('');
      setSelectedShift('');
      return;
    }
    if (!isShiftAvailable(date, shift)) return;
    setSelectedDate(dateStr);
    setSelectedShift(shift);
  };

  const handleShowConfirmModal = async () => {
    if (!selectedDate || !selectedShift) {
      alert('Please select a date and shift');
      return;
    }
    
    try {
      const response = await fetch(`${API_BASE}/api/calculate-charge`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authData.token}`
        },
        body: JSON.stringify({
          hall_id: currentBooking.hall_id,
          shift: selectedShift
        })
      });
      if (response.ok) {
        const data = await response.json();
        setChargeInfo(data);
        setShowConfirmModal(true);
      } else {
        throw new Error('Failed to calculate charge');
      }
    } catch (error) {
      console.error('Error calculating charge:', error);
      alert('Error calculating charge');
    }
  };

  const handleUpdateBooking = async () => {
    if (!selectedDate || !selectedShift) {
      alert('Please select a date and shift');
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/api/bookings/${bookingId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authData.token}`
        },
        body: JSON.stringify({
          id: parseInt(bookingId),
          booking_date: selectedDate,
          shift: selectedShift
        })
      });

      if (response.ok) {
        setShowConfirmModal(false);
        setSelectedDate('');
        setSelectedShift('');
        setChargeInfo(null);
        navigate(`/reservations/${bookingId}`); // Redirect to updated booking details
      } else {
        throw new Error('Failed to update booking');
      }
    } catch (error) {
      console.error('Error updating booking:', error);
      alert('Error updating booking');
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
              // New: Use isShiftDisabled for visual disabling
              const visuallyDisabled = selectedDate === dateStr && isShiftDisabled(shift.value);

              return (
                <button
                  key={shift.value}
                  onClick={() => isAvailable && !visuallyDisabled && handleDateShiftSelect(date, shift.value)}
                  disabled={!isAvailable || visuallyDisabled}
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
                    opacity: (!isAvailable || visuallyDisabled) ? 0.6 : 1,
                    cursor: (!isAvailable || visuallyDisabled) ? 'not-allowed' : 'pointer',
                    position: 'relative',
                    filter: visuallyDisabled ? 'grayscale(0.7)' : undefined,
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
            Update Booking for
          </h3>
          <h3 className="text-white text-md mb-4">
            <span>
              Month: {months.find(m => m.value === selectedMonth)?.label}, {selectedYear}
            </span>
            <span style={{ display: 'inline-block', marginLeft: 32 }}>
              Hall: {currentHall?.name}
            </span>
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
            <span style={{ display: 'inline-block', marginRight: 24 }}>
              <b>FN:</b> Forenoon (11am to 3pm)
            </span>
            <span style={{ display: 'inline-block', marginRight: 24 }}>
              <b>AN:</b> Afternoon (3pm to 11pm)
            </span>
            <span style={{ display: 'inline-block' }}>
              <b>FD:</b> Full Day (11am to 11pm)
            </span>
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
              onClick={handleShowConfirmModal}
              disabled={!agreedTerms}
              style={{
                backgroundColor: agreedTerms ? '#BFA465' : '#888888',
                color: '#FFFFFF',
                padding: '8px 24px',
                borderRadius: '0.375rem',
                border: '#B18E4E',
                fontSize: '16px',
                fontWeight: 'semibold',
                cursor: agreedTerms ? 'pointer' : 'not-allowed',
                opacity: agreedTerms ? 1 : 0.7,
              }}
            >
              Update Booking
            </button>
            {/* Terms & Conditions */}
            <div className="flex items-center justify-center mt-4">
              <span
                onClick={() => setAgreedTerms(!agreedTerms)}
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  width: '18px',
                  height: '18px',
                  border: '2px solid #B18E4E',
                  borderRadius: '50%',
                  marginRight: '10px',
                  cursor: 'pointer',
                  background: 'transparent',
                  position: 'relative',
                }}
                tabIndex={0}
                role="checkbox"
                aria-checked={agreedTerms}
              >
                {agreedTerms && (
                  <svg width="12" height="12" viewBox="0 0 16 16" style={{ position: 'absolute' }}>
                    <polyline
                      points="4,9 7,12 12,5"
                      style={{
                        fill: 'none',
                        stroke: '#BFA465',
                        strokeWidth: 2,
                        strokeLinecap: 'round',
                        strokeLinejoin: 'round',
                      }}
                    />
                  </svg>
                )}
              </span>
              <span style={{ color: '#999999', fontSize: '14px' }}>
                Upon updating reservation you are agreeing with our{' '}
                <Link
                  to={`/policy/${currentHall?.id}`}
                  className="underline"
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{ color: '#BFA465' }}
                >
                  Terms & Conditions
                </Link>.
              </span>
            </div>
          </div>
        )}
      </div>
    );
  };

  if (initialLoading) {
    return (
      <div className="absolute top-116 left-0 right-0 min-h-10 overflow-y-scroll bg-[#232323] p-7">
        <div className="max-w-4xl mx-auto">
          <div className="bg-[#333333] rounded-lg p-6">
            <div className="text-center text-white">Loading booking details...</div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="absolute top-116 left-0 right-0 min-h-10 overflow-y-scroll bg-[#232323] p-7">
      <div className="max-w-4xl mx-auto">
        <div className="bg-[#333333] rounded-lg p-6">
          <h2 className="text-white text-xl font-semibold mb-6 text-center">Update Booking</h2>
          <p className="text-white text-center mb-6">Update Hall Booking: {currentHall?.name}</p>
          
          {currentBooking && (
            <div className="bg-[#4D4D4D] p-4 rounded mb-6">
              <h3 className="text-[#BFA465] font-semibold mb-2">Current Booking Details:</h3>
              <p className="text-white text-sm">Date: {currentBooking.booking_date}</p>
              <p className="text-white text-sm">Shift: {currentBooking.shift}</p>
              <p className="text-white text-sm">Status: {currentBooking.status}</p>
            </div>
          )}

          <div className="space-y-4 mb-6">
            <div className="grid grid-cols-2 gap-4 px-2 md:px-30">
              <div>
                <label className="block text-white text-sm mb-2">Select Month</label>
                <select
                  value={selectedMonth}
                  onChange={(e) => setSelectedMonth(e.target.value)}
                  className="w-full p-1 md:p-2 bg-[#232323] text-white rounded border border-[#232323]"
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
                <select
                  value={selectedYear}
                  onChange={(e) => setSelectedYear(e.target.value)}
                  className="w-full p-1 md:p-2 bg-[#232323] text-white rounded border border-[#232323]"
                >
                  <option value="">Select Year</option>
                  {yearOptions.map(year => (
                    <option key={year} value={year}>
                      {year}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="text-center">
              <button
                onClick={() => handleSearch()}
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
          </div>
          <div className="bg-[#4D4D4D] py-4">
            {renderCalendar()}
          </div>
        </div>
      </div>

      {/* Confirmation Modal */}
      {showConfirmModal && (
        <div className="fixed inset-0 bg-[#00000073] flex items-center justify-center z-50">
          <div className="bg-[#444444] p-6 rounded-lg max-w-md w-full mx-4 text-left">
            <h3 className="text-white text-lg mb-4 font-bold">Confirm Booking Update</h3>
            <p className="text-gray-300 mb-2">Hall Name: {currentHall?.name}</p>
            <p className="text-gray-300 mb-2">Capacity: {currentHall?.capacity}</p>
            <p className="text-gray-300 mb-2">New Booking Date: {selectedDate}</p>
            <p className="text-gray-300 mb-2">New Shift: {selectedShift}</p>
            {chargeInfo && (
              <div className="mb-4">
                <p className="text-[#BFA465]">Pre-Booking Amount: <span className="font-semibold">{chargeInfo["Pre-book"]} BDT</span></p>
                <p className="text-[#BFA465]">Total Amount: <span className="font-semibold">{chargeInfo.total_charge} BDT</span></p>
              </div>
            )}
            <div className="grid grid-cols-2 gap-4">
              <button
                onClick={handleUpdateBooking}
                style={{ backgroundColor:"#444444", color: '#FFFFFF', padding: '8px 24px',  border:'#FFFFFF', borderWidth: '1px', borderStyle: 'solid', borderRadius: '4px', fontSize: '16px', fontWeight: 'semibold' }}
              >
                Update
              </button>
              <button
                onClick={() => { setShowConfirmModal(false); setChargeInfo(null); }}
                style={{ backgroundColor: '#BFA465',  color: '#FFFFFF', padding: '8px 24px',  border:'#B18E4E', borderWidth: '1px', borderStyle: 'solid', borderRadius: '4px', fontSize: '16px', fontWeight: 'semibold' }}
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

export default UpdateBooking;