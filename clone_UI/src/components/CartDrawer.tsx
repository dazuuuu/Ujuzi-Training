import React, { useState } from 'react';
import { CartItem } from '../types';
import { X, Trash2, ShoppingBag, ArrowRight, Tag, ShieldCheck, Check } from 'lucide-react';

interface CartDrawerProps {
  isOpen: boolean;
  onClose: () => void;
  cartItems: CartItem[];
  onUpdateQuantity: (productId: string, qty: number) => void;
  onRemoveItem: (productId: string) => void;
  onClearCart: () => void;
  onProceedToCheckout: (discountAmount: number) => void;
}

export const CartDrawer: React.FC<CartDrawerProps> = ({
  isOpen,
  onClose,
  cartItems,
  onUpdateQuantity,
  onRemoveItem,
  onClearCart,
  onProceedToCheckout,
}) => {
  if (!isOpen) return null;

  const [promoCode, setPromoCode] = useState('');
  const [promoApplied, setPromoApplied] = useState(false);
  const [promoError, setPromoError] = useState('');

  const subtotal = cartItems.reduce((sum, item) => sum + item.product.price * item.quantity, 0);
  const discount = promoApplied ? 5000 : 0;
  const shipping = subtotal > 50000 || subtotal === 0 ? 0 : 1500;
  const total = Math.max(0, subtotal - discount + shipping);

  const handleApplyPromo = (e: React.FormEvent) => {
    e.preventDefault();
    if (promoCode.trim().toUpperCase() === 'TRUNKNET50') {
      setPromoApplied(true);
      setPromoError('');
    } else {
      setPromoError('Invalid promo code. Try TRUNKNET50');
    }
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-custom flex justify-end">
      <div className="w-full max-w-md bg-[#121212] border-l border-[#262626] h-full flex flex-col shadow-2xl relative">
        
        {/* Header */}
        <div className="p-5 bg-[#181818] border-b border-[#262626] flex items-center justify-between">
          <div className="flex items-center gap-2">
            <ShoppingBag className="w-5 h-5 text-[#D4AF37]" />
            <h2 className="text-base font-bold text-white font-mono uppercase">Your Shopping Cart</h2>
            <span className="bg-[#D4AF37] text-black text-xs font-bold px-2 py-0.5 rounded font-mono">
              {cartItems.reduce((acc, i) => acc + i.quantity, 0)}
            </span>
          </div>

          <button onClick={onClose} className="p-1.5 text-gray-400 hover:text-white rounded-lg">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Cart Item List */}
        <div className="flex-1 overflow-y-auto p-5 space-y-4">
          {cartItems.length === 0 ? (
            <div className="text-center py-16 text-gray-400 space-y-3">
              <ShoppingBag className="w-12 h-12 text-[#333] mx-auto" />
              <p className="text-sm font-semibold font-mono text-gray-300">Your Cart is Currently Empty</p>
              <p className="text-xs text-gray-500 max-w-xs mx-auto">
                Explore smartphones, laptops, custom workstations, and gaming accessories.
              </p>
              <button 
                onClick={onClose}
                className="mt-4 bg-[#D4AF37] text-black font-bold text-xs px-5 py-2.5 rounded-xl hover:bg-[#C5A059]"
              >
                Browse Electronics Catalog
              </button>
            </div>
          ) : (
            cartItems.map((item) => (
              <div 
                key={item.product.id}
                className="bg-[#0B0B0B] border border-[#222222] p-3 rounded-xl flex gap-3 items-center"
              >
                <img 
                  src={item.product.image} 
                  alt={item.product.name} 
                  className="w-16 h-16 object-contain rounded-lg bg-[#181818] p-1 border border-[#2A2A2A] shrink-0"
                />

                <div className="flex-1 min-w-0">
                  <h4 className="text-xs font-bold text-white truncate">{item.product.name}</h4>
                  <div className="text-xs font-mono font-bold text-[#D4AF37] mt-1">
                    KES {item.product.price.toLocaleString()}
                  </div>

                  {/* Quantity Control */}
                  <div className="flex items-center gap-2 mt-2">
                    <div className="flex items-center border border-[#333] rounded bg-[#181818] text-xs">
                      <button 
                        onClick={() => onUpdateQuantity(item.product.id, item.quantity - 1)}
                        className="px-2 py-0.5 text-gray-300 hover:text-white"
                      >
                        -
                      </button>
                      <span className="px-2 font-mono font-bold text-white">{item.quantity}</span>
                      <button 
                        onClick={() => onUpdateQuantity(item.product.id, item.quantity + 1)}
                        className="px-2 py-0.5 text-gray-300 hover:text-white"
                      >
                        +
                      </button>
                    </div>

                    <button 
                      onClick={() => onRemoveItem(item.product.id)}
                      className="text-xs text-red-400 hover:text-red-300 ml-auto p-1"
                      title="Remove item"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Footer Summary & Checkout Button */}
        {cartItems.length > 0 && (
          <div className="p-5 bg-[#181818] border-t border-[#262626] space-y-3">
            {/* Promo Code Form */}
            <form onSubmit={handleApplyPromo} className="flex gap-2">
              <div className="relative flex-1">
                <input 
                  type="text" 
                  placeholder="Promo Code (TRUNKNET50)" 
                  value={promoCode}
                  onChange={(e) => setPromoCode(e.target.value)}
                  disabled={promoApplied}
                  className="w-full bg-[#0B0B0B] text-white text-xs pl-8 pr-2 py-2 rounded-lg border border-[#333] focus:border-[#D4AF37] focus:outline-none uppercase font-mono"
                />
                <Tag className="w-3.5 h-3.5 text-gray-500 absolute left-2.5 top-2.5" />
              </div>
              <button 
                type="submit"
                disabled={promoApplied}
                className="bg-[#2A2A2A] hover:bg-[#333] text-white font-bold text-xs px-3 py-2 rounded-lg transition border border-[#444]"
              >
                {promoApplied ? 'Applied' : 'Apply'}
              </button>
            </form>

            {promoApplied && (
              <div className="text-[11px] text-emerald-400 flex items-center gap-1 font-mono">
                <Check className="w-3.5 h-3.5" /> $50 Flash Promo Discount Applied!
              </div>
            )}
            {promoError && (
              <div className="text-[11px] text-red-400 font-mono">
                {promoError}
              </div>
            )}

            {/* Calculations Breakdown */}
            <div className="space-y-1.5 text-xs text-gray-300 pt-2 border-t border-[#262626]">
              <div className="flex justify-between font-mono">
                <span>Subtotal</span>
                <span>KES {subtotal.toLocaleString()}</span>
              </div>
              {promoApplied && (
                <div className="flex justify-between font-mono text-emerald-400">
                  <span>Discount (TRUNKNET50)</span>
                  <span>-KES 5,000</span>
                </div>
              )}
              <div className="flex justify-between font-mono">
                <span>Express Shipping</span>
                <span>{shipping === 0 ? 'FREE' : `KES ${shipping.toLocaleString()}`}</span>
              </div>
              <div className="flex justify-between font-mono font-extrabold text-sm text-white pt-2 border-t border-[#333]">
                <span>Total Amount</span>
                <span className="text-[#D4AF37]">KES {total.toLocaleString()}</span>
              </div>
            </div>

            <button 
              onClick={() => {
                onProceedToCheckout(discount);
              }}
              className="w-full bg-[#D4AF37] hover:bg-[#C5A059] text-black font-extrabold text-xs py-3.5 rounded-xl transition flex items-center justify-center gap-2 uppercase tracking-wide shadow-lg shadow-[#D4AF37]/20"
            >
              <span>Proceed to Checkout</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>
        )}

      </div>
    </div>
  );
};
