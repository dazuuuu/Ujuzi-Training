import React from 'react';
import { STORES } from '../data/stores';
import { MapPin, Phone, Clock, Navigation, X, ShieldCheck } from 'lucide-react';

interface StoreLocatorModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const StoreLocatorModal: React.FC<StoreLocatorModalProps> = ({ isOpen, onClose }) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-custom flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-[#121212] border border-[#2D2D2D] w-full max-w-3xl rounded-2xl overflow-hidden shadow-2xl relative my-auto">
        
        {/* Header */}
        <div className="bg-[#181818] px-6 py-4 border-b border-[#262626] flex items-center justify-between">
          <div className="flex items-center gap-2">
            <MapPin className="w-5 h-5 text-[#D4AF37]" />
            <h2 className="text-base font-bold text-white font-mono uppercase">
              TRUNKNET Stores & Pickup Hubs
            </h2>
          </div>

          <button onClick={onClose} className="p-1.5 text-gray-400 hover:text-white rounded-lg">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Store Cards */}
        <div className="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
          <p className="text-xs text-gray-300">
            Visit any of our nationwide experience centers for hands-on device testing, instant warranty repairs, and click-and-collect pickup.
          </p>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {STORES.map((store) => (
              <div 
                key={store.id}
                className="bg-[#0B0B0B] border border-[#262626] hover:border-[#D4AF37] p-4 rounded-xl transition space-y-2 flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-xs font-bold text-white font-mono">{store.name}</span>
                    {store.isFlagship && (
                      <span className="bg-[#D4AF37] text-black text-[9px] font-extrabold px-1.5 py-0.5 rounded uppercase font-mono">
                        Flagship
                      </span>
                    )}
                  </div>

                  <p className="text-xs text-gray-400 leading-relaxed flex items-start gap-1.5 mt-2">
                    <MapPin className="w-4 h-4 text-[#D4AF37] shrink-0 mt-0.5" />
                    <span>{store.address}</span>
                  </p>
                </div>

                <div className="space-y-1.5 pt-3 border-t border-[#1F1F1F] text-xs text-gray-300 font-mono">
                  <div className="flex items-center gap-2">
                    <Phone className="w-3.5 h-3.5 text-[#D4AF37]" />
                    <span>{store.phone}</span>
                  </div>
                  <div className="flex items-center gap-2 text-[11px] text-gray-400">
                    <Clock className="w-3.5 h-3.5 text-gray-500" />
                    <span>{store.hours}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>
    </div>
  );
};
