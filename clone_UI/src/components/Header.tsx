import React, { useState, useRef, useEffect } from 'react';
import { 
  Search, 
  ShoppingBag, 
  Heart, 
  Scale, 
  MapPin, 
  Menu, 
  X, 
  Phone, 
  Zap, 
  Cpu, 
  Laptop, 
  Smartphone, 
  Monitor, 
  Keyboard, 
  Headphones, 
  Gamepad2, 
  Wifi,
  Sparkles,
  Layers,
  ChevronDown
} from 'lucide-react';
import { CategoryId, Product } from '../types';
import { CATEGORIES } from '../data/products';

interface HeaderProps {
  selectedCategory: CategoryId;
  onSelectCategory: (cat: CategoryId) => void;
  searchQuery: string;
  onSearchChange: (q: string) => void;
  cartCount: number;
  cartTotal: number;
  wishlistCount: number;
  compareCount: number;
  onOpenCart: () => void;
  onOpenWishlist: () => void;
  onOpenCompare: () => void;
  onOpenStores: () => void;
  onOpenPCConfigurator: () => void;
  onOpenSupport: () => void;
  onSelectProduct: (p: Product) => void;
  allProducts: Product[];
  onShowFlashDeals: () => void;
  onShowNewArrivals: () => void;
}

export const Header: React.FC<HeaderProps> = ({
  selectedCategory,
  onSelectCategory,
  searchQuery,
  onSearchChange,
  cartCount,
  cartTotal,
  wishlistCount,
  compareCount,
  onOpenCart,
  onOpenWishlist,
  onOpenCompare,
  onOpenStores,
  onOpenPCConfigurator,
  onOpenSupport,
  onSelectProduct,
  allProducts,
  onShowFlashDeals,
  onShowNewArrivals,
}) => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [categoryDropdownOpen, setCategoryDropdownOpen] = useState(false);
  const [isSearchFocused, setIsSearchFocused] = useState(false);
  const searchRef = useRef<HTMLDivElement>(null);

  // Filter live search suggestions
  const searchSuggestions = searchQuery.trim().length > 1
    ? allProducts.filter(p => 
        p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        p.brand.toLowerCase().includes(searchQuery.toLowerCase()) ||
        p.category.toLowerCase().includes(searchQuery.toLowerCase())
      ).slice(0, 5)
    : [];

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(e.target as Node)) {
        setIsSearchFocused(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const getCategoryIcon = (catId: CategoryId) => {
    switch (catId) {
      case 'smartphones': return <Smartphone className="w-4 h-4" />;
      case 'laptops': return <Laptop className="w-4 h-4" />;
      case 'desktops': return <Monitor className="w-4 h-4" />;
      case 'accessories': return <Keyboard className="w-4 h-4" />;
      case 'audio': return <Headphones className="w-4 h-4" />;
      case 'gaming': return <Gamepad2 className="w-4 h-4" />;
      case 'networking': return <Wifi className="w-4 h-4" />;
      default: return <Layers className="w-4 h-4" />;
    }
  };

  return (
    <header className="sticky top-0 z-40 bg-[#0B0B0B] border-b border-[#222222] shadow-xl">
      <div className="max-w-7xl mx-auto px-3 sm:px-4 py-2.5 sm:py-3 flex items-center justify-between gap-2 sm:gap-4">
        
        {/* 1. LOGO AND NAME */}
        <div 
          onClick={() => {
            onSelectCategory('all');
            onSearchChange('');
          }}
          className="cursor-pointer flex items-center gap-2 sm:gap-2.5 shrink-0 group"
        >
          <div className="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center overflow-hidden group-hover:scale-105 transition-all duration-300">
            <img 
              src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcScmuc3aj_PsLJ72bR24YWR84QhPPMXH2N6aQD3Gih9NQ-V8D26ukaPZmLrLcX5J_OW&s=10&ec=121902082" 
              alt="TRUNKNET Logo" 
              className="w-full h-full object-contain"
              referrerPolicy="no-referrer"
            />
          </div>
          <div>
            <div className="flex items-center gap-1">
              <span className="text-sm sm:text-base md:text-lg font-extrabold tracking-wider text-white font-mono">
                TRUNKNET
              </span>
              <span className="text-[9px] sm:text-[10px] bg-[#D4AF37] text-black px-1 py-0.2 font-bold uppercase rounded tracking-tight">
                TECH
              </span>
            </div>
            <p className="text-[8px] sm:text-[9px] text-gray-400 font-medium tracking-widest uppercase -mt-0.5 hidden xs:block">
              TECHNOLOGIES
            </p>
          </div>
        </div>

        {/* 2. SEARCH BAR (Small on mobile view) */}
        <div className="flex-1 min-w-[110px] max-w-[200px] xs:max-w-[260px] sm:max-w-md lg:max-w-lg relative" ref={searchRef}>
          <div className="relative flex items-center">
            <input 
              type="text"
              value={searchQuery}
              onChange={(e) => {
                onSearchChange(e.target.value);
                setIsSearchFocused(true);
              }}
              onFocus={() => setIsSearchFocused(true)}
              placeholder="Search items..."
              className="w-full bg-[#161616] text-white text-[11px] sm:text-xs md:text-sm pl-7 sm:pl-9 pr-12 sm:pr-16 py-1.5 sm:py-2 rounded-lg border border-[#2E2E2E] focus:border-[#D4AF37] focus:outline-none transition-all placeholder:text-gray-500"
            />
            <Search className="w-3.5 h-3.5 text-gray-400 absolute left-2 sm:left-3 pointer-events-none" />
            
            {searchQuery && (
              <button 
                onClick={() => onSearchChange('')}
                className="absolute right-12 text-[10px] text-gray-400 hover:text-white p-0.5 hidden sm:block"
              >
                Clear
              </button>
            )}

            <button className="absolute right-1 bg-[#D4AF37] hover:bg-[#C5A059] text-black font-semibold text-[10px] sm:text-xs px-1.5 sm:px-2.5 py-1 rounded transition-colors">
              Search
            </button>
          </div>

          {/* Autocomplete Suggestions */}
          {isSearchFocused && searchSuggestions.length > 0 && (
            <div className="absolute left-0 right-0 top-full mt-1.5 bg-[#141414] border border-[#2D2D2D] rounded-xl shadow-2xl z-50 overflow-hidden divide-y divide-[#222]">
              <div className="p-2 text-[10px] sm:text-[11px] font-semibold uppercase text-[#D4AF37] tracking-wider bg-[#181818] flex justify-between">
                <span>Matching Products</span>
                <span>{searchSuggestions.length} Results</span>
              </div>
              {searchSuggestions.map((prod) => (
                <div 
                  key={prod.id}
                  onClick={() => {
                    onSelectProduct(prod);
                    setIsSearchFocused(false);
                  }}
                  className="p-2 sm:p-2.5 hover:bg-[#1E1E1E] transition cursor-pointer flex items-center gap-2 group"
                >
                  <img 
                    src={prod.image} 
                    alt={prod.name} 
                    className="w-8 h-8 sm:w-10 sm:h-10 object-cover rounded bg-[#000] border border-[#2A2A2A]"
                  />
                  <div className="flex-1 min-w-0">
                    <h4 className="text-xs font-medium text-white truncate group-hover:text-[#D4AF37] transition">
                      {prod.name}
                    </h4>
                    <div className="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-gray-400 mt-0.5">
                      <span className="text-[#D4AF37] font-bold font-mono">KES {prod.price.toLocaleString()}</span>
                      <span>•</span>
                      <span className="uppercase text-[9px] bg-[#222] px-1 py-0.5 rounded text-gray-300">{prod.brand}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* 3. CART */}
        <div className="shrink-0">
          <button 
            onClick={onOpenCart}
            className="bg-[#D4AF37] hover:bg-[#C5A059] text-black px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-[#D4AF37]/10"
            aria-label="Cart"
          >
            <div className="relative">
              <ShoppingBag className="w-4 h-4 stroke-[2.5]" />
              {cartCount > 0 && (
                <span className="absolute -top-2 -right-2 bg-black text-[#D4AF37] text-[10px] font-extrabold w-4 h-4 rounded-full flex items-center justify-center font-mono border border-[#D4AF37]">
                  {cartCount}
                </span>
              )}
            </div>
            <span className="hidden md:inline font-mono text-xs">
              KES {cartTotal.toLocaleString()}
            </span>
          </button>
        </div>

        {/* 4. NAV BAR (On the right side) */}
        <div className="flex items-center gap-1.5 sm:gap-2 shrink-0">
          
          {/* Desktop Nav Links (right side) */}
          <nav className="hidden lg:flex items-center gap-3 xl:gap-4 font-semibold uppercase tracking-wider text-[11px] text-gray-300">
            <button 
              onClick={() => onSelectCategory('all')}
              className={`py-1 hover:text-[#D4AF37] transition border-b-2 ${selectedCategory === 'all' ? 'text-[#D4AF37] border-[#D4AF37]' : 'border-transparent'}`}
            >
              Shop All
            </button>

            <button 
              onClick={() => onSelectCategory('laptops')}
              className={`py-1 hover:text-[#D4AF37] transition border-b-2 ${selectedCategory === 'laptops' ? 'text-[#D4AF37] border-[#D4AF37]' : 'border-transparent'}`}
            >
              Laptops
            </button>

            <button 
              onClick={() => onSelectCategory('smartphones')}
              className={`py-1 hover:text-[#D4AF37] transition border-b-2 ${selectedCategory === 'smartphones' ? 'text-[#D4AF37] border-[#D4AF37]' : 'border-transparent'}`}
            >
              Smartphones
            </button>

            <button 
              onClick={() => onSelectCategory('desktops')}
              className={`py-1 hover:text-[#D4AF37] transition border-b-2 ${selectedCategory === 'desktops' ? 'text-[#D4AF37] border-[#D4AF37]' : 'border-transparent'}`}
            >
              Computers
            </button>

            <button 
              onClick={() => onSelectCategory('accessories')}
              className={`py-1 hover:text-[#D4AF37] transition border-b-2 ${selectedCategory === 'accessories' ? 'text-[#D4AF37] border-[#D4AF37]' : 'border-transparent'}`}
            >
              Accessories
            </button>

            <button 
              onClick={onShowFlashDeals}
              className="py-1 hover:text-[#D4AF37] transition text-amber-400 flex items-center gap-1 font-bold"
            >
              <Zap className="w-3.5 h-3.5 text-[#D4AF37]" /> Flash Deals
            </button>

            <button 
              onClick={onOpenPCConfigurator}
              className="flex items-center gap-1 bg-[#1A1A1A] border border-[#D4AF37]/50 hover:border-[#D4AF37] text-[#D4AF37] px-2 py-1 rounded text-[10px] font-bold transition"
            >
              <Sparkles className="w-3 h-3 text-[#D4AF37]" />
              <span>PC Builder</span>
            </button>
          </nav>

          {/* Compare icon button */}
          <button 
            onClick={onOpenCompare}
            className="p-1.5 sm:p-2 text-gray-300 hover:text-[#D4AF37] hover:bg-[#181818] rounded-lg transition relative hidden md:flex items-center justify-center"
            title="Compare Specs"
            aria-label="Compare"
          >
            <Scale className="w-4 h-4" />
            {compareCount > 0 && (
              <span className="absolute -top-1 -right-1 bg-[#D4AF37] text-black font-extrabold text-[9px] w-3.5 h-3.5 rounded-full flex items-center justify-center font-mono">
                {compareCount}
              </span>
            )}
          </button>

          {/* Wishlist icon button */}
          <button 
            onClick={onOpenWishlist}
            className="p-1.5 sm:p-2 text-gray-300 hover:text-[#D4AF37] hover:bg-[#181818] rounded-lg transition relative hidden md:flex items-center justify-center"
            title="Wishlist"
            aria-label="Wishlist"
          >
            <Heart className="w-4 h-4" />
            {wishlistCount > 0 && (
              <span className="absolute -top-1 -right-1 bg-[#D4AF37] text-black font-extrabold text-[9px] w-3.5 h-3.5 rounded-full flex items-center justify-center font-mono">
                {wishlistCount}
              </span>
            )}
          </button>

          {/* Mobile Menu Nav Toggle */}
          <button 
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="lg:hidden p-1.5 sm:p-2 text-gray-300 hover:text-[#D4AF37] transition rounded-lg bg-[#181818] border border-[#2E2E2E]"
            aria-label="Toggle Navigation"
          >
            {mobileMenuOpen ? <X className="w-4 h-4 sm:w-5 sm:h-5" /> : <Menu className="w-4 h-4 sm:w-5 sm:h-5" />}
          </button>
        </div>

      </div>

      {/* MOBILE DRAWER NAVIGATION MENU */}
      {mobileMenuOpen && (
        <div className="lg:hidden bg-[#121212] border-t border-[#222222] px-4 py-4 space-y-3">
          <div className="text-[11px] font-bold text-[#D4AF37] uppercase tracking-wider mb-2">
            Navigation Menu
          </div>
          <div className="grid grid-cols-2 gap-2">
            <button
              onClick={() => {
                onSelectCategory('all');
                setMobileMenuOpen(false);
              }}
              className={`p-2.5 rounded text-left text-xs font-semibold border flex items-center gap-2 ${
                selectedCategory === 'all' 
                  ? 'bg-[#D4AF37] text-black border-[#D4AF37]' 
                  : 'bg-[#181818] text-gray-300 border-[#2A2A2A]'
              }`}
            >
              <Layers className="w-4 h-4" />
              <span>Shop All</span>
            </button>

            {CATEGORIES.map((cat) => (
              <button
                key={cat.id}
                onClick={() => {
                  onSelectCategory(cat.id);
                  setMobileMenuOpen(false);
                }}
                className={`p-2.5 rounded text-left text-xs font-semibold border flex items-center gap-2 ${
                  selectedCategory === cat.id 
                    ? 'bg-[#D4AF37] text-black border-[#D4AF37]' 
                    : 'bg-[#181818] text-gray-300 border-[#2A2A2A]'
                }`}
              >
                {getCategoryIcon(cat.id)}
                <span className="truncate">{cat.name}</span>
              </button>
            ))}
          </div>

          <div className="pt-3 border-t border-[#222222] flex flex-col gap-2 text-xs">
            <button 
              onClick={() => { onShowFlashDeals(); setMobileMenuOpen(false); }}
              className="w-full py-2 bg-[#1A1A1A] text-amber-400 border border-amber-500/40 font-bold rounded flex items-center justify-center gap-2"
            >
              <Zap className="w-4 h-4 text-[#D4AF37]" /> Flash Deals
            </button>
            <button 
              onClick={() => { onOpenPCConfigurator(); setMobileMenuOpen(false); }}
              className="w-full py-2 bg-[#1A1A1A] text-[#D4AF37] border border-[#D4AF37] font-semibold rounded flex items-center justify-center gap-2"
            >
              <Sparkles className="w-4 h-4" /> Custom PC Builder
            </button>
            <button 
              onClick={() => { onOpenStores(); setMobileMenuOpen(false); }}
              className="w-full py-2 bg-[#181818] text-gray-300 hover:text-white rounded flex items-center justify-center gap-2"
            >
              <MapPin className="w-4 h-4 text-[#D4AF37]" /> Store Locator
            </button>
            <button 
              onClick={() => { onOpenSupport(); setMobileMenuOpen(false); }}
              className="w-full py-2 bg-[#181818] text-gray-300 hover:text-white rounded flex items-center justify-center gap-2"
            >
              <Phone className="w-4 h-4 text-[#D4AF37]" /> 24/7 Support Hotline
            </button>
          </div>
        </div>
      )}
    </header>
  );
};
