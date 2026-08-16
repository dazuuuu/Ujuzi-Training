import React from 'react';
import { Product } from '../types';
import { X, Scale, Trash2, ShoppingBag, Check } from 'lucide-react';

interface CompareModalProps {
  isOpen: boolean;
  onClose: () => void;
  products: Product[];
  onRemoveFromCompare: (id: string) => void;
  onAddToCart: (p: Product) => void;
}

export const CompareModal: React.FC<CompareModalProps> = ({
  isOpen,
  onClose,
  products,
  onRemoveFromCompare,
  onAddToCart,
}) => {
  if (!isOpen) return null;

  // Gather unique spec keys across selected products
  const allSpecKeys: string[] = Array.from(
    new Set(products.flatMap(p => Object.keys(p.specs)))
  );

  return (
    <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-custom flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-[#121212] border border-[#2D2D2D] w-full max-w-5xl rounded-2xl overflow-hidden shadow-2xl relative my-auto max-h-[90vh] flex flex-col">
        
        {/* Header */}
        <div className="bg-[#181818] px-6 py-4 border-b border-[#262626] flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Scale className="w-5 h-5 text-[#D4AF37]" />
            <h2 className="text-base font-bold text-white font-mono uppercase">
              Tech Specs Side-by-Side Comparison ({products.length})
            </h2>
          </div>

          <button onClick={onClose} className="p-1.5 text-gray-400 hover:text-white rounded-lg">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 overflow-y-auto flex-1">
          {products.length === 0 ? (
            <div className="text-center py-16 text-gray-400 space-y-3">
              <Scale className="w-12 h-12 text-[#333] mx-auto" />
              <p className="text-sm font-semibold font-mono text-gray-300">No Electronics Selected for Comparison</p>
              <p className="text-xs text-gray-500 max-w-xs mx-auto">
                Click the compare icon on any product card to compare processor speeds, RAM, displays, and GPU specs.
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full border-collapse text-left text-xs">
                <thead>
                  <tr className="border-b border-[#262626]">
                    <th className="p-3 w-40 text-gray-400 font-mono uppercase bg-[#0B0B0B]">Product Info</th>
                    {products.map(p => (
                      <th key={p.id} className="p-3 min-w-[200px] bg-[#181818] border-l border-[#262626] align-top">
                        <div className="flex flex-col h-full justify-between gap-2">
                          <div className="flex justify-between items-start">
                            <span className="text-[10px] bg-[#D4AF37] text-black font-bold px-1.5 py-0.5 rounded uppercase font-mono">
                              {p.brand}
                            </span>
                            <button 
                              onClick={() => onRemoveFromCompare(p.id)}
                              className="text-gray-400 hover:text-red-400 p-1"
                              title="Remove"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          </div>

                          <img src={p.image} alt={p.name} className="w-20 h-20 object-contain mx-auto my-2" />

                          <h4 className="font-bold text-white line-clamp-2 min-h-[32px]">{p.name}</h4>

                          <div className="font-mono my-1">
                            {p.originalPrice ? (
                              <div>
                                <span className="text-[10px] text-gray-500 line-through">was KES {p.originalPrice.toLocaleString()}</span>
                                <div className="text-sm font-extrabold text-[#D4AF37]">
                                  NOW KES {p.price.toLocaleString()}
                                </div>
                              </div>
                            ) : (
                              <div className="text-sm font-extrabold text-[#D4AF37]">
                                KES {p.price.toLocaleString()}
                              </div>
                            )}
                          </div>

                          <button 
                            onClick={() => onAddToCart(p)}
                            className="w-full bg-[#D4AF37] hover:bg-[#C5A059] text-black font-bold text-[11px] py-2 rounded-lg transition flex items-center justify-center gap-1"
                          >
                            <ShoppingBag className="w-3.5 h-3.5" /> Add to Cart
                          </button>
                        </div>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#1F1F1F]">
                  <tr className="bg-[#0B0B0B]">
                    <td className="p-3 font-bold text-gray-300 font-mono">Rating</td>
                    {products.map(p => (
                      <td key={p.id} className="p-3 border-l border-[#262626] text-[#D4AF37] font-bold font-mono">
                        ⭐ {p.rating} / 5.0 ({p.reviewCount})
                      </td>
                    ))}
                  </tr>

                  <tr className="bg-[#0B0B0B]">
                    <td className="p-3 font-bold text-gray-300 font-mono">Warranty</td>
                    {products.map(p => (
                      <td key={p.id} className="p-3 border-l border-[#262626] text-gray-300 font-medium">
                        {p.warranty}
                      </td>
                    ))}
                  </tr>

                  {allSpecKeys.map(specKey => (
                    <tr key={specKey} className="hover:bg-[#161616]">
                      <td className="p-3 font-bold text-gray-400 font-mono uppercase bg-[#0B0B0B]">
                        {specKey}
                      </td>
                      {products.map(p => (
                        <td key={p.id} className="p-3 border-l border-[#262626] text-gray-200">
                          {p.specs[specKey] || '—'}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

      </div>
    </div>
  );
};
