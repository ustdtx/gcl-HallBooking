import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";

const API_BASE = import.meta.env.VITE_API_URL || "";

export default function Dashboard() {
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const token = localStorage.getItem("AdminToken");
  console.log('From Dashboard:', token);

  useEffect(() => {
    fetch(`${API_BASE}/api/admin/dashboard`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    })
      .then((res) => {
        if (!res.ok) throw new Error("Failed to fetch dashboard data");
        return res.json();
      })
      .then(setData)
      .catch((err) => {
        console.error(err);
        //navigate("/login");
      });
  }, []);

  const handleLogout = () => {
    localStorage.removeItem("AdminToken"); // <-- match the key used above
    navigate("/login");
  };

  return (
    <div className="flex w-screen min-h-screen">
      {/* Sidebar */}


      {/* Main Content */}
      <main className="flex-1 bg-gradient-to-br from-blue-100 via-white to-purple-100 p-4 sm:p-6 md:p-10">
        <div className="w-full max-w-7xl mx-auto">
          <h1 className="text-3xl font-extrabold text-gray-800 tracking-tight drop-shadow mb-10">
            Welcome, <span className="text-purple-600">Admin</span>
          </h1>

          {!data ? (
            <div className="flex justify-center items-center h-64">
              <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-purple-500"></div>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 w-full">
              <StatCard label="Total Users" value={data.total_users} icon={UserIcon} color="from-blue-100 to-blue-300" />
              <StatCard label="Total Bookings" value={data.total_bookings} icon={CalendarIcon} color="from-green-100 to-green-300" />
              <StatCard label="Total Halls" value={data.total_halls} icon={HallIcon} color="from-yellow-100 to-yellow-300" />
              <StatCard label="Total Revenue" value={`৳${data.total_revenue}`} icon={MoneyIcon} color="from-purple-100 to-purple-300" />
            </div>
          )}
        </div>
      </main>
    </div>
  );
}

function SidebarLink({ to, label }) {
  return (
    <Link
      to={to}
      className="text-gray-700 font-medium hover:text-purple-600 transition py-2"
    >
      {label}
    </Link>
  );
}

function StatCard({ label, value, icon: Icon, color }) {
  return (
    <div
      className={`bg-gradient-to-br ${color} shadow-lg rounded-xl p-6 flex flex-col items-center transition hover:scale-105`}
    >
      <div className="mb-3">
        <Icon />
      </div>
      <h2 className="text-lg font-semibold text-gray-700">{label}</h2>
      <p className="text-3xl font-extrabold text-gray-900 mt-2">{value}</p>
    </div>
  );
}

// SVG Icons
function UserIcon() {
  return (
    <svg className="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
      <path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
  );
}

function CalendarIcon() {
  return (
    <svg className="w-8 h-8 text-green-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
      <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
  );
}

function HallIcon() {
  return (
    <svg className="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
      <path d="M3 10l9-7 9 7v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8z" />
      <path d="M9 21V9h6v12" />
    </svg>
  );
}

function MoneyIcon() {
  return (
    <svg className="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
      <path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 0V4m0 16v-4" />
    </svg>
  );
}
