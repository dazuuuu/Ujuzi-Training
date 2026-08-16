import React, { useState } from 'react';
import { Product } from '../types';
import { Sparkles, Cpu, Laptop, Monitor, Smartphone, Check, ArrowRight, RotateCcw, X } from 'lucide-react';

interface SpecsFinderToolProps {
  isOpen: boolean;
  onClose: () => void;
  products: Product[];
  onSelectProduct: (p: Product) => void;
  onAddToCart: (p: Product) => void;
}

export const SpecsFinderTool: React.FC<SpecsFinderToolProps> = ({
  isOpen,
  onClose,
  products,
  onSelectProduct,
  onAddToCart,
}) => {
  if (!isOpen) return null;

  const [useCase, setUseCase] = useState<'ai' | 'gaming' | 'mobile' | 'editing'>('ai');
  const [budget, setBudget] = useState<'budget' | 'mid' | 'high'>('high');

  // Recommendation engine matching selected profile
  const recommended = products.filter(p => {
    if (budget === 'budget' && p.price > 100000) return false;
    if (budget === 'mid' && (p.price < 50000 || p.price > 250000)) return false;
    if (budget === 'high' && p.price < 200000) return false;

    if (useCase === 'ai' && (p.category === 'desktops' || p.category === 'laptops')) return true;
    if (useCase === 'gaming' && (p.category === 'gaming' || p.category === 'laptops' || p.category === 'desktops')) return true;
    if (useCase === 'mobile' && (p.category === 'smartphones' || p.category === 'laptops')) return true;
    if (useCase === 'editing' && (p.category === 'laptops' || p.category === 'desktops' || p.category === 'accessories')) return true;

    return true;
  }).slice(0, 3);

  return (
    <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-custom flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-[#121212] border border-[#2D2D2D] w-full max-w-3xl rounded-2xl overflow-hidden shadow-2xl relative my-auto">
        
        {/* Header */}
        <div className="bg-[#181818] px-6 py-4 border-b border-[#262626] flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Sparkles className="w-5 h-5 text-[#D4AF37]" />
            <h2 className="text-base font-bold text-white font-mono uppercase">
              Interactive Specs & Tech Builder
            </h2>
          </div>

          <button onClick={onClose} className="p-1.5 text-gray-400 hover:text-white rounded-lg">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content Body */}
        <div className="p-6 space-y-6">
          <p className="text-xs text-gray-300">
            Select your primary workload requirements and budget below. TRUNKNET’s hardware recommendation engine will match you with the optimal processor, RAM, and GPU configurations.
          </p>

          {/* 1. Use Case Selector */}
          <div>
            <label className="text-xs font-bold text-[#D4AF37] font-mono uppercase block mb-3">
              1. What is your primary workload?
            </label>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
              {[
                { id: 'ai', label: 'AI, Coding & 3D', icon: <Cpu className="w-4 h-4 text-[#D4AF37]" /> },
                { id: 'gaming', label: 'Pro Esports Gaming', icon: <Monitor className="w-4 h-4 text-[#D4AF37]" /> },
                { id: 'editing', label: '4K/8K Video Editing', icon: <Laptop className="w-4 h-4 text-[#D4AF37]" /> },
                { id: 'mobile', label: 'Ultra-Mobile Exec', icon: <Smartphone className="w-4 h-4 text-[#D4AF37]" /> },
              ].map(item => (
                <button
                  key={item.id}
                  onClick={() => setUseCase(item.id as any)}
                  className={`p-3 rounded-xl border text-left text-xs font-bold transition flex flex-col gap-2 ${
                    useCase === item.id 
                      ? 'border-[#D4AF37] bg-[#1E1E1E] text-white' 
                      : 'border-[#262626] bg-[#0B0B0B] text-gray-400 hover:text-white'
                  }`}
                >
                  {item.icon}
                  <span>{item.label}</span>
                </button>
              ))}
            </div>
          </div>

          {/* 2. Budget Tier Selector */}
          <div>
            <label className="text-xs font-bold text-[#D4AF37] font-mono uppercase block mb-3">
              2. Target Investment Budget
            </label>
            <div className="grid grid-cols-3 gap-3">
              {[
                { id: 'budget', label: 'Under KES 100,000', sub: 'Essential Performance' },
                { id: 'mid', label: 'KES 100k - 250k', sub: 'Professional Standard' },
                { id: 'high', label: 'KES 250,000+ Ultimate', sub: 'Max Spec Workstation' },
              ].map(b => (
                <button
                  key={b.id}
                  onClick={() => setBudget(b.id as any)}
                  className={`p-3 rounded-xl border text-left transition ${
                    budget === b.id 
                      ? 'border-[#D4AF37] bg-[#1E1E1E] text-white' 
                      : 'border-[#262626] bg-[#0B0B0B] text-gray-400 hover:text-white'
                  }`}
                >
                  <div className="text-xs font-bold font-mono">{b.label}</div>
                  <div className="text-[10px] text-gray-400 mt-0.5">{b.sub}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Results Recommendations */}
          <div className="pt-4 border-t border-[#262626]">
            <h3 className="text-xs font-bold text-white font-mono uppercase mb-3 flex items-center justify-between">
              <span>Matched Hardware Configurations ({recommended.length})</span>
            </h3>

            <div className="space-y-3">
              {recommended.length === 0 ? (
                <p className="text-xs text-gray-400">No exact match for this budget tier. Try selecting Mid or High Tier.</p>
              ) : (
                recommended.map(prod => (
                  <div 
                    key={prod.id}
                    className="bg-[#0B0B0B] border border-[#262626] hover:border-[#D4AF37] p-3 rounded-xl flex items-center justify-between gap-4 transition"
                  >
                    <div className="flex items-center gap-3 min-w-0">
                      <img src={prod.image} alt={prod.name} className="w-12 h-12 object-contain bg-[#181818] p-1 rounded border border-[#2A2A2A] shrink-0" />
                      <div className="min-w-0">
                        <h4 className="text-xs font-bold text-white truncate">{prod.name}</h4>
                        <div className="text-[11px] text-gray-400 font-mono mt-0.5">
                          {prod.specs.Processor || prod.specs.CPU || prod.brand} • <span className="text-[#D4AF37] font-bold">KES {prod.price.toLocaleString()}</span>
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                      <button 
                        onClick={() => { onSelectProduct(prod); onClose(); }}
                        className="bg-[#1F1F1F] text-gray-300 hover:text-white text-xs px-3 py-1.5 rounded-lg border border-[#333]"
                      >
                        Specs
                      </button>
                      <button 
                        onClick={() => onAddToCart(prod)}
                        className="bg-[#D4AF37] text-black font-bold text-xs px-3 py-1.5 rounded-lg hover:bg-[#C5A059]"
                      >
                        Add
                      </button>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>

        </div>

      </div>
    </div>
  );
};
