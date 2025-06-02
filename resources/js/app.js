import './bootstrap';

// Import our track status module to make it available globally
import TrackStatusAPI from './track-status';

// Import navigation module
import './modules/navigation';

// Make the API available globally
window.TrackStatusAPI = TrackStatusAPI;
