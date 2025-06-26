import React, { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import Footer from "../components/Footer";
import { PDFDownloadLink } from "@react-pdf/renderer";
import BookingDetailsPDF from "./BookingDetailsPDF";

const API_BASE = import.meta.env.VITE_API_URL || "";

export default function ReservationDetails() {
  const { bookingId } = useParams();
  const { authData } = useAuth();
  const navigate = useNavigate();
  const [booking, setBooking] = useState(null);
  const [charges, setCharges] = useState(null);

  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [showRequestedModal, setShowRequestedModal] = useState(false);

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

  useEffect(() => {
    const fetchCharges = async () => {
      if (!booking) return;
      try {
        const res = await fetch(`${API_BASE}/api/calculate-charge`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${authData.token}`,
          },
          body: JSON.stringify({
            hall_id: parseInt(booking.hall.id),
            shift: booking.shift,
          }),
        });
        const data = await res.json();
        if (res.ok) {
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

  const handlePayment = () => {
    navigate(`/payment/${bookingId}`);
  };

  if (!booking || !charges)
    return <div className="text-white p-6">Loading...</div>;

  const { hall, shift, status, booking_date } = booking;
  const displayStatus = status === "Unpaid" ? "On-Hold" : status;
  const prebookAmount = charges.pre_book_amount || 0;
  const fullAmount = charges.total_charges || 0;

  let remaining;
  if (status === "Confirmed") {
    remaining = 0;
  } else if (status === "Cancelled") {
    remaining = null;
  } else if (status === "Pre-Booked") {
    remaining = parseInt(fullAmount) - parseInt(prebookAmount);
  } else {
    remaining = parseInt(fullAmount);
  }

  const shiftLabel =
    shift === "FN"
      ? "Morning (11:00 - 15:00)"
      : shift === "AN"
      ? "Dinner (15:00 - 23:00)"
      : "Full Day (11:00 - 23:00)";

  return (
    <>
      {/* Cancel Confirmation Modal */}
      {showConfirmModal && (
        <div
          style={{
            position: "fixed",
            inset: 0,
            zIndex: 50,
            background: "rgba(0,0,0,0.6)",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
          }}
        >
          <div
            style={{
              background: "#2c2c2c",
              color: "white",
              borderRadius: "16px",
              padding: "24px",
              width: "90%",
              maxWidth: "400px",
              textAlign: "center",
            }}
          >
            <h3
              style={{
                fontSize: "1.125rem",
                fontWeight: "bold",
                marginBottom: "1rem",
              }}
            >
              Do you want to cancel?
            </h3>
            <ul
              style={{
                fontSize: "0.875rem",
                textAlign: "left",
                marginBottom: "1.5rem",
                color: "#d1d5db",
                listStyle: "disc inside",
              }}
            >
              <li>Once the pre-booking payment is made, it is non-refundable.</li>
              <li>
                If you wish to change the booking date using the pre-booking amount,
                update it within 48 hours. Otherwise, a new booking will be required.
              </li>
            </ul>
            <div
              style={{
                display: "flex",
                justifyContent: "space-between",
                gap: "1rem",
              }}
            >
              <button
                onClick={() => setShowConfirmModal(false)}
                style={{
                  flex: 1,
                  border: "1px solid #d1d5db",
                  padding: "0.5rem 0",
                  borderRadius: "8px",
                  background: "transparent",
                  color: "white",
                  transition: "background 0.2s",
                  cursor: "pointer",
                }}
              >
                No
              </button>
              <button
                onClick={async () => {
                  setShowConfirmModal(false);
                  try {
                    const res = await fetch(
                      `${API_BASE}/api/bookings/request-cancel`,
                      {
                        method: "POST",
                        headers: { 
                          "Content-Type": "application/json",
                          Authorization: `Bearer ${authData.token}` 
                        },
                        body: JSON.stringify({ id: parseInt(bookingId) }),
                      }
                    );
                    if (res.ok) {
                      setShowRequestedModal(true);
                    } else {
                      alert("Failed to cancel.");
                    }
                  } catch (err) {
                    console.error(err);
                    alert("An error occurred.");
                  }
                }}
                style={{
                  flex: 1,
                  background: "#F5534E",
                  color: "white",
                  padding: "0.5rem 0",
                  borderRadius: "8px",
                  border: "none",
                  transition: "background 0.2s",
                  cursor: "pointer",
                }}
              >
                Yes
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Cancel Requested Modal */}
      {showRequestedModal && (
        <div
          style={{
            position: "fixed",
            inset: 0,
            zIndex: 50,
            background: "rgba(0,0,0,0.6)",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
          }}
        >
          <div
            style={{
              background: "#2c2c2c",
              color: "white",
              borderRadius: "16px",
              padding: "24px",
              width: "90%",
              maxWidth: "400px",
              textAlign: "center",
            }}
          >
            <h3
              style={{
                fontSize: "1.125rem",
                fontWeight: "bold",
                marginBottom: "0.5rem",
              }}
            >
              Requested
            </h3>
            <p
              style={{
                color: "#d1d5db",
                marginBottom: "1.5rem",
              }}
            >
              Cancellation request has been sent. Will notify you soon.
            </p>
            <button
              onClick={() => {
                setShowRequestedModal(false);
                navigate("/reservations");
              }}
              style={{
                background: "#BFA465",
                color: "white",
                padding: "0.5rem 1.5rem",
                borderRadius: "8px",
                border: "none",
                transition: "background 0.2s",
                cursor: "pointer",
              }}
            >
              Ok
            </button>
          </div>
        </div>
      )}

      <div className="absolute top-12 left-0 right-0 bg-[#232323] text-white min-h-screen py-6">
        <div className="max-w-3xl mx-auto px-4 sm:px-8">
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

              <div className="border border-[#B18E4E] p-4 rounded">
                <div className="grid grid-cols-2 gap-y-2 gap-x-8 text-sm">
                  <p><b>Name:</b> {authData.member.name}</p>
                  <p><b>Club Account:</b> {authData.member.club_account}</p>
                  <p><b>Status:</b> {displayStatus}</p>
                  <p><b>Total Amount:</b> {fullAmount}tk</p>
                  <p><b>Pre Booking Amount:</b> {prebookAmount}tk</p>
                  <p><b>Amount to be Paid:</b> {remaining === null ? "NULL" : `${remaining.toLocaleString()}tk`}</p>
                  <p><b>Date:</b> {new Date(booking_date).toLocaleDateString()}</p>
                  <p><b>Shift:</b> {shiftLabel}</p>
                </div>
              </div>

              <div className="flex flex-col items-center mt-6 gap-3">
                <div className="flex flex-row gap-3 w-full justify-center">
                  <button
                    onClick={handlePayment}
                    disabled={
                      status === "Confirmed" ||
                      status === "Cancelled" ||
                      status === "Review"
                    }
                    style={{
                      backgroundColor:
                        status === "Confirmed" ||
                        status === "Cancelled" ||
                        status === "Review"
                          ? "#232323"
                          : "#BFA465",
                      color: "#fff",
                      padding: "0.5rem 1rem",
                      borderRadius: "0.5rem",
                      border: "none",
                      minWidth: "160px",
                      opacity:
                        status === "Confirmed" ||
                        status === "Cancelled" ||
                        status === "Review"
                          ? 0.6
                          : 1,
                      cursor:
                        status === "Confirmed" ||
                        status === "Cancelled" ||
                        status === "Review"
                          ? "not-allowed"
                          : "pointer",
                    }}
                  >
                    {status === "Unpaid"
                      ? "Pre-Book Now"
                      : status === "Confirmed"
                      ? "Payment Complete"
                      : "Make Full Payment"}
                  </button>

                  <button
                    onClick={() => navigate(`/update-booking/${bookingId}`)}
                    disabled={
                      !(status === "Pre-Booked" || status === "Unpaid") ||
                      status === "Review"
                    }
                    style={{
                      border: "1.5px solid #B18E4E",
                      color: "white",
                      backgroundColor: "transparent",
                      padding: "0.5rem 1rem",
                      borderRadius: "0.5rem",
                      minWidth: "160px",
                      opacity:
                        !(status === "Pre-Booked" || status === "Unpaid") ||
                        status === "Review"
                          ? 0.6
                          : 1,
                      cursor:
                        !(status === "Pre-Booked" || status === "Unpaid") ||
                        status === "Review"
                          ? "not-allowed"
                          : "pointer",
                    }}
                  >
                    Edit Booking
                  </button>
                </div>

                <div className="flex flex-row gap-3 w-full justify-center">
                  <button
                    onClick={() => setShowConfirmModal(true)}
                    disabled={
                      !(status === "Pre-Booked" || status === "Confirmed") ||
                      status === "Review"
                    }
                    style={{
                      border: "1.5px solid #F5534E",
                      color:
                        !(status === "Pre-Booked" || status === "Confirmed") ||
                        status === "Review"
                          ? "#888"
                          : "#F5534E",
                      backgroundColor: "transparent",
                      padding: "0.5rem 1rem",
                      borderRadius: "0.5rem",
                      minWidth: "160px",
                      opacity:
                        !(status === "Pre-Booked" || status === "Confirmed") ||
                        status === "Review"
                          ? 0.6
                          : 1,
                      cursor:
                        !(status === "Pre-Booked" || status === "Confirmed") ||
                        status === "Review"
                          ? "not-allowed"
                          : "pointer",
                    }}
                  >
                    Cancel Reservation
                  </button>

                  <PDFDownloadLink
                    document={
                      <BookingDetailsPDF
                        hall={hall}
                        member={authData.member}
                        displayStatus={displayStatus}
                        fullAmount={fullAmount}
                        prebookAmount={prebookAmount}
                        remaining={remaining}
                        booking_date={new Date(booking_date).toLocaleDateString()}
                        shiftLabel={shiftLabel}
                      />
                    }
                    fileName={`booking-details-${bookingId}.pdf`}
                  >
                    {({ loading }) => (
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
                        {loading ? "Preparing PDF..." : "Download PDF"}
                      </button>
                    )}
                  </PDFDownloadLink>
                </div>
              </div>
            </div>
          </div>

          <div className="absolute bg-[#232323] left-0">
            <Footer />
          </div>
        </div>
      </div>
    </>
  );
}
