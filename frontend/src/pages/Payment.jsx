import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate, useParams } from 'react-router-dom';
import Footer from '../components/Footer';

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

      const debugText = await res.text();
      console.log('Status:', res.status);
      console.log('Response:', debugText);

      if (!res.ok) throw new Error('Failed to initiate payment');
      const redirect = await res.text();
      window.location.href = redirect;
    } catch (err) {
      alert(err.message);
    }
  };

  const handleClick = () => {
    let purpose = '';
    if (booking.status === 'Unpaid') {
      purpose = 'Pre-Book';
    } else if (booking.status === 'Pre-Booked') {
      purpose = 'Final';
    } else {
      alert('Payment not allowed for this booking status.');
      return;
    }
    setPaymentPurpose(purpose);
    setShowFirstModal(true);
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
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-[#333333] text-white p-6 rounded-lg w-96 border border-[#444444]">
        <h3 className="text-lg font-semibold mb-2">Please Note</h3>
        <p className="mb-4 text-sm">{children}</p>
        <div className="flex justify-end gap-2">
          <button onClick={onCancel} className="border border-white px-4 py-2 rounded">Cancel</button>
          <button onClick={onContinue} className="bg-[#BFA465] text-black px-4 py-2 rounded">{continueLabel}</button>
        </div>
      </div>
    </div>
  );

  if (loading) return <div className="text-white text-center mt-20">Loading...</div>;
  if (error) return <div className="text-red-500 text-center mt-20">{error}</div>;

  return (
    <div className="absolute top-[116px] left-0 right-0 bg-[#232323] min-h-screen text-white">
      <div className="max-w-xl mx-auto mt-10 p-6 rounded-lg bg-[#333333] border border-[#444444]">
        <h2 className="text-center text-lg mb-4">Select Payment Option</h2>
        <div className="divide-y divide-[#444444]">
          {['Nagad', 'Bkash', 'AMEX', 'VISA', 'Master Card'].map((method, idx) => (
            <div
              key={idx}
              className="flex justify-between items-center py-4 px-3 cursor-pointer hover:bg-[#2a2a2a]"
              onClick={handleClick}
            >
              <span>{method}</span>
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
