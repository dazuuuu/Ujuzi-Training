import React, { useState } from 'react';
import { Phone, Mail, ShieldCheck, Headphones, X, Check, Search } from 'lucide-react';

interface SupportContactModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const SupportContactModal: React.FC<SupportContactModalProps> = ({ isOpen, onClose }) => {
  if (!isOpen) return null;

  const [serialNo, setSerialNo] = useState('');
  const [warrantyResult, setWarrantyResult] = useState<string | null>(null);

  const handleCheckWarranty = (e: React.FormEvent) => {
    e.preventDefault();
    if (!serialNo.trim()) return;
    setWarrantyResult(`Valid TRUNKNET Authorized 2-Year Warranty until August 2028. Active Coverage for Serial #${serialNo.toUpperCase()}`);
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-custom flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-[#121212] border border-[#2D2D2D] w-full max-w-2xl rounded-2xl overflow-hidden shadow-2xl relative my-auto">
        
        {/* Header */}
        <div className="bg-[#181818] px-6 py-4 border-b border-[#262626] flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Headphones className="w-5 h-5 text-[#D4AF37]" />
            <h2 className="text-base font-bold text-white font-mono uppercase">
              24/7 Tech Support & Warranty Portal
            </h2>
          </div>

          <button onClick={onClose} className="p-1.5 text-gray-400 hover:text-white rounded-lg">
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="p-6 space-y-6">
          {/* Warranty Serial Checker */}
          <div className="bg-[#0B0B0B] border border-[#262626] p-4 rounded-xl space-y-3">
            <h3 className="text-xs font-bold text-[#D4AF37] font-mono uppercase flex items-center gap-1.5">
              <ShieldCheck className="w-4 h-4" /> Verify Device Warranty Status
            </h3>
            <form onSubmit={handleCheckWarranty} className="flex gap-2">
              <input 
                type="text" 
                placeholder="Enter Serial Number or SKU (e.g. TRK-LAP-MBP16M3)" 
                value={serialNo}
                onChange={(e) => setSerialNo(e.target.value)}
                className="flex-1 bg-[#181818] text-white text-xs p-2.5 rounded-lg border border-[#333] focus:border-[#D4AF37] focus:outline-none uppercase font-mono"
              />
              <button 
                type="submit" 
                className="bg-[#D4AF37] text-black font-bold text-xs px-4 py-2.5 rounded-lg hover:bg-[#C5A059]"
              >
                Verify
              </button>
            </form>

            {warrantyResult && (
              <div className="bg-[#1A1A1A] p-3 rounded-lg text-xs text-emerald-400 border border-emerald-500/30 font-mono flex items-center gap-2">
                <Check className="w-4 h-4 shrink-0 text-emerald-400" />
                <span>{warrantyResult}</span>
              </div>
            )}
          </div>

          {/* Direct Support Channels */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="bg-[#181818] border border-[#262626] p-4 rounded-xl space-y-2">
              <Phone className="w-5 h-5 text-[#D4AF37]" />
              <h4 className="text-xs font-bold text-white font-mono">24/7 Phone Technical Hotline</h4>
              <p className="text-sm font-bold text-[#D4AF37] font-mono">+254 700 878656</p>
              <p className="text-[11px] text-gray-400">Direct connection to senior hardware engineers.</p>
            </div>

            <div className="bg-[#181818] border border-[#262626] p-4 rounded-xl space-y-2">
              <Mail className="w-5 h-5 text-[#D4AF37]" />
              <h4 className="text-xs font-bold text-white font-mono">Email Support Desk</h4>
              <p className="text-sm font-bold text-[#D4AF37] font-mono">support@trunknettech.com</p>
              <p className="text-[11px] text-gray-400">Response guaranteed within 2 business hours.</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
};
