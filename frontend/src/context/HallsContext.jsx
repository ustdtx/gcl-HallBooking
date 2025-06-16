import React, { createContext, useContext, useEffect, useState } from "react";

const HallsContext = createContext();

export const useHalls = () => useContext(HallsContext);

export const HallsProvider = ({ children }) => {
  const [halls, setHalls] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchHalls = async () => {
      try {
        const apiUrl = `${import.meta.env.VITE_API_URL}/halls/`;
        const res = await fetch(apiUrl);
        const data = await res.json();
        const formatted = data.map(hall => {
          // Handle images: pick random if available, else empty string
          let image = "";
          if (Array.isArray(hall.images) && hall.images.length > 0) {
            const randImg = hall.images[Math.floor(Math.random() * hall.images.length)];
            image = randImg.replace(/\\\//g, "/");
          }
          // Charges: filter out keys with null values and "0"
          let charges = {};
          if (typeof hall.charges === "object" && hall.charges !== null && !Array.isArray(hall.charges)) {
            Object.entries(hall.charges).forEach(([k, v]) => {
              if (v !== null && k !== "0") charges[k] = v;
            });
          }
          // Policy content: filter out nulls and "0"
          let policy_content = {};
          if (typeof hall.policy_content === "object" && hall.policy_content !== null) {
            Object.entries(hall.policy_content).forEach(([k, v]) => {
              if (v !== null && k !== "0") policy_content[k] = v;
            });
          }
          return {
            ...hall,
            image,
            charges,
            policy_content,
          };
        });
        setHalls(formatted);
      } catch (e) {
        setHalls([]);
      } finally {
        setLoading(false);
      }
    };
    fetchHalls();
  }, []);

  return (
    <HallsContext.Provider value={{ halls, loading }}>
      {children}
    </HallsContext.Provider>
  );
};