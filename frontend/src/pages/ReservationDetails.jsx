import React, { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import Footer from "../components/Footer";

const API_BASE = import.meta.env.VITE_API_URL || "";

export default function ReservationDetails() {
  const { bookingId } = useParams();
  const { authData } = useAuth();
  const navigate = useNavigate();
  const [booking, setBooking] = useState(null);
  const [charges, setCharges] = useState(null);

  useEffect(() => {
    const fetchBooking = async () => {
      try {
        const res = await fetch(`${API_BASE}/api/bookings/user`, {
          headers: { Authorization: `Bearer ${authData.token}` },
        });
        const data = await res.json();
        if (res.ok) {
          const found = data.find((b) => b.id === parseInt(bookingId));
          setBooking(found);
        } else {
          console.error("Error fetching booking:", data);
        }
      } catch (e) {
        console.error("Fetch error:", e);
      }
    };
    fetchBooking();
  }, [authData.token, bookingId]);

  // Fetch charges after booking is loaded
  useEffect(() => {
  const fetchCharges = async () => {
    if (!booking) return;
    try {
      const res = await fetch(`${API_BASE}/api/calculate-charge`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authData.token}`
        },
        body: JSON.stringify({
          hall_id: parseInt(booking.hall.id),
          shift: booking.shift
        })
      });
      const data = await res.json();
      if (res.ok) {
        console.log("Charges fetched:", data);
        setCharges({
          total_charges: data.total_charge,
          pre_book_amount: data["Pre-book"] || 0,
        });
      } else {
        setCharges(null);
      }
    } catch (e) {
      setCharges(null);
    }
  };
  fetchCharges();
}, [booking, authData.token]);

  const handleCancel = async () => {
    try {
      const res = await fetch(`${API_BASE}/api/bookings/${bookingId}/cancel`, {
        method: "PUT",
        headers: { Authorization: `Bearer ${authData.token}` },
      });
      if (res.ok) {
        alert("Reservation cancelled.");
        navigate("/reservations");
      } else {
        alert("Failed to cancel.");
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handlePayment = () => {
    navigate(`/payment/${bookingId}`);
  };

  if (!booking || !charges) return <div className="text-white p-6">Loading...</div>;

  const { hall, shift, status, booking_date } = booking;
  const displayStatus = status === "Unpaid" ? "On-Hold" : status;
  const prebookAmount = charges.pre_book_amount || 0;
  const fullAmount = charges.total_charges || 0;
  const remaining =
    status === "Pre-Booked"
      ? parseInt(fullAmount) - parseInt(prebookAmount)
      : parseInt(fullAmount);

  const shiftLabel =
    shift === "FN"
      ? "Morning (09:00 - 15:00)"
      : shift === "AN"
      ? "Dinner (17:00 - 23:00)"
      : "Full Day (09:00 - 23:00)";

  return (
    <>
      <div className="absolute top-12 left-0 right-0 px-120 bg-[#232323] text-white min-h-screen p-6">
        {/* Back Link */}
        <div className="mb-4 text-left">
          <a
            href="/reservations"
            className="text-[#BFA465] underline hover:text-[#d1b97c] transition"
          >
            ← Back
          </a>
        </div>

        <div
          style={{
            background: "#333333",
            borderRadius: "0.75rem",
            overflow: "hidden",
          }}
        >
          <h2 className="text-center text-xl text-[#BFA465] font-bold py-4">
            {hall.name}
          </h2>

          <img
            src={`${API_BASE}/${hall.images[0]}`}
            alt="Hall"
            className="w-full max-h-[400px] object-cover"
          />

          <div className="p-6">
            <h3 className="text-lg font-semibold text-[#BFA465] mb-1">
              {hall.name}
            </h3>
            <p className="text-gray-300 mb-4">
              Maximum capacity: {hall.capacity}
            </p>

            <div className="border border-gray-600 p-4 rounded">
              <div className="grid grid-cols-2 gap-y-2 gap-x-8 text-sm">
                <p><b>Name:</b> {authData.member.name}</p>
                <p><b>Club Account:</b> {authData.member.club_account}</p>
                <p><b>Status:</b> {displayStatus}</p>
                <p><b>Total Amount:</b> {fullAmount}tk</p>
                <p><b>Pre Booking Amount:</b> {prebookAmount}tk</p>
                <p><b>Amount to be Paid:</b> {remaining.toLocaleString()}tk</p>
                <p><b>Date:</b> {new Date(booking_date).toLocaleDateString()}</p>
                <p><b>Shift:</b> {shiftLabel}</p>
              </div>
            </div>

            {/* Centered 2x2 Button Grid */}
            <div className="flex flex-col items-center mt-6 gap-3">
              <div className="flex flex-row gap-3 w-full justify-center">
                {/* Payment Button */}
                <button
                  onClick={handlePayment}
                  disabled={status === "Confirmed"}
                  style={{
                    backgroundColor: status === "Confirmed" ? "#232323" : "#BFA465",
                    color: "#fff",
                    padding: "0.5rem 1rem",
                    borderRadius: "0.5rem",
                    border: "none",
                    minWidth: "160px",
                    opacity: status === "Confirmed" ? 0.6 : 1,
                    cursor: status === "Confirmed" ? "not-allowed" : "pointer",
                  }}
                >
                  {status === "Unpaid"
                    ? "Pre-Book Now"
                    : status === "Confirmed"
                    ? "Payment Complete"
                    : "Make Full Payment"}
                </button>

                {/* Edit Button */}
                <button
                  style={{
                    border: "1.5px solid #B18E4E",
                    color: "white",
                    backgroundColor: "transparent",
                    padding: "0.5rem 1rem",
                    borderRadius: "0.5rem",
                    minWidth: "160px",
                  }}
                >
                  Edit Booking
                </button>
              </div>
              <div className="flex flex-row gap-3 w-full justify-center">
                {/* Cancel Button */}
                <button
                  onClick={handleCancel}
                  style={{
                    border: "1.5px solid #F5534E",
                    color: "#F5534E",
                    backgroundColor: "transparent",
                    padding: "0.5rem 1rem",
                    borderRadius: "0.5rem",
                    minWidth: "160px",
                  }}
                >
                  Cancel Reservation
                </button>

                {/* Download PDF */}
                <button
                  onClick={() => window.open(`${API_BASE}/${hall.policy_pdf}`, "_blank")}
                  style={{
                    border: "1.5px solid #B18E4E",
                    color: "white",
                    backgroundColor: "transparent",
                    padding: "0.5rem 1rem",
                    borderRadius: "0.5rem",
                    minWidth: "160px",
                  }}
                >
                  Download PDF
                </button>
              </div>
            </div>
          </div>
        </div>
        <div className="absolute bg-[#232323] left-0">
          <Footer />
        </div>
      </div>
    </>
  );
}
