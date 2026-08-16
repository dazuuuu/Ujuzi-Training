import React from 'react';
import { CategoryId } from '../types';
import { CATEGORIES } from '../data/products';
import { 
  Smartphone, 
  Laptop, 
  Monitor, 
  Keyboard, 
  Headphones, 
  Gamepad2, 
  Wifi, 
  ArrowRight 
} from 'lucide-react';

interface CategoryGridProps {
  onSelectCategory: (catId: CategoryId) => void;
}

export const CategoryGrid: React.FC<CategoryGridProps> = ({ onSelectCategory }) => {
  const getIcon = (id: CategoryId) => {
    switch (id) {
      case 'smartphones': return <Smartphone className="w-5 h-5 text-[#D4AF37]" />;
      case 'laptops': return <Laptop className="w-5 h-5 text-[#D4AF37]" />;
      case 'desktops': return <Monitor className="w-5 h-5 text-[#D4AF37]" />;
      case 'accessories': return <Keyboard className="w-5 h-5 text-[#D4AF37]" />;
      case 'audio': return <Headphones className="w-5 h-5 text-[#D4AF37]" />;
      case 'gaming': return <Gamepad2 className="w-5 h-5 text-[#D4AF37]" />;
      case 'networking': return <Wifi className="w-5 h-5 text-[#D4AF37]" />;
      default: return null;
    }
  };

  return (
    <section className="py-10 bg-[#0B0B0B]">
      <div className="max-w-7xl mx-auto px-4">
        {/* Section Heading */}
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-8 pb-4 border-b border-[#222222]">
          <div>
            <span className="text-xs font-extrabold text-[#D4AF37] uppercase tracking-widest font-mono">
              CURATED ELECTRONICS
            </span>
            <h2 className="text-2xl md:text-3xl font-extrabold text-white font-mono mt-1">
              Shop by Category
            </h2>
          </div>
          <p className="text-xs text-gray-400 max-w-md mt-2 md:mt-0">
            Browse our full range of enterprise laptops, smartphones, custom workstations, and high-performance gaming gear.
          </p>
        </div>

        {/* Grid Cards */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          {CATEGORIES.map((cat) => (
            <div
              key={cat.id}
              onClick={() => onSelectCategory(cat.id)}
              className="group relative bg-[#121212] border border-[#222222] hover:border-[#D4AF37] rounded-xl p-4 overflow-hidden transition-all duration-300 cursor-pointer shadow-lg hover:shadow-[#D4AF37]/10 flex flex-col justify-between"
            >
              {/* Image background with overlay */}
              <div className="h-28 w-full rounded-lg overflow-hidden relative mb-3 bg-[#0B0B0B]">
                <img 
                  src={cat.bannerImage} 
                  alt={cat.name} 
                  className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-60"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-[#121212] via-transparent to-transparent"></div>
                
                <div className="absolute top-2 left-2 bg-[#0B0B0B]/80 backdrop-blur-custom p-2 rounded-lg border border-[#2A2A2A]">
                  {getIcon(cat.id)}
                </div>

                <span className="absolute top-2 right-2 bg-[#D4AF37] text-black text-[10px] font-bold px-2 py-0.5 rounded font-mono">
                  {cat.itemCount} ITEMS
                </span>
              </div>

              <div>
                <h3 className="text-sm font-bold text-white group-hover:text-[#D4AF37] transition flex items-center justify-between">
                  <span>{cat.name}</span>
                  <ArrowRight className="w-4 h-4 text-[#D4AF37] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all" />
                </h3>
                <p className="text-[11px] text-gray-400 mt-1 line-clamp-2">
                  {cat.description}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
