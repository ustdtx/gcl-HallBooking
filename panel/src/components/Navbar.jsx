import React from "react";
import { Link, useNavigate } from "react-router-dom";

function SidebarLink({ to, label }) {
  return (
    <Link
      to={to}
      style={{
        textDecoration: "none",
        color: "#333",
        fontWeight: "500",
        padding: "0.5rem 1rem",
        borderLeft: "1px solid #eee",
        borderRight: "1px solid #eee",
        marginLeft: "0.5rem"
      }}
      className="hover:text-purple-600 transition"
    >
      {label}
    </Link>
  );
}

const Navbar = () => {
  const navigate = useNavigate();
  const token = localStorage.getItem("AdminToken");

  if (!token) return null;

  const handleLogout = () => {
    localStorage.removeItem("AdminToken");
    navigate("/login");
  };

  return (
    <>
      <style>
        {`
          .navbar-links {
            display: flex;
            align-items: center;
          }
          @media (max-width: 800px) {
            .navbar-links {
              display: grid !important;
              grid-template-columns: repeat(3, 1fr);
              grid-template-rows: repeat(2, auto);
              gap: 0.5rem;
              width: 100%;
            }
            .navbar-links a {
              width: 100%;
              border-left: none !important;
              border-right: none !important;
              border-top: 1px solid #eee;
              border-bottom: 1px solid #eee;
              margin-left: 0 !important;
              text-align: center;
            }
          }
        `}
      </style>
      <nav style={{
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        padding: "1rem",
        background: "#f5f5f5",
        borderBottom: "1px solid #ddd"
      }}>
        <div className="navbar-links">
          <Link
            to="/dashboard"
            style={{
              textDecoration: "none",
              fontWeight: "bold",
              color: "#333",
              padding: "0.5rem 1.5rem",
              borderLeft: "1px solid #ccc",
              borderRight: "1px solid #ccc"
            }}
          >
            Dashboard
          </Link>
          <SidebarLink to="/users" label="Users" />
          <SidebarLink to="/BookEvents" label="Book Events" />
          <SidebarLink to="/halls" label="Halls" />
          <SidebarLink to="/bookings" label="Bookings" />
          <SidebarLink to="/payments" label="Payments" />
          
        </div>
        <button onClick={handleLogout} style={{
          background: "#e74c3c",
          color: "#fff",
          border: "none",
          padding: "0.5rem 1rem",
          borderRadius: "4px",
          cursor: "pointer"
        }}>
          Logout
        </button>
      </nav>
    </>
  );
};

export default Navbar;