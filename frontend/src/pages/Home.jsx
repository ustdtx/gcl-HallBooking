import React, { useEffect, useRef } from 'react';
import Footer from '../components/Footer.jsx';
import { useHalls } from '../context/HallsContext';
import { Link } from "react-router-dom";

const GulshanClub = () => {
  const { halls, loading } = useHalls();
  const contentRef = useRef(null);
  const coverRef = useRef(null);

  useEffect(() => {
    const updateCoverHeight = () => {
      if (contentRef.current && coverRef.current) {
        const contentHeight = contentRef.current.scrollHeight;
        const coverStartOffset = 464; // equivalent to top-116 (116 * 0.25rem = 29rem = 464px)
        const totalHeightNeeded = contentHeight + coverStartOffset;
        
        coverRef.current.style.height = `${totalHeightNeeded}px`;
      }
    };

    // Update on mount and when halls data changes
    updateCoverHeight();
    
    // Update on window resize
    const handleResize = () => {
      setTimeout(updateCoverHeight, 100); // Small delay to ensure layout is complete
    };
    
    window.addEventListener('resize', handleResize);
    
    return () => window.removeEventListener('resize', handleResize);
  }, [halls, loading]);

  return (
    <div className="fixed top-0 left-0 right-0 w-full h-full overflow-y-scroll">
      {/* Hero background - stays the same */}
      
      <div className="relative inset-0 top-72 left-0 right-0 w-full py-8 flex justify-center items-start z-10">
        <div 
          ref={contentRef}
          className="px-6 flex flex-col items-center max-w-6xl w-full text-white space-y-12"
        >
          {/* Hall Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 w-full">
            {loading ? (
              <div className="col-span-1 sm:col-span-2 md:col-span-2 lg:col-span-4 text-center text-white">Loading...</div>
            ) : halls.length === 0 ? (
              <div className="col-span-1 sm:col-span-2 md:col-span-2 lg:col-span-4 text-center text-white">No halls found.</div>
            ) : (
              halls.map((hall) => (
                <div
                  key={hall.id}
                  className="bg-[#363636] text-white rounded-lg overflow-hidden shadow-lg border border-[#BFA465] flex flex-col"
                >
                  <img src={hall.image} alt={hall.name} className="w-full h-48 object-cover" />
                  <div className="p-4 flex flex-col flex-grow">
                    <h3 className="text-lg font-semibold mb-4">{hall.name}</h3>
                    <div className="mt-auto flex justify-between gap-2">
                      <button
                        style={{
                          background: '#363636',
                          color: 'white',
                          fontSize: '0.875rem',
                          padding: '0.5rem 1rem',
                          borderRadius: '0.375rem',
                          border: '1px solid #B18E4E',
                          flex: 1,
                          minWidth: 0,
                        }}
                      >
                        <Link to={`/policy/${hall.id}`} style={{ color: "inherit", textDecoration: "none" }}>
                        Policies
                        </Link>
                      </button>
                      <button
                        style={{
                          background: '#BFA465',
                          color: 'white',
                          fontSize: '0.875rem',
                          padding: '0.5rem 1rem',
                          borderRadius: '0.375rem',
                          border: '1px solid #B18E4E',
                          flex: 1,
                          minWidth: 0,
                        }}
                      >
                        <Link to={`/charges/${hall.id}`} style={{ color: "inherit", textDecoration: "none" }}>
                          Book Now
                        </Link>
                      </button>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Description Text */}
          <div className="text-center max-w-3xl px-4">
            <h2 className="text-xl md:text-2xl font-bold text-white mb-4">
              Welcome to <span className="text-[#BFA465]">Gulshan Club</span> <span className="text-[#BFA465]">Banquet Hall Booking</span> System
            </h2>
            <p className="text-sm md:text-base text-gray-300">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 
              Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. 
              Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. 
              Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
            </p>
          </div>
          <Footer />
        </div>
      </div>
      
      {/* Dynamic cover background - this is the key fix */}
      <div 
        ref={coverRef}
        className="absolute top-116 w-full left-0 right-0 bg-[#232323] z-[5]"
        style={{ minHeight: '100vh' }} // Fallback minimum height
      />
    </div>
  );
};

export default function Home() {
  return <GulshanClub />;
}