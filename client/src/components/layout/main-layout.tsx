import React, { useState } from "react";
import { Navbar } from "@/components/layout/navbar";
import { Sidebar } from "@/components/layout/sidebar";
import { useAuth } from "@/contexts/auth-context";

interface MainLayoutProps {
  children: React.ReactNode;
  title?: string;
}

export function MainLayout({ children, title }: MainLayoutProps) {
  const [showMobileSidebar, setShowMobileSidebar] = useState(false);
  const { user } = useAuth();

  const toggleMobileSidebar = () => {
    setShowMobileSidebar(!showMobileSidebar);
  };

  const closeMobileSidebar = () => {
    setShowMobileSidebar(false);
  };

  if (!user) {
    return null;
  }

  return (
    <div className="min-h-screen flex flex-col">
      <Navbar onMenuClick={toggleMobileSidebar} />
      
      <div className="flex flex-1 overflow-hidden">
        {/* Desktop sidebar */}
        <aside className="hidden lg:flex flex-col w-64 bg-white border-r border-gray-200 overflow-y-auto">
          <Sidebar />
        </aside>
        
        {/* Mobile sidebar */}
        {showMobileSidebar && (
          <div className="fixed inset-0 z-40 lg:hidden">
            <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={closeMobileSidebar}></div>
            
            <div className="relative flex flex-col w-full max-w-xs bg-white h-full">
              <div className="absolute top-0 right-0 -mr-12 pt-2">
                <button 
                  className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                  onClick={closeMobileSidebar}
                >
                  <span className="sr-only">Close sidebar</span>
                  <svg
                    className="h-6 w-6 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
              
              <Sidebar isMobile onLinkClick={closeMobileSidebar} />
            </div>
          </div>
        )}
        
        <main className="flex-1 overflow-auto bg-gray-50 p-4 lg:p-6">
          <div className="max-w-7xl mx-auto">
            {title && (
              <div className="mb-6">
                <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                <p className="mt-1 text-sm text-gray-500">Manage your tuition system</p>
              </div>
            )}
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}
