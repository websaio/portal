import { useEffect } from "react";
import { useLocation } from "wouter";
import { useAuth } from "@/contexts/auth-context";

interface ProtectedRouteProps {
  children: React.ReactNode;
  isAdminOnly?: boolean;
}

export default function ProtectedRoute({ 
  children, 
  isAdminOnly = false 
}: ProtectedRouteProps) {
  const { isLoggedIn, isAdmin, isLoading } = useAuth();
  const [, setLocation] = useLocation();

  useEffect(() => {
    if (!isLoading) {
      if (!isLoggedIn) {
        setLocation("/login");
      } else if (isAdminOnly && !isAdmin) {
        setLocation("/dashboard");
      }
    }
  }, [isLoggedIn, isAdmin, isLoading, isAdminOnly, setLocation]);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-gray-500">Loading...</p>
        </div>
      </div>
    );
  }

  if (!isLoggedIn) {
    return null;
  }

  if (isAdminOnly && !isAdmin) {
    return null;
  }

  return <>{children}</>;
}
