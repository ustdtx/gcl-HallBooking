import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import { AuthProvider } from "./context/Authcontext";
import { HallsProvider } from "./context/HallsContext";
import "./index.css";

ReactDOM.createRoot(document.getElementById("root")).render(
  <HallsProvider>
    <AuthProvider>
      <App />
    </AuthProvider>
  </HallsProvider>
);
