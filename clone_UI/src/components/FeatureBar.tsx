import React from 'react';
import { Truck, ShieldCheck, Headphones, CreditCard, RefreshCw } from 'lucide-react';

export const FeatureBar: React.FC = () => {
  const features = [
    {
      icon: <Truck className="w-5 h-5 text-[#D4AF37]" />,
      title: 'Same-Day Express Delivery',
      desc: 'Free on orders above $500',
    },
    {
      icon: <ShieldCheck className="w-5 h-5 text-[#D4AF37]" />,
      title: '100% Genuine Warranty',
      desc: 'Authorized brand distributor',
    },
    {
      icon: <Headphones className="w-5 h-5 text-[#D4AF37]" />,
      title: '24/7 Tech Support Hotline',
      desc: 'Expert engineers on call',
    },
    {
      icon: <CreditCard className="w-5 h-5 text-[#D4AF37]" />,
      title: 'Secure Encrypted Payment',
      desc: 'M-Pesa, Card & Bank Transfer',
    },
    {
      icon: <RefreshCw className="w-5 h-5 text-[#D4AF37]" />,
      title: '14-Day Free Returns',
      desc: 'Hassle-free guarantee',
    },
  ];

  return (
    <div className="bg-[#121212] border-b border-[#222222] py-5">
      <div className="max-w-7xl mx-auto px-4">
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          {features.map((feat, idx) => (
            <div 
              key={idx} 
              className="flex items-center gap-3 p-3 rounded-xl bg-[#161616] border border-[#222222] hover:border-[#D4AF37]/40 transition group"
            >
              <div className="w-10 h-10 rounded-lg bg-[#0B0B0B] border border-[#2A2A2A] flex items-center justify-center shrink-0 group-hover:border-[#D4AF37] transition">
                {feat.icon}
              </div>
              <div>
                <h4 className="text-xs font-bold text-white group-hover:text-[#D4AF37] transition">
                  {feat.title}
                </h4>
                <p className="text-[11px] text-gray-400 mt-0.5">
                  {feat.desc}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
