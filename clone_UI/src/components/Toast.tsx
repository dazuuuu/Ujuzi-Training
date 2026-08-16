import React, { useEffect } from 'react';
import { Check, X, Info } from 'lucide-react';

interface ToastProps {
  message: string | null;
  type?: 'success' | 'info';
  onClose: () => void;
}

export const Toast: React.FC<ToastProps> = ({ message, type = 'success', onClose }) => {
  useEffect(() => {
    if (message) {
      const timer = setTimeout(onClose, 3000);
      return () => clearTimeout(timer);
    }
  }, [message, onClose]);

  if (!message) return null;

  return (
    <div className="fixed bottom-6 right-6 z-50 bg-[#181818] border border-[#D4AF37] text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 max-w-sm animate-in slide-in-from-bottom-5">
      <div className="w-6 h-6 rounded-full bg-[#D4AF37] text-black flex items-center justify-center shrink-0">
        {type === 'success' ? <Check className="w-4 h-4 stroke-[3]" /> : <Info className="w-4 h-4 stroke-[3]" />}
      </div>
      <p className="text-xs font-bold font-mono text-gray-100 flex-1">{message}</p>
      <button onClick={onClose} className="text-gray-400 hover:text-white p-1">
        <X className="w-4 h-4" />
      </button>
    </div>
  );
};
