import React, { useState } from "react";
import Footer from "../components/Footer";
import { useAuth } from "../context/AuthContext";

export default function Login() {
  const [step, setStep] = useState(1);
  const [clubAccount, setClubAccount] = useState("");
  const [emailOrPhone, setEmailOrPhone] = useState("");
  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState("");
  const { setAuthData } = useAuth();

  // Replace with your actual API base URL
  const API_BASE = import.meta.env.VITE_API_URL || "";

  const handleNext = async () => {
    if (step === 1 && clubAccount.trim()) {
      setStep(2);
    }
  };

  const handleRequestOtp = async () => {
    if (!emailOrPhone.trim()) return;
    setLoading(true);
    setMessage("");
    try {
      const res = await fetch(`${API_BASE}/auth/request-otp`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          club_account: clubAccount,
          email_or_phone: emailOrPhone,
        }),
      });
      if (res.ok) {
        setStep(3);
      } else {
        const data = await res.json();
        setMessage(data.message || "Failed to request OTP");
      }
    } catch (e) {
      setMessage("Network error");
    }
    setLoading(false);
  };

  const handleVerifyOtp = async () => {
    if (!otp.trim()) return;
    setLoading(true);
    setMessage("");
    try {
      const res = await fetch(`${API_BASE}/auth/verify-otp`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          club_account: clubAccount,
          otp: otp,
        }),
      });
      if (res.ok) {
        const data = await res.json();
        setAuthData(data); // Store the object globally
        setStep(4);
      } else {
        const data = await res.json();
        setMessage(data.message || "OTP verification failed");
      }
    } catch (e) {
      setMessage("Network error");
    }
    setLoading(false);
  };

  return (
    <>
      <div className="backdrop-blur-lg bg-[#FFFFFF03]/10 p-8 rounded-xl w-[360px] text-white text-center shadow-lg border border-[#E0E0E0]">
        <div className="mb-6">
          <img
            src="/assets/gclogo.png"
            alt="Logo"
            className="mx-auto mb-2 w-28"
          />
        </div>
        {step === 1 && (
          <>
            <div className="text-left mb-2 text-sm font-medium text-gray-300">
              Club Account
            </div>
            <input
              type="text"
              placeholder="Enter your club account"
              value={clubAccount}
              onChange={(e) => setClubAccount(e.target.value)}
              className="w-full p-3 rounded-md bg-white/90 text-black outline-none mb-4"
            />
            <button
              className="w-full bg-gradient-to-r from-[#C2A059] to-[#A17C3F] text-white py-3 rounded-md text-sm font-medium hover:opacity-90 transition-all"
              onClick={handleNext}
              disabled={!clubAccount.trim()}
            >
              Next
            </button>
          </>
        )}

        {step === 2 && (
          <>
            <div className="text-left mb-2 text-sm font-medium text-gray-300">
              Registered Phone or Email
            </div>
            <input
              type="text"
              placeholder="Phone or Email"
              value={emailOrPhone}
              onChange={(e) => setEmailOrPhone(e.target.value)}
              className="w-full p-3 rounded-md bg-white/90 text-black outline-none mb-4"
            />
            <button
              className="w-full bg-gradient-to-r from-[#C2A059] to-[#A17C3F] text-white py-3 rounded-md text-sm font-medium hover:opacity-90 transition-all"
              onClick={handleRequestOtp}
              disabled={loading || !emailOrPhone.trim()}
            >
              {loading ? "Requesting..." : "Next"}
            </button>
            {message && <div className="mt-2 text-red-400">{message}</div>}
          </>
        )}

        {step === 3 && (
          <>
            <div className="text-left mb-2 text-sm font-medium text-gray-300">
              Verification Code
            </div>
            <div className="flex justify-between mb-4">
              {[...Array(6)].map((_, i) => (
                <div key={i} className="w-10">
                  <input
                    type="text"
                    inputMode="numeric"
                    maxLength={1}
                    value={otp[i] || ""}
                    onChange={(e) => {
                      let val = e.target.value.replace(/[^0-9]/g, "");
                      if (!val) val = "";
                      const newOtp = otp.split("");
                      newOtp[i] = val;
                      setOtp(newOtp.join("").slice(0, 6));
                      // Move to next input if filled
                      if (val && i < 5) {
                        document.getElementById(`otp-input-${i + 1}`)?.focus();
                      }
                    }}
                    onKeyDown={(e) => {
                      if (e.key === "Backspace" && !otp[i] && i > 0) {
                        document.getElementById(`otp-input-${i - 1}`)?.focus();
                      }
                    }}
                    id={`otp-input-${i}`}
                    className={`w-full text-center text-xl bg-transparent outline-none border-b-4 transition-colors ${
                      otp[i] ? "border-[#BFA465]" : "border-white"
                    }`}
                    style={{ caretColor: "transparent" }}
                  />
                </div>
              ))}
            </div>
            <button
              className="w-full bg-gradient-to-r from-[#C2A059] to-[#A17C3F] text-white py-3 rounded-md text-sm font-medium hover:opacity-90 transition-all"
              onClick={handleVerifyOtp}
              disabled={loading || otp.length !== 6}
            >
              {loading ? "Verifying..." : "Verify OTP"}
            </button>
            {message && <div className="mt-2 text-red-400">{message}</div>}
          </>
        )}

        {step === 4 && (
          <div className="text-green-400 text-lg font-semibold">
            Login successful!
          </div>
        )}
      </div>
      <div className="absolute top-175 w-full min-h-70 left-0 right-0 bg-[#232323] z-[5]">
        <Footer />
      </div>
    </>
  );
}