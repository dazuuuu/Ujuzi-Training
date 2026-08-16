export type CategoryId = 
  | 'all'
  | 'smartphones'
  | 'laptops'
  | 'desktops'
  | 'accessories'
  | 'audio'
  | 'gaming'
  | 'networking';

export interface CategoryInfo {
  id: CategoryId;
  name: string;
  itemCount: number;
  description: string;
  iconName: string; // Lucide icon name mapping
  bannerImage: string;
}

export interface TechSpec {
  label: string;
  value: string;
}

export interface Product {
  id: string;
  name: string;
  category: CategoryId;
  brand: string;
  price: number;
  originalPrice?: number;
  rating: number;
  reviewCount: number;
  inStock: boolean;
  stockCount: number;
  isFeatured?: boolean;
  isNewArrival?: boolean;
  isFlashDeal?: boolean;
  discountPercent?: number;
  image: string;
  galleryImages: string[];
  description: string;
  keyFeatures: string[];
  specs: Record<string, string>;
  sku: string;
  warranty: string;
  tags: string[];
}

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface Review {
  id: string;
  userName: string;
  userLocation: string;
  rating: number;
  date: string;
  comment: string;
  verifiedPurchase: boolean;
}

export interface StoreLocation {
  id: string;
  name: string;
  city: string;
  address: string;
  phone: string;
  hours: string;
  isFlagship?: boolean;
  mapEmbedUrl?: string;
}

export interface Order {
  id: string;
  date: string;
  items: CartItem[];
  subtotal: number;
  discount: number;
  shipping: number;
  total: number;
  status: 'Processing' | 'Shipped' | 'Out for Delivery' | 'Delivered';
  trackingNumber: string;
  customerName: string;
  customerEmail: string;
  customerPhone: string;
  shippingAddress: string;
  paymentMethod: string;
}

export interface FilterOptions {
  category: CategoryId;
  search: string;
  minPrice: number;
  maxPrice: number;
  brand: string;
  sortBy: 'featured' | 'price-asc' | 'price-desc' | 'rating' | 'newest';
  inStockOnly: boolean;
  minRating: number;
}
