import React from "react";
import { useParams, useNavigate, Link } from "react-router-dom";
import { useHalls } from "../context/HallsContext";
import { useAuth } from "../context/AuthContext"; 
import Footer from '../components/Footer.jsx';

const formatChargeLabel = (key) => {
  const mapping = {
    FN: "Forenoon (11am-3pm)",
    AN: "Afternoon (3pm-11pm)",
    FD: "Full Day (11am-11pm)",
    "Pre-Book": "Pre-Booking",
  };
  return mapping[key] || key;
};

const Charges = () => {
  const { hallId } = useParams();
  const { halls, loading } = useHalls();
  const { user } = useAuth(); // Get user from auth context
  const navigate = useNavigate();

  if (loading) return <div className="text-white p-8">Loading...</div>;

  const hall = halls.find(h => String(h.id) === hallId);
  if (!hall) return <div className="text-white p-8">Hall not found.</div>;

  const { name, image, charges = {} } = hall;

  // Split charges into booking & others
  const bookingKeys = ["FN", "AN", "FD", "Pre-Book"];
  const bookingCharges = Object.entries(charges).filter(([key]) => bookingKeys.includes(key));
  const otherCharges = Object.entries(charges).filter(([key]) => !bookingKeys.includes(key));

  // Handler for booking calendar button
  const handleBookingCalendar = () => {

      navigate(`/booking/${hallId}/calendar`);
    };

  return (<>
    <div className="relative w-full min-h-screen">
      {/* Overlay to cover background */}
      <div
        className="fixed inset-0 z-0"
        style={{ background: "#232323", pointerEvents: "none" }}
      />
      <div className="relative z-10 w-full min-h-screen text-white py-10 px-4">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row gap-10">
          {/* Left: Image & Links */}
          <div className="md:w-1/4 w-full space-y-6">
            <div>
              <h2 className="text-xl font-bold mb-2">{name}</h2>
              <img
                src={image || "/default-hall.jpg"}
                alt={name}
                className="rounded-md w-full"
              />
            </div>
            <div style={{ marginLeft: '2rem' }}>
              <p className="font-semibold mb-2 text-left">Important Link</p>
              <ol className="space-y-1 text-sm text-left list-decimal" style={{ marginLeft: '1.5rem', color: '#CABEA4' }}>
                <li>
                  <Link to={`/policy/${hallId}`} style={{ color: "#CABEA4", display: "inline", textDecoration: "underline" }}>
                    Terms and Conditions
                  </Link>
                </li>
                <li>
                  No outside catering is allowed.
                </li>
                <li>
                  Enlisted Event Management Company.
                </li>
              </ol>
            </div>
          </div>

          {/* Right: Charges */}
          <div className="md:w-3/4 w-full space-y-10">
            {/* Charges Section */}
            <div className="bg-[#2B2B2B] border border-[#B18E4E66] rounded-md p-6 space-y-8">
              {/* Booking Charges */}
              <div className="bg-[#232323] border border-[#444444] rounded-md p-4">
                <h2 className="text-center text-xl font-bold text-[#B18E4E] mb-4">Welcome to {hall.name}</h2>
                <h2 className="text-center text-md font-bold text-[#ffffff] mb-4">Welcome to {hall.name}</h2>
                <table className="w-full max-w-2xl mx-auto rounded text-left">
                  <thead>
                    <tr>
                      <th className="py-3 px-4 bg-[#B18E4E] text-white font-bold text-base rounded-tl-md">Type</th>
                      <th className="py-3 px-4 bg-[#B18E4E] text-white font-bold text-base rounded-tr-md">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    {bookingCharges.map(([type, amount], idx) => (
                      <tr key={type}>
                        <td className={`py-2 px-4 border-t border-[#444444] text-white ${idx !== 0 ? "" : ""}`}>{formatChargeLabel(type)}</td>
                        <td className="py-2 px-4 border-t border-l border-[#444444] text-white">{amount} BDT</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <div className="mt-4 text-left text-xs sm:text-sm text-[#CABEA4]">
                  <span className="font-bold text-white block mb-1">Notes:</span>
                  <ul className="list-decimal list-inside">
                    <li>Maximum capacity: {hall.capacity || 2000}</li>
                    <li>Time: Dinner Shift (17:00 to 23:00)</li>
                    <li>Catering and Event management must be chosen from the list provided.</li>
                  </ul>
                </div>
              </div>

              {/* Other Charges */}
              <div className="bg-[#232323] border border-[#444444] rounded-md p-4">
                <h2 className="text-center text-md font-bold text-[#ffffff] mb-4">Other Charges (BDT)</h2>
                <table className="w-full max-w-2xl mx-auto rounded text-left">
                  <thead>
                    <tr>
                      <th className="py-3 px-4 bg-[#B18E4E] text-white font-bold text-base rounded-tl-md">Description</th>
                      <th className="py-3 px-4 bg-[#B18E4E] text-white font-bold text-base rounded-tr-md">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    {otherCharges.map(([type, amount], idx) => (
                      <tr key={type}>
                        <td className="py-2 px-4 border-t border-[#444444] text-white">{type}</td>
                        <td className="py-2 px-4 border-t border-l border-[#444444] text-white">{amount} BDT</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <div className="mt-4 text-left text-xs sm:text-sm text-[#CABEA4]">
                  <span className="font-bold text-white block mb-1">Notes:</span>
                  <ul className="list-decimal list-inside">
                    <li>Maximum capacity: {hall.capacity || 2000}</li>
                    <li>Time: Dinner Shift (17:00 to 23:00)</li>
                    <li>Catering and Event management must be chosen from the list provided.</li>
                  </ul>
                </div>
              </div>
            </div>

            {/* Go to Booking Calendar Button */}
            <div className="flex justify-center mt-8">
              <button
                type="button"
                onClick={handleBookingCalendar}
                style={{
                  background: "#B18E4E",
                  color: "#fff",
                  padding: "0.75rem 2rem",
                  borderRadius: "0.375rem",
                  fontWeight: "bold",
                  textDecoration: "none",
                  fontSize: "1rem",
                  boxShadow: "0 2px 8px 0 #0002",
                  border: "none",
                  cursor: "pointer"
                }}
              >
                Go to Booking Calendar
              </button>
            </div>
          </div>
        </div>

      </div>

    </div>
    <div className="absolute left-0 z-10">
      <Footer />
    </div>
  </>
  );
};

export default Charges;
