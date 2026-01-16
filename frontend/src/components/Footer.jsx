// src/components/Footer.jsx
import {
  FaFacebookF,
  FaInstagram,
  FaYoutube,
  FaLinkedinIn,
  FaPhone,
  FaEnvelope,
} from 'react-icons/fa';

export default function Footer() {
  return (
    <footer className="w-screen text-white px-6 md:px-16 py-8 text-sm">
      <div className="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 items-start border-b border-gray-700 pb-6">
        {/* Socials */}
        <div className="text-left">
          <h4 className="text-sm mb-4 border-b-2 border-[#BFA465] inline-block">KEEP IN TOUCH</h4>
          <div className="flex space-x-4 text-lg">
            <a
              href="https://www.facebook.com/GulshanClubLtd#"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Facebook"
              style={{ color: "#BFA465" }}
            >
              <FaFacebookF />
            </a>
            <a
              href="https://www.gulshanclub.com/"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Instagram"
              style={{ color: "#BFA465" }}
            >
              <FaInstagram />
            </a>
            <a
              href="https://www.youtube.com/c/GulshanClubLtd"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="YouTube"
              style={{ color: "#BFA465" }}
            >
              <FaYoutube />
            </a>
            <a
              href="https://www.gulshanclub.com/"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="LinkedIn"
              style={{ color: "#BFA465" }}
            >
              <FaLinkedinIn />
            </a>
          </div>
        </div>

        {/* Club Info */}
        <div className="md:col-span-1 text-left">
          <h4 className="text-lg font-semibold text-white border-b-2 border-[#BFA465] inline-block mb-2">
            Gulshan Club Ltd.
          </h4>
          <p>House: NWJ-2/A, Bir Uttom Sultan Mahmud Road (Old Road: 50)</p>
          <p>Gulshan 2, Dhaka 1212, Bangladesh</p>
          <p className="mt-2 flex items-center gap-2">
            <FaEnvelope className="text-[#BFA465]" /> mail@gulshanclub.com
          </p>
        </div>

        {/* Phone */}
        <div className="flex flex-col items-start md:items-end justify-between">
          <div className="flex items-center gap-2 text-white">
            <FaPhone className="text-[#ffffff]" /> 16717
          </div>
        </div>

        {/* QR */}
        <div className="flex flex-col items-start md:items-end justify-between">
          <img
            src="/assets/qr.png"
            alt="QR"
            className="w-28 h-28 rounded-md border border-[#BFA465]"
          />
        </div>
      </div>

      {/* Bottom Row */}
      <div className="flex flex-col md:flex-row justify-between items-center mt-6 text-xs text-gray-400">
        <img src="/assets/gclogo.png" alt="Logo" className="h-10" />
        <div className="text-center">
          Copyright © 2024 Gulshan Club Limited. All rights reserved.
          <br />
          <span className="italic text-[11px]">
            Designed & developed by Workspace Infotech LTD
          </span>
        </div>
      </div>
    </footer>
  );
}
