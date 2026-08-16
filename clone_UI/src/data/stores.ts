import { StoreLocation, Review } from '../types';

export const STORES: StoreLocation[] = [
  {
    id: 'store-nairobi',
    name: 'TRUNKNET Flagship Tech Experience Center',
    city: 'Nairobi',
    address: 'Trunknet Towers, 5th Floor, Westlands Commerce Park, Nairobi',
    phone: '+254 700 878656',
    hours: 'Mon - Sat: 8:00 AM - 8:00 PM | Sun: 10:00 AM - 5:00 PM',
    isFlagship: true,
  },
  {
    id: 'store-mombasa',
    name: 'TRUNKNET Coastal Tech Hub',
    city: 'Mombasa',
    address: 'Nyali City Mall, Ground Floor Suite 12, Mombasa',
    phone: '+254 711 988776',
    hours: 'Mon - Sat: 9:00 AM - 7:00 PM',
  },
  {
    id: 'store-kisumu',
    name: 'TRUNKNET Lakeside Experience Store',
    city: 'Kisumu',
    address: 'Mega City Complex, Wing B, Kisumu',
    phone: '+254 722 554433',
    hours: 'Mon - Sat: 9:00 AM - 6:30 PM',
  },
  {
    id: 'store-eldoret',
    name: 'TRUNKNET Rift Tech Outlet',
    city: 'Eldoret',
    address: 'Rupa’s Mall, 1st Floor, Eldoret',
    phone: '+254 733 112233',
    hours: 'Mon - Sat: 9:00 AM - 6:00 PM',
  },
];

export const SAMPLE_REVIEWS: Review[] = [
  {
    id: 'rev-1',
    userName: 'Dr. Evans Kiprop',
    userLocation: 'Nairobi, Kenya',
    rating: 5,
    date: '2026-07-28',
    comment: 'Purchased the MacBook Pro M3 Max for heavy video editing. Arrived same-day in original sealed box with 2-year warranty card! Excellent service by TRUNKNET team.',
    verifiedPurchase: true,
  },
  {
    id: 'rev-2',
    userName: 'Amina Mohamed',
    userLocation: 'Mombasa, Kenya',
    rating: 5,
    date: '2026-08-01',
    comment: 'The Sony WH-1000XM5 headphones are authentic and sound unbelievable. The solid gold branding and packaging at TRUNKNET is truly top tier.',
    verifiedPurchase: true,
  },
  {
    id: 'rev-3',
    userName: 'David Ochieng',
    userLocation: 'Kisumu, Kenya',
    rating: 5,
    date: '2026-08-04',
    comment: 'Ordered the Apex Pro Custom PC. Unboxed and tested on 4K gaming immediately. Cables are perfectly managed and build quality is immaculate.',
    verifiedPurchase: true,
  },
];
