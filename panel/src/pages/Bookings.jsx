import React, { useEffect, useState } from "react";
import { useAuth } from "../context/Authcontext";
import { useHalls } from "../context/HallsContext";

const API_BASE = import.meta.env.VITE_API_URL || "";

const FILTERS = [
  { label: "Bookings", value: "bookings" },
  { label: "Admin Bookings", value: "admin" },
  { label: "Cancellation Requests", value: "cancellation" },
];

function PaymentModal({ booking, open, onClose, onPaymentSuccess }) {
  const { admin } = useAuth();
  const [charges, setCharges] = useState(null);
  const [loading, setLoading] = useState(true);
  const [purpose, setPurpose] = useState("");
  const [error, setError] = useState("");
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    if (!open || !booking) return;
    setLoading(true);
    setError("");
    const fetchCharges = async () => {
      try {
        const res = await fetch(`${API_BASE}/api/calculate-charge`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${localStorage.getItem("AdminToken")}`,
          },
          body: JSON.stringify({
            hall_id: parseInt(booking.hall_id),
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
          setError("Could not fetch charges.");
        }
      } catch (e) {
        setCharges(null);
        setError("Could not fetch charges.");
      }
      setLoading(false);
    };
    fetchCharges();
  }, [open, booking]);

  const getAmount = () => {
    if (!charges) return 0;
    if (booking.status === "Unpaid") return charges.pre_book_amount;
    if (booking.status === "Pre-Booked") return charges.total_charges - charges.pre_book_amount;
    return 0;
  };

  const handleConfirm = async () => {
    setProcessing(true);
    let paymentPurpose = "";
    if (booking.status === "Unpaid") paymentPurpose = "Pre-Book";
    else if (booking.status === "Pre-Booked") paymentPurpose = "Final";
    else {
      setProcessing(false);
      return;
    }
    try {
      const res = await fetch(`${API_BASE}/api/payments/manual-add`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("AdminToken")}`,
        },
        body: JSON.stringify({
          booking_id: booking.id,
          purpose: paymentPurpose,
        }),
      });
      if (res.ok) {
        onPaymentSuccess && onPaymentSuccess();
        onClose();
      } else {
        setError("Payment failed.");
      }
    } catch (e) {
      setError("Payment failed.");
    }
    setProcessing(false);
  };

  if (!open) return null;

  return (
    <div
      style={{
        position: "fixed",
        inset: 0,
        background: "rgba(0,0,0,0.5)",
        zIndex: 50,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
      }}
    >
      <div
        style={{
          background: "#232323",
          borderRadius: "0.5rem",
          padding: "2rem",
          minWidth: 320,
          color: "#fff",
          maxWidth: "90vw",
        }}
      >
        <h3 style={{ fontWeight: 700, fontSize: "1.25rem", marginBottom: "1rem" }}>
          Make Payment
        </h3>
        {loading ? (
          <div>Loading charges...</div>
        ) : error ? (
          <div style={{ color: "#f87171" }}>{error}</div>
        ) : (
          <>
            <div style={{ marginBottom: "1rem" }}>
              <div>
                <b>Booking ID:</b> {booking.id}
              </div>
              <div>
                <b>Status:</b> {booking.status}
              </div>
              <div>
                <b>
                  {booking.status === "Unpaid"
                    ? "Pre-book Amount"
                    : booking.status === "Pre-Booked"
                    ? "Final Amount"
                    : "Amount"}
                  :
                </b>{" "}
                ₹{getAmount()}
              </div>
            </div>
            <button
              style={{
                background: "#B18E4E",
                color: "#fff",
                padding: "0.5rem 1rem",
                borderRadius: "0.25rem",
                border: "none",
                cursor: "pointer",
                fontWeight: 600,
                marginRight: "0.5rem",
                minWidth: 100,
              }}
              disabled={processing}
              onClick={handleConfirm}
            >
              {processing ? "Processing..." : "Confirm Payment"}
            </button>
            <button
              style={{
                background: "#444",
                color: "#fff",
                padding: "0.5rem 1rem",
                borderRadius: "0.25rem",
                border: "none",
                cursor: "pointer",
                fontWeight: 600,
                minWidth: 100,
              }}
              onClick={onClose}
              disabled={processing}
            >
              Cancel
            </button>
          </>
        )}
      </div>
    </div>
  );
}

