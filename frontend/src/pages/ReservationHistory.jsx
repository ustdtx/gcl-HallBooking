import React, { useEffect, useState } from "react";
import { useAuth } from "../context/AuthContext";
import Footer from "../components/Footer";
import { useNavigate } from "react-router-dom"; // <-- Add this import

const API_BASE = import.meta.env.VITE_API_URL || "";

const statusColors = {
  Confirmed: "bg-[#87F54E]",
  "Pre-Booked": "bg-[#BFA465]",
  Cancelled: "bg-[#F5534E]",
  Unpaid: "bg-yellow-700", // For On-Hold
};

export default function ReservationHistory() {
  const { authData } = useAuth();
  const [bookings, setBookings] = useState([]);
  const [filter, setFilter] = useState("All");
  const navigate = useNavigate(); // <-- Add this line

  useEffect(() => {
    const fetchReservations = async () => {
      try {
        const response = await fetch(`${API_BASE}/api/bookings/user`, {
          headers: {
            Authorization: `Bearer ${authData.token}`,
          },
        });
        const data = await response.json();
        if (response.ok) setBookings(data);
        else console.error("Fetch error:", data);
      } catch (error) {
        console.error("Error:", error);
      }
    };
    fetchReservations();
  }, [authData.token]);

  const FILTERS = [
    { key: "All", label: "All" },
    { key: "Confirmed", label: "Confirmed" },
    { key: "Pre-Booked", label: "Pre-Booked" },
    { key: "Unpaid", label: "On-Hold" },
    { key: "Cancelled", label: "Cancelled" },
  ];

  const filtered =
    filter === "All"
      ? bookings.filter((b) => b.status !== "Unavailable")
      : bookings.filter(
          (b) =>
            (filter === "Unpaid"
              ? b.status === "Unpaid"
              : b.status === filter) && b.status !== "Unavailable"
        );

  return (
    <div className="absolute top-12 left-0 right-0 bg-[#232323] text-white p-8">
      <div className="max-w-7xl mx-auto">
        <h2 className="text-left text-xl font-semibold mb-4 py-4">
          Reservation History
        </h2>

        {/* Filter Buttons */}
        <div className="flex gap-2 mb-6">
          {FILTERS.map(({ key, label }) => (
            <button
              key={key}
              onClick={() => setFilter(key)}
              style={{
                padding: "0.25rem 1rem",
                borderRadius: "0.375rem",
                border: `1.5px solid ${
                  filter === key ? "#B18E4E" : "#232323"
                }`,
                background: filter === key ? "#BFA465" : "#232323",
                color: filter === key ? "#fff" : "#BFA465",
                fontWeight: filter === key ? 600 : 400,
                transition: "all 0.15s",
              }}
            >
              {label}
            </button>
          ))}
        </div>

        {/* Booking Cards */}
        <div className="flex flex-col gap-4">
          {filtered.length === 0 ? (
            <p>No reservations found.</p>
          ) : (
            filtered.map((b) => (
              <button
                key={b.id}
                onClick={() => navigate(`/reservations/${b.id}`)} // <-- Add this line
                style={{
                  background: "#333333",
                  borderRadius: "0.75rem",
                  overflow: "hidden",
                  boxShadow: "0 2px 8px rgba(0,0,0,0.15)",
                  display: "flex",
                  gap: "1rem",
                  padding: "1rem",
                  border: "none",
                  cursor: "pointer",
                  alignItems: "center",
                  transition: "box-shadow 0.15s",
                }}
              >
                <img
                  src={`${API_BASE}/${b.hall.images[0]}`}
                  alt="Hall"
                  style={{
                    width: "6rem",
                    height: "6rem",
                    objectFit: "cover",
                    borderRadius: "0.5rem",
                  }}
                />
                <div
                  style={{
                    display: "flex",
                    flexDirection: "column",
                    justifyContent: "center",
                  }}
                >
                  <span
                    style={{
                      fontSize: "0.75rem",
                      fontWeight: 600,
                      color: "#fff",
                      padding: "0.25rem 0.5rem",
                      borderRadius: "0.375rem",
                      width: "fit-content",
                      marginBottom: "0.5rem",
                      background:
                        b.status === "Confirmed"
                          ? "#87F54E"
                          : b.status === "Pre-Booked"
                          ? "#BFA465"
                          : b.status === "Cancelled"
                          ? "#F5534E"
                          : b.status === "Unpaid"
                          ? "#b45309"
                          : "#6b7280",
                    }}
                  >
                    {b.status === "Unpaid" ? "On-Hold" : b.status}
                  </span>
                  <h3
                    style={{
                      fontSize: "1.125rem",
                      fontWeight: "bold",
                      color: "#B18E4E",
                    }}
                  >
                    {b.hall.name}
                  </h3>
                  <p
                    style={{
                      fontSize: "0.875rem",
                      color: "#d1d5db",
                    }}
                  >
                    Capacity: {b.hall.capacity} Person
                  </p>
                </div>
              </button>
            ))
          )}
        </div>
      </div>

      <Footer />
    </div>
  );
}
