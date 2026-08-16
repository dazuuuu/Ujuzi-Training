import React, { useState, useEffect } from 'react';
import { CategoryId, Product, CartItem, Order } from './types';
import { PRODUCTS } from './data/products';
import { Header } from './components/Header';
import { HeroSlider } from './components/HeroSlider';
import { FeatureBar } from './components/FeatureBar';
import { CategoryGrid } from './components/CategoryGrid';
import { FlashDeals } from './components/FlashDeals';
import { ProductGrid } from './components/ProductGrid';
import { ProductDetailModal } from './components/ProductDetailModal';
import { CartDrawer } from './components/CartDrawer';
import { CheckoutModal } from './components/CheckoutModal';
import { CompareModal } from './components/CompareModal';
import { SpecsFinderTool } from './components/SpecsFinderTool';
import { StoreLocatorModal } from './components/StoreLocatorModal';
import { SupportContactModal } from './components/SupportContactModal';
import { Footer } from './components/Footer';
import { Toast } from './components/Toast';

export default function App() {
  // Navigation & Filtering
  const [selectedCategory, setSelectedCategory] = useState<CategoryId>('all');
  const [searchQuery, setSearchQuery] = useState<string>('');

  // Cart & Persistence
  const [cartItems, setCartItems] = useState<CartItem[]>(() => {
    try {
      const saved = localStorage.getItem('trunknet_cart');
      return saved ? JSON.parse(saved) : [];
    } catch {
      return [];
    }
  });

  // Wishlist & Compare
  const [wishlistIds, setWishlistIds] = useState<string[]>(() => {
    try {
      const saved = localStorage.getItem('trunknet_wishlist');
      return saved ? JSON.parse(saved) : ['tn-mbp-16'];
    } catch {
      return [];
    }
  });

  const [compareIds, setCompareIds] = useState<string[]>([]);

  // Modals visibility
  const [selectedProductModal, setSelectedProductModal] = useState<Product | null>(null);
  const [cartDrawerOpen, setCartDrawerOpen] = useState(false);
  const [checkoutModalOpen, setCheckoutModalOpen] = useState(false);
  const [appliedDiscount, setAppliedDiscount] = useState(0);
  const [compareModalOpen, setCompareModalOpen] = useState(false);
  const [storesModalOpen, setStoresModalOpen] = useState(false);
  const [pcConfiguratorOpen, setPcConfiguratorOpen] = useState(false);
  const [supportModalOpen, setSupportModalOpen] = useState(false);

  // Toast feedback
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  useEffect(() => {
    try {
      localStorage.setItem('trunknet_cart', JSON.stringify(cartItems));
    } catch (e) {
      console.error(e);
    }
  }, [cartItems]);

  useEffect(() => {
    try {
      localStorage.setItem('trunknet_wishlist', JSON.stringify(wishlistIds));
    } catch (e) {
      console.error(e);
    }
  }, [wishlistIds]);

  // Cart Handlers
  const handleAddToCart = (product: Product, quantity = 1) => {
    setCartItems(prev => {
      const existing = prev.find(item => item.product.id === product.id);
      if (existing) {
        return prev.map(item =>
          item.product.id === product.id
            ? { ...item, quantity: item.quantity + quantity }
            : item
        );
      }
      return [...prev, { product, quantity }];
    });
    setToastMessage(`Added "${product.brand} ${product.name.split(' ')[1] || ''}" to cart!`);
  };

  const handleUpdateQuantity = (productId: string, qty: number) => {
    if (qty <= 0) {
      handleRemoveFromCart(productId);
      return;
    }
    setCartItems(prev =>
      prev.map(item =>
        item.product.id === productId ? { ...item, quantity: qty } : item
      )
    );
  };

  const handleRemoveFromCart = (productId: string) => {
    setCartItems(prev => prev.filter(item => item.product.id !== productId));
  };

  const handleClearCart = () => {
    setCartItems([]);
  };

  // Wishlist Handler
  const handleToggleWishlist = (product: Product) => {
    setWishlistIds(prev => {
      if (prev.includes(product.id)) {
        setToastMessage(`Removed from Wishlist`);
        return prev.filter(id => id !== product.id);
      } else {
        setToastMessage(`Added to Wishlist`);
        return [...prev, product.id];
      }
    });
  };

  // Compare Handler
  const handleToggleCompare = (product: Product) => {
    setCompareIds(prev => {
      if (prev.includes(product.id)) {
        return prev.filter(id => id !== product.id);
      } else {
        if (prev.length >= 4) {
          setToastMessage(`You can compare up to 4 items max.`);
          return prev;
        }
        setToastMessage(`Added to comparison list (${prev.length + 1}/4)`);
        return [...prev, product.id];
      }
    });
  };

  const handleBuyNow = (product: Product, quantity = 1) => {
    handleAddToCart(product, quantity);
    setSelectedProductModal(null);
    setCartDrawerOpen(false);
    setCheckoutModalOpen(true);
  };

  const handleProceedToCheckout = (discountAmount: number) => {
    setAppliedDiscount(discountAmount);
    setCartDrawerOpen(false);
    setCheckoutModalOpen(true);
  };

  const handleOrderComplete = (order: Order) => {
    setCartItems([]);
  };

  const cartCount = cartItems.reduce((sum, item) => sum + item.quantity, 0);
  const cartTotal = cartItems.reduce((sum, item) => sum + item.product.price * item.quantity, 0);

  const comparedProducts = PRODUCTS.filter(p => compareIds.includes(p.id));

  const handleShowFlashDeals = () => {
    setSelectedCategory('all');
    const el = document.getElementById('flash-deals-section');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  const handleShowNewArrivals = () => {
    setSelectedCategory('all');
    const el = document.getElementById('product-catalog');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <div className="min-h-screen bg-[#0B0B0B] text-white flex flex-col font-sans selection:bg-[#D4AF37] selection:text-black">
      
      {/* 1. Header */}
      <Header 
        selectedCategory={selectedCategory}
        onSelectCategory={(cat) => {
          setSelectedCategory(cat);
          setSearchQuery('');
        }}
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
        cartCount={cartCount}
        cartTotal={cartTotal}
        wishlistCount={wishlistIds.length}
        compareCount={compareIds.length}
        onOpenCart={() => setCartDrawerOpen(true)}
        onOpenWishlist={() => {
          // Open catalog filtered by wishlist items if any
          setSelectedCategory('all');
          setToastMessage(`${wishlistIds.length} items in your wishlist`);
        }}
        onOpenCompare={() => setCompareModalOpen(true)}
        onOpenStores={() => setStoresModalOpen(true)}
        onOpenPCConfigurator={() => setPcConfiguratorOpen(true)}
        onOpenSupport={() => setSupportModalOpen(true)}
        onSelectProduct={(p) => setSelectedProductModal(p)}
        allProducts={PRODUCTS}
        onShowFlashDeals={handleShowFlashDeals}
        onShowNewArrivals={handleShowNewArrivals}
      />

      <main className="flex-1">
        {/* 2. Hero Slideshow Banner */}
        <HeroSlider 
          onSelectCategory={setSelectedCategory} 
          onOpenPCConfigurator={() => setPcConfiguratorOpen(true)}
        />

        {/* 3. Value Proposition Feature Bar */}
        <FeatureBar />

        {/* 4. Flash Deals Section */}
        <div id="flash-deals-section">
          <FlashDeals 
            products={PRODUCTS}
            onSelectProduct={(p) => setSelectedProductModal(p)}
            onAddToCart={(p) => handleAddToCart(p, 1)}
            onToggleWishlist={handleToggleWishlist}
            onToggleCompare={handleToggleCompare}
            wishlistIds={wishlistIds}
            compareIds={compareIds}
          />
        </div>

        {/* 5. Category Showcase Grid */}
        <CategoryGrid 
          onSelectCategory={(cat) => {
            setSelectedCategory(cat);
            const el = document.getElementById('product-catalog');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
          }} 
        />

        {/* 6. Main Product Catalog Grid with Filters */}
        <ProductGrid 
          products={PRODUCTS}
          selectedCategory={selectedCategory}
          onSelectCategory={setSelectedCategory}
          searchQuery={searchQuery}
          onSearchChange={setSearchQuery}
          onSelectProduct={(p) => setSelectedProductModal(p)}
          onAddToCart={(p) => handleAddToCart(p, 1)}
          onToggleWishlist={handleToggleWishlist}
          onToggleCompare={handleToggleCompare}
          wishlistIds={wishlistIds}
          compareIds={compareIds}
        />
      </main>

      {/* 7. Footer */}
      <Footer 
        onSelectCategory={setSelectedCategory}
        onOpenStores={() => setStoresModalOpen(true)}
        onOpenSupport={() => setSupportModalOpen(true)}
        onOpenPCConfigurator={() => setPcConfiguratorOpen(true)}
      />

      {/* MODALS & DRAWERS */}
      <ProductDetailModal 
        product={selectedProductModal}
        onClose={() => setSelectedProductModal(null)}
        onAddToCart={(p, qty) => {
          handleAddToCart(p, qty);
          setSelectedProductModal(null);
        }}
        onBuyNow={handleBuyNow}
        onToggleWishlist={handleToggleWishlist}
        onToggleCompare={handleToggleCompare}
        isWishlisted={selectedProductModal ? wishlistIds.includes(selectedProductModal.id) : false}
        isCompared={selectedProductModal ? compareIds.includes(selectedProductModal.id) : false}
      />

      <CartDrawer 
        isOpen={cartDrawerOpen}
        onClose={() => setCartDrawerOpen(false)}
        cartItems={cartItems}
        onUpdateQuantity={handleUpdateQuantity}
        onRemoveItem={handleRemoveFromCart}
        onClearCart={handleClearCart}
        onProceedToCheckout={handleProceedToCheckout}
      />

      <CheckoutModal 
        isOpen={checkoutModalOpen}
        onClose={() => setCheckoutModalOpen(false)}
        cartItems={cartItems}
        appliedDiscount={appliedDiscount}
        onOrderComplete={handleOrderComplete}
      />

      <CompareModal 
        isOpen={compareModalOpen}
        onClose={() => setCompareModalOpen(false)}
        products={comparedProducts}
        onRemoveFromCompare={(id) => handleToggleCompare(PRODUCTS.find(p => p.id === id)!)}
        onAddToCart={(p) => handleAddToCart(p, 1)}
      />

      <SpecsFinderTool 
        isOpen={pcConfiguratorOpen}
        onClose={() => setPcConfiguratorOpen(false)}
        products={PRODUCTS}
        onSelectProduct={(p) => setSelectedProductModal(p)}
        onAddToCart={(p) => handleAddToCart(p, 1)}
      />

      <StoreLocatorModal 
        isOpen={storesModalOpen}
        onClose={() => setStoresModalOpen(false)}
      />

      <SupportContactModal 
        isOpen={supportModalOpen}
        onClose={() => setSupportModalOpen(false)}
      />

      <Toast 
        message={toastMessage}
        onClose={() => setToastMessage(null)}
      />

    </div>
  );
}
