import React from "react";
import { useAuth } from "../context/AuthContext";
import Footer from "../components/Footer";

const API_BASE = import.meta.env.VITE_API_URL || "";

export default function Profile() {
  const { authData } = useAuth();
  const member = authData.member;

  const profileImage = member.profile_picture
    ? `${API_BASE}/${member.profile_picture}`
    : null;

  return (
    <>
    <div className="absolute top-12 min-h-screen left-0 right-0 bg-[#232323] text-white pt-20 pb-12">
      <div className="max-w-4xl mx-auto px-6">
        {/* Profile Card */}
        <div className="bg-[#2c2c2c] rounded-lg p-6 text-center mb-12 shadow-md">
          {/* Profile Picture or Placeholder */}
          <div className="flex justify-center mb-4">
            {profileImage ? (
              <img
                src={profileImage}
                alt="Profile"
                className="w-20 h-20 rounded-full object-cover border border-[#BFA465]"
              />
            ) : (
              <div className="w-20 h-20 bg-[#3d3d3d] rounded-full flex items-center justify-center text-3xl text-[#BFA465]">
                <i className="fas fa-user"></i>
              </div>
            )}
          </div>

          <h2 className="text-2xl text-[#BFA465] font-semibold">{member.name}</h2>
          <p className="text-gray-400 text-sm">Member</p>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left mt-6 text-sm px-6">
            <p><b>Club A/C #:</b> {member.club_account}</p>
            <p><b>Phone:</b> {member.phone}</p>
            <p><b>Email:</b> {member.email}</p>
            <p><b>Member Since:</b> {new Date(member.date_joined).toLocaleDateString()}</p>
            <p className="sm:col-span-2"><b>Address:</b> {member.address}</p>
          </div>
          <div className="absolute bottom-0 left-0"><Footer /></div>
          
        </div>



      </div>

    </div>

        </>
  );
}
