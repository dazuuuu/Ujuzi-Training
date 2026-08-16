import React from 'react';
import { Product } from '../types';
import { Star, ShoppingBag, Eye, Heart, Scale, ShieldCheck, Check } from 'lucide-react';

interface ProductCardProps {
  product: Product;
  onSelectProduct: (p: Product) => void;
  onAddToCart: (p: Product) => void;
  onToggleWishlist: (p: Product) => void;
  onToggleCompare: (p: Product) => void;
  isWishlisted: boolean;
  isCompared: boolean;
}

export const ProductCard: React.FC<ProductCardProps> = ({
  product,
  onSelectProduct,
  onAddToCart,
  onToggleWishlist,
  onToggleCompare,
  isWishlisted,
  isCompared,
}) => {
  return (
    <div className="group bg-[#121212] border border-[#222222] hover:border-[#D4AF37] rounded-2xl p-4 transition-all duration-300 relative flex flex-col justify-between shadow-lg hover:shadow-[#D4AF37]/10">
      
      {/* Badges */}
      <div className="absolute top-3 left-3 z-10 flex flex-col gap-1 items-start">
        {product.discountPercent && (
          <span className="bg-[#D4AF37] text-black text-[10px] font-extrabold px-2 py-0.5 rounded font-mono">
            -{product.discountPercent}% OFF
          </span>
        )}
        {product.isNewArrival && (
          <span className="bg-white text-black text-[10px] font-extrabold px-2 py-0.5 rounded uppercase font-mono">
            NEW
          </span>
        )}
      </div>

      {/* Top Action Buttons (Wishlist & Compare) */}
      <div className="absolute top-3 right-3 z-10 flex flex-col gap-1.5">
        <button 
          onClick={(e) => { e.stopPropagation(); onToggleWishlist(product); }}
          className={`p-2 rounded-lg border transition ${
            isWishlisted 
              ? 'bg-[#D4AF37] text-black border-[#D4AF37]' 
              : 'bg-black/60 text-gray-300 hover:text-[#D4AF37] border-[#2A2A2A]'
          }`}
          title="Wishlist"
          aria-label="Wishlist"
        >
          <Heart className="w-3.5 h-3.5 fill-current" />
        </button>

        <button 
          onClick={(e) => { e.stopPropagation(); onToggleCompare(product); }}
          className={`p-2 rounded-lg border transition ${
            isCompared 
              ? 'bg-[#D4AF37] text-black border-[#D4AF37]' 
              : 'bg-black/60 text-gray-300 hover:text-[#D4AF37] border-[#2A2A2A]'
          }`}
          title="Compare Specs"
          aria-label="Compare"
        >
          <Scale className="w-3.5 h-3.5" />
        </button>
      </div>

      {/* Product Image Box */}
      <div 
        onClick={() => onSelectProduct(product)}
        className="cursor-pointer overflow-hidden rounded-xl bg-[#0B0B0B] border border-[#1F1F1F] p-4 mb-3 relative group"
      >
        <img 
          src={product.image} 
          alt={product.name} 
          className="w-full h-44 object-contain group-hover:scale-105 transition-transform duration-300"
        />
        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
          <span className="bg-[#D4AF37] text-black text-xs font-extrabold px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow">
            <Eye className="w-4 h-4 text-black" /> View Tech Specs
          </span>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 flex flex-col justify-between">
        <div>
          {/* Brand & Ratings */}
          <div className="flex items-center justify-between text-[11px] text-gray-400 mb-1">
            <span className="uppercase font-bold text-[#D4AF37] tracking-wider">{product.brand}</span>
            <div className="flex items-center gap-1 text-[#D4AF37]">
              <Star className="w-3.5 h-3.5 fill-current" />
              <span className="font-mono text-white text-xs">{product.rating}</span>
              <span className="text-gray-500">({product.reviewCount})</span>
            </div>
          </div>

          {/* Product Name */}
          <h3 
            onClick={() => onSelectProduct(product)}
            className="text-sm font-bold text-white hover:text-[#D4AF37] transition cursor-pointer line-clamp-2 min-h-[40px] leading-snug"
          >
            {product.name}
          </h3>

          {/* Key spec chips preview */}
          <div className="flex flex-wrap gap-1 my-2">
            {product.tags.slice(0, 2).map((tag, i) => (
              <span key={i} className="text-[10px] bg-[#181818] border border-[#262626] text-gray-300 px-1.5 py-0.5 rounded font-mono">
                {tag}
              </span>
            ))}
          </div>
        </div>

        <div>
          {/* Warranty reassurance tag */}
          <div className="flex items-center gap-1 text-[10px] text-gray-400 my-2">
            <ShieldCheck className="w-3 h-3 text-[#D4AF37]" />
            <span className="truncate">{product.warranty.split('+')[0]}</span>
          </div>

          {/* Price & Add To Cart Row */}
          <div className="flex flex-col gap-2 pt-2 border-t border-[#1F1F1F] mt-2">
            <div>
              {product.originalPrice ? (
                <div className="text-xs text-gray-400 font-mono">
                  <span className="line-through text-gray-500">was KES {product.originalPrice.toLocaleString()}</span>
                  <span className="text-[#D4AF37] font-extrabold text-sm sm:text-base ml-1.5">
                    NOW KES {product.price.toLocaleString()}
                  </span>
                </div>
              ) : (
                <div className="text-sm sm:text-base font-extrabold text-[#D4AF37] font-mono">
                  KES {product.price.toLocaleString()}
                </div>
              )}
            </div>

            <button 
              onClick={() => onAddToCart(product)}
              className="w-full bg-[#D4AF37] hover:bg-[#C5A059] text-black font-extrabold text-xs py-2 rounded-xl transition flex items-center justify-center gap-1.5 shadow"
            >
              <ShoppingBag className="w-4 h-4 stroke-[2.5]" />
              <span>Add to Cart</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};
