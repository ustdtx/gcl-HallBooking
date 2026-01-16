import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { useHalls } from "../context/HallsContext"; // Assuming halls are in this context
import Footer from "../components/Footer";

export default function Policy() {
  const { hallId } = useParams();
  const { halls } = useHalls();
  const [hall, setHall] = useState(null);
  const [expanded, setExpanded] = useState(null);

  useEffect(() => {
    const found = halls.find((h) => h.id === parseInt(hallId));
    setHall(found || null);
  }, [hallId, halls]);

  if (!hall) return <div className="text-white p-6">Loading...</div>;

  const toggleExpand = (key) => {
    setExpanded((prev) => (prev === key ? null : key));
  };

  return (
    <div className="absolute left-0 right-0 top-12 bg-[#232323] min-h-screen pt-20 pb-10">
      <div className="max-w-6xl mx-auto px-6">
        <h1 className="text-xl text-[#BFA465] sm:text-xl font-bold text-center mb-8">
          Policies 
        </h1>

        {/* Accordion */}
        <div
          style={{
            display: "grid",
            gridTemplateColumns: "1fr 1fr",
            gap: "16px",
            marginBottom: "40px",
            maxWidth: "100%",
          }}
        >
          {Object.entries(hall.policy_content).map(([key, value], idx) => (
            <div
              key={key}
              style={{
                background: "#363636",
                border: "1px solid #B18E4E",
                borderRadius: "8px",
                overflow: "hidden",
                display: "flex",
                flexDirection: "column",
              }}
            >
              <button
                onClick={() => setExpanded(expanded === key ? null : key)}
                style={{
                  width: "100%",
                  textAlign: "left",
                  fontWeight: "bold",
                  background: "none",
                  border: "none",
                  outline: "none",
                  color: "#fff", // White text for key
                  padding: "18px",
                  fontSize: "16px",
                  borderBottom:
                    expanded === key ? "1px solid #B18E4E" : "1px solid #B18E4E",
                  cursor: "pointer",
                  transition: "background 0.2s",
                }}
              >
                {key}
              </button>
              {expanded === key && (
                <div
                  style={{
                    background: "#363636",
                    color: "#e5e5e5",
                    padding: "18px",
                    fontSize: "15px",
                    borderTop: "none",
                    lineHeight: "1.7",
                    whiteSpace: "pre-line",
                    transition: "all 0.3s",
                  }}
                >
                  {value}
                </div>
              )}
            </div>
          ))}
        </div>

        {/* Embedded PDF */}
        <div className="bg-white rounded shadow overflow-hidden mb-12">
          <iframe
            title="Policy PDF"
            src={hall.policy_pdf}
            className="w-full h-[600px]"
            frameBorder="0"
          ></iframe>
        </div>

        {/* Footer */}
        <div className="w-full mt-8 flex justify-center">
          <Footer />
        </div>
      </div>
    </div>
  );
}
