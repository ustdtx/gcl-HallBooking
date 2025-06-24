import React, { useState, useRef, useEffect } from "react";
import { useAuth } from "../context/AuthContext";
import { useNavigate } from "react-router-dom";
import { useHalls } from "../context/HallsContext"; // <-- Add this import

export default function Navbar() {
  const { authData, setAuthData } = useAuth();
  const navigate = useNavigate();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [bookingOpen, setBookingOpen] = useState(false); // <-- Add state
  const [policiesOpen, setPoliciesOpen] = useState(false); // <-- Add state
  const dropdownRef = useRef(null);
  const bookingRef = useRef(null); // <-- Add ref
  const policiesRef = useRef(null); // <-- Add ref

  const { halls = [] } = useHalls(); // <-- Get halls

  const isLoggedIn = !!authData?.member;
  const firstName = authData?.member?.name ? authData.member.name.split(" ")[0] : "Member";
  const profilePic = authData?.member?.profilePic || "/assets/default-profile.png";

  // Close dropdowns when clicking outside
  useEffect(() => {
    function handleClickOutside(event) {
      if (
        dropdownRef.current && !dropdownRef.current.contains(event.target) &&
        bookingRef.current && !bookingRef.current.contains(event.target) &&
        policiesRef.current && !policiesRef.current.contains(event.target)
      ) {
        setDropdownOpen(false);
        setBookingOpen(false);
        setPoliciesOpen(false);
      }
      // Close each individually if open and clicked outside
      if (bookingOpen && bookingRef.current && !bookingRef.current.contains(event.target)) setBookingOpen(false);
      if (policiesOpen && policiesRef.current && !policiesRef.current.contains(event.target)) setPoliciesOpen(false);
      if (dropdownOpen && dropdownRef.current && !dropdownRef.current.contains(event.target)) setDropdownOpen(false);
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [dropdownOpen, bookingOpen, policiesOpen]);

  const handleLogout = () => {
    setAuthData(null);
    setDropdownOpen(false);
    navigate("/login");
  };

  return (
    <nav className="fixed top-0 left-0 right-0 w-full bg-[#232323] border-[#444444] py-2 px-4 flex items-center shadow-md z-50">
      {/* Left: Logo */}
      <div className="flex-none">
        <img src="/assets/gclogo.png" alt="GC Logo" className="h-10 w-auto" />
      </div>

      {/* Right: Menu */}
      <div className="ml-auto flex items-center gap-6">
        <button
          onClick={() => navigate("/home")}
          className="font-medium"
          style={{
            color: "#BFA465",
            fontFamily: "'Playfair Display', serif",
            background: "none",
            border: "none",
            cursor: "pointer",
            padding: 0,
          }}
        >
          Home
        </button>

        {/* Banquet Booking Dropdown */}
        <div className="relative" ref={bookingRef}>
          <button
            className="font-medium flex items-center"
            style={{
              color: "#fff",
              fontFamily: "'Playfair Display', serif",
              background: "none",
              border: "none",
              cursor: "pointer",
              padding: 0,
            }}
            onClick={() => {
              setBookingOpen((open) => !open);
              setPoliciesOpen(false);
              setDropdownOpen(false);
            }}
          >
            Banquet Booking
            <svg
              className="ml-1 w-4 h-4"
              fill="none"
              stroke="currentColor"
              strokeWidth={2}
              viewBox="0 0 24 24"
              style={{ color: "#fff" }}
            >
              <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </button>
          {bookingOpen && (
            <div
              className="absolute right-0"
              style={{
                top: "100%",
                marginTop: "8px",
                width: "180px",
                background: "#252525",
                border: "1px solid #444444",
                borderRadius: "0px",
                boxShadow: "0 2px 8px rgba(0,0,0,0.15)",
                zIndex: 100,
              }}
            >
              {halls.length === 0 ? (
                <div className="px-4 py-2 text-white" style={{ fontFamily: "'Playfair Display', serif" }}>No halls</div>
              ) : (
                halls.map((hall) => (
                  <button
                    key={hall.id}
                    className="block w-full text-left px-4 py-2"
                    style={{
                      color: "#fff",
                      background: "none",
                      border: "none",
                      fontFamily: "'Playfair Display', serif",
                      cursor: "pointer",
                    }}
                    onClick={() => {
                      setBookingOpen(false);
                      navigate(`/charges/${hall.id}`);
                    }}
                  >
                    {hall.name}
                  </button>
                ))
              )}
            </div>
          )}
        </div>

        {/* Policies Dropdown */}
        <div className="relative" ref={policiesRef}>
          <button
            className="font-medium flex items-center"
            style={{
              color: "#fff",
              fontFamily: "'Playfair Display', serif",
              background: "none",
              border: "none",
              cursor: "pointer",
              padding: 0,
            }}
            onClick={() => {
              setPoliciesOpen((open) => !open);
              setBookingOpen(false);
              setDropdownOpen(false);
            }}
          >
            Policies
            <svg
              className="ml-1 w-4 h-4"
              fill="none"
              stroke="currentColor"
              strokeWidth={2}
              viewBox="0 0 24 24"
              style={{ color: "#fff" }}
            >
              <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </button>
          {policiesOpen && (
            <div
              className="absolute right-0"
              style={{
                top: "100%",
                marginTop: "8px",
                width: "180px",
                background: "#252525",
                border: "1px solid #444444",
                borderRadius: "0px",
                boxShadow: "0 2px 8px rgba(0,0,0,0.15)",
                zIndex: 100,
              }}
            >
              {halls.length === 0 ? (
                <div className="px-4 py-2 text-white" style={{ fontFamily: "'Playfair Display', serif" }}>No halls</div>
              ) : (
                halls.map((hall) => (
                  <button
                    key={hall.id}
                    className="block w-full text-left px-4 py-2"
                    style={{
                      color: "#fff",
                      background: "none",
                      border: "none",
                      fontFamily: "'Playfair Display', serif",
                      cursor: "pointer",
                    }}
                    onClick={() => {
                      setPoliciesOpen(false);
                      navigate(`/policy/${hall.id}`);
                    }}
                  >
                    {hall.name}
                  </button>
                ))
              )}
            </div>
          )}
        </div>

        {isLoggedIn && (
          <button
            className="font-medium flex items-center"
            style={{
              color: "#fff",
              fontFamily: "'Playfair Display', serif",
              background: "none",
              border: "none",
              cursor: "pointer",
              padding: 0,
            }}
            onClick={() => navigate("/reservations")}
          >
            Reservation History
          </button>
        )}

        {!isLoggedIn ? (
          <button
            className="px-4 py-1 rounded font-semibold text-white"
            style={{
              backgroundColor: "#BFA465",
              border: "#B18E4E",
              fontFamily: "'Playfair Display', serif",
            }}
            onClick={() => navigate("/login")}
          >
            Login
          </button>
        ) : (
          <div className="relative flex items-center" ref={dropdownRef}>
            <img
              src={profilePic}
              alt="Profile"
              className="h-8 w-8 rounded-full object-cover border border-[#222] bg-white cursor-pointer"
              onClick={() => {
                setDropdownOpen((open) => !open);
                setBookingOpen(false);
                setPoliciesOpen(false);
              }}
            />
            <span
              className="text-white font-semibold ml-2 flex items-center cursor-pointer"
              style={{ fontFamily: "'Playfair Display', serif" }}
              onClick={() => {
                setDropdownOpen((open) => !open);
                setBookingOpen(false);
                setPoliciesOpen(false);
              }}
            >
              {firstName}
              {/* Chevron icon */}
              <svg
                className="ml-1 w-4 h-4"
                fill="none"
                stroke="currentColor"
                strokeWidth={2}
                viewBox="0 0 24 24"
                style={{ color: "#fff" }}
              >
                <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </span>
            {dropdownOpen && (
              <div
                className="absolute right-0"
                style={{
                  top: "100%",
                  marginTop: "8px",
                  width: "160px",
                  background: "#252525",
                  border: "1px solid #444444",
                  borderRadius: "0px",
                  boxShadow: "0 2px 8px rgba(0,0,0,0.15)",
                  zIndex: 100,
                }}
              >
                <button
                  className="block w-full text-left px-4 py-2"
                  style={{
                    color: "#fff",
                    background: "none",
                    border: "none",
                    fontFamily: "'Playfair Display', serif",
                    cursor: "pointer",
                  }}
                  onClick={() => {
                    setDropdownOpen(false);
                    navigate("/profile");
                  }}
                >
                  Profile
                </button>
                <button
                  className="block w-full text-left px-4 py-2"
                  style={{
                    color: "#EB6147",
                    background: "none",
                    border: "none",
                    fontFamily: "'Playfair Display', serif",
                    cursor: "pointer",
                  }}
                  onClick={handleLogout}
                >
                  Logout
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </nav>
  );
}
