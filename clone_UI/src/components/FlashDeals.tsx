import React, { useState, useEffect } from 'react';
import { Product } from '../types';
import { Zap, Clock, Star, ShoppingBag, Eye, Heart, Scale } from 'lucide-react';

interface FlashDealsProps {
  products: Product[];
  onSelectProduct: (p: Product) => void;
  onAddToCart: (p: Product) => void;
  onToggleWishlist: (p: Product) => void;
  onToggleCompare: (p: Product) => void;
  wishlistIds: string[];
  compareIds: string[];
}

export const FlashDeals: React.FC<FlashDealsProps> = ({
  products,
  onSelectProduct,
  onAddToCart,
  onToggleWishlist,
  onToggleCompare,
  wishlistIds,
  compareIds,
}) => {
  const flashProducts = products.filter(p => p.isFlashDeal).slice(0, 4);

  // Timer state for flash deal
  const [timeLeft, setTimeLeft] = useState({ hours: 14, minutes: 32, seconds: 45 });

  useEffect(() => {
    const interval = setInterval(() => {
      setTimeLeft(prev => {
        if (prev.seconds > 0) return { ...prev, seconds: prev.seconds - 1 };
        if (prev.minutes > 0) return { ...prev, minutes: 59, seconds: 59 };
        if (prev.hours > 0) return { hours: prev.hours - 1, minutes: 59, seconds: 59 };
        return { hours: 24, minutes: 0, seconds: 0 };
      });
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  if (flashProducts.length === 0) return null;

  return (
    <section className="py-10 bg-[#121212] border-y border-[#222222]">
      <div className="max-w-7xl mx-auto px-4">
        {/* Header with Countdown Timer */}
        <div className="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-4 border-b border-[#222222] gap-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-[#D4AF37] rounded-lg flex items-center justify-center text-black font-extrabold shadow-lg shadow-[#D4AF37]/20">
              <Zap className="w-6 h-6 fill-black" />
            </div>
            <div>
              <span className="text-xs font-bold text-[#D4AF37] uppercase tracking-widest font-mono">
                LIMITED TIME EXCLUSIVE OFFERS
              </span>
              <h2 className="text-2xl font-extrabold text-white font-mono">
                Flash Tech Sales
              </h2>
            </div>
          </div>

          {/* Countdown Clock Box */}
          <div className="flex items-center gap-3 bg-[#0B0B0B] border border-[#2D2D2D] p-2.5 rounded-xl">
            <Clock className="w-4 h-4 text-[#D4AF37]" />
            <span className="text-xs text-gray-400 font-semibold uppercase">Ends In:</span>
            <div className="flex items-center gap-1.5 font-mono text-sm font-extrabold text-white">
              <span className="bg-[#1E1E1E] text-[#D4AF37] px-2 py-1 rounded border border-[#333]">
                {String(timeLeft.hours).padStart(2, '0')}h
              </span>
              <span>:</span>
              <span className="bg-[#1E1E1E] text-[#D4AF37] px-2 py-1 rounded border border-[#333]">
                {String(timeLeft.minutes).padStart(2, '0')}m
              </span>
              <span>:</span>
              <span className="bg-[#1E1E1E] text-[#D4AF37] px-2 py-1 rounded border border-[#333]">
                {String(timeLeft.seconds).padStart(2, '0')}s
              </span>
            </div>
          </div>
        </div>

        {/* Flash Deals Product Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {flashProducts.map((prod) => {
            const isWishlisted = wishlistIds.includes(prod.id);
            const isCompared = compareIds.includes(prod.id);
            const soldPercent = Math.min(85, Math.floor((prod.stockCount / 30) * 100) + 15);

            return (
              <div 
                key={prod.id} 
                className="group bg-[#0B0B0B] border border-[#222222] hover:border-[#D4AF37] rounded-2xl p-4 transition-all duration-300 relative flex flex-col justify-between"
              >
                {/* Discount Badge */}
                <div className="absolute top-3 left-3 z-10 bg-[#D4AF37] text-black text-xs font-extrabold px-2.5 py-1 rounded-md font-mono shadow">
                  -{prod.discountPercent || 15}% OFF
                </div>

                {/* Quick Action Overlay Buttons */}
                <div className="absolute top-3 right-3 z-10 flex flex-col gap-2">
                  <button 
                    onClick={() => onToggleWishlist(prod)}
                    className={`p-2 rounded-lg border transition ${
                      isWishlisted 
                        ? 'bg-[#D4AF37] text-black border-[#D4AF37]' 
                        : 'bg-black/70 text-gray-300 hover:text-[#D4AF37] border-[#333]'
                    }`}
                    title="Add to Wishlist"
                  >
                    <Heart className="w-4 h-4 fill-current" />
                  </button>
                  <button 
                    onClick={() => onToggleCompare(prod)}
                    className={`p-2 rounded-lg border transition ${
                      isCompared 
                        ? 'bg-[#D4AF37] text-black border-[#D4AF37]' 
                        : 'bg-black/70 text-gray-300 hover:text-[#D4AF37] border-[#333]'
                    }`}
                    title="Compare Tech Specs"
                  >
                    <Scale className="w-4 h-4" />
                  </button>
                </div>

                {/* Product Image */}
                <div 
                  onClick={() => onSelectProduct(prod)}
                  className="cursor-pointer overflow-hidden rounded-xl bg-[#121212] border border-[#1F1F1F] p-4 mb-4 relative"
                >
                  <img 
                    src={prod.image} 
                    alt={prod.name} 
                    className="w-full h-44 object-contain group-hover:scale-105 transition-transform duration-300"
                  />
                  <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <span className="bg-[#D4AF37] text-black text-xs font-extrabold px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                      <Eye className="w-4 h-4 text-black" /> Quick View Specs
                    </span>
                  </div>
                </div>

                {/* Product Details */}
                <div>
                  <div className="flex items-center justify-between text-[11px] text-gray-400 mb-1">
                    <span className="uppercase font-semibold text-[#D4AF37]">{prod.brand}</span>
                    <div className="flex items-center gap-1 text-amber-400">
                      <Star className="w-3.5 h-3.5 fill-current text-[#D4AF37]" />
                      <span className="font-mono text-white text-xs">{prod.rating}</span>
                      <span className="text-gray-500">({prod.reviewCount})</span>
                    </div>
                  </div>

                  <h3 
                    onClick={() => onSelectProduct(prod)}
                    className="text-sm font-bold text-white hover:text-[#D4AF37] transition cursor-pointer line-clamp-2 min-h-[40px]"
                  >
                    {prod.name}
                  </h3>

                  {/* Pricing */}
                  <div className="mt-3 mb-3">
                    {prod.originalPrice ? (
                      <div className="text-xs font-mono">
                        <span className="line-through text-gray-500">was KES {prod.originalPrice.toLocaleString()}</span>
                        <div className="text-lg font-extrabold text-[#D4AF37]">
                          NOW KES {prod.price.toLocaleString()}
                        </div>
                      </div>
                    ) : (
                      <span className="text-lg font-extrabold text-[#D4AF37] font-mono">
                        KES {prod.price.toLocaleString()}
                      </span>
                    )}
                  </div>

                  {/* Stock Progress Bar */}
                  <div className="mb-4">
                    <div className="flex justify-between text-[10px] text-gray-400 mb-1 font-mono">
                      <span>Claimed: {soldPercent}%</span>
                      <span className="text-amber-400 font-bold">Only {prod.stockCount} left</span>
                    </div>
                    <div className="w-full bg-[#1A1A1A] h-1.5 rounded-full overflow-hidden border border-[#2D2D2D]">
                      <div 
                        className="bg-[#D4AF37] h-full rounded-full transition-all duration-500" 
                        style={{ width: `${soldPercent}%` }}
                      ></div>
                    </div>
                  </div>

                  {/* Add to Cart Button */}
                  <button 
                    onClick={() => onAddToCart(prod)}
                    className="w-full bg-[#1A1A1A] hover:bg-[#D4AF37] text-white hover:text-black font-bold text-xs py-2.5 rounded-xl border border-[#333] hover:border-[#D4AF37] transition flex items-center justify-center gap-2"
                  >
                    <ShoppingBag className="w-4 h-4" />
                    <span>Add to Cart</span>
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
};
