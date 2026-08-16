import React, { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight, Zap, ShieldCheck, ArrowRight, Cpu, Laptop, Smartphone } from 'lucide-react';
import { CategoryId } from '../types';

interface HeroSliderProps {
  onSelectCategory: (cat: CategoryId) => void;
  onOpenPCConfigurator: () => void;
}

export const HeroSlider: React.FC<HeroSliderProps> = ({ onSelectCategory, onOpenPCConfigurator }) => {
  const [currentSlide, setCurrentSlide] = useState(0);

  const slides = [
    {
      id: 1,
      tag: 'FLAGSHIP COMPUTING',
      title: 'Next-Gen M3 Max & RTX 4090 Workstations',
      subtitle: 'Engineered for AI Developers, 3D Renderers, and Uncompromised Mobile Performance.',
      offerBadge: 'UP TO KES 45,000 INSTANT OFF',
      image: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1600&q=80',
      category: 'laptops' as CategoryId,
      buttonText: 'Explore Pro Laptops',
      icon: <Laptop className="w-4 h-4 text-black" />,
    },
    {
      id: 2,
      tag: 'TITANIUM MOBILE POWER',
      title: 'iPhone 16 Pro Max & Galaxy S25 Ultra',
      subtitle: 'Grade 5 Titanium, 4K 120fps video recording, and groundbreaking hardware AI acceleration.',
      offerBadge: 'FREE EXPRESS SAME-DAY DELIVERY',
      image: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=1600&q=80',
      category: 'smartphones' as CategoryId,
      buttonText: 'Shop Smartphones',
      icon: <Smartphone className="w-4 h-4 text-black" />,
    },
    {
      id: 3,
      tag: 'CUSTOM APEX RIGS',
      title: 'Engineered PC Rigs & 240Hz OLED Displays',
      subtitle: 'Hand-assembled liquid cooled workstations with 3-year master onsite warranty.',
      offerBadge: 'CUSTOM SPECS CONFIGURATOR',
      image: 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?auto=format&fit=crop&w=1600&q=80',
      category: 'desktops' as CategoryId,
      buttonText: 'Build Custom Rig',
      icon: <Cpu className="w-4 h-4 text-black" />,
    },
  ];

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 6000);
    return () => clearInterval(timer);
  }, [slides.length]);

  return (
    <div className="relative bg-[#0B0B0B] border-b border-[#222222] overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 py-6 md:py-8">
        <div className="relative rounded-2xl overflow-hidden border border-[#262626] bg-[#121212] min-h-[420px] md:min-h-[480px] flex items-center">
          
          {/* Background Image with Dark Vignette */}
          <div className="absolute inset-0 z-0">
            <img 
              src={slides[currentSlide].image} 
              alt={slides[currentSlide].title} 
              className="w-full h-full object-cover object-center opacity-35 scale-105 transition-all duration-1000 ease-out"
            />
            <div className="absolute inset-0 bg-gradient-to-r from-[#0B0B0B] via-[#0B0B0B]/80 to-transparent"></div>
          </div>

          {/* Slide Text Content */}
          <div className="relative z-10 p-6 md:p-12 max-w-2xl">
            {/* Tag Badge */}
            <div className="inline-flex items-center gap-2 bg-[#D4AF37] text-black px-3 py-1 rounded-md text-xs font-extrabold uppercase tracking-widest mb-4">
              <Zap className="w-3.5 h-3.5 fill-black" />
              <span>{slides[currentSlide].tag}</span>
            </div>

            <h1 className="text-2xl sm:text-4xl md:text-5xl font-extrabold text-white leading-tight font-mono tracking-tight mb-3">
              {slides[currentSlide].title}
            </h1>

            <p className="text-sm md:text-base text-gray-300 font-normal leading-relaxed mb-6">
              {slides[currentSlide].subtitle}
            </p>

            {/* Offer Box & CTA Buttons */}
            <div className="flex flex-wrap items-center gap-4">
              <button 
                onClick={() => {
                  if (slides[currentSlide].id === 3) {
                    onOpenPCConfigurator();
                  } else {
                    onSelectCategory(slides[currentSlide].category);
                  }
                }}
                className="bg-[#D4AF37] hover:bg-[#C5A059] text-black font-extrabold text-xs md:text-sm px-6 py-3.5 rounded-xl transition flex items-center gap-2 shadow-lg shadow-[#D4AF37]/20 uppercase tracking-wide cursor-pointer"
              >
                {slides[currentSlide].icon}
                <span>{slides[currentSlide].buttonText}</span>
                <ArrowRight className="w-4 h-4 text-black" />
              </button>

              <div className="inline-flex items-center gap-2 bg-[#1A1A1A] border border-[#333] px-4 py-3 rounded-xl text-xs text-[#D4AF37] font-mono font-bold">
                <ShieldCheck className="w-4 h-4 text-[#D4AF37]" />
                <span>{slides[currentSlide].offerBadge}</span>
              </div>
            </div>
          </div>

          {/* Carousel Arrows */}
          <button 
            onClick={() => setCurrentSlide((prev) => (prev === 0 ? slides.length - 1 : prev - 1))}
            className="absolute left-4 z-20 p-3 bg-black/60 border border-[#333] text-white hover:text-[#D4AF37] hover:border-[#D4AF37] rounded-full transition backdrop-blur-custom"
            aria-label="Previous Slide"
          >
            <ChevronLeft className="w-5 h-5" />
          </button>

          <button 
            onClick={() => setCurrentSlide((prev) => (prev + 1) % slides.length)}
            className="absolute right-4 z-20 p-3 bg-black/60 border border-[#333] text-white hover:text-[#D4AF37] hover:border-[#D4AF37] rounded-full transition backdrop-blur-custom"
            aria-label="Next Slide"
          >
            <ChevronRight className="w-5 h-5" />
          </button>

          {/* Dots Indicator */}
          <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2 bg-black/50 px-3 py-1.5 rounded-full border border-[#333]">
            {slides.map((_, idx) => (
              <button
                key={idx}
                onClick={() => setCurrentSlide(idx)}
                className={`h-2 rounded-full transition-all ${
                  currentSlide === idx ? 'w-8 bg-[#D4AF37]' : 'w-2 bg-gray-600 hover:bg-gray-400'
                }`}
                aria-label={`Go to slide ${idx + 1}`}
              />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};