export default function AdminBookingTable() {
  const { admin } = useAuth();
  const { halls } = useHalls();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [filter, setFilter] = useState("bookings");
  const [statusEditId, setStatusEditId] = useState(null);
  const [statusValue, setStatusValue] = useState("");
  const [paymentModal, setPaymentModal] = useState({ open: false, booking: null });

  useEffect(() => {
    if (!admin) return;
    fetch(`${API_BASE}/api/bookings`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem("AdminToken")}`,
      },
    })
      .then((res) => res.json())
      .then((data) => {
        setBookings(data);
        setLoading(false);
      })
      .catch(() => {
        setError("Could not load bookings.");
        setLoading(false);
      });
  }, [admin]);

  if (!admin) return null;
  if (loading) return <div className="text-white p-6">Loading bookings...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  // Filtering logic
  const filteredBookings = bookings.filter((b) => {
    if (filter === "admin") return !b.member_id;
    if (filter === "cancellation") return b.status === "Review";
    // Default: Bookings
    return b.member_id && b.status !== "Review";
  });

  // Get hall name by id from halls context
  const getHallName = (id) => {
    const hall = halls.find((h) => h.id === id);
    return hall ? hall.name : "N/A";
  };

  // Handle status update
  const handleStatusUpdate = async (booking) => {
    if (!statusValue) return;
    const res = await fetch(
      `${API_BASE}/api/admin/bookings/${booking.id}/status`,
      {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("AdminToken")}`,
        },
        body: JSON.stringify({ status: statusValue }),
      }
    );
    if (res.ok) {
      setBookings((prev) =>
        prev.map((b) =>
          b.id === booking.id ? { ...b, status: statusValue } : b
        )
      );
      setStatusEditId(null);
      setStatusValue("");
    }
  };

  return (
    <div className="w-screen min-h-screen bg-[#181818] flex flex-col p-2 sm:p-6">
      <PaymentModal
        booking={paymentModal.booking}
        open={paymentModal.open}
        onClose={() => setPaymentModal({ open: false, booking: null })}
        onPaymentSuccess={() => {
          // Optionally refresh bookings after payment
          setLoading(true);
          fetch(`${API_BASE}/api/bookings`, {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("AdminToken")}`,
            },
          })
            .then((res) => res.json())
            .then((data) => {
              setBookings(data);
              setLoading(false);
            })
            .catch(() => {
              setError("Could not load bookings.");
              setLoading(false);
            });
        }}
      />
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
        <h2 className="text-2xl font-bold text-white">Booking Management</h2>
        <select
          className="bg-[#232323] border border-[#555] text-white p-2 rounded w-full sm:w-auto"
          value={filter}
          onChange={(e) => setFilter(e.target.value)}
        >
          {FILTERS.map((f) => (
            <option key={f.value} value={f.value}>
              {f.label}
            </option>
          ))}
        </select>
      </div>
      <div className="overflow-x-auto rounded border border-[#444] bg-[#2B2B2B]">
        <table className="min-w-[900px] w-full text-white text-sm">
          <thead>
            <tr className="bg-[#B18E4E] text-left">
              <th className="p-3">ID</th>
              <th className="p-3">Hall</th>
              <th className="p-3">Date</th>
              <th className="p-3">Shift</th>
              <th className="p-3">Status</th>
              <th className="p-3">Updated by</th>
              <th className="p-3">Email</th>
              <th className="p-3">Club Account</th>
              <th className="p-3">Created At</th>
              {admin.admin?.role === "admin" && <th className="p-3">Update Status</th>}
            </tr>
          </thead>
          <tbody>
            {filteredBookings.length === 0 ? (
              <tr>
                <td colSpan={admin.admin?.role === "admin" ? 10 : 9} className="p-4 text-center text-gray-400">
                  No bookings found.
                </td>
              </tr>
            ) : (
              filteredBookings.map((b) => (
                <tr key={b.id} className="border-t border-[#444]">
                  <td className="p-3">{b.id}</td>
                  <td className="p-3">{getHallName(b.hall_id)}</td>
                  <td className="p-3">{b.booking_date}</td>
                  <td className="p-3">{b.shift}</td>
                  <td className="p-3">{b.status}</td>
                  <td className="p-3">{b.statusUpdater || "N/A"}</td>
                  <td className="p-3">{b.member?.email || "N/A"}</td>
                  <td className="p-3">{b.member?.club_account || b.club_account || "N/A"}</td>
                  <td className="p-3">{b.created_at ? new Date(b.created_at).toLocaleString() : "N/A"}</td>
                  {admin.admin?.role === "admin" && (
                    <td className="p-3">
                      {statusEditId === b.id ? (
                        <div className="flex gap-2">
                          <select
                            className="bg-[#232323] border border-[#555] text-white p-1 rounded"
                            value={statusValue}
                            onChange={(e) => setStatusValue(e.target.value)}
                          >
                            <option value="">Select</option>
                            <option value="Pre-Booked">Pre-Booked</option>
                            <option value="Booked">Booked</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Unavailable">Unavailable</option>
                            {/* Admins can't set to Review */}
                          </select>
                          <button
                            style={{
                              background: "#B18E4E",
                              color: "#fff",
                              padding: "0.25rem 0.5rem",
                              borderRadius: "0.25rem",
                              border: "none",
                              cursor: "pointer",
                              fontWeight: 500,
                            }}
                            onClick={() => handleStatusUpdate(b)}
                          >
                            Save
                          </button>
                          <button
                            style={{
                              background: "#444",
                              color: "#fff",
                              padding: "0.25rem 0.5rem",
                              borderRadius: "0.25rem",
                              border: "none",
                              cursor: "pointer",
                              fontWeight: 500,
                            }}
                            onClick={() => {
                              setStatusEditId(null);
                              setStatusValue("");
                            }}
                          >
                            Cancel
                          </button>
                        </div>
                      ) : (
                        <button
                          style={{
                            background: "#B18E4E",
                            color: "#fff",
                            padding: "0.25rem 0.5rem",
                            borderRadius: "0.25rem",
                            border: "none",
                            cursor: "pointer",
                            fontWeight: 500,
                          }}
                          onClick={() => {
                            setStatusEditId(b.id);
                            setStatusValue(b.status);
                          }}
                        >
                          Update Status
                        </button>
                      )}
                    </td>
                  )}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
