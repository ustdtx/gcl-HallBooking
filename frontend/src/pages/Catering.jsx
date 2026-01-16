import React from "react";

const companies = [
  "Eventify Solutions",
  "Celebration Creators",
  "Elite Events Co.",
  "Grand Gala Planners",
  "Moments & Memories",
  "Sparkle Event Management",
  "Urban Gatherings",
];

export default function Catering() {
  return (
    <div className="absolute left-0 right-0 top-12 bg-[#232323] min-h-screen text-white pt-20 pb-12">
      <div style={{ maxWidth: 700, margin: "0 auto", textAlign: "center" }}>
        <h1 style={{ fontWeight: 700, marginBottom: 24, letterSpacing: 1 }}>
          List of Catering Companies
        </h1>
        <ul style={{ listStyle: "none", padding: 0, marginBottom: 40 }}>
          {companies.map((company, idx) => (
            <li
              key={company}
              style={{
                background: "#292929",
                margin: "10px 0",
                padding: "14px 0",
                borderRadius: 8,
                fontSize: 18,
                fontWeight: 500,
                boxShadow: "0 2px 8px #0002",
              }}
            >
              {idx + 1}. {company}
            </li>
          ))}
        </ul>
        <div style={{ fontSize: 20, lineHeight: 1.6, color: "#ccc" }}>
          Looking to make your next event unforgettable? These event management companies offer a wide range of services to help you plan, organize, and execute memorable occasions. From corporate gatherings to weddings and parties, trust the experts to handle every detail with professionalism and creativity.
        </div>
      </div>
    </div>
  );
}