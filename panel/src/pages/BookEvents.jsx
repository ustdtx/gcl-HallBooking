import React, { useEffect, useState } from "react";
import { useAuth } from "../context/Authcontext";
import { useHalls } from "../context/HallsContext";

const API_BASE = import.meta.env.VITE_API_URL || "";

function getDaysInMonth(month, year) {
  return new Date(year, month, 0).getDate();
}

export default function AdminEventBookingForm() {
  const { admin } = useAuth();
  const { halls, loading: hallsLoading } = useHalls();

  const today = new Date();
  const currentYear = today.getFullYear();
  const [hallId, setHallId] = useState("");
  const [shift, setShift] = useState("FN");
  const [year, setYear] = useState(currentYear);
  const [month, setMonth] = useState(today.getMonth() + 1); // 1-indexed
  const [day, setDay] = useState(today.getDate());
  const [message, setMessage] = useState(null);
  const [error, setError] = useState(null);

  // Only show this form for admin role
  if (!admin || admin.admin.role !== "admin") return null;

  // Update day if month/year changes and current day is out of range
  useEffect(() => {
    const daysInMonth = getDaysInMonth(month, year);
    if (day > daysInMonth) setDay(daysInMonth);
  }, [month, year]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage(null);
    setError(null);

    // Compose date string
    const bookingDate = `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

    try {
      const res = await fetch(`${API_BASE}/api/bookings/block`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("AdminToken")}`,
        },
        body: JSON.stringify({
          hall_id: parseInt(hallId),
          booking_date: bookingDate,
          shift,
        }),
      });

      const data = await res.json();
      if (res.status === 201) {
        setMessage("Booking created successfully.");
        setHallId("");
        setShift("FN");
        setYear(currentYear);
        setMonth(today.getMonth() + 1);
        setDay(today.getDate());
      } else if (res.status === 409) {
        setError(data.error || "Slot already booked.");
      } else {
        setError("Something went wrong.");
      }
    } catch (err) {
      setError("Network error.");
    }
  };

  if (hallsLoading) return <div className="text-white p-6">Loading halls...</div>;

  // Generate options
  const years = Array.from({ length: 11 }, (_, i) => currentYear + i);
  const months = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
  ];
  const daysInMonth = getDaysInMonth(month, year);

  return (
    <div className="min-h-screen min-w-screen flex items-center justify-center bg-[#232323]">
      <div className="bg-[#2B2B2B] text-white w-full max-w-2xl mx-auto p-8 rounded-md border border-[#B18E4E66] shadow-md">
        <h2 className="text-2xl font-bold mb-6 text-[#B18E4E] text-center">Create Club Event Booking</h2>
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Hall Selector */}
          <div>
            <label className="block mb-1 font-semibold">Select Hall</label>
            <select
              className="w-full p-2 rounded bg-[#232323] text-white border border-[#444]"
              value={hallId}
              onChange={(e) => setHallId(e.target.value)}
              required
            >
              <option value="">-- Select Hall --</option>
              {halls.map((hall) => (
                <option key={hall.id} value={hall.id}>
                  {hall.name}
                </option>
              ))}
            </select>
          </div>

          {/* Shift Selector */}
          <div>
            <label className="block mb-1 font-semibold">Select Shift</label>
            <select
              className="w-full p-2 rounded bg-[#232323] text-white border border-[#444]"
              value={shift}
              onChange={(e) => setShift(e.target.value)}
              required
            >
              <option value="FN">Forenoon (FN)</option>
              <option value="AN">Afternoon (AN)</option>
              <option value="FD">Full Day (FD)</option>
            </select>
          </div>

          {/* Custom Date Picker */}
          <div>
            <label className="block mb-1 font-semibold">Select Date</label>
            <div className="flex gap-2 flex-wrap">
              {/* Day */}
              <select
                className="p-2 rounded bg-[#232323] text-white border border-[#444] flex-1 min-w-[80px]"
                value={day}
                onChange={e => setDay(Number(e.target.value))}
                required
              >
                {Array.from({ length: daysInMonth }, (_, i) => i + 1).map(d => (
                  <option key={d} value={d}>{d}</option>
                ))}
              </select>
              {/* Month */}
              <select
                className="p-2 rounded bg-[#232323] text-white border border-[#444] flex-1 min-w-[120px]"
                value={month}
                onChange={e => setMonth(Number(e.target.value))}
                required
              >
                {months.map((m, idx) => (
                  <option key={m} value={idx + 1}>{m}</option>
                ))}
              </select>
              {/* Year */}
              <select
                className="p-2 rounded bg-[#232323] text-white border border-[#444] flex-1 min-w-[100px]"
                value={year}
                onChange={e => setYear(Number(e.target.value))}
                required
              >
                {years.map(y => (
                  <option key={y} value={y}>{y}</option>
                ))}
              </select>
            </div>
          </div>

          {/* Submit Button */}
          <div className="flex justify-end">
            <button
              type="submit"
              style={{
                backgroundColor: "#B18E4E",
                color: "white",
                padding: "0.5rem 2rem",
                borderRadius: "0.375rem",
                width: "100%",
                maxWidth: "200px",
                fontWeight: "bold",
                border: "none",
                cursor: "pointer",
                transition: "background 0.2s",
              }}
              onMouseOver={e => (e.currentTarget.style.backgroundColor = "#9e7b3f")}
              onMouseOut={e => (e.currentTarget.style.backgroundColor = "#B18E4E")}
            >
              Book Date
            </button>
          </div>

          {/* Success & Error Messages */}
          {message && <div className="text-green-400 font-medium">{message}</div>}
          {error && <div className="text-red-400 font-medium">{error}</div>}
        </form>
      </div>
    </div>
  );
}
