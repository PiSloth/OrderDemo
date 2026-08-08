import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Local Wi-Fi API Base URL for Android Expo App (Points to Laravel port 8000)
export const API_BASE_URL = 'http://192.168.100.33:8000/api/v1/mobile';



const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 30000,
});

// Interceptor to inject Sanctum Bearer Token
api.interceptors.request.use(async (config) => {
  const token = await AsyncStorage.getItem('user_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
