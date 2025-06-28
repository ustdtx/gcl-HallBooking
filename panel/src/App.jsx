import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import Login from "./pages/Login";
import Dashboard from "./pages/Dashboard";
import Navbar from "./components/Navbar";
import AdminBookingTable from "./pages/Bookings";
import AdminEventBookingForm from "./pages/BookEvents";


function App() {

  return (
    <Router>
      <Navbar />
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="*" element={<Navigate to="/login" />} />
        <Route path="/bookevents" element={<AdminEventBookingForm />} />
        <Route path="/bookings" element={<AdminBookingTable />} />
      </Routes>
    </Router>
  );
}

export default App;
