import React, { useState } from 'react';
import { CategoryId } from '../types';
import { 
  Cpu, 
  MapPin, 
  Phone, 
  Mail, 
  ShieldCheck, 
  Truck, 
  CreditCard, 
  Send, 
  Check, 
  ArrowUp 
} from 'lucide-react';

interface FooterProps {
  onSelectCategory: (cat: CategoryId) => void;
  onOpenStores: () => void;
  onOpenSupport: () => void;
  onOpenPCConfigurator: () => void;
}

export const Footer: React.FC<FooterProps> = ({
  onSelectCategory,
  onOpenStores,
  onOpenSupport,
  onOpenPCConfigurator,
}) => {
  const [email, setEmail] = useState('');
  const [subscribed, setSubscribed] = useState(false);

  const handleSubscribe = (e: React.FormEvent) => {
    e.preventDefault();
    if (email.trim()) {
      setSubscribed(true);
      setEmail('');
    }
  };

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <footer className="bg-[#080808] border-t border-[#222222] text-gray-300">
      
      {/* Newsletter Bar */}
      <div className="bg-[#121212] border-b border-[#222222] py-8">
        <div className="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="space-y-1 text-center md:text-left">
            <h3 className="text-base font-extrabold text-white font-mono uppercase flex items-center gap-2 justify-center md:justify-start">
              <ShieldCheck className="w-5 h-5 text-[#D4AF37]" /> Stay Updated with VIP Electronics Drops
            </h3>
            <p className="text-xs text-gray-400">
              Subscribe to receive instant price drop alerts, exclusive promo codes, and tech release news.
            </p>
          </div>

          <form onSubmit={handleSubscribe} className="flex gap-2 w-full md:w-auto max-w-md">
            <input 
              type="email" 
              required
              placeholder="Enter your work email address..." 
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="bg-[#0B0B0B] text-white text-xs px-4 py-3 rounded-xl border border-[#333] focus:border-[#D4AF37] focus:outline-none flex-1 font-sans"
            />
            <button 
              type="submit" 
              className="bg-[#D4AF37] hover:bg-[#C5A059] text-black font-extrabold text-xs px-5 py-3 rounded-xl transition flex items-center gap-1.5 uppercase font-mono"
            >
              {subscribed ? <Check className="w-4 h-4 text-black" /> : <Send className="w-4 h-4 text-black" />}
              <span>{subscribed ? 'Subscribed' : 'Join'}</span>
            </button>
          </form>
        </div>
      </div>

      {/* Main Footer Links */}
      <div className="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 text-xs">
        
        {/* Col 1: Brand Info */}
        <div className="lg:col-span-2 space-y-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 flex items-center justify-center overflow-hidden">
              <img 
                src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcScmuc3aj_PsLJ72bR24YWR84QhPPMXH2N6aQD3Gih9NQ-V8D26ukaPZmLrLcX5J_OW&s=10&ec=121902082" 
                alt="TRUNKNET Logo" 
                className="w-full h-full object-contain"
                referrerPolicy="no-referrer"
              />
            </div>
            <div>
              <span className="text-lg font-extrabold tracking-wider text-white font-mono">
                TRUNKNET
              </span>
              <span className="text-xs bg-[#D4AF37] text-black px-1.5 py-0.5 rounded ml-1 font-bold">
                TECH
              </span>
            </div>
          </div>

          <p className="text-gray-400 leading-relaxed text-xs max-w-sm">
            TRUNKNET TECHNOLOGIES is a premier authorized distributor of high-performance smartphones, enterprise laptops, workstations, and computer peripherals. Delivering tech excellence with nationwide warranty support.
          </p>

          <div className="space-y-2 text-xs font-mono text-gray-300">
            <div className="flex items-center gap-2">
              <MapPin className="w-4 h-4 text-[#D4AF37]" />
              <span>Westlands Commerce Park, Nairobi</span>
            </div>
            <div className="flex items-center gap-2">
              <Phone className="w-4 h-4 text-[#D4AF37]" />
              <span>+254 700 878656</span>
            </div>
            <div className="flex items-center gap-2">
              <Mail className="w-4 h-4 text-[#D4AF37]" />
              <span>sales@trunknettech.com</span>
            </div>
          </div>
        </div>

        {/* Col 2: Categories */}
        <div className="space-y-3">
          <h4 className="text-xs font-extrabold text-white font-mono uppercase tracking-wider text-[#D4AF37]">
            Categories
          </h4>
          <ul className="space-y-2 text-gray-400">
            <li>
              <button onClick={() => onSelectCategory('smartphones')} className="hover:text-white transition">
                Smartphones & Tablets
              </button>
            </li>
            <li>
              <button onClick={() => onSelectCategory('laptops')} className="hover:text-white transition">
                Laptops & Workstations
              </button>
            </li>
            <li>
              <button onClick={() => onSelectCategory('desktops')} className="hover:text-white transition">
                Computers & Custom Rigs
              </button>
            </li>
            <li>
              <button onClick={() => onSelectCategory('accessories')} className="hover:text-white transition">
                Mice, Keyboards & Docks
              </button>
            </li>
            <li>
              <button onClick={() => onSelectCategory('audio')} className="hover:text-white transition">
                Studio Audio & Wearables
              </button>
            </li>
            <li>
              <button onClick={() => onSelectCategory('gaming')} className="hover:text-white transition">
                Gaming & 240Hz Displays
              </button>
            </li>
          </ul>
        </div>

        {/* Col 3: Customer Care */}
        <div className="space-y-3">
          <h4 className="text-xs font-extrabold text-white font-mono uppercase tracking-wider text-[#D4AF37]">
            Customer Services
          </h4>
          <ul className="space-y-2 text-gray-400">
            <li>
              <button onClick={onOpenStores} className="hover:text-white transition">
                Store Locator & Pickups
              </button>
            </li>
            <li>
              <button onClick={onOpenSupport} className="hover:text-white transition">
                Warranty Verification
              </button>
            </li>
            <li>
              <button onClick={onOpenPCConfigurator} className="hover:text-white transition">
                Specs & PC Builder Tool
              </button>
            </li>
            <li>
              <button onClick={onOpenSupport} className="hover:text-white transition">
                24/7 Technical Support
              </button>
            </li>
            <li>
              <span className="text-gray-500">14-Day Free Exchange Policy</span>
            </li>
          </ul>
        </div>

        {/* Col 4: Trust & Payment */}
        <div className="space-y-3">
          <h4 className="text-xs font-extrabold text-white font-mono uppercase tracking-wider text-[#D4AF37]">
            Accepted Payment
          </h4>
          <div className="grid grid-cols-2 gap-2 text-[10px] font-mono text-gray-300">
            <span className="bg-[#141414] p-2 rounded border border-[#222] text-center">M-Pesa Express</span>
            <span className="bg-[#141414] p-2 rounded border border-[#222] text-center">Visa / Mastercard</span>
            <span className="bg-[#141414] p-2 rounded border border-[#222] text-center">Bank Wire</span>
            <span className="bg-[#141414] p-2 rounded border border-[#222] text-center">PayPal</span>
          </div>

          <div className="pt-2 text-[11px] text-gray-500">
            100% Encrypted 256-Bit SSL Checkout Security.
          </div>
        </div>

      </div>

      {/* Bottom Legal bar */}
      <div className="bg-[#0B0B0B] border-t border-[#1C1C1C] py-4 text-xs">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-gray-500">
          <div>
            © {new Date().getFullYear()} <span className="text-white font-bold font-mono">TRUNKNET TECHNOLOGIES LTD</span>. All Rights Reserved.
          </div>

          <button 
            onClick={scrollToTop}
            className="flex items-center gap-1.5 text-[#D4AF37] hover:text-white transition font-mono"
          >
            <span>Back to Top</span>
            <ArrowUp className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

    </footer>
  );
};
