import React, { createContext, useContext, useState } from "react";

// Create the context
const AuthContext = createContext();

// Provider component
export function AuthProvider({ children }) {
  // This state will hold your login information (e.g., user, token, etc.)
  const [authData, setAuthData] = useState(null);

  return (
    <AuthContext.Provider value={{ authData, setAuthData }}>
      {children}
    </AuthContext.Provider>
  );
}

// Custom hook for easy access
export function useAuth() {
  return useContext(AuthContext);
}