import React, { useState } from 'react';
import { Product, CategoryId, FilterOptions } from '../types';
import { ProductCard } from './ProductCard';
import { CATEGORIES, BRANDS } from '../data/products';
import { SlidersHorizontal, X, Filter, Check, RotateCcw } from 'lucide-react';

interface ProductGridProps {
  products: Product[];
  selectedCategory: CategoryId;
  onSelectCategory: (cat: CategoryId) => void;
  searchQuery: string;
  onSearchChange: (q: string) => void;
  onSelectProduct: (p: Product) => void;
  onAddToCart: (p: Product) => void;
  onToggleWishlist: (p: Product) => void;
  onToggleCompare: (p: Product) => void;
  wishlistIds: string[];
  compareIds: string[];
}

export const ProductGrid: React.FC<ProductGridProps> = ({
  products,
  selectedCategory,
  onSelectCategory,
  searchQuery,
  onSearchChange,
  onSelectProduct,
  onAddToCart,
  onToggleWishlist,
  onToggleCompare,
  wishlistIds,
  compareIds,
}) => {
  const [mobileFilterOpen, setMobileFilterOpen] = useState(false);

  // Local filter states
  const [filterBrand, setFilterBrand] = useState<string>('');
  const [minPrice, setMinPrice] = useState<number>(0);
  const [maxPrice, setMaxPrice] = useState<number>(5000);
  const [inStockOnly, setInStockOnly] = useState<boolean>(false);
  const [sortBy, setSortBy] = useState<'featured' | 'price-asc' | 'price-desc' | 'rating' | 'newest'>('featured');

  // Filter logic
  let filtered = products.filter(p => {
    // Category match
    if (selectedCategory !== 'all' && p.category !== selectedCategory) {
      return false;
    }
    // Search match
    if (searchQuery.trim().length > 0) {
      const query = searchQuery.toLowerCase();
      const matchName = p.name.toLowerCase().includes(query);
      const matchBrand = p.brand.toLowerCase().includes(query);
      const matchDesc = p.description.toLowerCase().includes(query);
      const matchTags = p.tags.some(t => t.toLowerCase().includes(query));
      if (!matchName && !matchBrand && !matchDesc && !matchTags) return false;
    }
    // Brand match
    if (filterBrand && p.brand !== filterBrand) {
      return false;
    }
    // Price match
    if (p.price < minPrice || p.price > maxPrice) {
      return false;
    }
    // In Stock match
    if (inStockOnly && !p.inStock) {
      return false;
    }
    return true;
  });

  // Sorting logic
  filtered = [...filtered].sort((a, b) => {
    if (sortBy === 'price-asc') return a.price - b.price;
    if (sortBy === 'price-desc') return b.price - a.price;
    if (sortBy === 'rating') return b.rating - a.rating;
    if (sortBy === 'newest') return (b.isNewArrival ? 1 : 0) - (a.isNewArrival ? 1 : 0);
    return (b.isFeatured ? 1 : 0) - (a.isFeatured ? 1 : 0);
  });

  const resetFilters = () => {
    setFilterBrand('');
    setMinPrice(0);
    setMaxPrice(5000);
    setInStockOnly(false);
    setSortBy('featured');
    onSearchChange('');
  };

  const activeCategoryObj = CATEGORIES.find(c => c.id === selectedCategory);

  return (
    <section className="py-10 bg-[#0B0B0B] min-h-screen" id="product-catalog">
      <div className="max-w-7xl mx-auto px-4">
        
        {/* Title and active filter bar */}
        <div className="flex flex-col md:flex-row md:items-center justify-between mb-6 pb-4 border-b border-[#222222] gap-4">
          <div>
            <span className="text-xs font-bold text-[#D4AF37] uppercase tracking-widest font-mono">
              ELECTRONICS CATALOG
            </span>
            <h2 className="text-2xl md:text-3xl font-extrabold text-white font-mono mt-1">
              {activeCategoryObj ? activeCategoryObj.name : 'All Electronics & Hardware'}
            </h2>
            <p className="text-xs text-gray-400 mt-1">
              Showing <span className="text-[#D4AF37] font-bold font-mono">{filtered.length}</span> premium products
            </p>
          </div>

          <div className="flex items-center gap-3">
            {/* Mobile Filter Drawer Button */}
            <button 
              onClick={() => setMobileFilterOpen(true)}
              className="lg:hidden bg-[#181818] border border-[#333] hover:border-[#D4AF37] text-white px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2"
            >
              <Filter className="w-4 h-4 text-[#D4AF37]" /> Filter Options
            </button>

            {/* Sort Dropdown */}
            <div className="flex items-center gap-2 bg-[#121212] border border-[#262626] rounded-xl px-3 py-1.5">
              <span className="text-xs text-gray-400 hidden sm:inline">Sort:</span>
              <select 
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value as any)}
                className="bg-transparent text-white text-xs font-semibold focus:outline-none cursor-pointer"
              >
                <option value="featured" className="bg-[#121212] text-white">Featured First</option>
                <option value="price-asc" className="bg-[#121212] text-white">Price: Low to High</option>
                <option value="price-desc" className="bg-[#121212] text-white">Price: High to Low</option>
                <option value="rating" className="bg-[#121212] text-white">Customer Rating</option>
                <option value="newest" className="bg-[#121212] text-white">Newest Arrivals</option>
              </select>
            </div>
          </div>
        </div>

        {/* MAIN LAYOUT: SIDEBAR FILTERS + PRODUCT GRID */}
        <div className="flex flex-col lg:flex-row gap-8">
          
          {/* DESKTOP FILTER SIDEBAR */}
          <aside className="hidden lg:block w-64 shrink-0 space-y-6">
            <div className="bg-[#121212] border border-[#222222] rounded-2xl p-5 space-y-6">
              
              <div className="flex items-center justify-between pb-3 border-b border-[#222222]">
                <h3 className="text-sm font-bold text-white font-mono uppercase flex items-center gap-2">
                  <SlidersHorizontal className="w-4 h-4 text-[#D4AF37]" /> Filters
                </h3>
                <button 
                  onClick={resetFilters}
                  className="text-[11px] text-[#D4AF37] hover:underline font-semibold flex items-center gap-1"
                >
                  <RotateCcw className="w-3 h-3" /> Reset
                </button>
              </div>

              {/* Category selector */}
              <div>
                <label className="text-xs font-bold text-gray-300 uppercase tracking-wider block mb-2 font-mono">
                  Categories
                </label>
                <div className="space-y-1">
                  <button
                    onClick={() => onSelectCategory('all')}
                    className={`w-full text-left px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                      selectedCategory === 'all' 
                        ? 'bg-[#D4AF37] text-black font-extrabold' 
                        : 'text-gray-400 hover:text-white hover:bg-[#181818]'
                    }`}
                  >
                    All Electronics
                  </button>
                  {CATEGORIES.map(cat => (
                    <button
                      key={cat.id}
                      onClick={() => onSelectCategory(cat.id)}
                      className={`w-full text-left px-3 py-1.5 rounded-lg text-xs transition flex justify-between items-center ${
                        selectedCategory === cat.id 
                          ? 'bg-[#D4AF37] text-black font-extrabold' 
                          : 'text-gray-400 hover:text-white hover:bg-[#181818]'
                      }`}
                    >
                      <span>{cat.name}</span>
                      <span className="text-[10px] font-mono opacity-60">({cat.itemCount})</span>
                    </button>
                  ))}
                </div>
              </div>

              {/* Brand filter */}
              <div className="pt-4 border-t border-[#222222]">
                <label className="text-xs font-bold text-gray-300 uppercase tracking-wider block mb-2 font-mono">
                  Brand
                </label>
                <div className="space-y-1 max-h-48 overflow-y-auto pr-1">
                  <button
                    onClick={() => setFilterBrand('')}
                    className={`w-full text-left px-3 py-1.5 rounded-lg text-xs transition ${
                      filterBrand === '' ? 'text-[#D4AF37] font-bold bg-[#181818]' : 'text-gray-400 hover:text-white'
                    }`}
                  >
                    All Brands
                  </button>
                  {BRANDS.map(brand => (
                    <button
                      key={brand}
                      onClick={() => setFilterBrand(brand)}
                      className={`w-full text-left px-3 py-1.5 rounded-lg text-xs transition flex items-center justify-between ${
                        filterBrand === brand ? 'text-[#D4AF37] font-bold bg-[#181818]' : 'text-gray-400 hover:text-white'
                      }`}
                    >
                      <span>{brand}</span>
                      {filterBrand === brand && <Check className="w-3.5 h-3.5 text-[#D4AF37]" />}
                    </button>
                  ))}
                </div>
              </div>

              {/* Price Range */}
              <div className="pt-4 border-t border-[#222222]">
                <label className="text-xs font-bold text-gray-300 uppercase tracking-wider block mb-2 font-mono">
                  Max Price: ${maxPrice.toLocaleString()}
                </label>
                <input 
                  type="range" 
                  min="100" 
                  max="5000" 
                  step="100"
                  value={maxPrice} 
                  onChange={(e) => setMaxPrice(Number(e.target.value))}
                  className="w-full accent-[#D4AF37] bg-[#222] cursor-pointer"
                />
                <div className="flex justify-between text-[10px] text-gray-400 font-mono mt-1">
                  <span>$100</span>
                  <span>$5,000</span>
                </div>
              </div>

              {/* In Stock toggle */}
              <div className="pt-4 border-t border-[#222222]">
                <label className="flex items-center gap-2 cursor-pointer text-xs text-gray-300 font-semibold">
                  <input 
                    type="checkbox" 
                    checked={inStockOnly} 
                    onChange={(e) => setInStockOnly(e.target.checked)}
                    className="accent-[#D4AF37] rounded"
                  />
                  <span>In-Stock Items Only</span>
                </label>
              </div>

            </div>
          </aside>

          {/* PRODUCT CARDS LISTING */}
          <div className="flex-1">
            {filtered.length === 0 ? (
              <div className="bg-[#121212] border border-[#222222] rounded-2xl p-12 text-center my-8">
                <SlidersHorizontal className="w-12 h-12 text-[#D4AF37] mx-auto mb-4" />
                <h3 className="text-lg font-bold text-white font-mono">
                  No Electronics Match Your Filters
                </h3>
                <p className="text-xs text-gray-400 max-w-sm mx-auto mt-2 mb-6">
                  Try adjusting your search criteria, price range, or selecting a different category.
                </p>
                <button 
                  onClick={resetFilters}
                  className="bg-[#D4AF37] hover:bg-[#C5A059] text-black font-bold text-xs px-6 py-2.5 rounded-xl transition"
                >
                  Reset All Filters
                </button>
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {filtered.map(product => (
                  <ProductCard 
                    key={product.id}
                    product={product}
                    onSelectProduct={onSelectProduct}
                    onAddToCart={onAddToCart}
                    onToggleWishlist={onToggleWishlist}
                    onToggleCompare={onToggleCompare}
                    isWishlisted={wishlistIds.includes(product.id)}
                    isCompared={compareIds.includes(product.id)}
                  />
                ))}
              </div>
            )}
          </div>

        </div>
      </div>

      {/* MOBILE FILTER MODAL */}
      {mobileFilterOpen && (
        <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-custom flex justify-end lg:hidden">
          <div className="w-full max-w-xs bg-[#121212] h-full p-6 overflow-y-auto space-y-6">
            <div className="flex items-center justify-between pb-4 border-b border-[#222]">
              <h3 className="text-sm font-bold text-white font-mono uppercase">Filter Catalog</h3>
              <button onClick={() => setMobileFilterOpen(false)} className="p-1 text-gray-400 hover:text-white">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div>
              <label className="text-xs font-bold text-[#D4AF37] uppercase block mb-2 font-mono">Category</label>
              <div className="space-y-1">
                <button
                  onClick={() => { onSelectCategory('all'); setMobileFilterOpen(false); }}
                  className="w-full text-left p-2 text-xs text-white"
                >
                  All Categories
                </button>
                {CATEGORIES.map(cat => (
                  <button
                    key={cat.id}
                    onClick={() => { onSelectCategory(cat.id); setMobileFilterOpen(false); }}
                    className={`w-full text-left p-2 rounded text-xs ${selectedCategory === cat.id ? 'bg-[#D4AF37] text-black font-bold' : 'text-gray-300'}`}
                  >
                    {cat.name}
                  </button>
                ))}
              </div>
            </div>

            <div className="pt-4 border-t border-[#222]">
              <label className="text-xs font-bold text-[#D4AF37] uppercase block mb-2 font-mono">Max Price: ${maxPrice}</label>
              <input 
                type="range" 
                min="100" 
                max="5000" 
                step="100" 
                value={maxPrice} 
                onChange={(e) => setMaxPrice(Number(e.target.value))}
                className="w-full accent-[#D4AF37]"
              />
            </div>

            <button 
              onClick={() => setMobileFilterOpen(false)}
              className="w-full bg-[#D4AF37] text-black font-bold text-xs py-3 rounded-xl uppercase tracking-wider"
            >
              Apply & View ({filtered.length})
            </button>
          </div>
        </div>
      )}
    </section>
  );
};
