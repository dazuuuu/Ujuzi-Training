import React, { useState } from 'react';
import { CartItem, Order } from '../types';
import { X, CheckCircle2, ShieldCheck, Truck, CreditCard, Smartphone, Building2, Copy, Check } from 'lucide-react';

interface CheckoutModalProps {
  isOpen: boolean;
  onClose: () => void;
  cartItems: CartItem[];
  appliedDiscount: number;
  onOrderComplete: (order: Order) => void;
}

export const CheckoutModal: React.FC<CheckoutModalProps> = ({
  isOpen,
  onClose,
  cartItems,
  appliedDiscount,
  onOrderComplete,
}) => {
  if (!isOpen) return null;

  const [step, setStep] = useState<'details' | 'payment' | 'confirmation'>('details');

  // Customer form details
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [city, setCity] = useState('Nairobi');
  const [paymentMethod, setPaymentMethod] = useState<'card' | 'mpesa' | 'bank'>('mpesa');
  const [mpesaNumber, setMpesaNumber] = useState('');
  const [copied, setCopied] = useState(false);

  // Completed order state
  const [completedOrder, setCompletedOrder] = useState<Order | null>(null);

  const subtotal = cartItems.reduce((acc, item) => acc + item.product.price * item.quantity, 0);
  const shipping = subtotal > 50000 ? 0 : 1500;
  const total = Math.max(0, subtotal - appliedDiscount + shipping);

  const handleProceedToPayment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name || !email || !phone || !address) return;
    setStep('payment');
  };

  const handleFinalizeOrder = (e: React.FormEvent) => {
    e.preventDefault();
    const orderRef = `TRK-${Math.floor(100000 + Math.random() * 900000)}`;
    const trackingRef = `TRK-EXP-${Math.floor(1000 + Math.random() * 9000)}`;

    const newOrder: Order = {
      id: orderRef,
      date: new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }),
      items: cartItems,
      subtotal,
      discount: appliedDiscount,
      shipping,
      total,
      status: 'Processing',
      trackingNumber: trackingRef,
      customerName: name,
      customerEmail: email,
      customerPhone: phone,
      shippingAddress: `${address}, ${city}`,
      paymentMethod: paymentMethod === 'mpesa' ? `M-Pesa (${phone})` : paymentMethod === 'card' ? 'Credit Card' : 'Bank Transfer',
    };

    setCompletedOrder(newOrder);
    onOrderComplete(newOrder);
    setStep('confirmation');
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-custom flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-[#121212] border border-[#2D2D2D] w-full max-w-2xl rounded-2xl overflow-hidden shadow-2xl relative my-auto">
        
        {/* Header */}
        <div className="bg-[#181818] px-6 py-4 border-b border-[#262626] flex items-center justify-between">
          <div className="flex items-center gap-2">
            <ShieldCheck className="w-5 h-5 text-[#D4AF37]" />
            <h2 className="text-base font-bold text-white font-mono uppercase">
              {step === 'confirmation' ? 'Order Confirmed' : 'Secure Checkout'}
            </h2>
          </div>

          {step !== 'confirmation' && (
            <button onClick={onClose} className="p-1 text-gray-400 hover:text-white">
              <X className="w-5 h-5" />
            </button>
          )}
        </div>

        {/* STEP 1: SHIPPING DETAILS */}
        {step === 'details' && (
          <form onSubmit={handleProceedToPayment} className="p-6 space-y-4">
            <h3 className="text-xs font-bold text-[#D4AF37] font-mono uppercase">1. Customer Shipping Information</h3>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="text-[11px] font-bold text-gray-300 block mb-1">Full Name *</label>
                <input 
                  type="text" 
                  required
                  placeholder="e.g. Alex Kibet" 
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="w-full bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333] focus:border-[#D4AF37] focus:outline-none"
                />
              </div>

              <div>
                <label className="text-[11px] font-bold text-gray-300 block mb-1">Email Address *</label>
                <input 
                  type="email" 
                  required
                  placeholder="alex@domain.com" 
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333] focus:border-[#D4AF37] focus:outline-none"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="text-[11px] font-bold text-gray-300 block mb-1">Phone Number (For Delivery SMS) *</label>
                <input 
                  type="tel" 
                  required
                  placeholder="+254 700 000000" 
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  className="w-full bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333] focus:border-[#D4AF37] focus:outline-none"
                />
              </div>

              <div>
                <label className="text-[11px] font-bold text-gray-300 block mb-1">City / Region *</label>
                <select 
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                  className="w-full bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333] focus:border-[#D4AF37]"
                >
                  <option value="Nairobi">Nairobi</option>
                  <option value="Mombasa">Mombasa</option>
                  <option value="Kisumu">Kisumu</option>
                  <option value="Eldoret">Eldoret</option>
                  <option value="Nakuru">Nakuru</option>
                  <option value="International Express">International Express Delivery</option>
                </select>
              </div>
            </div>

            <div>
              <label className="text-[11px] font-bold text-gray-300 block mb-1">Physical Delivery Street Address *</label>
              <textarea 
                required
                rows={2}
                placeholder="Building Name, Floor, Suite / House No, Street Address..." 
                value={address}
                onChange={(e) => setAddress(e.target.value)}
                className="w-full bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333] focus:border-[#D4AF37] focus:outline-none"
              />
            </div>

            <div className="bg-[#181818] p-4 rounded-xl border border-[#262626] flex justify-between items-center text-xs">
              <span className="text-gray-400">Total Payable Amount:</span>
              <span className="text-lg font-extrabold text-[#D4AF37] font-mono">KES {total.toLocaleString()}</span>
            </div>

            <button 
              type="submit" 
              className="w-full bg-[#D4AF37] hover:bg-[#C5A059] text-black font-extrabold text-xs py-3.5 rounded-xl uppercase tracking-wider transition"
            >
              Continue to Payment Method →
            </button>
          </form>
        )}

        {/* STEP 2: PAYMENT METHOD */}
        {step === 'payment' && (
          <form onSubmit={handleFinalizeOrder} className="p-6 space-y-4">
            <h3 className="text-xs font-bold text-[#D4AF37] font-mono uppercase">2. Choose Payment Gateway</h3>

            <div className="grid grid-cols-3 gap-3">
              <button
                type="button"
                onClick={() => setPaymentMethod('mpesa')}
                className={`p-3 rounded-xl border text-center text-xs font-bold flex flex-col items-center gap-1.5 transition ${
                  paymentMethod === 'mpesa' 
                    ? 'border-[#D4AF37] bg-[#1E1E1E] text-[#D4AF37]' 
                    : 'border-[#2D2D2D] bg-[#0B0B0B] text-gray-400'
                }`}
              >
                <Smartphone className="w-5 h-5 text-[#D4AF37]" />
                <span>M-Pesa / Mobile</span>
              </button>

              <button
                type="button"
                onClick={() => setPaymentMethod('card')}
                className={`p-3 rounded-xl border text-center text-xs font-bold flex flex-col items-center gap-1.5 transition ${
                  paymentMethod === 'card' 
                    ? 'border-[#D4AF37] bg-[#1E1E1E] text-[#D4AF37]' 
                    : 'border-[#2D2D2D] bg-[#0B0B0B] text-gray-400'
                }`}
              >
                <CreditCard className="w-5 h-5 text-[#D4AF37]" />
                <span>Credit / Debit Card</span>
              </button>

              <button
                type="button"
                onClick={() => setPaymentMethod('bank')}
                className={`p-3 rounded-xl border text-center text-xs font-bold flex flex-col items-center gap-1.5 transition ${
                  paymentMethod === 'bank' 
                    ? 'border-[#D4AF37] bg-[#1E1E1E] text-[#D4AF37]' 
                    : 'border-[#2D2D2D] bg-[#0B0B0B] text-gray-400'
                }`}
              >
                <Building2 className="w-5 h-5 text-[#D4AF37]" />
                <span>Bank Transfer</span>
              </button>
            </div>

            {/* Sub-form based on choice */}
            {paymentMethod === 'mpesa' && (
              <div className="bg-[#181818] p-4 rounded-xl border border-[#2A2A2A] space-y-3 text-xs">
                <p className="text-gray-300 font-semibold">
                  Enter your mobile number below. You will receive an instant STK push prompt on your handset to authorize <span className="text-[#D4AF37] font-bold font-mono">KES {total.toLocaleString()}</span>.
                </p>
                <input 
                  type="tel" 
                  defaultValue={phone}
                  placeholder="254700000000" 
                  className="w-full bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333] focus:border-[#D4AF37]"
                />
              </div>
            )}

            {paymentMethod === 'card' && (
              <div className="bg-[#181818] p-4 rounded-xl border border-[#2A2A2A] space-y-3 text-xs">
                <input 
                  type="text" 
                  placeholder="Card Number (4000 0000 0000 0000)" 
                  className="w-full bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333]"
                />
                <div className="grid grid-cols-2 gap-2">
                  <input 
                    type="text" 
                    placeholder="MM / YY" 
                    className="bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333]"
                  />
                  <input 
                    type="text" 
                    placeholder="CVV" 
                    className="bg-[#0B0B0B] text-white text-xs p-3 rounded-lg border border-[#333]"
                  />
                </div>
              </div>
            )}

            {paymentMethod === 'bank' && (
              <div className="bg-[#181818] p-4 rounded-xl border border-[#2A2A2A] text-xs space-y-1 font-mono text-gray-300">
                <p><span className="text-gray-400">Bank:</span> Stanbic Bank Kenya</p>
                <p><span className="text-gray-400">Account Name:</span> TRUNKNET TECHNOLOGIES LTD</p>
                <p><span className="text-gray-400">Account No:</span> 0100098745201</p>
                <p><span className="text-gray-400">Swift:</span> SBICKE2X</p>
              </div>
            )}

            <div className="flex gap-3">
              <button 
                type="button" 
                onClick={() => setStep('details')}
                className="w-1/3 bg-[#1F1F1F] text-gray-300 font-bold text-xs py-3.5 rounded-xl border border-[#333]"
              >
                ← Back
              </button>
              <button 
                type="submit" 
                className="w-2/3 bg-[#D4AF37] hover:bg-[#C5A059] text-black font-extrabold text-xs py-3.5 rounded-xl uppercase tracking-wider transition"
              >
                Complete Payment (KES {total.toLocaleString()})
              </button>
            </div>
          </form>
        )}

        {/* STEP 3: ORDER CONFIRMATION RECEIPT */}
        {step === 'confirmation' && completedOrder && (
          <div className="p-6 space-y-6 text-center">
            <div className="w-14 h-14 bg-[#D4AF37] rounded-full flex items-center justify-center text-black mx-auto shadow-lg shadow-[#D4AF37]/30">
              <CheckCircle2 className="w-8 h-8 stroke-[2.5]" />
            </div>

            <div>
              <h3 className="text-xl font-extrabold text-white font-mono">
                Order Placed Successfully!
              </h3>
              <p className="text-xs text-gray-400 mt-1">
                Thank you, <span className="text-white font-bold">{completedOrder.customerName}</span>. Your tech hardware dispatch is being prepared.
              </p>
            </div>

            {/* Receipt Box */}
            <div className="bg-[#0B0B0B] border border-[#262626] rounded-2xl p-5 text-left text-xs space-y-3 font-mono">
              <div className="flex justify-between items-center pb-2 border-b border-[#222]">
                <span className="text-gray-400">Order Reference:</span>
                <div className="flex items-center gap-1.5 font-bold text-[#D4AF37]">
                  <span>{completedOrder.id}</span>
                  <button 
                    onClick={() => copyToClipboard(completedOrder.id)}
                    className="p-1 hover:text-white"
                    title="Copy Reference"
                  >
                    {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                  </button>
                </div>
              </div>

              <div className="flex justify-between">
                <span className="text-gray-400">Tracking Code:</span>
                <span className="text-white font-bold">{completedOrder.trackingNumber}</span>
              </div>

              <div className="flex justify-between">
                <span className="text-gray-400">Estimated Express Delivery:</span>
                <span className="text-emerald-400 font-bold">Within 24 Hours</span>
              </div>

              <div className="flex justify-between">
                <span className="text-gray-400">Delivery Address:</span>
                <span className="text-gray-200">{completedOrder.shippingAddress}</span>
              </div>

              <div className="pt-2 border-t border-[#222] flex justify-between font-bold text-sm">
                <span className="text-white">Total Amount Paid:</span>
                <span className="text-[#D4AF37]">KES {completedOrder.total.toLocaleString()}</span>
              </div>
            </div>

            <button 
              onClick={onClose}
              className="w-full bg-[#D4AF37] hover:bg-[#C5A059] text-black font-extrabold text-xs py-3.5 rounded-xl uppercase tracking-wider transition"
            >
              Back to Store
            </button>
          </div>
        )}

      </div>
    </div>
  );
};
