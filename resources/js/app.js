import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

import { carousel } from './components/carousel';
import { heroSlider } from './components/hero-slider';
import { productGallery } from './components/product-gallery';
import { searchBox } from './components/search-box';
import { cookieConsent } from './components/cookie-consent';

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.data('carousel', carousel);
Alpine.data('heroSlider', heroSlider);
Alpine.data('productGallery', productGallery);
Alpine.data('searchBox', searchBox);
Alpine.data('cookieConsent', cookieConsent);

window.Alpine = Alpine;
Alpine.start();
