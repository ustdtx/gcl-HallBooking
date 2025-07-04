import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'
import { AuthProvider } from "./context/AuthContext";
import { HallsProvider } from "./context/HallsContext";
import { BookingProvider } from './context/BookingContext';

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <AuthProvider>
      <HallsProvider>
        <BookingProvider>
          <App />
        </BookingProvider>
      </HallsProvider>
    </AuthProvider>
  </StrictMode>,
)
