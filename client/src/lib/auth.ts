import { jwtDecode } from "jwt-decode";
import { apiRequest } from "./queryClient";

interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'staff';
}

interface DecodedToken {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'staff';
  exp: number;
}

interface AuthResponse {
  token: string;
  user: User;
}

const LOCAL_STORAGE_TOKEN_KEY = 'tuition_auth_token';

export const login = async (email: string, password: string): Promise<User> => {
  try {
    const response = await apiRequest('POST', '/api/auth/login', { email, password });
    const data: AuthResponse = await response.json();
    
    localStorage.setItem(LOCAL_STORAGE_TOKEN_KEY, data.token);
    
    return data.user;
  } catch (error) {
    console.error('Login error:', error);
    throw error;
  }
};

export const logout = (): void => {
  localStorage.removeItem(LOCAL_STORAGE_TOKEN_KEY);
  window.location.href = '/login';
};

export const getToken = (): string | null => {
  return localStorage.getItem(LOCAL_STORAGE_TOKEN_KEY);
};

export const getUser = (): User | null => {
  const token = getToken();
  
  if (!token) {
    return null;
  }
  
  try {
    const decoded = jwtDecode<DecodedToken>(token);
    
    // Check if token is expired
    const currentTime = Date.now() / 1000;
    if (decoded.exp < currentTime) {
      logout();
      return null;
    }
    
    return {
      id: decoded.id,
      name: decoded.name,
      email: decoded.email,
      role: decoded.role
    };
  } catch (error) {
    console.error('Error decoding token:', error);
    return null;
  }
};

export const isAuthenticated = (): boolean => {
  return getUser() !== null;
};

export const isAdmin = (): boolean => {
  const user = getUser();
  return user !== null && user.role === 'admin';
};

export const fetchWithAuth = async (url: string, options: RequestInit = {}): Promise<Response> => {
  const token = getToken();
  
  if (!token) {
    throw new Error('Authentication required');
  }
  
  const headers = {
    ...options.headers,
    'Authorization': `Bearer ${token}`
  };
  
  return fetch(url, {
    ...options,
    headers
  });
};
