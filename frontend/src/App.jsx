import { useState } from 'react'
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import reactLogo from './assets/react.svg'
import viteLogo from '/vite.svg'
import './index.css';
import './App.css'
import Home from './pages/Home';
import Navbar from './components/Navbar';
import Login from "./pages/Login";
import Charges from "./pages/Charges"; 
import BookingCalendar from "./pages/BookingCalendar";
import Payment from './pages/Payment';
import ReservationHistory from './pages/ReservationHistory';
import ReservationDetails from './pages/ReservationDetails';
import UpdateBooking from './pages/UpdateBooking';
import Profile from './pages/Profile';
import Policy from './pages/Policy';
import Management from './pages/Management';
import Catering from './pages/Catering';

function App() {
  const [count, setCount] = useState(0)

  return (
        <Router>
          {/* Global background image */}
          <div
            style={{
              position: "fixed",
              top: 0,
              left: 0,
              width: "100vw",
              height: "100vh",
              zIndex: -10,
              backgroundImage: "url('/assets/hero.png')",
              backgroundSize: "cover",
              backgroundPosition: "center",
              backgroundRepeat: "no-repeat",
            }} />
          <Navbar />
          <Routes>
            <Route path="/" element={
              <>
                <div>
                  <a href="https://vite.dev" target="_blank">
                    <img src={viteLogo} className="logo" alt="Vite logo" />
                  </a>
                  <a href="https://react.dev" target="_blank">
                    <img src={reactLogo} className="logo react" alt="React logo" />
                  </a>
                </div>
                <h1>Vite + React</h1>
                <div className="card">
                  <button onClick={() => setCount((count) => count + 1)}>
                    count is {count}
                  </button>
                  <p>
                    Edit <code>src/App.jsx</code> and save to test HMR
                  </p>
                </div>
                <p className="read-the-docs">
                  Click on the Vite and React logos to learn more
                </p>
              </>
            } />
            <Route path="/home" element={<Home />} />
            <Route path="/login" element={<Login />} />
            <Route path="/charges/:hallId" element={<Charges />} />
            <Route path="/booking/:hallId/calendar" element={<BookingCalendar />} />
            <Route path="/payment/:bookingId" element={<Payment />} />
            <Route path="/reservations" element={<ReservationHistory />} />
            <Route path="/reservations/:bookingId" element={<ReservationDetails />} />
            <Route path="/update-booking/:bookingId" element={<UpdateBooking />} />
            <Route path="/profile" element={<Profile />} />
            <Route path="/policy/:hallId" element={<Policy />} />
            <Route path="/management" element={<Management />} />
            <Route path="/catering" element={<Catering />} />

          </Routes>
        </Router>
  )
}

export default App
