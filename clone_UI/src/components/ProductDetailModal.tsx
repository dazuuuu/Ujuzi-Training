import React, { useState } from 'react';
import { Product } from '../types';
import { 
  X, 
  Star, 
  ShoppingBag, 
  ShieldCheck, 
  Truck, 
  Heart, 
  Scale, 
  Check, 
  ChevronRight, 
  RotateCcw,
  Cpu,
  Info
} from 'lucide-react';
import { SAMPLE_REVIEWS } from '../data/stores';

interface ProductDetailModalProps {
  product: Product | null;
  onClose: () => void;
  onAddToCart: (p: Product, qty: number) => void;
  onBuyNow: (p: Product, qty: number) => void;
  onToggleWishlist: (p: Product) => void;
  onToggleCompare: (p: Product) => void;
  isWishlisted: boolean;
  isCompared: boolean;
}

export const ProductDetailModal: React.FC<ProductDetailModalProps> = ({
  product,
  onClose,
  onAddToCart,
  onBuyNow,
  onToggleWishlist,
  onToggleCompare,
  isWishlisted,
  isCompared,
}) => {
  if (!product) return null;

  const [activeImage, setActiveImage] = useState<string>(product.image);
  const [quantity, setQuantity] = useState<number>(1);
  const [activeTab, setActiveTab] = useState<'specs' | 'features' | 'reviews'>('specs');

  // Review state
  const [reviews, setReviews] = useState(SAMPLE_REVIEWS);
  const [newReviewComment, setNewReviewComment] = useState('');
  const [newReviewName, setNewReviewName] = useState('');
  const [newReviewRating, setNewReviewRating] = useState(5);

  const handleAddReview = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newReviewComment.trim() || !newReviewName.trim()) return;
    const rev = {
      id: `rev-${Date.now()}`,
      userName: newReviewName,
      userLocation: 'Verified Buyer',
      rating: newReviewRating,
      date: 'Just now',
      comment: newReviewComment,
      verifiedPurchase: true,
    };
    setReviews([rev, ...reviews]);
    setNewReviewComment('');
    setNewReviewName('');
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-custom flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div className="bg-[#121212] border border-[#2D2D2D] w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl relative my-auto max-h-[92vh] flex flex-col">
        
        {/* Modal Header */}
        <div className="bg-[#181818] px-6 py-4 border-b border-[#262626] flex items-center justify-between shrink-0">
          <div className="flex items-center gap-2">
            <span className="bg-[#D4AF37] text-black font-extrabold text-[10px] px-2 py-0.5 rounded font-mono uppercase">
              {product.brand}
            </span>
            <span className="text-xs text-gray-400 font-mono">
              SKU: {product.sku}
            </span>
          </div>

          <button 
            onClick={onClose}
            className="p-1.5 text-gray-400 hover:text-white hover:bg-[#222] rounded-lg transition"
            aria-label="Close"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Modal Scrollable Body */}
        <div className="p-6 overflow-y-auto space-y-8 flex-1">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            {/* LEFT: IMAGE GALLERY */}
            <div className="space-y-4">
              <div className="bg-[#0B0B0B] border border-[#262626] rounded-2xl p-6 flex items-center justify-center relative min-h-[300px]">
                <img 
                  src={activeImage} 
                  alt={product.name} 
                  className="max-h-[280px] w-full object-contain"
                />
                {product.discountPercent && (
                  <span className="absolute top-3 left-3 bg-[#D4AF37] text-black text-xs font-extrabold px-2.5 py-1 rounded font-mono">
                    -{product.discountPercent}% OFF
                  </span>
                )}
              </div>

              {/* Gallery Thumbnails */}
              <div className="flex items-center gap-3 overflow-x-auto pb-2">
                {product.galleryImages.map((imgUrl, i) => (
                  <button
                    key={i}
                    onClick={() => setActiveImage(imgUrl)}
                    className={`w-16 h-16 rounded-xl border p-1 bg-[#0B0B0B] shrink-0 transition ${
                      activeImage === imgUrl ? 'border-[#D4AF37] ring-1 ring-[#D4AF37]' : 'border-[#262626] hover:border-gray-500'
                    }`}
                  >
                    <img src={imgUrl} alt="" className="w-full h-full object-contain" />
                  </button>
                ))}
              </div>

              {/* Service Badges */}
              <div className="grid grid-cols-2 gap-2 text-[11px] text-gray-300 bg-[#181818] border border-[#222] p-3 rounded-xl">
                <div className="flex items-center gap-1.5">
                  <ShieldCheck className="w-4 h-4 text-[#D4AF37]" />
                  <span>2-Year Warranty</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <Truck className="w-4 h-4 text-[#D4AF37]" />
                  <span>Same-Day Express</span>
                </div>
              </div>
            </div>

            {/* RIGHT: BUY BOX & OVERVIEW */}
            <div className="flex flex-col justify-between">
              <div>
                <h1 className="text-xl md:text-2xl font-extrabold text-white font-mono leading-tight">
                  {product.name}
                </h1>

                {/* Rating & Stock */}
                <div className="flex items-center gap-4 my-3 text-xs">
                  <div className="flex items-center gap-1 text-[#D4AF37]">
                    <Star className="w-4 h-4 fill-current" />
                    <span className="font-bold text-white font-mono">{product.rating}</span>
                    <span className="text-gray-400">({product.reviewCount} customer reviews)</span>
                  </div>
                  <span className="text-gray-600">|</span>
                  <span className="text-emerald-400 font-semibold flex items-center gap-1 font-mono">
                    <Check className="w-3.5 h-3.5" /> In Stock ({product.stockCount} units)
                  </span>
                </div>

                {/* Price Display */}
                <div className="bg-[#181818] border border-[#262626] p-4 rounded-xl my-4">
                  {product.originalPrice ? (
                    <div className="font-mono">
                      <span className="text-xs text-gray-500 line-through">was KES {(product.originalPrice * quantity).toLocaleString()}</span>
                      <div className="text-xl sm:text-2xl font-extrabold text-[#D4AF37]">
                        NOW KES {(product.price * quantity).toLocaleString()}
                      </div>
                    </div>
                  ) : (
                    <span className="text-xl sm:text-2xl font-extrabold text-[#D4AF37] font-mono">
                      KES {(product.price * quantity).toLocaleString()}
                    </span>
                  )}
                  {quantity > 1 && (
                    <div className="text-xs text-gray-400 font-mono mt-1">
                      (KES {product.price.toLocaleString()}/unit)
                    </div>
                  )}
                </div>

                <p className="text-xs text-gray-300 leading-relaxed mb-4">
                  {product.description}
                </p>

                {/* Quantity Picker */}
                <div className="flex items-center gap-4 mb-6">
                  <span className="text-xs font-bold text-gray-300 uppercase font-mono">Quantity:</span>
                  <div className="flex items-center border border-[#333] rounded-lg bg-[#0B0B0B]">
                    <button 
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                      className="px-3 py-1.5 text-gray-300 hover:text-white font-bold"
                    >
                      -
                    </button>
                    <span className="px-4 text-xs font-mono font-bold text-[#D4AF37]">
                      {quantity}
                    </span>
                    <button 
                      onClick={() => setQuantity(quantity + 1)}
                      className="px-3 py-1.5 text-gray-300 hover:text-white font-bold"
                    >
                      +
                    </button>
                  </div>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="space-y-3 pt-4 border-t border-[#262626]">
                <div className="grid grid-cols-2 gap-3">
                  <button 
                    onClick={() => onAddToCart(product, quantity)}
                    className="bg-[#1F1F1F] hover:bg-[#2A2A2A] text-white font-bold text-xs py-3.5 rounded-xl border border-[#333] hover:border-[#D4AF37] transition flex items-center justify-center gap-2"
                  >
                    <ShoppingBag className="w-4 h-4 text-[#D4AF37]" />
                    <span>Add to Cart</span>
                  </button>

                  <button 
                    onClick={() => onBuyNow(product, quantity)}
                    className="bg-[#D4AF37] hover:bg-[#C5A059] text-black font-extrabold text-xs py-3.5 rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-[#D4AF37]/20 uppercase tracking-wide"
                  >
                    <span>Instant Checkout</span>
                  </button>
                </div>

                <div className="flex justify-between items-center text-xs text-gray-400 pt-2">
                  <button 
                    onClick={() => onToggleWishlist(product)}
                    className={`flex items-center gap-1.5 hover:text-[#D4AF37] transition ${isWishlisted ? 'text-[#D4AF37]' : ''}`}
                  >
                    <Heart className="w-4 h-4 fill-current" />
                    <span>{isWishlisted ? 'In Wishlist' : 'Add to Wishlist'}</span>
                  </button>

                  <button 
                    onClick={() => onToggleCompare(product)}
                    className={`flex items-center gap-1.5 hover:text-[#D4AF37] transition ${isCompared ? 'text-[#D4AF37]' : ''}`}
                  >
                    <Scale className="w-4 h-4" />
                    <span>{isCompared ? 'In Compare List' : 'Compare Tech Specs'}</span>
                  </button>
                </div>
              </div>
            </div>

          </div>

          {/* LOWER TABS: SPECS / FEATURES / REVIEWS */}
          <div className="pt-6 border-t border-[#262626]">
            <div className="flex border-b border-[#262626] gap-6 text-xs font-bold font-mono uppercase">
              <button
                onClick={() => setActiveTab('specs')}
                className={`pb-3 border-b-2 transition ${
                  activeTab === 'specs' 
                    ? 'border-[#D4AF37] text-[#D4AF37]' 
                    : 'border-transparent text-gray-400 hover:text-white'
                }`}
              >
                Technical Specifications
              </button>
              <button
                onClick={() => setActiveTab('features')}
                className={`pb-3 border-b-2 transition ${
                  activeTab === 'features' 
                    ? 'border-[#D4AF37] text-[#D4AF37]' 
                    : 'border-transparent text-gray-400 hover:text-white'
                }`}
              >
                Key Features
              </button>
              <button
                onClick={() => setActiveTab('reviews')}
                className={`pb-3 border-b-2 transition ${
                  activeTab === 'reviews' 
                    ? 'border-[#D4AF37] text-[#D4AF37]' 
                    : 'border-transparent text-gray-400 hover:text-white'
                }`}
              >
                Reviews ({reviews.length})
              </button>
            </div>

            {/* TAB CONTENT: SPECS TABLE */}
            {activeTab === 'specs' && (
              <div className="py-4">
                <div className="bg-[#0B0B0B] border border-[#222] rounded-xl overflow-hidden divide-y divide-[#1F1F1F]">
                  {Object.entries(product.specs).map(([key, val], idx) => (
                    <div key={idx} className="grid grid-cols-3 p-3 text-xs">
                      <span className="font-bold text-gray-400 font-mono uppercase">{key}</span>
                      <span className="col-span-2 text-white font-medium">{val}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* TAB CONTENT: KEY FEATURES */}
            {activeTab === 'features' && (
              <div className="py-4">
                <ul className="space-y-2">
                  {product.keyFeatures.map((feat, idx) => (
                    <li key={idx} className="text-xs text-gray-300 flex items-start gap-2 bg-[#181818] p-3 rounded-lg border border-[#222]">
                      <Check className="w-4 h-4 text-[#D4AF37] shrink-0 mt-0.5" />
                      <span>{feat}</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {/* TAB CONTENT: REVIEWS */}
            {activeTab === 'reviews' && (
              <div className="py-4 space-y-6">
                {/* Add Review Form */}
                <form onSubmit={handleAddReview} className="bg-[#181818] p-4 rounded-xl border border-[#262626] space-y-3">
                  <h4 className="text-xs font-bold text-[#D4AF37] font-mono uppercase">Write a Verified Review</h4>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input 
                      type="text" 
                      placeholder="Your Full Name" 
                      value={newReviewName}
                      onChange={(e) => setNewReviewName(e.target.value)}
                      className="bg-[#0B0B0B] text-white text-xs p-2.5 rounded border border-[#333] focus:border-[#D4AF37] focus:outline-none"
                    />
                    <select 
                      value={newReviewRating}
                      onChange={(e) => setNewReviewRating(Number(e.target.value))}
                      className="bg-[#0B0B0B] text-white text-xs p-2.5 rounded border border-[#333] focus:border-[#D4AF37]"
                    >
                      <option value={5}>⭐⭐⭐⭐⭐ (5/5 Stars)</option>
                      <option value={4}>⭐⭐⭐⭐ (4/5 Stars)</option>
                      <option value={3}>⭐⭐⭐ (3/5 Stars)</option>
                    </select>
                  </div>
                  <textarea 
                    placeholder="Share your experience with this tech product..." 
                    value={newReviewComment}
                    onChange={(e) => setNewReviewComment(e.target.value)}
                    rows={2}
                    className="w-full bg-[#0B0B0B] text-white text-xs p-2.5 rounded border border-[#333] focus:border-[#D4AF37] focus:outline-none"
                  />
                  <button 
                    type="submit" 
                    className="bg-[#D4AF37] text-black font-bold text-xs px-4 py-2 rounded-lg hover:bg-[#C5A059]"
                  >
                    Submit Review
                  </button>
                </form>

                {/* Review List */}
                <div className="space-y-3">
                  {reviews.map((rev) => (
                    <div key={rev.id} className="bg-[#0B0B0B] p-4 rounded-xl border border-[#222] text-xs">
                      <div className="flex items-center justify-between mb-1">
                        <span className="font-bold text-white font-mono">{rev.userName}</span>
                        <div className="flex text-[#D4AF37]">
                          {Array.from({ length: rev.rating }).map((_, i) => (
                            <Star key={i} className="w-3 h-3 fill-current" />
                          ))}
                        </div>
                      </div>
                      <p className="text-gray-300 mt-1">{rev.comment}</p>
                      <div className="text-[10px] text-gray-500 mt-2 font-mono">
                        {rev.date} • Verified Purchase
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

        </div>

      </div>
    </div>
  );
};
