import { useEffect, useState } from 'react';
import { useLocation } from 'wouter';
import { getUser, isAuthenticated, login as authLogin, logout as authLogout } from '@/lib/auth';
import { useToast } from '@/hooks/use-toast';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'staff';
}

interface UseAuthReturn {
  user: User | null;
  isLoggedIn: boolean;
  isAdmin: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
}

export const useAuth = (): UseAuthReturn => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [, setLocation] = useLocation();
  const { toast } = useToast();

  // Load user on initial render
  useEffect(() => {
    const currentUser = getUser();
    setUser(currentUser);
    setIsLoading(false);
  }, []);

  const login = async (email: string, password: string) => {
    try {
      setIsLoading(true);
      const user = await authLogin(email, password);
      setUser(user);
      setLocation('/dashboard');
      toast({
        title: "Login successful",
        description: `Welcome back, ${user.name}!`,
      });
    } catch (error) {
      toast({
        title: "Login failed",
        description: "Invalid email or password",
        variant: "destructive",
      });
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = () => {
    authLogout();
    setUser(null);
    setLocation('/login');
    toast({
      title: "Logged out",
      description: "You have been successfully logged out",
    });
  };

  return {
    user,
    isLoggedIn: isAuthenticated(),
    isAdmin: user?.role === 'admin',
    isLoading,
    login,
    logout,
  };
};
