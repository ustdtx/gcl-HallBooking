import React, { createContext, useContext, useState, useEffect } from 'react';

const BookingContext = createContext();

export const useBooking = () => useContext(BookingContext);

export const BookingProvider = ({ children }) => {
  const [bookingState, setBookingState] = useState(() => {
    // Try to load from localStorage
    const saved = localStorage.getItem('bookingState');
    return saved ? JSON.parse(saved) : {};
  });

  useEffect(() => {
    localStorage.setItem('bookingState', JSON.stringify(bookingState));
  }, [bookingState]);

  return (
    <BookingContext.Provider value={{ bookingState, setBookingState }}>
      {children}
    </BookingContext.Provider>
  );
};