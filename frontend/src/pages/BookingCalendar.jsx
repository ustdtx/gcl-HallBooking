import React, { useEffect, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate, useParams } from 'react-router-dom';
import { useHalls } from '../context/HallsContext';
import axios from 'axios';

const API_BASE = import.meta.env.VITE_API_URL;

const shifts = ['FN', 'AN', 'FD'];

const statusColor = {
  Unavailable: 'bg-red-600 text-white',
  'Pre-Booked': 'bg-yellow-400 text-black',
  Unpaid: 'bg-green-500 text-white',
  Cancelled: 'bg-green-500 text-white',
  Available: 'bg-green-500 text-white'
};

export default function BookingCalendar() {
  const { authData } = useAuth();
  const { halls } = useHalls();
  const navigate = useNavigate();
  const { hallId } = useParams();
  console.log("Hall ID:", hallId);

  const [selectedMonth, setSelectedMonth] = useState('06');
  const [selectedYear, setSelectedYear] = useState('2025');
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selected, setSelected] = useState({});
  const [showResults, setShowResults] = useState(false);

  const currentHall = halls.find(h => h.id === parseInt(hallId));

  useEffect(() => {
    if (!authData?.member) navigate('/login');
  }, [authData, navigate]);

  const fetchBookings = async () => {
    setLoading(true);
    setSelected({});
    try {
      const res = await axios.get(`${API_BASE}/bookings/hall/${hallId}?month=${selectedYear}-${selectedMonth}`);
      setBookings(res.data);
      setShowResults(true);
    } catch (err) {
      console.error(err);
    }
    setLoading(false);
  };

  const getStatus = (date, shift) => {
    const match = bookings.find(b => b.booking_date === date && b.shift === shift);
    if (!match) return 'Available';
    return match.status;
  };

  const handleSelect = (date, shift) => {
    const key = `${date}-${shift}`;
    if (selected[key]) {
      const updated = { ...selected };
      delete updated[key];
      setSelected(updated);
    } else {
      const newSelected = { ...selected };

      // enforce shift logic
      const prefix = `${date}-`;
      if (shift === 'FD') {
        shifts.forEach(s => delete newSelected[`${prefix}${s}`]);
        newSelected[`${date}-FD`] = true;
      } else {
        delete newSelected[`${date}-FD`];
        newSelected[key] = true;
      }

      setSelected(newSelected);
    }
  };

  const handleConfirm = async () => {
    for (let key in selected) {
      const [booking_date, shift] = key.split('-');
      await axios.post(`${API_BASE}/bookings`, {
        hall_id: parseInt(hallId),
        booking_date,
        shift
      }, {
        headers: {
          Authorization: `Bearer ${authData.token}`
        }
      });
    }
    fetchBookings();
  };

  const renderCalendar = () => {
    const year = parseInt(selectedYear);
    const month = parseInt(selectedMonth) - 1;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();

    const weeks = [];
    let day = 1;

    for (let w = 0; w < 6; w++) {
      const days = [];
      for (let d = 0; d < 7; d++) {
        if ((w === 0 && d < firstDay) || day > daysInMonth) {
          days.push(<td key={d}></td>);
        } else {
          const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
          const shiftBtns = shifts.map(shift => {
            const status = getStatus(dateStr, shift);
            const key = `${dateStr}-${shift}`;
            const isSelected = selected[key];

            const isDisabled = status === 'Unavailable' || status === 'Pre-Booked' || (shift === 'FD' && (selected[`${dateStr}-FN`] || selected[`${dateStr}-AN`])) || ((shift === 'FN' || shift === 'AN') && selected[`${dateStr}-FD`]);

            return (
              <button
                key={shift}
                disabled={isDisabled}
                className={`block w-full my-0.5 text-xs py-1 rounded ${statusColor[status] || 'bg-gray-300'} ${isSelected ? 'ring-2 ring-white' : ''} ${isDisabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                onClick={() => handleSelect(dateStr, shift)}
              >
                {shift}
              </button>
            );
          });

          days.push(
            <td key={d} className="p-1 text-center border border-gray-700">
              <div className="text-xs mb-1 text-white">{day}</div>
              {shiftBtns}
            </td>
          );
          day++;
        }
      }
      weeks.push(<tr key={w}>{days}</tr>);
    }

    return <tbody>{weeks}</tbody>;
  };

  return (
    <div className="bg-[#232323] text-white min-h-screen py-10 px-4">
      <div className="max-w-5xl mx-auto">
        <h2 className="text-xl font-semibold mb-4">
          Check Hall Availability: {currentHall?.name}
        </h2>

        <div className="flex gap-4 mb-4 items-end">
          <div>
            <label className="block mb-1 text-sm">Select Month</label>
            <select
              className="bg-gray-700 text-white px-2 py-1 rounded"
              value={selectedMonth}
              onChange={e => setSelectedMonth(e.target.value)}
            >
              {Array.from({ length: 12 }, (_, i) => (
                <option key={i} value={String(i + 1).padStart(2, '0')}>
                  {new Date(0, i).toLocaleString('default', { month: 'long' })}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block mb-1 text-sm">Select Year</label>
            <input
              type="number"
              className="bg-gray-700 text-white px-2 py-1 rounded"
              value={selectedYear}
              onChange={e => setSelectedYear(e.target.value)}
            />
          </div>
          <button
            onClick={fetchBookings}
            className="bg-yellow-500 text-black font-semibold px-4 py-2 rounded"
          >
            Search
          </button>
        </div>

        {showResults && (
          <>
            <div className="mb-2">
              <p className="text-sm text-gray-300">Result of: {new Date(selectedYear, selectedMonth - 1).toLocaleString('default', { month: 'long' })} – Hall: {currentHall?.name}</p>
              <p className="text-xs mt-1">
                <span className="text-green-400">● Available</span>{" "}
                <span className="text-yellow-400 ml-2">● Pre-Booked</span>{" "}
                <span className="text-red-500 ml-2">● Unavailable</span>
              </p>
            </div>
            <table className="w-full border border-gray-600 bg-gray-900 text-sm">
              <thead>
                <tr>
                  {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
                    <th key={day} className="p-2 border border-gray-700">{day}</th>
                  ))}
                </tr>
              </thead>
              {renderCalendar()}
            </table>

            <div className="mt-4 text-right">
              <button
                className="bg-green-500 text-white font-semibold px-4 py-2 rounded"
                onClick={handleConfirm}
              >
                Confirm Booking
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
