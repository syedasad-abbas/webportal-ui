
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const getCookie = (name) => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
};

window.axios.interceptors.request.use((config) => {
  const token = getCookie('csrf_token');
  if (token) {
    config.headers['X-CSRF-Token'] = token;
  }
  return config;
});