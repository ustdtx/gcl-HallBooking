import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate, useParams } from 'react-router-dom';
import Footer from '../components/Footer';
import nagadImg from '/assets/nagad.png';
import bkashImg from '/assets/bkash.png';
import amexImg from '/assets/amex.png';
import visaImg from '/assets/visa.png';
import mastercardImg from '/assets/mastercard.png';

const API_BASE = import.meta.env.VITE_API_URL;

export default function Payment() {
  const { bookingId } = useParams();
  const { authData } = useAuth();
  const navigate = useNavigate();

  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showFirstModal, setShowFirstModal] = useState(false);
  const [showSecondModal, setShowSecondModal] = useState(false);
  const [paymentPurpose, setPaymentPurpose] = useState(null);
  console.log(authData.token);

  useEffect(() => {
    const fetchBooking = async () => {
      try {
        const res = await fetch(`${API_BASE}/api/bookings/user`, {
          headers: {
            Authorization: `Bearer ${authData.token}`,
          },
        });

        const data = await res.json();
        const match = data.find(b => b.id == bookingId);
        if (!match) throw new Error('Booking not found');

        setBooking(match);
        setLoading(false);
      } catch (err) {
        setError(err.message);
        setLoading(false);
      }
    };

    fetchBooking();
  }, [bookingId, authData.token]);

const initiatePayment = async (purpose) => {
  try {
    const res = await fetch(`${API_BASE}/api/payment/initiate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${authData.token}`,
      },
      body: JSON.stringify({
        booking_id: bookingId,
        purpose,
      }),
    });

    if (!res.ok) {
      const errorText = await res.text();
      throw new Error(`Failed to initiate payment: ${errorText}`);
    }

    const data = await res.json(); // parse JSON once
    if (data.gateway_url) {
      window.location.href = data.gateway_url; // redirect user to SSLCommerz payment page
    } else {
      throw new Error('No payment URL received');
    }
  } catch (err) {
    alert(err.message);
  }
};


  const handleClick = () => {
    let purpose = '';
    if (booking.status === 'Unpaid') {
      purpose = 'Pre-Book';
      setPaymentPurpose(purpose);
      setShowFirstModal(true);
    } else if (booking.status === 'Pre-Booked') {
      // Directly initiate payment for pre-booked bookings
      initiatePayment('Final');
    } else if (booking.status === 'Paid') {
      alert('This booking is already fully paid.');
      return;
    } else {
      alert('Payment not allowed for this booking status.');
      return;
    }
  };

  const handleFirstModalContinue = () => {
    setShowFirstModal(false);
    setShowSecondModal(true);
  };

  const handleSecondModalContinue = () => {
    setShowSecondModal(false);
    initiatePayment(paymentPurpose);
  };

  const Modal = ({ children, onCancel, onContinue, continueLabel = 'Continue' }) => (
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
          background: "#333333",
          color: "white",
          borderRadius: "16px",
          padding: "24px",
          width: "90%",
          maxWidth: "400px",
          textAlign: "center",
          border: "1px solid #444444",
        }}
      >
        <h3
          style={{
            fontSize: "1.125rem",
            fontWeight: "bold",
            marginBottom: "1rem",
            textAlign: "left", // Add this line
          }}
        >
          Please Note
        </h3>
        <div
          style={{
            fontSize: "0.875rem",
            textAlign: "left",
            marginBottom: "1.5rem",
            color: "#d1d5db",
          }}
        >
          {children}
        </div>
        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            gap: "1rem",
          }}
        >
          {/* Swapped button styles */}
          <button
            onClick={onContinue}
            style={{
              flex: 1,
              background: "#BFA465",
              color: "#FFFFFF",
              border: "1px solid #B18E4E",
              padding: "0.5rem 0",
              borderRadius: "8px",
              fontSize: "16px",
              fontWeight: "semibold",
              cursor: "pointer",
              transition: "background 0.3s",
            }}
          >
            {continueLabel}
          </button>
          <button
            onClick={onCancel}
            style={{
              flex: 1,
              background: "#444444",
              color: "#FFFFFF",
              border: "1px solid #FFFFFF",
              padding: "0.5rem 0",
              borderRadius: "8px",
              fontSize: "16px",
              fontWeight: "semibold",
              cursor: "pointer",
              transition: "background 0.3s",
            }}
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  );

  const paymentMethods = [
    { name: 'Nagad', img: nagadImg },
    { name: 'Bkash', img: bkashImg },
    { name: 'AMEX', img: amexImg },
    { name: 'VISA', img: visaImg },
    { name: 'Master Card', img: mastercardImg },
  ];

  if (loading) return <div className="text-white text-center mt-20">Loading...</div>;
  if (error) return <div className="text-red-500 text-center mt-20">{error}</div>;

  return (
    <div className="absolute top-76 left-0 right-0 bg-[#232323] min-h-screen text-white">
      <div className="max-w-xl mx-auto mt-10 p-6 rounded-lg bg-[#333333] border border-[#444444]">
        <h2 className="text-center text-lg mb-4">Select Payment Option</h2>
        <div className="divide-y divide-[#444444]">
          {paymentMethods.map((method, idx) => (
            <div
              key={idx}
              className="flex justify-between items-center py-4 px-3 cursor-pointer hover:bg-[#2a2a2a]"
              onClick={handleClick}
            >
              <div className="flex items-center gap-3">
                <img src={method.img} alt={method.name} className="w-8 h-8 object-contain" />
                <span>{method.name}</span>
              </div>
              <span className="text-[#BFA465]">&gt;</span>
            </div>
          ))}
        </div>
      </div>

      {showFirstModal && (
        <Modal
          onCancel={() => setShowFirstModal(false)}
          onContinue={handleFirstModalContinue}
        >
          Once the pre-booking payment is made, it is non-refundable.
        </Modal>
      )}

      {showSecondModal && (
        <Modal
          onCancel={() => setShowSecondModal(false)}
          onContinue={handleSecondModalContinue}
          continueLabel="Continue Payment"
        >
          If you wish to change the booking date using the pre-booking amount, must update it within 48 hours. Otherwise, a new booking will be required.
        </Modal>
      )}

      <Footer />
    </div>
  );
}
