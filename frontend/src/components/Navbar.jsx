import React, { useState } from "react";
import { useAuth } from "../context/AuthContext"; // Import the context
import { useNavigate } from "react-router-dom"; // Import useNavigate

export default function Navbar() {
  const { authData } = useAuth();
  const navigate = useNavigate(); // Initialize navigate
  const isLoggedIn = !!authData.member;
  const firstName = authData?.member?.name ? authData.member.name.split(" ")[0] : "Member";
  const profilePic = authData?.member?.profilePic || "/assets/default-profile.png";

  return (
    <nav className="fixed top-0 left-0 right-0 w-full bg-[#232323] py-2 px-4 flex items-center shadow-md z-50">
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

        <a
          href="#"
          className="font-medium flex items-center"
          style={{
            color: "#fff",
            fontFamily: "'Playfair Display', serif",
          }}
        >
          Banquet Booking
          <svg
            className="ml-1 w-4 h-4"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            viewBox="0 0 24 24"
          >
            <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </a>

        <a
          href="#"
          className="font-medium flex items-center"
          style={{
            color: "#fff",
            fontFamily: "'Playfair Display', serif",
          }}
        >
          Policies
          <svg
            className="ml-1 w-4 h-4"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            viewBox="0 0 24 24"
          >
            <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </a>

        {isLoggedIn && (
          <a
            href="#"
            className="font-medium flex items-center"
            style={{
              color: "#fff",
              fontFamily: "'Playfair Display', serif",
            }}
          >
            Reservation History
          </a>
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
          <div className="flex items-center">
            <img
              src={profilePic}
              alt="Profile"
              className="h-8 w-8 rounded-full object-cover border border-[#222] bg-white"
            />
            <span
              className="text-white font-semibold ml-2"
              style={{ fontFamily: "'Playfair Display', serif" }}
            >
              {firstName}
            </span>
          </div>
        )}
      </div>
    </nav>
  );
}
