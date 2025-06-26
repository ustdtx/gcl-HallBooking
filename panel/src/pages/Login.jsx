import { useState, useContext } from "react";
import { useNavigate } from "react-router-dom";
import { AuthContext } from "../context/Authcontext"; // adjust path if needed

export default function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const navigate = useNavigate();
  const API_BASE = import.meta.env.VITE_API_URL || "";
  const { setAdmin } = useContext(AuthContext); // <-- use context

  const handleLogin = async (e) => {
    e.preventDefault();
    setError("");

    try {
      const res = await fetch(`${API_BASE}/api/admin/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });

      if (!res.ok) {
        const err = await res.json();
        throw new Error(err.message || "Login failed");
      }

      const data = await res.json();
      localStorage.setItem("AdminToken", data.token);
      setAdmin(data.admin); // <-- save admin object in context
      navigate("/dashboard");
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <div className="w-screen h-screen min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 via-white to-blue-200">
      <form
        onSubmit={handleLogin}
        className="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md flex flex-col items-center"
      >
        <div className="mb-6 flex flex-col items-center">
          <div className="bg-blue-600 rounded-full h-16 w-16 flex items-center justify-center mb-2 shadow-lg">
            <svg
              className="h-8 w-8 text-white"
              fill="none"
              stroke="currentColor"
              strokeWidth={2}
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
          </div>
          <h2 className="text-2xl font-extrabold text-gray-800 mb-1">
            Admin Login
          </h2>
          <p className="text-gray-500 text-sm">
            Sign in to your admin account
          </p>
        </div>
        {error && (
          <p className="text-red-500 mb-4 w-full text-center bg-red-50 border border-red-200 rounded py-2 px-3">
            {error}
          </p>
        )}
        <input
          type="text"
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="w-full p-3 mb-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
          required
        />
        <input
          type="password"
          placeholder="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="w-full p-3 mb-6 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
          required
        />
        <button
          type="submit"
          style={{
            width: "100%",
            background: "linear-gradient(90deg, #2563eb 0%, #1e40af 100%)",
            color: "#fff",
            padding: "12px 0",
            borderRadius: "0.5rem",
            fontWeight: "600",
            boxShadow: "0 4px 14px 0 rgba(37,99,235,0.15)",
            border: "none",
            cursor: "pointer",
            transition: "background 0.2s",
          }}
        >
          Login
        </button>
      </form>
    </div>
  );
}
